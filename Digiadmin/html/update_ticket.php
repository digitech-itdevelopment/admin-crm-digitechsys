<?php
session_start();

if (!isset($_SESSION['userdata'])) {
    header("Location: adminlogin.php");
    exit;
}

require_once 'config.php';
$conn = getDbConnection();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    die("Invalid ticket id.");
}

$complainnumber   = isset($_POST['txtcomplaintno']) ? $_POST['txtcomplaintno'] : '';
$callregtime      = isset($_POST['txtDateTimeregcall']) ? $_POST['txtDateTimeregcall'] : '';
$banknamee        = isset($_POST['bankname']) ? $_POST['bankname'] : '';
$atmidd           = isset($_POST['atmid']) ? $_POST['atmid'] : '';
$bname            = isset($_POST['txtbranchname']) ? $_POST['txtbranchname'] : '';
$custnumber       = isset($_POST['txtcustodianno']) ? $_POST['txtcustodianno'] : '';
$calltype         = isset($_POST['typeofcall']) ? $_POST['typeofcall'] : '';
$warrantystatus   = isset($_POST['warrantystatusoem']) ? $_POST['warrantystatusoem'] : '';
$partdemand       = isset($_POST['partdemand']) ? $_POST['partdemand'] : '';
$error            = isset($_POST['errorcode']) ? $_POST['errorcode'] : '';
$issues           = isset($_POST['issuestxt']) ? $_POST['issuestxt'] : '';
$stationaryreq    = (isset($_POST['stationaryreq']) && is_array($_POST['stationaryreq'])) ? implode(', ', $_POST['stationaryreq']) : '';
$callregthr       = isset($_POST['callregthrough']) ? $_POST['callregthrough'] : '';
$approvalpending  = isset($_POST['approvalpending']) ? 'Yes' : 'No';
$bankendtext      = isset($_POST['bankendtext']) ? $_POST['bankendtext'] : '';
$atmworking       = isset($_POST['atmstatus']) ? 'Not Working' : 'Working';

// Only registration-stage fields are editable here — closure fields are
// handled separately by the Call Close flow, so they're intentionally
// left untouched by this update.
$sql = "UPDATE digitechcrm SET
    complainno = ?, callregtimings = ?, bnkname = ?, atmid = ?, branchname = ?,
    custnumber = ?, calltype = ?, warrantystatus = ?, partdemand = ?, error = ?,
    issues = ?, stationaryreq = ?, callregthr = ?, approvalpending = ?,
    bankendtext = ?, atmworking = ?
    WHERE id = ? AND tiknumber <> ''";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "ssssssssssssssssi",
    $complainnumber, $callregtime, $banknamee, $atmidd, $bname,
    $custnumber, $calltype, $warrantystatus, $partdemand, $error,
    $issues, $stationaryreq, $callregthr, $approvalpending,
    $bankendtext, $atmworking, $id
);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Ticket updated successfully.";
} else {
    $_SESSION['error'] = "Update failed: " . $conn->error;
}

$stmt->close();
$conn->close();
header("Location: viewuserdetails.php");
exit();