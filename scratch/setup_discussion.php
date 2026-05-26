<?php
// scratch/setup_discussion.php
// This script creates the necessary database tables for the discussion forum.

// Include the database connection logic
require_once(__DIR__ . '/../includes/db_connect.php');

echo "Starting database setup...\n<br>";

// 1. Create the discussion_threads table
// This table will store the main topics/posts created by users
$createThreadsTable = "CREATE TABLE IF NOT EXISTS discussion_threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $createThreadsTable)) {
    echo "Table 'discussion_threads' is ready.\n<br>";
} else {
    echo "Error creating 'discussion_threads': " . mysqli_error($conn) . "\n<br>";
}

// 2. Create the discussion_replies table
// This table will store replies/comments on specific discussion threads
$createRepliesTable = "CREATE TABLE IF NOT EXISTS discussion_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES discussion_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";

if (mysqli_query($conn, $createRepliesTable)) {
    echo "Table 'discussion_replies' is ready.\n<br>";
} else {
    echo "Error creating 'discussion_replies': " . mysqli_error($conn) . "\n<br>";
}

// 3. Add initial dummy data to help beginners test the feature
$result = mysqli_query($conn, "SELECT COUNT(*) FROM discussion_threads");
$countRow = mysqli_fetch_row($result);

if ($countRow[0] == 0) {
    // Only insert if no threads exist to prevent duplicates
    
    // Get the first user to assign the welcome post to, to avoid hardcoding user ID = 1 which might not exist
    $userResult = mysqli_query($conn, "SELECT id FROM users LIMIT 1");
    if ($userRow = mysqli_fetch_assoc($userResult)) {
        $firstUserId = $userRow['id'];
        
        $insertQuery = "INSERT INTO discussion_threads (user_id, title, content) 
                        VALUES (?, 'Welcome to the Research Discussion Forum!', 'This is a place to discuss your ideas, share papers, and collaborate openly.')";
                        
        // Use prepared statements for security, even in setup scripts!
        $stmt = $conn->prepare($insertQuery);
        if ($stmt) {
            $stmt->bind_param("i", $firstUserId);
            if ($stmt->execute()) {
                echo "Inserted a welcome discussion thread!\n<br>";
            }
        }
    } else {
        echo " No users found in the database. Skipping welcome post creation.\n<br>";
    }
}

// Always close the database connection when finished
mysqli_close($conn);
echo "Setup complete!\n";
?>
