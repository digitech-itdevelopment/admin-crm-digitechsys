<?php
session_start();
require_once 'config.php';

$conn = getDbConnection();

$dateandtime = isset($_POST['txtDateTimefaulty']) ? $_POST['txtDateTimefaulty'] : '';

$cardreader = isset($_POST['dd_cardreader']) ? $_POST['dd_cardreader'] : '';
$casset = isset($_POST['dd_casset']) ? $_POST['dd_casset'] : '';
$shuttercable = isset($_POST['dd_shuttercable']) ? $_POST['dd_shuttercable'] : '';
$hoodlock = isset($_POST['dd_hooddoorlock']) ? $_POST['dd_hooddoorlock'] : '';
$softkey = isset($_POST['dd_softkeys']) ? $_POST['dd_softkeys'] : '';
$divertcasset = isset($_POST['dd_divertcasset']) ? $_POST['dd_divertcasset'] : '';
$belts = isset($_POST['dd_belts']) ? $_POST['dd_belts'] : '';
$roller = isset($_POST['dd_rollers']) ? $_POST['dd_rollers'] : '';
$cpufans = isset($_POST['dd_cpufan']) ? $_POST['dd_cpufan'] : '';
$clamps = isset($_POST['dd_clamps']) ? $_POST['dd_clamps'] : '';
$ddu = isset($_POST['dd_ddus']) ? $_POST['dd_ddus'] : '';




$sql = "INSERT INTO  faultyparts (dateandtime, cardreaders, cassettes, shuttercables, hooddoorlocks, softkeys, 
divertcassettes, belts, rollers, cpufans, clamps, ddus)
        VALUES ('$dateandtime', '$cardreader', '$casset', '$shuttercable', '$hoodlock', '$softkey', '$divertcasset', '$belts',
         '$roller', '$cpufans', '$clamps', '$ddu')";



if ($conn->query($sql) === TRUE) {
    echo "Records inserted successfully.";

    header("Location: Addfaultyparts.php");
}


else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}


$conn->close();
exit();

