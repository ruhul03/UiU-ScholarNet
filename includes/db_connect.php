<?php
$host = "127.0.0.1";
$user = "root";
$pass = "salman7?";
$dbname = "uiu_scholarnet";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
