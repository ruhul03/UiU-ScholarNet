<?php
require_once('../../includes/db_connect.php');

$queries = [
    "ALTER TABLE users ADD COLUMN reputation INT DEFAULT 0"
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
echo "<h3>03_add_user_reputation complete!</h3>";
?>
