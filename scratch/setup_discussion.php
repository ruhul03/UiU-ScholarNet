<?php
require_once(__DIR__ . '/../includes/db_connect.php');

$sql1 = "CREATE TABLE IF NOT EXISTS discussion_threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

$sql2 = "CREATE TABLE IF NOT EXISTS discussion_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES discussion_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $sql1)) {
    echo "Table discussion_threads created successfully.\n";
} else {
    echo "Error creating table discussion_threads: " . mysqli_error($conn) . "\n";
}

if (mysqli_query($conn, $sql2)) {
    echo "Table discussion_replies created successfully.\n";
} else {
    echo "Error creating table discussion_replies: " . mysqli_error($conn) . "\n";
}

// Add some dummy data if empty
$res = mysqli_query($conn, "SELECT COUNT(*) FROM discussion_threads");
$row = mysqli_fetch_row($res);
if ($row[0] == 0) {
    mysqli_query($conn, "INSERT INTO discussion_threads (user_id, title, content) VALUES (1, 'Welcome to the Research Discussion Forum!', 'This is a place to discuss your ideas, share papers, and collaborate openly.')");
}

mysqli_close($conn);
?>
