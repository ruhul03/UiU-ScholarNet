<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');
require_once(__DIR__ . '/../../includes/progress_helper.php');

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
        
        // Verify user has permission (project owner, editor, or task assignee)
        $checkResQuery = db_query(
            "SELECT t.id, t.status, t.assigned_to, t.project_id FROM tasks t JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ? WHERE t.id = ? AND (p.creator_id = ? OR t.assigned_to = ? OR pm.role IN ('owner', 'editor'))",
            [$user_id, $task_id, $user_id, $user_id],
            "iiii"
        );
        $checkRes = $checkResQuery ? $checkResQuery->fetch_assoc() : null;
        
        if ($checkRes) {
            // Update the task status
            $upStmt = db_query("UPDATE tasks SET status = ? WHERE id = ?", [$status, $task_id], "si");
            
            if ($upStmt) {
                // Reward reputation points for task completion
                if ($status === 'done' && $checkRes['status'] !== 'done') {
                    $assignee = (int)($checkRes['assigned_to'] ?: $user_id);
                    
                    // Get dynamic points
                    $ruleResQuery = db_query("SELECT points FROM reputation_rules WHERE action_key = 'task_completed'");
                    $award_points = ($ruleResQuery && $ruleResQuery->num_rows > 0) ? (int)$ruleResQuery->fetch_assoc()['points'] : 50;

                    // Update user's points
                    db_query("UPDATE users SET points = points + ? WHERE id = ?", [$award_points, $assignee], "ii");
                }
                
                // Update project progress
                if (isset($checkRes['project_id']) && $checkRes['project_id'] > 0) {
                    update_project_progress($conn, $checkRes['project_id']);
                }
            }
        }
    }
}
$redirect = isset($_POST['project_id']) && (int)$_POST['project_id'] > 0 ? '?project_id=' . (int)$_POST['project_id'] : '';
header("Location: ../dashboard/tasks.php" . $redirect);
exit();
?>
