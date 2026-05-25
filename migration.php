<?php
require_once('includes/db_connect.php');

$sql = "ALTER TABLE project_members ADD COLUMN status ENUM('pending', 'active') DEFAULT 'active'";
if ($conn->query($sql) === TRUE) {
    echo "Column added successfully.";
} else {
    echo "Error: " . $conn->error;
}
?>
