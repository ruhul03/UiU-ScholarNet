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
    $content = trim($_POST['content'] ?? '');

    if ($thread_id > 0 && !empty($content)) {
        // Verify user owns the thread
        $check = db_query("SELECT id FROM discussion_threads WHERE id = ? AND user_id = ?", [$thread_id, $user_id], "ii");
        if ($check && $check->num_rows > 0) {
            $update = db_query("UPDATE discussion_threads SET content = ? WHERE id = ?", [$content, $thread_id], "si");
            if ($update) {
                $_SESSION['success'] = "Discussion thread updated successfully.";
            }
        } else {
            $_SESSION['error'] = "You do not have permission to edit this thread.";
        }
    }
}
header("Location: ../dashboard/discussion_thread.php?id=" . $thread_id);
exit();
