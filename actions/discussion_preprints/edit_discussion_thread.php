<?php
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/auth_check.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_validate_or_die();

    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    $current_user_id = (int)$_SESSION['user_id'];
    $thread_id = (int)($_POST['thread_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    // Early return if thread ID is invalid or content is empty
    if ($thread_id <= 0 || empty($content)) {
        header("Location: ../dashboard/discussion_thread.php?id=" . $thread_id);
        exit();
    }

    // 1. Verify user owns the thread
    $verifyThreadQuery = "SELECT id FROM discussion_threads WHERE id = ? AND user_id = ?";
    $verifyThreadResult = db_query($verifyThreadQuery, [$thread_id, $current_user_id], "ii");
    
    // Early return if not authorized
    if (!$verifyThreadResult || $verifyThreadResult->num_rows <= 0) {
        $_SESSION['error'] = "You do not have permission to edit this thread.";
        header("Location: ../dashboard/discussion_thread.php?id=" . $thread_id);
        exit();
    }
    
    // 2. Update the thread
    $updateThreadQuery = "UPDATE discussion_threads SET content = ? WHERE id = ?";
    $updateResult = db_query($updateThreadQuery, [$content, $thread_id], "si");
    
    if ($updateResult) {
        $_SESSION['success'] = "Discussion thread updated successfully.";
    } else {
        $_SESSION['error'] = "Database error while updating thread.";
    }
}
header("Location: ../dashboard/discussion_thread.php?id=" . $thread_id);
exit();
