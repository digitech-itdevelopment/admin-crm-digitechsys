# CRM Inventory + Form Changes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a batch of CRM changes — side-by-side pairing of registered/closed calls, a CSFB bank filter option, re-adding the "Call Registered through" field, and rebuilding the stock inventory as an extensible parts list seeded with 22 new parts (also added to the Call Close part dropdown).

**Architecture:** PHP + MariaDB Bitnami LAMP app on AWS Lightsail (`65.1.88.251`), served at `https://digitechsys.net/Digiadmin/...` behind Cloudflare. No test framework exists, so each task is verified by (a) `php -l` syntax lint on the server, (b) live browser check via Playwright, and (c) direct SQL queries. Edits are made locally, deployed with backup + lint + swap (the established workflow), then verified live, then committed to `git@github.com:digitech-itdevelopment/admin_crm_digitechsys.git`.

**Tech Stack:** PHP 8 (mysqli, strict SQL mode), MariaDB 12, vanilla JS + Bootstrap, `/opt/bitnami` toolchain on the server.

## Global Constraints

- All form handlers write to table `digitechcrm`; MariaDB runs in `STRICT_TRANS_TABLES`, and mysqli throws exceptions — every new query must not leave a NOT-NULL column unset and must be wrapped so a failure cannot abort a save.
- Deploy method (per file): `scp` to `/tmp/newfiles/`, `sudo cp -p` live file to `<file>.bak-<stamp>`, `php -l` the new file, `sudo cp` into `/var/www/digitechsys.net/Digiadmin/html/`, `sudo chown daemon:daemon`, `sudo chmod 755`.
- SSH: `ssh -i <key> bitnami@65.1.88.251`; MySQL client is `/opt/bitnami/mariadb/bin/mysql -u nitin_login -p'<pw>' digitechdata` (credentials in `Digiadmin/html/config.php`, git-ignored).
- Live DB is in active use by staff — schema changes must be **additive** (new table / new columns only); never drop or rewrite existing data. Back up structure (`mysqldump --no-data`) before any DDL.
- Part-key consistency: a part's `inventory.part_key` MUST equal the `<option value>` used for it in the Call Close "Name of Part" dropdown, so `userdetails.php` can decrement stock by that key.
- Always clean up test rows (`DELETE ... WHERE branchname LIKE 'PLANTEST%'`) after live verification.

---

## File Structure

- `Digiadmin/html/viewinventorystatus.php` — **rebuilt**: reads/writes the new `inventory` table; lists every part with an editable quantity and an "add new part" form. (Currently a fixed 11-column grid over `stocklist`.)
- `Digiadmin/html/userdetails.php` — **modified**: stock decrement switches from `UPDATE stocklist SET \`key\`...` to `UPDATE inventory SET quantity = quantity - 1 WHERE part_key = ?`.
- `Digiadmin/html/callclosed.php` — **modified**: append the new parts to the "Name of Part" dropdown.
- `Digiadmin/html/callregistered.php` — **modified**: re-add the "Call Registered through" (Email/Telecalling) dropdown above the Approval Pending toggle.
- `Digiadmin/html/viewuserdetails.php` — **modified**: render each ticket's registration + closure side-by-side; add CSFB (and JKSCB) as fixed options in the "Select Bank" filter.
- New table `inventory (id, part_key, part_name, quantity)` — the extensible parts library (replaces the `stocklist` single-row grid as the source of truth; `stocklist` is left in place, unused, for rollback).

**Canonical new-parts seed** (used in Task 1 and Task 4 — `part_key` → display name; keys that already exist in the Close dropdown reuse that exact value):

