<?php
require_once('includes/db_connect.php');
$res = $conn->query("SHOW COLUMNS FROM notifications");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . " | " . $row['Default'] . "\n";
}
?>
