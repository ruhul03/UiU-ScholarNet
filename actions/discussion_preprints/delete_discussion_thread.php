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

    $user_id = (int)$_SESSION['user_id'];
    $thread_id = (int)($_POST['thread_id'] ?? 0);

    if ($thread_id > 0) {
        // Verify user owns the thread
        $check = db_query("SELECT id FROM discussion_threads WHERE id = ? AND user_id = ?", [$thread_id, $user_id], "ii");
        if ($check && $check->num_rows > 0) {
            // Delete the replies of this thread to clean up dependencies
            db_query("DELETE FROM discussion_replies WHERE thread_id = ?", [$thread_id], "i");

            // Delete the thread itself
            $del_thread = db_query("DELETE FROM discussion_threads WHERE id = ?", [$thread_id], "i");
            if ($del_thread) {
                $_SESSION['success'] = "Discussion thread deleted successfully.";
                header("Location: ../dashboard/research_discussion.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "You do not have permission to delete this thread.";
        }
    }
}
header("Location: ../dashboard/research_discussion.php");
exit();
