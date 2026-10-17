<?php

session_start();
require_once 'config.php';
$conn = getDbConnection();

// Retrieve posted username and password
$inputUsername = trim($_POST['username'] ?? '');
$inputPassword = $_POST['password'] ?? '';

// Authenticate from the `adminlogin` database table
$stmt = $conn->prepare("SELECT username, password, role FROM adminlogin WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $inputUsername);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($dbUsername, $storedPassword, $storedRole);
    $stmt->fetch();

    if ($inputPassword === $storedPassword) {
        $_SESSION["userdata"] = $dbUsername;
        $_SESSION["role"] = $storedRole ?: 'admin';
        header("Location: index.html");
        exit();
    }
}

unset($_SESSION["userdata"], $_SESSION["role"]);
$_SESSION['error'] = "Invalid username or password.";
header("Location: adminlogin.php");
exit();
?>