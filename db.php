<?php
$servername = "localhost";
$username = "root";   
$password = "";       
$dbname = "u587983768_Kalinga_Clinic";




$con = new mysqli($servername, $username, $password, $dbname);

$con->set_charset('utf8mb4');

if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}
?>

