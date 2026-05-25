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
    $content = trim($_POST['content'] ?? '');

    if ($thread_id > 0 && !empty($content)) {
        // Verify user owns the thread
        $stmt = $conn->prepare("SELECT id FROM discussion_threads WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $thread_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $update = $conn->prepare("UPDATE discussion_threads SET content = ? WHERE id = ?");
            $update->bind_param("si", $content, $thread_id);
            if ($update->execute()) {
                $_SESSION['success'] = "Discussion thread updated successfully.";
            }
        } else {
            $_SESSION['error'] = "You do not have permission to edit this thread.";
        }
    }
}
header("Location: ../dashboard/discussion_thread.php?id=" . $thread_id);
exit();
