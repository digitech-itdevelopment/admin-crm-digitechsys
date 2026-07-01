<?php

session_start();
require_once 'config.php';
$conn = getDbConnection();


// Retrieve posted username and password
$inputUsername = $_POST['username'];
$inputPassword = $_POST['password'];
$_SESSION["userdata"] = $inputUsername;



// Prepare and bind
$stmt = $conn->prepare("SELECT password FROM adminlogin WHERE username = ?");
$stmt->bind_param("s", $inputUsername);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Bind the result to the variable
    $stmt->bind_result($storedPassword);
    $stmt->fetch();

    // Verify the password
    if ($inputPassword === $storedPassword ) {  // Use password_verify() if passwords are hashed
        header("Location: index.html");
        exit();
    }
    
    
    
    
    else {
        $_SESSION['error'] = "Invalid username or password.";
        header("Location: adminlogin.php");
        exit();
    }
} 


?>