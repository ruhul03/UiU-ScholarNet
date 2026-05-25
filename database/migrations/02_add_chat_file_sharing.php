<?php
require_once('../../includes/db_connect.php');

$queries = [
    "ALTER TABLE messages ADD COLUMN file_path VARCHAR(255) NULL AFTER message",
    "ALTER TABLE messages ADD COLUMN file_name VARCHAR(255) NULL AFTER file_path"
];

foreach ($queries as $q) {
    try {
        if ($conn->query($q) === TRUE) {
            echo "Query success: $q<br>\n";
        } else {
            echo "Query ignored (already applied?): " . $conn->error . "<br>\n";
        }
    } catch (Exception $e) {
        echo "Query failed/ignored: " . $e->getMessage() . "<br>\n";
    }
}
echo "<h3>02_add_chat_file_sharing complete!</h3>";
?>
