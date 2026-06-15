<?php
require_once(__DIR__ . '/../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../includes/db_connect.php');
require_once(__DIR__ . '/../includes/csrf.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_die();
    $action_type = $_POST['action_type'] ?? '';
    $thread_id = (int)($_POST['thread_id'] ?? 0);

    if ($action_type === 'delete' && $thread_id > 0) {
        $deleteResult = db_query("DELETE FROM discussion_threads WHERE id = ?", [$thread_id], "i");
        if ($deleteResult) {
            $_SESSION['success'] = "Discussion thread successfully deleted by admin.";
        } else {
            $_SESSION['error'] = "Failed to delete discussion thread.";
        }
    }
}

header("Location: ../dashboard/admin.php");
exit();
?>
