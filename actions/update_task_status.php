<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_die();
    $user_id = (int)$_SESSION['user_id'];
    $task_id = (int)($_POST['task_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($task_id > 0 && in_array($status, ['todo', 'in_progress', 'done'], true)) {
        // Verify the user owns the project or is assigned to the task
        $checkStmt = $conn->prepare("SELECT t.id FROM tasks t JOIN projects p ON t.project_id = p.id WHERE t.id = ? AND (p.creator_id = ? OR t.assigned_to = ?)");
        $checkStmt->bind_param("iii", $task_id, $user_id, $user_id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $upStmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
            $upStmt->bind_param("si", $status, $task_id);
            $upStmt->execute();
        }
    }
}
$redirect = isset($_POST['project_id']) && (int)$_POST['project_id'] > 0 ? '?project_id=' . (int)$_POST['project_id'] : '';
header("Location: ../dashboard/tasks.php" . $redirect);
exit();
?>
