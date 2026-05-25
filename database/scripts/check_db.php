<?php
require_once('includes/db_connect.php');
$res = $conn->query("DESCRIBE projects");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>
