<?php
$servername = "localhost";
$username = "root";
$password = "lawrence";
$dbase = "hrm_schema";

$conn = new mysqli($servername, $username, $password, $dbase);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
