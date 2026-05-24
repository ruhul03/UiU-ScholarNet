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
        $checkStmt = $conn->prepare("SELECT t.id, t.status, t.assigned_to FROM tasks t JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ? WHERE t.id = ? AND (p.creator_id = ? OR t.assigned_to = ? OR pm.role IN ('owner', 'editor'))");
        $checkStmt->bind_param("iiii", $user_id, $task_id, $user_id, $user_id);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result()->fetch_assoc();
        
        if ($checkRes) {
            // QUERY: Update the task status in the tasks table
            $upStmt = $conn->prepare("UPDATE tasks SET status = ? WHERE id = ?");
            $upStmt->bind_param("si", $status, $task_id);
            
            if ($upStmt->execute()) {
                // REPUTATION POINTS: Fetch dynamic points for task completion
                if ($status === 'done' && $checkRes['status'] !== 'done') {
                    $assignee = (int)($checkRes['assigned_to'] ?: $user_id);
                    
                    // Get dynamic points from reputation_rules
                    $ruleStmt = $conn->prepare("SELECT points FROM reputation_rules WHERE action_key = 'task_completed'");
                    $ruleStmt->execute();
                    $ruleRes = $ruleStmt->get_result()->fetch_assoc();
                    $award_points = $ruleRes ? (int)$ruleRes['points'] : 50;

                    $ptsStmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
                    $ptsStmt->bind_param("ii", $award_points, $assignee);
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
