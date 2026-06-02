<?php
require_once('../../includes/db_connect.php');

$queries = [
    "ALTER TABLE discussion_threads ADD COLUMN category VARCHAR(50) DEFAULT 'General' AFTER title"
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
echo "<h3>05_add_discussion_category complete!</h3>";
?>