```
epp                 -> EPP                 (dropdown value already "epp")
Stacker             -> Stacker             (dropdown value already "Stacker")
cassette            -> Currency Cassette   (already in inventory as cassette — SKIP add)
k80printer          -> K80 Printer
printerhead         -> Printer Head
rejectstopelement   -> Reject Stop Element
shuttermotor        -> Shutter Motor
stackerunit4        -> Stacker Unit4
receiptprinter      -> Receipt Printer     (dropdown value already "receiptprinter")
camslock            -> Cams Lock
doubleextractor     -> Double Extractor    (dropdown value already "doubleextractor")
notepickingroller   -> Note Picking Roller
safelock            -> Safe Lock           (dropdown value already "safelock")
smallbelts          -> Small Belts
controllercard4x    -> 4x Controller Card
atmscreen           -> ATM Screen
motherboard         -> Motherboard         (dropdown value already "motherboard")
fascia              -> Fascia
horizontaltransport -> Horizontal Transport
housingunit         -> Housing Unit
shutter             -> Shutter
smps                -> Power Supply (SMPS)
```

---

## Task 1: Create + seed the `inventory` table

**Files:**
- Create (server-side, one-time): `/tmp/inventory_migration.sql`
- Reference: current `stocklist` row (id=1) supplies starting quantities for the 11 existing parts.

**Interfaces:**
- Produces: table `inventory(id INT PK, part_key VARCHAR(64) UNIQUE, part_name VARCHAR(150), quantity INT DEFAULT 0)` seeded with the 11 existing parts (migrated quantities) + the new parts (quantity 0). Consumed by Tasks 2 and 3.

- [ ] **Step 1: Back up structure**

