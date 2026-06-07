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

    // Early return if invalid reply or thread ID
    if ($reply_id <= 0 || $thread_id <= 0) {
        header("Location: ../dashboard/discussion_thread.php?id=" . $thread_id);
        exit();
    }

    // 1. Verify user owns the reply
    $verifyReplyQuery = "SELECT id FROM discussion_replies WHERE id = ? AND user_id = ?";
    $verifyReplyResult = db_query($verifyReplyQuery, [$reply_id, $current_user_id], "ii");
    
    // Early return if user is not authorized to delete the reply
    if (!$verifyReplyResult || $verifyReplyResult->num_rows <= 0) {
        $_SESSION['error'] = "You do not have permission to delete this reply.";
        header("Location: ../dashboard/discussion_thread.php?id=" . $thread_id);
        exit();
    }
    
    // 2. Delete the reply
    $deleteReplyQuery = "DELETE FROM discussion_replies WHERE id = ?";
    $deleteResult = db_query($deleteReplyQuery, [$reply_id], "i");
    
    if ($deleteResult) {
        $_SESSION['success'] = "Reply deleted successfully.";
    } else {
        $_SESSION['error'] = "Database error while deleting reply.";
    }
}
header("Location: ../dashboard/discussion_thread.php?id=" . $thread_id);
exit();
