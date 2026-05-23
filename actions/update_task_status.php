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
    // SECURITY: Validate CSRF token to prevent cross-site request forgery attacks
    csrf_validate_or_die();
    
    $user_id = (int)$_SESSION['user_id'];
    $task_id = (int)($_POST['task_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    // Check if task ID is valid and the selected status is allowed
    if ($task_id > 0 && in_array($status, ['todo', 'in_progress', 'done'], true)) {
        
        // QUERY: Get task assignee and current status. Verify user has permission (project owner or task assignee)
        $checkStmt = $conn->prepare("SELECT t.id, t.status, t.assigned_to FROM tasks t JOIN projects p ON t.project_id = p.id WHERE t.id = ? AND (p.creator_id = ? OR t.assigned_to = ?)");
        $checkStmt->bind_param("iii", $task_id, $user_id, $user_id);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result()->fetch_assoc();
        
        if ($checkRes) {
            // QUERY: Update the task status in the tasks table
            $upStmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
            $upStmt->bind_param("si", $status, $task_id);
            
            if ($upStmt->execute()) {
                // REPUTATION POINTS: If the task is newly completed ('done'), award +50 points to the assignee
                if ($status === 'done' && $checkRes['status'] !== 'done') {
                    $assignee = (int)($checkRes['assigned_to'] ?: $user_id);
                    $ptsStmt = $conn->prepare("UPDATE users SET points = points + 50 WHERE id = ?");
                    $ptsStmt->bind_param("i", $assignee);
                    $ptsStmt->execute();
                }
            }
        }
    }
}
$redirect = isset($_POST['project_id']) && (int)$_POST['project_id'] > 0 ? '?project_id=' . (int)$_POST['project_id'] : '';
header("Location: ../dashboard/tasks.php" . $redirect);
exit();
?>
