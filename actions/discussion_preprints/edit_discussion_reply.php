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
    $reply_id = (int)($_POST['reply_id'] ?? 0);
    $thread_id = (int)($_POST['thread_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    // Early return for invalid reply, thread or empty content
    if ($reply_id <= 0 || $thread_id <= 0 || empty($content)) {
        header("Location: ../dashboard/discussion_thread.php?id=" . $thread_id);
        exit();
    }

    // 1. Verify user owns the reply
    $verifyReplyQuery = "SELECT id FROM discussion_replies WHERE id = ? AND user_id = ?";
    $verifyReplyResult = db_query($verifyReplyQuery, [$reply_id, $current_user_id], "ii");
    
    // Early return if not authorized
    if (!$verifyReplyResult || $verifyReplyResult->num_rows <= 0) {
        $_SESSION['error'] = "You do not have permission to edit this reply.";
        header("Location: ../dashboard/discussion_thread.php?id=" . $thread_id);
        exit();
    }
    
    // 2. Update the reply
    $updateReplyQuery = "UPDATE discussion_replies SET content = ? WHERE id = ?";
    $updateResult = db_query($updateReplyQuery, [$content, $reply_id], "si");
    
    if ($updateResult) {
        $_SESSION['success'] = "Reply updated successfully.";
    } else {
        $_SESSION['error'] = "Database error while updating reply.";
    }
}
header("Location: ../dashboard/discussion_thread.php?id=" . $thread_id);
exit();
