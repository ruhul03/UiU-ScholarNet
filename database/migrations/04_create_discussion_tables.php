<?php
require_once('../../includes/db_connect.php');

$queries = [
    "CREATE TABLE IF NOT EXISTS discussion_threads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        category VARCHAR(50) DEFAULT 'General',
        content TEXT NOT NULL,
        views INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS discussion_replies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        thread_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (thread_id) REFERENCES discussion_threads(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
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
echo "<h3>04_create_discussion_tables complete!</h3>";
?>
