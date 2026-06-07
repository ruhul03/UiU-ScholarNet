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

    // Early return for invalid thread ID
    if ($thread_id <= 0) {
        header("Location: ../dashboard/research_discussion.php");
        exit();
    }

    // 1. Verify user owns the thread
    $verifyThreadQuery = "SELECT id FROM discussion_threads WHERE id = ? AND user_id = ?";
    $verifyThreadResult = db_query($verifyThreadQuery, [$thread_id, $current_user_id], "ii");
    
    // Early return if not authorized
    if (!$verifyThreadResult || $verifyThreadResult->num_rows <= 0) {
        $_SESSION['error'] = "You do not have permission to delete this thread.";
        header("Location: ../dashboard/research_discussion.php");
        exit();
    }
    
    // 2. Delete the replies of this thread to clean up dependencies
    $deleteRepliesQuery = "DELETE FROM discussion_replies WHERE thread_id = ?";
    db_query($deleteRepliesQuery, [$thread_id], "i");

    // 3. Delete the thread itself
    $deleteThreadQuery = "DELETE FROM discussion_threads WHERE id = ?";
    $deleteThreadResult = db_query($deleteThreadQuery, [$thread_id], "i");
    
    if ($deleteThreadResult) {
        $_SESSION['success'] = "Discussion thread deleted successfully.";
    } else {
        $_SESSION['error'] = "Database error while deleting thread.";
    }
    
    header("Location: ../dashboard/research_discussion.php");
    exit();
}
header("Location: ../dashboard/research_discussion.php");
exit();
