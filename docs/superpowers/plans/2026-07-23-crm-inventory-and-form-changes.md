# CRM Inventory + Form Changes Implementation Plan (v2 — post-review)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a batch of CRM changes — side-by-side pairing of registered/closed calls (with a safe Delete), a CSFB bank filter option, re-adding "Call Registered through", and rebuilding the stock inventory as an extensible parts list seeded with 22 new parts (also added to the Call Close dropdown) — plus fixing a live mass-delete bug and two auth gaps surfaced in review.

**Architecture:** PHP 8 + MariaDB 12 Bitnami LAMP on AWS Lightsail (`65.1.88.251`), served at `https://digitechsys.net/Digiadmin/...` behind Cloudflare. No test framework, so each task is verified by (a) `php -l` on the server, (b) live Playwright browser check, (c) direct SQL. Edits are local → deploy (backup + lint + swap) → verify live → commit to `git@github.com:digitech-itdevelopment/admin_crm_digitechsys.git`.

**Tech Stack:** PHP 8 (mysqli, exceptions ON, `STRICT_TRANS_TABLES`), MariaDB 12, vanilla JS + Bootstrap, `/opt/bitnami` toolchain.

## Global Constraints

- mysqli throws on error and MariaDB is in `STRICT_TRANS_TABLES`: every new query must set all NOT-NULL columns and be wrapped so a failure cannot abort a user save or 500 a page.
- Deploy method (per file): `scp` → `/tmp/newfiles/`; `sudo cp -p` live file to `<file>.bak-<stamp>`; `php -l` the new file; `sudo cp` into `/var/www/digitechsys.net/Digiadmin/html/`; `sudo chown daemon:daemon`; `sudo chmod 755`.
- SSH `bitnami@65.1.88.251`; MySQL client `/opt/bitnami/mariadb/bin/mysql -u nitin_login -p'<pw>' digitechdata` (creds in git-ignored `config.php`).
- **Schema changes are additive only** and require explicit user go-ahead (production DB, staff actively using it). Back up structure (`mysqldump --no-data`) before DDL.
- **Deploy ordering is load-bearing:** the `inventory` table (Task 4) MUST exist and verify before any code that queries it (Tasks 5, 6, 7) is deployed. Do not interleave.
- Part-key rule: `inventory.part_key` MUST equal the Close dropdown `<option value>` for that part. `part_key` is stored under a **case-insensitive collation** (pinned in Task 4), so `Softkeys` (dropdown) safely matches `softkeys` (seed).
- `digitechcrm` has an AUTO_INCREMENT primary key `id`; use it as the stable row identifier for deletes (never `tiknumber`, which is empty on closure rows).
- Clean up all test data after live checks: `DELETE FROM digitechcrm WHERE branchname LIKE 'PLANTEST%'`; for inventory tests use the disposable key `plantestpart` and restore any real part quantity touched.

---

## File Structure

- `Digiadmin/html/delete_ticket.php` — **fixed**: delete by primary `id`, reject empty id (closes a live mass-delete bug).
- `Digiadmin/html/viewuserdetails.php` — **modified**: side-by-side registration+closure; remove the `#hiddenTable`/DataTable scaffolding; delete-by-`id`; add CSFB+JKSCB to the Select Bank filter.
- `Digiadmin/html/callregistered.php` — **modified**: re-add "Call Registered through" dropdown above the Approval Pending toggle.
- New table `inventory(id, part_key, part_name, quantity)` — extensible parts library (source of truth; `stocklist` kept unused for rollback).
- `Digiadmin/html/viewinventorystatus.php` — **rebuilt**: session-gated; per-part editable quantity + add-part form over `inventory`; guarded SELECT.
- `Digiadmin/html/userdetails.php` — **modified**: decrement `inventory` (not `stocklist`) on close.
- `Digiadmin/html/callclosed.php` — **modified**: append 15 new part options (dedupe the double `Stacker`).
- `Digiadmin/html/search_tickets.php` — **fixed**: add the session gate (was leaking ticket PII unauthenticated).

