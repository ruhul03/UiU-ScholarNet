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

$current_user_id = (int)$_SESSION['user_id'];
$post_id = (int)($_POST['post_id'] ?? 0);

// Early return for invalid post ID
if ($post_id <= 0) {
    header("Location: ../dashboard/collaboration.php?error=1");
    exit();
}

// 1. Ensure the applications table exists
$createTableQuery = "
    CREATE TABLE IF NOT EXISTS collaboration_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        message TEXT NULL,
        status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_post_user (post_id, user_id),
        FOREIGN KEY (post_id) REFERENCES collaboration_posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
";
db_query($createTableQuery);

// 2. Prevent users from applying to their own posts
$checkPostQuery = "SELECT user_id FROM collaboration_posts WHERE id = ? LIMIT 1";
$checkPostResult = db_query($checkPostQuery, [$post_id], "i");
$postData = $checkPostResult ? $checkPostResult->fetch_assoc() : null;

// Early return if post doesn't exist or belongs to the current user
if (!$postData || (int)$postData['user_id'] === $current_user_id) {
    header("Location: ../dashboard/collaboration.php?error=1");
    exit();
}

// 3. Insert the application
$checkAppQuery = "SELECT id FROM collaboration_applications WHERE post_id = ? AND user_id = ?";
$checkAppResult = db_query($checkAppQuery, [$post_id, $current_user_id], "ii");

if ($checkAppResult && $checkAppResult->num_rows > 0) {
    $_SESSION['error'] = "You have already applied for this collaboration.";
    header("Location: ../dashboard/collaboration.php");
    exit();
}

$insertApplicationQuery = "INSERT INTO collaboration_applications (post_id, user_id) VALUES (?, ?)";
$insertResult = db_query($insertApplicationQuery, [$post_id, $current_user_id], "ii");

if ($insertResult) {
    $_SESSION['success'] = "Application submitted successfully! The project lead will review your profile.";
    
    // Notify post owner
    $post_owner_id = (int)$postData['user_id'];
    $userDataResult = db_query("SELECT full_name FROM users WHERE id = ?", [$current_user_id], "i");
    $userData = $userDataResult ? $userDataResult->fetch_assoc() : null;
    $applicantName = $userData['full_name'] ?? 'Someone';
    
    send_notification(
        $post_owner_id,
        "New Collaboration Application",
        "{$applicantName} has applied to your collaboration post.",
        "../dashboard/collaboration.php",
        "collaboration"
    );
} else {
    $_SESSION['error'] = "You have already applied for this collaboration or an error occurred.";
}

header("Location: ../dashboard/collaboration.php");
exit();
?>
