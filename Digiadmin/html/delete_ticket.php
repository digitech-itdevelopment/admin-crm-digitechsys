<?php
session_start();
require_once 'config.php';
$conn = getDbConnection();


// Check if ticket_id is set
if (isset($_POST['ticket_id'])) {
    $ticket_id = $_POST['ticket_id'];

    // Sanitize the ticket ID to prevent SQL injection
    $ticket_id = mysqli_real_escape_string($conn, $ticket_id);



    // SQL query to delete the ticket
    $sql = "DELETE FROM digitechcrm WHERE tiknumber = ?";
          $stmt = $conn->prepare($sql);
               $stmt->bind_param("s", $ticket_id);


    if ($stmt->execute()) {
        header("Location: viewuserdetails.php");
    } else {
        echo "Error updating record: " . $stmt->error;
    }

    $stmt->close();
}
?>