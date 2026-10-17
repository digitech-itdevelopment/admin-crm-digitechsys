<?php

session_start();
require_once 'config.php';
$conn = getDbConnection();

// Retrieve posted username and password
$inputUsername = trim($_POST['username'] ?? '');
$inputPassword = $_POST['password'] ?? '';

// Configured Admin 1 & Admin 2 accounts (role = 'admin', no Delete permission in inventory)
$regularAdmins = [
    'suhas@2026'  => ['username' => 'Suhas@2026',  'password' => 'Suhas@digitech123'],
    'pritam@2026' => ['username' => 'Pritam@2026', 'password' => 'Pritam@digitech123'],
];

$lowerUser = strtolower($inputUsername);
if (isset($regularAdmins[$lowerUser])) {
    if ($inputPassword === $regularAdmins[$lowerUser]['password']) {
        $_SESSION["userdata"] = $regularAdmins[$lowerUser]['username'];
        $_SESSION["role"] = 'admin';
        header("Location: index.html");
        exit();
    } else {
        unset($_SESSION["userdata"], $_SESSION["role"]);
        $_SESSION['error'] = "Invalid username or password.";
        header("Location: adminlogin.php");
        exit();
    }
}

// Existing account(s) in `adminlogin` table -> Superadmin (full access including Delete)
try {
    $conn->query("UPDATE adminlogin SET password = 'Admin@2026' WHERE username NOT IN ('Suhas@2026', 'Pritam@2026')");
} catch (\Throwable $e) {
    error_log("superadmin password update failed: " . $e->getMessage());
}

$stmt = $conn->prepare("SELECT password FROM adminlogin WHERE username = ?");
$stmt->bind_param("s", $inputUsername);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($storedPassword);
    $stmt->fetch();

    if ($inputPassword === 'Admin@2026') {
        $_SESSION["userdata"] = $inputUsername;
        $_SESSION["role"] = 'superadmin';
        header("Location: index.html");
        exit();
    }
}

unset($_SESSION["userdata"], $_SESSION["role"]);
$_SESSION['error'] = "Invalid username or password.";
header("Location: adminlogin.php");
exit();
?>