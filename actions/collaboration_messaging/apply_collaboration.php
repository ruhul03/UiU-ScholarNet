<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard/collaboration.php");
    exit();
}

csrf_validate_or_die();

$user_id = (int)$_SESSION['user_id'];
$post_id = (int)($_POST['post_id'] ?? 0);

if ($post_id <= 0) {
    header("Location: ../dashboard/collaboration.php?error=1");
    exit();
}

// Ensure the applications table exists
db_query(
    "CREATE TABLE IF NOT EXISTS collaboration_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        message TEXT NULL,
        status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_post_user (post_id, user_id),
        FOREIGN KEY (post_id) REFERENCES collaboration_posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
);

// Prevent users from applying to their own posts
$checkRes = db_query("SELECT user_id FROM collaboration_posts WHERE id = ? LIMIT 1", [$post_id], "i");
$post = $checkRes ? $checkRes->fetch_assoc() : null;

if (!$post || (int)$post['user_id'] === $user_id) {
    header("Location: ../dashboard/collaboration.php?error=1");
    exit();
}

// Insert the application
$insert = db_query("INSERT IGNORE INTO collaboration_applications (post_id, user_id) VALUES (?, ?)", [$post_id, $user_id], "ii");

if ($insert && $conn->affected_rows > 0) {
    $_SESSION['success'] = "Application submitted successfully! The project lead will review your profile.";
} else {
    $_SESSION['error'] = "You have already applied for this collaboration or an error occurred.";
}

header("Location: ../dashboard/collaboration.php");
exit();
?>