**Canonical seed** (`part_key` → display; keys already in the Close dropdown reuse the exact value): `epp`→EPP, `Stacker`→Stacker, `cassette`→Currency Cassette (already inventory — skip), `k80printer`→K80 Printer, `printerhead`→Printer Head, `rejectstopelement`→Reject Stop Element, `shuttermotor`→Shutter Motor, `stackerunit4`→Stacker Unit4, `receiptprinter`→Receipt Printer, `camslock`→Cams Lock, `doubleextractor`→Double Extractor, `notepickingroller`→Note Picking Roller, `safelock`→Safe Lock, `smallbelts`→Small Belts, `controllercard4x`→4x Controller Card, `atmscreen`→ATM Screen, `motherboard`→Motherboard, `fascia`→Fascia, `horizontaltransport`→Horizontal Transport, `housingunit`→Housing Unit, `shutter`→Shutter, `smps`→Power Supply (SMPS).

---

## Task 1: Fix `delete_ticket.php` — delete by `id`, reject empty (BLOCKER)

**Files:**
- Modify (replace whole file): `Digiadmin/html/delete_ticket.php`

**Interfaces:**
- Consumes: a POST `ticket_id` that (after Task 2) carries the row's primary `id`.
- Produces: deletes exactly one `digitechcrm` row by `id`; an empty/missing id deletes nothing.

- [ ] **Step 1: Replace the file** with:

```php
<?php
session_start();
if (!isset($_SESSION['userdata'])) { header("Location: adminlogin.php"); exit; }
require_once 'config.php';
$conn = getDbConnection();

$ticket_id = isset($_POST['ticket_id']) ? trim($_POST['ticket_id']) : '';
if ($ticket_id !== '' && ctype_digit($ticket_id)) {
    try {
        $stmt = $conn->prepare("DELETE FROM digitechcrm WHERE id = ?");
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
    } catch (\Throwable $e) { error_log("delete_ticket failed: " . $e->getMessage()); }
}
header("Location: viewuserdetails.php");
exit;
```

- [ ] **Step 2: Lint + deploy** (Global Constraints). `php -l` → `No syntax errors detected`.
- [ ] **Step 3: Verify guard** — hit `delete_ticket.php` with no `ticket_id` (or empty) and confirm `SELECT COUNT(*) FROM digitechcrm` is unchanged (no mass delete). Deletion-by-id is exercised in Task 2's verify.
- [ ] **Step 4: Commit**

```bash
git add Digiadmin/html/delete_ticket.php
git commit -m "fix: delete tickets by primary id and reject empty id (was mass-deleting closed rows)"
```

---

## Task 2: `viewuserdetails.php` — side-by-side pairing (safe delete) + CSFB filter

**Files:**
- Modify: `Digiadmin/html/viewuserdetails.php`

**Interfaces:**
- Consumes: `delete_ticket.php` deleting by `id` (Task 1).
- Produces: each unified ticket number renders its registration and closure as two `col-md-6` columns in one `.row`; Delete posts the row `id`; the Select Bank filter always offers CSFB + JKSCB.

- [ ] **Step 1: Add `id` to the SELECT** (so the delete form can post it). Change the `SELECT` list (line ~35) to begin `SELECT id, tiknumber, error, ...` (prepend `id,`).

- [ ] **Step 2: Add fixed banks to the filter.** After the `while` that fills `$bankNames` (line ~29), insert:

```php
foreach (array('JKSCB', 'CSFB') as $fixedBank) {
    if (!in_array($fixedBank, $bankNames, true)) { $bankNames[] = $fixedBank; }
}
sort($bankNames);
```

- [ ] **Step 3: Remove the hidden-table scaffolding.** Delete these so cards no longer depend on HTML foster-parenting:
  - the `<table id="hiddenTable" class="display" style="display:none;">` open, its `<thead>...</thead>`, and `<tbody>` (lines ~239-277);
  - the matching `</tbody>` and `</table>` after the loop (lines ~364-365);
  - the `$('#hiddenTable').DataTable();` line (~375);
  - the `#hiddenTable { display: none; }` CSS rule (~420-422).

