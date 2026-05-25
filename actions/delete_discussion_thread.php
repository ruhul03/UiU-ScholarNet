<?php
require_once('../includes/db_connect.php');
require_once('../includes/auth_check.php');
require_once('../includes/csrf.php');

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
        $stmt = $conn->prepare("SELECT id FROM discussion_threads WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $thread_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            // Delete the thread (cascading deletes usually handle replies if set up, otherwise we delete replies manually to be safe)
            $del_replies = $conn->prepare("DELETE FROM discussion_replies WHERE thread_id = ?");
            $del_replies->bind_param("i", $thread_id);
            $del_replies->execute();

            $del_thread = $conn->prepare("DELETE FROM discussion_threads WHERE id = ?");
            $del_thread->bind_param("i", $thread_id);
            if ($del_thread->execute()) {
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
