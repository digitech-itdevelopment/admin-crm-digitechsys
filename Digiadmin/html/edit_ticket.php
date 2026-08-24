<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['userdata'])) {
    header("Location: adminlogin.php");
    exit;
}

require_once 'config.php';
$conn = getDbConnection();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Invalid ticket id.");
}

$stmt = $conn->prepare("SELECT * FROM digitechcrm WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_assoc();
$stmt->close();

if (!$rows) {
    die("Ticket not found.");
}

// Only registration rows (tiknumber not empty) are editable here — closure
// details are edited via the Call Close flow instead.
if (trim((string)$rows['tiknumber']) === '') {
    die("This record is a closure entry and can't be edited here.");
}

$stationaryreqSelected = array_filter(array_map('trim', explode(',', (string)$rows['stationaryreq'])));

function esc($v) { return htmlspecialchars((string)$v); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Ticket - Digitech CRM</title>
    <meta name="robots" content="noindex,nofollow" />
    <link rel="stylesheet" href="./css/bootstrap.min.css" />
    <script src="./Scripts/jquery.min.js"></script>
    <style>
        body { background:#fff; }
        .container { max-width: 720px; margin: 30px auto; }
        label { font-weight: 700; display:block; margin-top: 14px; }
        .form-control { border-radius: 6px; }
        .mybtn { background:#5a4fcf; color:#fff; border:none; padding:10px 24px; border-radius:6px; margin-top:24px; }
        h4 { text-align:center; color:#e05353; }
    </style>
</head>
<body>
<div class="container">
    <h4>Edit Registered Ticket: <?php echo esc($rows['tiknumber']); ?></h4>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success"><?php echo esc($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>

    <form method="post" action="update_ticket.php">
        <input type="hidden" name="id" value="<?php echo (int)$rows['id']; ?>">

        <label>Ticket No</label>
        <input type="text" class="form-control" value="<?php echo esc($rows['tiknumber']); ?>" readonly>

        <label>Complaint Number</label>
        <input type="text" name="txtcomplaintno" class="form-control" value="<?php echo esc($rows['complainno']); ?>">

        <label>Call Register Date / Time</label>
        <input type="datetime-local" name="txtDateTimeregcall" class="form-control" value="<?php echo esc($rows['callregtimings']); ?>">

        <label>Bank Name</label>
        <select name="bankname" class="form-control">
            <?php foreach (['KCCB','HPSCB','JCCB','JKSCB','CSFB'] as $b): ?>
                <option value="<?php echo esc($b); ?>" <?php echo ($rows['bnkname'] === $b) ? 'selected' : ''; ?>><?php echo esc($b); ?></option>
            <?php endforeach; ?>
        </select>

        <label>ATM ID</label>
        <input type="text" name="atmid" class="form-control" value="<?php echo esc($rows['atmid']); ?>">

        <label>Branch Name</label>
        <input type="text" name="txtbranchname" class="form-control" value="<?php echo esc($rows['branchname']); ?>">

        <label>Custodian Number</label>
        <input type="text" name="txtcustodianno" class="form-control" value="<?php echo esc($rows['custnumber']); ?>">

        <label>Type Of Call</label>
        <select name="typeofcall" class="form-control">
            <?php foreach (['Not Applicable','AMC','BLA','Non-AMC','UPS','Stationary'] as $t): ?>
                <option value="<?php echo esc($t); ?>" <?php echo ($rows['calltype'] === $t) ? 'selected' : ''; ?>><?php echo esc($t); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Warranty status with OEM</label>
        <select name="warrantystatusoem" class="form-control">
            <?php foreach (['Not Applicable','Under-Warranty','Non-Warranty','Dibold','dsi'] as $w): ?>
                <option value="<?php echo esc($w); ?>" <?php echo ($rows['warrantystatus'] === $w) ? 'selected' : ''; ?>><?php echo esc($w); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Part Demand</label>
        <select name="partdemand" class="form-control">
            <option value="">Please Select</option>
            <?php foreach (['OEM','DSI'] as $pd): ?>
                <option value="<?php echo esc($pd); ?>" <?php echo ($rows['partdemand'] === $pd) ? 'selected' : ''; ?>><?php echo esc($pd); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Error Code</label>
        <input type="text" name="errorcode" class="form-control" value="<?php echo esc($rows['error']); ?>">

        <label>Issues</label>
        <input type="text" name="issuestxt" class="form-control" value="<?php echo esc($rows['issues']); ?>">

        <label>Stationary Required</label>
        <select name="stationaryreq[]" class="form-control" multiple>
            <?php foreach (['Not Applicable','Journal Printer','Receipt Printer','JP Ribbon'] as $sr): ?>
                <option value="<?php echo esc($sr); ?>" <?php echo in_array($sr, $stationaryreqSelected, true) ? 'selected' : ''; ?>><?php echo esc($sr); ?></option>
            <?php endforeach; ?>
        </select>

        <label>Call Registered through</label>
        <select name="callregthrough" class="form-control">
            <?php foreach (['Not Applicable','Email','Telecalling'] as $cr): ?>
                <option value="<?php echo esc($cr); ?>" <?php echo ($rows['callregthr'] === $cr) ? 'selected' : ''; ?>><?php echo esc($cr); ?></option>
            <?php endforeach; ?>
        </select>

        <div class="form-check form-switch" style="margin-top:14px;">
            <input class="form-check-input" name="approvalpending" type="checkbox" id="approvalPendingSwitch"
                <?php echo ($rows['approvalpending'] === 'Yes') ? 'checked' : ''; ?>>
            <label class="form-check-label" for="approvalPendingSwitch">Approval Pending (BANK END)</label>
        </div>

        <label>Bank End details</label>
        <input type="text" name="bankendtext" class="form-control" value="<?php echo esc($rows['bankendtext']); ?>">

        <div class="form-check form-switch" style="margin-top:14px;">
            <input class="form-check-input" name="atmstatus" type="checkbox" id="atmStatusSwitch"
                <?php echo ($rows['atmworking'] === 'Not Working') ? 'checked' : ''; ?>>
            <label class="form-check-label" for="atmStatusSwitch">ATM was not Working</label>
        </div>

        <div class="text-center">
            <button type="submit" class="mybtn">Save Changes</button>
            <a href="viewuserdetails.php" class="btn btn-secondary" style="margin-top:24px; margin-left:8px;">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>