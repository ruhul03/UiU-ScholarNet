<?php
require_once('../../includes/db_connect.php');

$queries = [
    "ALTER TABLE project_members ADD COLUMN status ENUM('pending', 'active') DEFAULT 'active'",
    "ALTER TABLE projects ADD COLUMN supervisor_id INT DEFAULT NULL",
    "ALTER TABLE projects ADD CONSTRAINT fk_supervisor FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE SET NULL",
    "ALTER TABLE projects ADD COLUMN supervisor_approved TINYINT(1) DEFAULT 0"
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
echo "<h3>01_add_project_collaboration_fields complete!</h3>";
?>
