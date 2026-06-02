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
    $reply_id = (int)($_POST['reply_id'] ?? 0);
    $thread_id = (int)($_POST['thread_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if ($reply_id > 0 && $thread_id > 0 && !empty($content)) {
        // Verify user owns the reply
        $check = db_query("SELECT id FROM discussion_replies WHERE id = ? AND user_id = ?", [$reply_id, $user_id], "ii");
        if ($check && $check->num_rows > 0) {
            $update = db_query("UPDATE discussion_replies SET content = ? WHERE id = ?", [$content, $reply_id], "si");
            if ($update) {
                $_SESSION['success'] = "Reply updated successfully.";
            }
        } else {
            $_SESSION['error'] = "You do not have permission to edit this reply.";
        }
    }
}
header("Location: ../dashboard/discussion_thread.php?id=" . $thread_id);
exit();
