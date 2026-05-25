<?php
require_once('includes/db_connect.php');

$res = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($res->num_rows == 0) {
    echo "Table 'notifications' does not exist.\n";
    
    // Create it
    $sql = "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    if ($conn->query($sql)) {
        echo "Created 'notifications' table.\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
} else {
    echo "Table 'notifications' exists.\n";
    $res = $conn->query("DESCRIBE notifications");
    while($row = $res->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
}
?>
