<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

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

$conn->query(
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

$check = $conn->prepare("SELECT user_id FROM collaboration_posts WHERE id = ? LIMIT 1");
$check->bind_param("i", $post_id);
$check->execute();
$post = $check->get_result()->fetch_assoc();

if (!$post || (int)$post['user_id'] === $user_id) {
    header("Location: ../dashboard/collaboration.php?error=1");
    exit();
}

$insert = $conn->prepare("INSERT IGNORE INTO collaboration_applications (post_id, user_id, status) VALUES (?, ?, 'pending')");
$insert->bind_param("ii", $post_id, $user_id);
$insert->execute();

header("Location: ../dashboard/collaboration.php?applied=1");
exit();
?>