- [ ] **Step 4: Replace the card `while` loop** (the block from `$result->data_seek(0);` through the loop's closing `<?php } ?>`) with a group-and-pair renderer. Paste the EXISTING card-body `<p>...</p>` field block (Ticket Status … Remarks, including the Approval Pending (Bank End) line and the SCR-documents PHP) verbatim where marked:

```php
<?php
$result->data_seek(0);
$groups = array();
$order  = array();
while ($rows = $result->fetch_assoc()) {
    $uid = ($rows['tiknumber'] !== '') ? $rows['tiknumber'] : $rows['tickview'];
    if (!isset($groups[$uid])) { $groups[$uid] = array(); $order[] = $uid; }
    $groups[$uid][] = $rows;
}
foreach ($order as $uid):
    // registration (tiknumber set) first, then closure
    usort($groups[$uid], function($a, $b){ return ($a['tiknumber'] !== '' ? 0 : 1) - ($b['tiknumber'] !== '' ? 0 : 1); });
?>
  <div class="row">
    <?php foreach ($groups[$uid] as $rows):
        $isClose = in_array($rows['tktstatusdetails'], array('Pending','Closed')); ?>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <div class="row">
              <div class="col-md-9">
                <img src="../images/ticket-16.png" alt="Ticket Icon">
                Ticket Number: <?php echo htmlspecialchars($isClose ? $rows['tickview'] : $rows['tiknumber']); ?>
                <span style="margin-left:8px; padding:2px 8px; border-radius:4px; font-size:12px; background:<?php echo $isClose ? '#8e1b1b' : '#1e7d3c'; ?>; color:#fff;">
                  <?php echo $isClose ? 'CLOSED (' . htmlspecialchars($rows['tktstatusdetails']) . ')' : 'REGISTERED'; ?>
                </span>
              </div>
              <div class="col-md-3">
                <form action="delete_ticket.php" method="post" style="margin:0;">
                  <input type="hidden" name="ticket_id" value="<?php echo (int)$rows['id']; ?>">
                  <input style="color:black;" class="form-control font-16" type="submit" value="Delete"
                         onclick="return confirm('Delete this <?php echo $isClose ? 'closure' : 'registration'; ?> record for ticket <?php echo htmlspecialchars($isClose ? $rows['tickview'] : $rows['tiknumber']); ?>?');">
                </form>
              </div>
            </div>
          </div>
          <div class="card-body">
            <!-- PASTE the existing <p><strong>...</strong> ...</p> field block verbatim here -->
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
```

- [ ] **Step 5: Lint + deploy.** `php -l` → clean.
- [ ] **Step 6: Live verify (Playwright, logged in):**
  1. register + close a `PLANTEST` ticket; open `viewuserdetails.php`; confirm the REGISTERED and CLOSED cards for that number sit in one horizontal row; a lone open ticket shows a single column; the page body does not scroll horizontally at ~375px width.
  2. **Delete safety:** note `SELECT COUNT(*) FROM digitechcrm`; click Delete on the CLOSED card of the test ticket; confirm the count drops by exactly 1 and only that closure is gone (all other closures intact).
  3. Clean up remaining `PLANTEST` rows.
- [ ] **Step 7: Commit**

```bash
git add Digiadmin/html/viewuserdetails.php
git commit -m "feat(view): side-by-side registration/closure, delete-by-id, CSFB filter"
```

---

## Task 3: Re-add "Call Registered through" to Call Registration

**Files:**
- Modify: `Digiadmin/html/callregistered.php` (insert immediately before the `<!-- Approval Pending (BANK END) (added) -->` comment, line ~856 — this places it above both the Approval Pending toggle and the ATM toggle)

**Interfaces:**
- Consumes: none; `userdetails.php` already reads `$_POST['callregthrough']` (defaults '') and saves to column `callregthr`.
- Produces: a "Call Registered through" dropdown (Please Select / Email / Telecalling) on registration.

- [ ] **Step 1: Insert** before that comment:

```html
                    <div class="row py-3">
                        <div class="col-md-12"><label>Call Registered through:</label></div>
                        <div class="col-md-12">
                            <select ID="callregthrough" name="callregthrough" class="form-control rounded rounded-5">
                                <option value="Not Applicable">Please Select</option>
                                <option value="Email">Email</option>
                                <option value="Telecalling">Telecalling</option>
                            </select>
                        </div>
                    </div>
                    <!--row ends-->

```

- [ ] **Step 2: Lint + deploy.** `php -l` → clean.
- [ ] **Step 3: Live verify** — load `callregistered.php`; confirm the dropdown appears above the Approval Pending toggle; register a `PLANTEST` ticket with `Telecalling`; `SELECT callregthr FROM digitechcrm WHERE branchname LIKE 'PLANTEST%'` → `Telecalling`. Clean up.
- [ ] **Step 4: Commit**

```bash
git add Digiadmin/html/callregistered.php
git commit -m "feat: re-add Call Registered through dropdown to registration"
```

---

## Task 4: Create + seed the `inventory` table (production DDL — needs explicit go-ahead)

**Files:**
- Create (server): `/tmp/inventory_migration.sql`; keep a copy at `Digiadmin/db/2026-07-23-inventory_migration.sql`.

**Interfaces:**
- Produces: `inventory(id INT PK, part_key VARCHAR(64) UNIQUE [CI collation], part_name VARCHAR(150), quantity INT NOT NULL DEFAULT 0)`, seeded with the 11 existing parts (quantities cast safely from `stocklist`) + 21 new parts at 0. Consumed by Tasks 5, 6.

- [ ] **Step 1: Pre-flight + structure backup**

Run: `mysqldump --no-data digitechdata stocklist inventory > /tmp/inv-structure-backup.sql` and `SELECT * FROM stocklist WHERE id=1;` — confirm a row with id=1 exists and its 11 count columns are clean integers. If any is blank/non-numeric, the CAST below coerces it to 0 (intended).

- [ ] **Step 2: Write `/tmp/inventory_migration.sql`**

```sql
CREATE TABLE IF NOT EXISTS inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_key  VARCHAR(64)  NOT NULL COLLATE utf8mb4_general_ci,
  part_name VARCHAR(150) NOT NULL,
  quantity  INT NOT NULL DEFAULT 0,
  UNIQUE KEY uq_part_key (part_key)
);

-- Migrate the 11 existing parts; CAST guards against blank/non-numeric VARCHAR counts.
INSERT INTO inventory (part_key, part_name, quantity)
  SELECT 'cardreader','Card Reader',CAST(cardreader AS UNSIGNED)          FROM stocklist WHERE id=1
  UNION ALL SELECT 'cassette','Currency Cassette',CAST(cassette AS UNSIGNED)         FROM stocklist WHERE id=1
  UNION ALL SELECT 'shuttercable','Shutter Cable',CAST(shuttercable AS UNSIGNED)     FROM stocklist WHERE id=1
  UNION ALL SELECT 'hooddoorlock','Hood Door Lock',CAST(hooddoorlock AS UNSIGNED)    FROM stocklist WHERE id=1
  UNION ALL SELECT 'softkeys','Softkeys',CAST(softkeys AS UNSIGNED)                  FROM stocklist WHERE id=1
  UNION ALL SELECT 'divertcassette','Divert Cassette',CAST(divertcassette AS UNSIGNED) FROM stocklist WHERE id=1
  UNION ALL SELECT 'belts','High Belts',CAST(belts AS UNSIGNED)                      FROM stocklist WHERE id=1
  UNION ALL SELECT 'roller','Roller',CAST(roller AS UNSIGNED)                        FROM stocklist WHERE id=1
  UNION ALL SELECT 'cpufan','CPU Fan',CAST(cpufan AS UNSIGNED)                       FROM stocklist WHERE id=1
  UNION ALL SELECT 'clamp','Clamp',CAST(clamp AS UNSIGNED)                           FROM stocklist WHERE id=1
  UNION ALL SELECT 'ddu','DDU',CAST(ddu AS UNSIGNED)                                 FROM stocklist WHERE id=1
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

INSERT IGNORE INTO inventory (part_key, part_name, quantity) VALUES
  ('epp','EPP',0),('Stacker','Stacker',0),('k80printer','K80 Printer',0),
  ('printerhead','Printer Head',0),('rejectstopelement','Reject Stop Element',0),
  ('shuttermotor','Shutter Motor',0),('stackerunit4','Stacker Unit4',0),
  ('receiptprinter','Receipt Printer',0),('camslock','Cams Lock',0),
  ('doubleextractor','Double Extractor',0),('notepickingroller','Note Picking Roller',0),
  ('safelock','Safe Lock',0),('smallbelts','Small Belts',0),
  ('controllercard4x','4x Controller Card',0),('atmscreen','ATM Screen',0),
  ('motherboard','Motherboard',0),('fascia','Fascia',0),
  ('horizontaltransport','Horizontal Transport',0),('housingunit','Housing Unit',0),
  ('shutter','Shutter',0),('smps','Power Supply (SMPS)',0);
```

- [ ] **Step 3: Apply** (after explicit user OK): `mysql digitechdata < /tmp/inventory_migration.sql`. Expect no output.
- [ ] **Step 4: Verify** — `SELECT COUNT(*) parts, SUM(quantity) total FROM inventory;` (~32 parts; migrated parts show real counts e.g. belts=100; new parts 0). `SHOW FULL COLUMNS FROM inventory LIKE 'part_key';` → collation ends `_ci`.
- [ ] **Step 5: Commit** the SQL copy.

```bash
git add Digiadmin/db/2026-07-23-inventory_migration.sql
git commit -m "db: add extensible inventory table seeded with existing + new parts"
```

---

## Task 5: Rebuild `viewinventorystatus.php` over `inventory` (gated, guarded)

**Files:**
- Modify: `Digiadmin/html/viewinventorystatus.php` (replace PHP head lines 1-56 and the body table at line ~447)

**Interfaces:**
- Consumes: `inventory` (Task 4 — must exist first).
- Produces: session-gated page listing every part with an editable quantity (`UPDATE inventory SET quantity=? WHERE id=?`) and an add-part form (`INSERT IGNORE`), surfacing "already exists" when the key collides.

- [ ] **Step 1: Replace the head block** (lines 1-56):

```php
<?php
session_start();
if (!isset($_SESSION['userdata'])) { header("Location: adminlogin.php"); exit; }
require_once 'config.php';
$conn = getDbConnection();
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$notice = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_qty'])) {
    $id = (int)$_POST['id']; $qty = (int)$_POST['quantity'];
    try { $stmt = $conn->prepare("UPDATE inventory SET quantity=? WHERE id=?"); $stmt->bind_param("ii",$qty,$id); $stmt->execute(); }
    catch (\Throwable $e) { error_log("inventory update failed: ".$e->getMessage()); }
    header("Location: viewinventorystatus.php"); exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_part'])) {
    $name = trim($_POST['part_name'] ?? '');
    $key  = strtolower(preg_replace('/[^A-Za-z0-9]/','',$name));
    $qty  = (int)($_POST['quantity'] ?? 0);
    if ($name !== '' && $key !== '') {
        try {
            $stmt = $conn->prepare("INSERT IGNORE INTO inventory (part_key, part_name, quantity) VALUES (?,?,?)");
            $stmt->bind_param("ssi",$key,$name,$qty); $stmt->execute();
            $_SESSION['inv_notice'] = ($stmt->affected_rows === 0) ? "A part with a similar name already exists." : "Part added.";
        } catch (\Throwable $e) { error_log("inventory add failed: ".$e->getMessage()); }
    }
    header("Location: viewinventorystatus.php"); exit();
}
$notice = $_SESSION['inv_notice'] ?? ''; unset($_SESSION['inv_notice']);

try { $result = $conn->query("SELECT id, part_key, part_name, quantity FROM inventory ORDER BY part_name ASC"); }
catch (\Throwable $e) { die("Inventory table unavailable. Run the inventory migration first."); }
if (!$result) { die("Query failed: " . $conn->error); }
?>
```

- [ ] **Step 2: Replace the body table** (the `<table ...>` at line ~447 through its `</table>`):

```html
<div class="container" style="margin-top:20px;">
  <h3 class="text-center text-danger">Digitech Online Stock Inventory Record</h3>
  <?php if (!empty($notice)): ?><div class="alert alert-info"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?>
  <div style="overflow-x:auto;">
  <table class="table table-bordered">
    <thead><tr><th>Part Name</th><th style="width:180px;">Quantity</th><th style="width:110px;"></th></tr></thead>
    <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?php echo htmlspecialchars($row['part_name']); ?></td>
        <td>
          <form method="post" action="viewinventorystatus.php" style="margin:0; display:flex; gap:6px;">
            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
            <input type="number" name="quantity" value="<?php echo (int)$row['quantity']; ?>" class="form-control" min="0">
        </td>
        <td><button type="submit" name="update_qty" class="btn btn-primary">Save</button></form></td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  </div>
  <h5 class="mt-4">Add a new part</h5>
  <form method="post" action="viewinventorystatus.php" class="row g-2">
    <div class="col-md-6"><input type="text" name="part_name" class="form-control" placeholder="Part name" required></div>
    <div class="col-md-3"><input type="number" name="quantity" class="form-control" value="0" min="0"></div>
    <div class="col-md-3"><button type="submit" name="add_part" class="btn btn-success">Add Part</button></div>
  </form>
</div>
```

- [ ] **Step 3: Lint + deploy** (only after Task 4 verified). `php -l` → clean.
- [ ] **Step 4: Live verify (logged in)** — ~32 rows render; edit a **disposable** row's qty → Save → persists; Add Part `PLANTEST Part` → row appears (key `plantestpart`); Add it again → "already exists" notice, no duplicate. Cleanup: `DELETE FROM inventory WHERE part_key='plantestpart';`.
- [ ] **Step 5: Commit**

```bash
git add Digiadmin/html/viewinventorystatus.php
git commit -m "feat(inventory): rebuild stock page as gated extensible parts list"
```

---

## Task 6: Point stock decrement at `inventory`

**Files:**
- Modify: `Digiadmin/html/userdetails.php` (replace the `if ($partnm !== '') { ... }` stocklist block, lines ~242-250, inside `if ($stmt->execute())`)

**Interfaces:**
- Consumes: `inventory.part_key` (= Close dropdown value).
- Produces: closing a call with a part decrements that part's `inventory.quantity` by 1 (floored at 0), best-effort.

- [ ] **Step 1: Replace the block:**

```php
    // Decrement inventory for the selected part, if any (best-effort, floored at 0).
    if ($partnm !== '' && $partnm !== 'Not Applicable') {
        try {
            $up = $conn->prepare("UPDATE inventory SET quantity = quantity - 1 WHERE part_key = ? AND quantity > 0");
            $up->bind_param("s", $partnm);
            $up->execute();
        } catch (\Throwable $stockError) {
            error_log("Digitech CRM: stock update skipped/failed: " . $stockError->getMessage());
        }
    }
```

- [ ] **Step 2: Lint + deploy** (only after Task 4). `php -l` → clean.
- [ ] **Step 3: Live verify** — seed a disposable part `('plantestpart','PLANTEST Part',5)`; register+close a `PLANTEST` ticket selecting a Close option whose value is `plantestpart` (temporarily add such an option in the browser DOM for the test, or use an existing seeded part like `cardreader` and restore it after). Confirm `quantity` dropped by 1; then `UPDATE inventory SET quantity=quantity+1 WHERE part_key='<part>'` to restore, and delete the disposable part + `PLANTEST` rows.
- [ ] **Step 4: Commit**

```bash
git add Digiadmin/html/userdetails.php
git commit -m "feat(inventory): decrement inventory table on call close"
```

---

## Task 7: Append new parts to the Call Close "Name of Part" dropdown

**Files:**
- Modify: `Digiadmin/html/callclosed.php` (before the partname `</select>` at line 1041; also remove the duplicate `<option value="Stacker">` at line ~1040)

**Interfaces:**
- Consumes: seed keys from Task 4 (option `value` = `part_key`).
- Produces: 15 new options; selecting one and closing decrements its inventory row (Task 6).

- [ ] **Step 1: Remove the duplicate Stacker option** (line ~1040, the second `<option value="Stacker">Stacker</option>`).
- [ ] **Step 2: Insert before `</select>`** (skip the 7 already present — epp, Stacker, receiptprinter, doubleextractor, safelock, motherboard, cassette):

```html
                                <option value="k80printer">K80 Printer</option>
                                <option value="printerhead">Printer Head</option>
                                <option value="rejectstopelement">Reject Stop Element</option>
                                <option value="shuttermotor">Shutter Motor</option>
                                <option value="stackerunit4">Stacker Unit4</option>
                                <option value="camslock">Cams Lock</option>
                                <option value="notepickingroller">Note Picking Roller</option>
                                <option value="smallbelts">Small Belts</option>
                                <option value="controllercard4x">4x Controller Card</option>
                                <option value="atmscreen">ATM Screen</option>
                                <option value="fascia">Fascia</option>
                                <option value="horizontaltransport">Horizontal Transport</option>
                                <option value="housingunit">Housing Unit</option>
                                <option value="shutter">Shutter</option>
                                <option value="smps">Power Supply (SMPS)</option>
```

- [ ] **Step 3: Lint + deploy** (only after Task 4). `php -l` → clean.
- [ ] **Step 4: Live verify** — load `callclosed.php`; confirm the 15 options appear once each and `Stacker` appears only once; picking "K80 Printer" and closing a `PLANTEST` ticket keeps `k80printer` at 0 (floor holds), no 500. Clean up.
- [ ] **Step 5: Commit**

```bash
git add Digiadmin/html/callclosed.php
git commit -m "feat: add new parts to Call Close part dropdown; dedupe Stacker"
```

---

## Task 8: Gate `search_tickets.php` behind login (PII fix)

**Files:**
- Modify: `Digiadmin/html/search_tickets.php` (add the session guard after `session_start()`)

**Interfaces:**
- Produces: the endpoint returns `[]` (or redirects) for unauthenticated callers instead of leaking custodian/complaint data.

- [ ] **Step 1: Insert** immediately after `session_start();`:

```php
if (!isset($_SESSION['userdata'])) { header('Content-Type: application/json'); echo json_encode([]); exit; }
```

- [ ] **Step 2: Lint + deploy.** `php -l` → clean.
- [ ] **Step 3: Live verify** — logged-out `fetch('search_tickets.php?q=0001')` returns `[]`; logged-in still returns matches (re-run the Close search flow).
- [ ] **Step 4: Commit**

```bash
git add Digiadmin/html/search_tickets.php
git commit -m "fix: require login for ticket search endpoint"
```

---

## Task 9: Push + full smoke test

- [ ] **Step 1: Push** all commits: `GIT_SSH_COMMAND="ssh -i ~/.ssh/id_ed25519" git push origin main`.
- [ ] **Step 2: Smoke test (logged in)** — register (with Call Registered through) → close via ticket search → view shows the pair side-by-side → Delete the closure removes only it → inventory page edits/persist. No 500s. Restore any real stock touched; remove all `PLANTEST`/`plantestpart` data.

---

## Deploy order (must follow)

Tasks 1, 2, 3, 8 are independent of the DB and can deploy anytime. **Task 4 (create+seed `inventory`) must be applied and verified before deploying Tasks 5, 6, 7** (they query `inventory`). Recommended sequence: 1 → 2 → 3 → 8 → **4** → 5 → 6 → 7 → 9.

## Rollback

- Code: restore each file from its `*.bak-<stamp>` and `git revert` the commit.
- Inventory: `stocklist` is left intact and is the pre-migration source of truth. **Caveat:** once staff edit quantities on the new page, those live only in `inventory`; rolling back to the `stocklist`-based decrement/grid reverts stock to stale numbers. Before any post-go-live rollback, copy quantities back: for each part, `UPDATE stocklist SET <col> = (SELECT quantity FROM inventory WHERE part_key='<col>')`.

## Known limitations (accepted / flagged to user)

- **New vs Refurbished:** the handwritten note said "New Spares and Refurbished Spares," but per the user's explicit answer parts are added to one library at quantity 0 with a single count — no separate refurbished tracking. Adding a `stock_type` column later is possible but out of scope now.
- **Un-seeded dropdown parts no-op on decrement:** 13 existing Close-dropdown values have no `inventory` row (`dispensershutter, journalprinter, controllercard, ccaboard, hookbelt, fan, cmdcontrollercard, feedsocket, hdd, upsbatteries, upscontrollercard, dispenser, feedshafts`); closing with these changes no stock — same as today. Seed them too if full coverage is wanted.
- **Add-part key derivation** (`strtolower`, alnum-only) will not reproduce curated multi-word keys (e.g. UI-adding "4x Controller Card" → `4xcontrollercard`, not `controllercard4x`); the "already exists" notice mitigates accidental dupes, and staff normally won't re-add seeded parts.
- **JKSCB in the filter:** requirement #2 named only CSFB; JKSCB is included too since it's now a valid bank on the forms.
