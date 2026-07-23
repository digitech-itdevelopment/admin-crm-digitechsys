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
