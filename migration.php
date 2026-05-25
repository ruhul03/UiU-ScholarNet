<?php
require_once('includes/db_connect.php');

$queries = [
    "ALTER TABLE project_members ADD COLUMN status ENUM('pending', 'active') DEFAULT 'active'",
    "ALTER TABLE projects ADD COLUMN supervisor_id INT DEFAULT NULL",
    "ALTER TABLE projects ADD CONSTRAINT fk_supervisor FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE SET NULL",
    "ALTER TABLE projects ADD COLUMN supervisor_approved TINYINT(1) DEFAULT 0"
];

foreach ($queries as $q) {
    try {
        if ($conn->query($q)) {
            echo "Query success: $q\n";
        }
    } catch (Exception $e) {
        echo "Query failed/ignored: " . $e->getMessage() . "\n";
    }
}
?>
