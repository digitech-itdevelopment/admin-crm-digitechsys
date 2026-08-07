<?php
// Live ticket search for the Call Close form.
// Returns JSON of OPEN tickets (registered but not yet closed) matching ?q=.
session_start();
if (!isset($_SESSION['userdata'])) { header('Content-Type: application/json'); echo json_encode([]); exit; }
require_once 'config.php';
header('Content-Type: application/json');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === '') { echo json_encode([]); exit; }

$conn = getDbConnection();
$like = '%' . $q . '%';

// Registrations have a ticket number in `tiknumber`; closures store it in
// `tickview`. A ticket is "open" if its number has not been closed yet.
$sql = "SELECT tiknumber, bnkname, branchname, atmid, custnumber, complainno, calltype, warrantystatus,
               error, issues, partdemand, callregthr, callregtimings, approvalpending, bankendtext
        FROM digitechcrm
        WHERE tiknumber <> ''
          AND tiknumber NOT IN (
              SELECT tickview FROM digitechcrm
              WHERE tktstatusdetails = 'Closed' AND tickview <> ''
          )
          AND (tiknumber LIKE ? OR bnkname LIKE ? OR branchname LIKE ? OR atmid LIKE ?)
        GROUP BY tiknumber
        ORDER BY tiknumber DESC
        LIMIT 15";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) { $rows[] = $row; }
    echo json_encode($rows);
    $stmt->close();
} catch (\Throwable $e) {
    error_log("Digitech CRM: ticket search failed: " . $e->getMessage());
    echo json_encode([]);
}
$conn->close();
