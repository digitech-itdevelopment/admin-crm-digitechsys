<?php


define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'nitin_login');  
define('DB_PASSWORD', 'Sharma#1984');      
define('DB_NAME', 'digitechdata');

function getDbConnection() {
    
   

    $connection = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

    // Check connection
    if ($connection->connect_error) {
        die("Connection failed: " . $connection->connect_error);
    }

    return $connection;
}