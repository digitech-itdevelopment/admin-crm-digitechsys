<?php

// Copy this file to config.php and fill in real credentials.
// config.php is git-ignored so secrets are never committed.

define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'YOUR_DB_USERNAME');
define('DB_PASSWORD', 'YOUR_DB_PASSWORD');
define('DB_NAME', 'YOUR_DB_NAME');

function getDbConnection() {

    $connection = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Check connection
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }

    return $connection;
}