Run: `ssh -i <key> bitnami@65.1.88.251 "/opt/bitnami/mariadb/bin/mysqldump -u nitin_login -p'<pw>' --no-data digitechdata stocklist inventory 2>/dev/null > /tmp/inv-structure-backup.sql; wc -l /tmp/inv-structure-backup.sql"`
Expected: a line count printed (inventory may not exist yet — that's fine).

- [ ] **Step 2: Write the migration SQL** (`/tmp/inventory_migration.sql`)

```sql
CREATE TABLE IF NOT EXISTS inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  part_key  VARCHAR(64)  NOT NULL UNIQUE,
  part_name VARCHAR(150) NOT NULL,
  quantity  INT NOT NULL DEFAULT 0
);

-- Migrate the 11 existing stocklist parts, preserving current quantities.
INSERT INTO inventory (part_key, part_name, quantity)
  SELECT 'cardreader','Card Reader',cardreader         FROM stocklist WHERE id=1
  UNION ALL SELECT 'cassette','Currency Cassette',cassette       FROM stocklist WHERE id=1
  UNION ALL SELECT 'shuttercable','Shutter Cable',shuttercable   FROM stocklist WHERE id=1
  UNION ALL SELECT 'hooddoorlock','Hood Door Lock',hooddoorlock  FROM stocklist WHERE id=1
  UNION ALL SELECT 'softkeys','Softkeys',softkeys                FROM stocklist WHERE id=1
  UNION ALL SELECT 'divertcassette','Divert Cassette',divertcassette FROM stocklist WHERE id=1
  UNION ALL SELECT 'belts','High Belts',belts                    FROM stocklist WHERE id=1
  UNION ALL SELECT 'roller','Roller',roller                      FROM stocklist WHERE id=1
  UNION ALL SELECT 'cpufan','CPU Fan',cpufan                     FROM stocklist WHERE id=1
  UNION ALL SELECT 'clamp','Clamp',clamp                         FROM stocklist WHERE id=1
  UNION ALL SELECT 'ddu','DDU',ddu                               FROM stocklist WHERE id=1
ON DUPLICATE KEY UPDATE quantity = VALUES(quantity);

-- Add the new parts at quantity 0 (skips any part_key that already exists).
INSERT IGNORE INTO inventory (part_key, part_name, quantity) VALUES
  ('epp','EPP',0),
  ('Stacker','Stacker',0),
  ('k80printer','K80 Printer',0),
  ('printerhead','Printer Head',0),
  ('rejectstopelement','Reject Stop Element',0),
  ('shuttermotor','Shutter Motor',0),
  ('stackerunit4','Stacker Unit4',0),
  ('receiptprinter','Receipt Printer',0),
  ('camslock','Cams Lock',0),
  ('doubleextractor','Double Extractor',0),
  ('notepickingroller','Note Picking Roller',0),
  ('safelock','Safe Lock',0),
  ('smallbelts','Small Belts',0),
  ('controllercard4x','4x Controller Card',0),
  ('atmscreen','ATM Screen',0),
  ('motherboard','Motherboard',0),
  ('fascia','Fascia',0),
  ('horizontaltransport','Horizontal Transport',0),
  ('housingunit','Housing Unit',0),
  ('shutter','Shutter',0),
  ('smps','Power Supply (SMPS)',0);
```

- [ ] **Step 3: Apply it** — get explicit user go-ahead first (production DDL), then:

Run: `scp -i <key> /tmp/inventory_migration.sql bitnami@65.1.88.251:/tmp/ && ssh -i <key> bitnami@65.1.88.251 "/opt/bitnami/mariadb/bin/mysql -u nitin_login -p'<pw>' digitechdata < /tmp/inventory_migration.sql"`
Expected: no output = success.

- [ ] **Step 4: Verify seed**

Run: `ssh ... "/opt/bitnami/mariadb/bin/mysql ... digitechdata -t -e 'SELECT COUNT(*) AS parts, SUM(quantity) AS total_qty FROM inventory; SELECT part_key,part_name,quantity FROM inventory ORDER BY part_name LIMIT 40;'"`
Expected: ~32 parts; the 11 migrated parts show their real quantities (cardreader=10, belts=100, …); new parts show 0.

- [ ] **Step 5: Commit** (SQL kept in-repo for the record)

```bash
mkdir -p Digiadmin/db
cp /tmp/inventory_migration.sql Digiadmin/db/2026-07-23-inventory_migration.sql   # or recreate locally
git add Digiadmin/db/2026-07-23-inventory_migration.sql
git commit -m "db: add extensible inventory table seeded with existing + 22 new parts"
```

---

## Task 2: Rebuild `viewinventorystatus.php` over the `inventory` table

**Files:**
- Modify: `Digiadmin/html/viewinventorystatus.php` (replace the PHP handler at lines 12-55 and the display table in the body)

**Interfaces:**
- Consumes: `inventory` table from Task 1.
- Produces: a page that (a) lists every part with an editable quantity (one `UPDATE inventory SET quantity=? WHERE id=?` per row save), and (b) has an "Add new part" form (`INSERT INTO inventory (part_key, part_name, quantity)`), so parts can be added without code changes.

- [ ] **Step 1: Replace the PHP head block** (lines 1-56) with the handler below

```php
<?php
session_start();
require_once 'config.php';
$conn = getDbConnection();
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// Update one part's quantity
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_qty'])) {
    $id  = (int)$_POST['id'];
    $qty = (int)$_POST['quantity'];
    try {
        $stmt = $conn->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $qty, $id);
        $stmt->execute();
    } catch (\Throwable $e) { error_log("inventory update failed: ".$e->getMessage()); }
    header("Location: viewinventorystatus.php"); exit();
}

// Add a new part
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_part'])) {
    $name = trim($_POST['part_name'] ?? '');
    $key  = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $name));
    $qty  = (int)($_POST['quantity'] ?? 0);
    if ($name !== '' && $key !== '') {
        try {
            $stmt = $conn->prepare("INSERT IGNORE INTO inventory (part_key, part_name, quantity) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $key, $name, $qty);
            $stmt->execute();
        } catch (\Throwable $e) { error_log("inventory add failed: ".$e->getMessage()); }
    }
    header("Location: viewinventorystatus.php"); exit();
}

$result = $conn->query("SELECT id, part_key, part_name, quantity FROM inventory ORDER BY part_name ASC");
if (!$result) { die("Query failed: " . $conn->error); }
?>
```

- [ ] **Step 2: Replace the display grid in the body** with a per-row editable list + add-part form

```html
<div class="container" style="margin-top:20px;">
  <h3 class="text-center text-danger">Digitech Online Stock Inventory Record</h3>
  <table class="table table-bordered">
    <thead><tr><th>Part Name</th><th style="width:180px;">Quantity</th><th style="width:120px;"></th></tr></thead>
    <tbody>
    <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <form method="post" action="viewinventorystatus.php">
          <td><?php echo htmlspecialchars($row['part_name']); ?></td>
          <td>
            <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
            <input type="number" name="quantity" value="<?php echo (int)$row['quantity']; ?>" class="form-control" min="0">
          </td>
          <td><button type="submit" name="update_qty" class="btn btn-primary">Save</button></td>
        </form>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>

  <h5 class="mt-4">Add a new part</h5>
  <form method="post" action="viewinventorystatus.php" class="row g-2">
    <div class="col-md-6"><input type="text" name="part_name" class="form-control" placeholder="Part name" required></div>
    <div class="col-md-3"><input type="number" name="quantity" class="form-control" value="0" min="0"></div>
    <div class="col-md-3"><button type="submit" name="add_part" class="btn btn-success">Add Part</button></div>
  </form>
</div>
```

- [ ] **Step 3: Lint + deploy** (Global Constraints deploy method)

Run: `php -l /tmp/newfiles/viewinventorystatus.php` → Expected: `No syntax errors detected`. Then deploy.

- [ ] **Step 4: Live verify** (Playwright, logged in)

Load `https://digitechsys.net/Digiadmin/html/viewinventorystatus.php`; confirm ~32 rows render; edit one quantity → Save → value persists; use "Add Part" with name `PLANTEST Widget` → row appears. Then `DELETE FROM inventory WHERE part_key='planttestwidget'`.

- [ ] **Step 5: Commit**

```bash
git add Digiadmin/html/viewinventorystatus.php
git commit -m "feat(inventory): rebuild stock page as extensible parts list"
```

---

## Task 3: Point stock decrement at the `inventory` table

**Files:**
- Modify: `Digiadmin/html/userdetails.php` (the stock-update block, currently `UPDATE stocklist SET \`$columnToUpdate\` ...`)

**Interfaces:**
- Consumes: `inventory.part_key` (= Close dropdown option value) from Task 1.
- Produces: on a call close with a part selected, `inventory.quantity` for that `part_key` decreases by 1 (never below 0), best-effort (never aborts the save).

- [ ] **Step 1: Replace the stock-update block** inside `if ($stmt->execute()) { ... }`

```php
    // Decrement inventory for the selected part, if any (best-effort).
    if ($partnm !== '') {
        try {
            $up = $conn->prepare("UPDATE inventory SET quantity = quantity - 1 WHERE part_key = ? AND quantity > 0");
            $up->bind_param("s", $partnm);
            $up->execute();
        } catch (\Throwable $stockError) {
            error_log("Digitech CRM: stock update skipped/failed: " . $stockError->getMessage());
        }
    }
```

- [ ] **Step 2: Lint + deploy.** `php -l` → `No syntax errors detected`; deploy.

- [ ] **Step 3: Live verify** — register a `PLANTEST` ticket, then close it selecting part "Card Reader" (value `cardreader`); confirm `inventory.quantity` for `cardreader` dropped by 1:

Run: `SELECT quantity FROM inventory WHERE part_key='cardreader';` before/after. Then clean up test rows.

- [ ] **Step 4: Commit**

```bash
git add Digiadmin/html/userdetails.php
git commit -m "feat(inventory): decrement inventory table on call close"
```

---

## Task 4: Append new parts to the Call Close "Name of Part" dropdown

**Files:**
- Modify: `Digiadmin/html/callclosed.php` (the `<select ID="partname" ...>` block ending at line ~1041)

**Interfaces:**
- Consumes: canonical seed keys from Task 1 (option `value` must equal `inventory.part_key`).
- Produces: the dropdown offers the new parts; selecting one and closing a call decrements that inventory row (via Task 3).

- [ ] **Step 1: Insert the new options** immediately before the partname `</select>` (skip keys already present: `epp`, `Stacker`, `receiptprinter`, `doubleextractor`, `safelock`, `motherboard`, `cassette`)

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

- [ ] **Step 2: Lint + deploy.** `php -l` → clean; deploy.

- [ ] **Step 3: Live verify** — load `callclosed.php`; confirm the 15 new options appear in "Name of Part"; pick "K80 Printer", close a `PLANTEST` ticket, confirm `inventory` `k80printer` went 0 → -1 is prevented (stays 0 due to `quantity > 0` guard) and no 500. Clean up.

- [ ] **Step 4: Commit**

```bash
git add Digiadmin/html/callclosed.php
git commit -m "feat: add new parts to Call Close part dropdown"
```

---

## Task 5: Side-by-side registration + closure in View User Details

**Files:**
- Modify: `Digiadmin/html/viewuserdetails.php` (the card render loop; currently one `.card` per row stacked, ordered by unified ticket number)

**Interfaces:**
- Consumes: the existing paired ordering (registration then closure, grouped by unified number) already in the query's `ORDER BY`.
- Produces: for each unified ticket number, the registration card and closure card sit in one horizontal row (two Bootstrap columns); tickets with only one record show a single column.

- [ ] **Step 1: Wrap consecutive same-number cards into a flex row.** Replace the `while ($rows = $result->fetch_assoc()) { ?> ... <?php }` card loop with a version that buffers by unified number and emits a `<div class="row">` containing one `col-md-6` per record:

```php
<?php
$groups = [];
$result->data_seek(0);
while ($rows = $result->fetch_assoc()) {
    $uid = ($rows['tiknumber'] !== '') ? $rows['tiknumber'] : $rows['tickview'];
    $groups[$uid][] = $rows;
}
foreach ($groups as $uid => $records): ?>
  <div class="row">
    <?php foreach ($records as $rows):
        $isClose = in_array($rows['tktstatusdetails'], array('Pending','Closed')); ?>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <img src="../images/ticket-16.png" alt="Ticket Icon">
            Ticket Number: <?php echo htmlspecialchars($isClose ? $rows['tickview'] : $rows['tiknumber']); ?>
            <span style="margin-left:10px; padding:2px 8px; border-radius:4px; font-size:12px; background:<?php echo $isClose ? '#8e1b1b' : '#1e7d3c'; ?>; color:#fff;">
              <?php echo $isClose ? 'CLOSED ('.htmlspecialchars($rows['tktstatusdetails']).')' : 'REGISTERED'; ?>
            </span>
            <form action="delete_ticket.php" method="post" style="display:inline; float:right;">
              <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($rows['tiknumber']); ?>">
              <input type="submit" value="Delete" onclick="return confirm('Delete ticket <?php echo htmlspecialchars($rows['tiknumber']); ?>?');">
            </form>
          </div>
          <div class="card-body">
            <!-- KEEP the existing <p><strong>...</strong> ...</p> field lines verbatim here -->
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
```

Note: paste the existing card-body `<p>` field block (Ticket Status … Remarks, including the Approval Pending (Bank End) and SCR-documents PHP) unchanged into the marked spot.

- [ ] **Step 2: Lint + deploy.** `php -l` → clean; deploy.

- [ ] **Step 3: Live verify** — register + close a `PLANTEST` ticket; open `viewuserdetails.php` (logged in); confirm the REGISTERED and CLOSED cards for that number appear in the same horizontal row (screenshot). Confirm a lone (open-only) ticket shows a single column and page body does not scroll horizontally on a narrow window. Clean up.

- [ ] **Step 4: Commit**

```bash
git add Digiadmin/html/viewuserdetails.php
git commit -m "feat(view): show registration and closure side-by-side"
```

---

## Task 6: Add CSFB (and JKSCB) to the "Select Bank" filter

**Files:**
- Modify: `Digiadmin/html/viewuserdetails.php` (the `$bankNames` build + the `<select id="bankDropdown">`)

**Interfaces:**
- Consumes: nothing new.
- Produces: the Select Bank filter always lists CSFB and JKSCB even when no record uses them yet, without duplicating banks that are already present.

- [ ] **Step 1: Merge fixed banks into the list.** After the `while` that fills `$bankNames`, add:

```php
foreach (array('JKSCB', 'CSFB') as $fixedBank) {
    if (!in_array($fixedBank, $bankNames, true)) { $bankNames[] = $fixedBank; }
}
sort($bankNames);
```

- [ ] **Step 2: Lint + deploy.** `php -l` → clean; deploy.

- [ ] **Step 3: Live verify** — open `viewuserdetails.php`; confirm the Select Bank dropdown contains `CSFB` and `JKSCB` (in addition to `All Banks` and existing banks), and selecting `CSFB` filters (empty result is fine).

- [ ] **Step 4: Commit**

```bash
git add Digiadmin/html/viewuserdetails.php
git commit -m "feat(view): add CSFB and JKSCB to Select Bank filter"
```

---

## Task 7: Re-add "Call Registered through" to Call Registration

**Files:**
- Modify: `Digiadmin/html/callregistered.php` (insert above the Approval Pending (BANK END) block, which sits above the ATM toggle)

**Interfaces:**
- Consumes: nothing new; `userdetails.php` already reads `$_POST['callregthrough']` (defaults to '' when absent), so no handler change is needed.
- Produces: a "Call Registered through" dropdown (Please Select / Email / Telecalling) on the registration form; its value saves to `digitechcrm.callregthr`.

- [ ] **Step 1: Insert the field** immediately before the `<!-- Approval Pending (BANK END) (added) -->` comment

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

- [ ] **Step 2: Lint + deploy.** `php -l` → clean; deploy.

- [ ] **Step 3: Live verify** — load `callregistered.php`; confirm the "Call Registered through" dropdown appears above the Approval Pending toggle with Email/Telecalling; register a `PLANTEST` ticket with `Telecalling`; confirm `SELECT callregthr FROM digitechcrm WHERE branchname='PLANTEST...'` returns `Telecalling`. Clean up.

- [ ] **Step 4: Commit**

```bash
git add Digiadmin/html/callregistered.php
git commit -m "feat: re-add Call Registered through dropdown to registration"
```

---

## Task 8: Push + confirm live

- [ ] **Step 1: Push** all task commits:

```bash
GIT_SSH_COMMAND="ssh -i ~/.ssh/id_ed25519" git push origin main
```

- [ ] **Step 2: Final smoke test** — with the browser logged in, walk register → close (via ticket search) → view (side-by-side pair) → inventory (edit qty), confirming no 500s and that inventory decremented for a real part. Remove any remaining `PLANTEST` rows.

---

## Self-Review

**Spec coverage:**
- Handwritten #1 (registered/closed parallel) → Task 5 (side-by-side). ✓
- Handwritten #2 (Select Bank → Add CSFB) → Task 6. ✓
- Handwritten #3 (re-add Call Registered through Email/Telecalling) → Task 7. ✓
- Handwritten #4 (Stock Inventory: add new spares) → Tasks 1-2 (inventory + 22 parts). ✓
- Excel parts also in Close dropdown (user answer) → Task 4. ✓
- Inventory decrement stays correct after rebuild → Task 3. ✓

**Placeholder scan:** No TBD/TODO; every step has concrete SQL/PHP/HTML and a verify command. Card-body reuse in Task 5 is explicitly "paste existing block verbatim," not a placeholder.

**Type/key consistency:** `inventory.part_key` values in Task 1 match the Close dropdown `<option value>` in Task 4 and the decrement key in Task 3 (`$partnm`). The 7 keys already present in the dropdown (`epp`, `Stacker`, `receiptprinter`, `doubleextractor`, `safelock`, `motherboard`, `cassette`) are intentionally NOT re-added in Task 4 and are covered by `INSERT IGNORE` in Task 1.

**Open risk (flag to user):** existing Close-dropdown parts that are NOT in `inventory` (e.g. `journalprinter`, `controllercard`, `hdd`) will silently no-op on decrement (same as today, where `stocklist` had only 11 columns). If full coverage is wanted, seed those into `inventory` too — out of scope per the stated ask ("add the 22").
