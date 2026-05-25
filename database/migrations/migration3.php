<?php
require_once('includes/db_connect.php');

$queries = [
    "ALTER TABLE messages ADD COLUMN file_path VARCHAR(255) NULL AFTER message;",
    "ALTER TABLE messages ADD COLUMN file_name VARCHAR(255) NULL AFTER file_path;"
];

foreach ($queries as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Successfully executed: $sql<br>";
    } else {
        echo "Error executing: $sql - " . $conn->error . "<br>";
    }
}
echo "Migration 3 complete!";
?>
