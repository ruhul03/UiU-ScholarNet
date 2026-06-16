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
    
    $current_user_id = (int)$_SESSION['user_id'];
    $task_id = (int)($_POST['task_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $redirect = isset($_POST['project_id']) && (int)$_POST['project_id'] > 0 ? '?project_id=' . (int)$_POST['project_id'] : '';

    // 1. Check if task ID is valid and the selected status is allowed
    if ($task_id <= 0 || !in_array($status, ['todo', 'in_progress', 'done'], true)) {
        header("Location: ../dashboard/tasks.php" . $redirect);
        exit();
    }

    // 2. Verify user has permission (project owner, editor, or task assignee)
    $permissionCheckQuery = "
        SELECT t.id, t.status, t.assigned_to, t.project_id 
        FROM tasks t 
        JOIN projects p ON t.project_id = p.id 
        LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ? 
        WHERE t.id = ? AND (p.creator_id = ? OR t.assigned_to = ? OR pm.role IN ('owner', 'editor'))
    ";
    
    $permissionCheckResult = db_query($permissionCheckQuery, [$current_user_id, $task_id, $current_user_id, $current_user_id], "iiii");
    $taskData = $permissionCheckResult ? $permissionCheckResult->fetch_assoc() : null;
    
    // Early return if unauthorized
    if (!$taskData) {
        header("Location: ../dashboard/tasks.php" . $redirect);
        exit();
    }

    // 3. Update the task status
    $updateTaskQuery = "UPDATE tasks SET status = ? WHERE id = ?";
    $updateTaskResult = db_query($updateTaskQuery, [$status, $task_id], "si");
    
    if ($updateTaskResult) {
        // 4. Reward reputation points for task completion
        if ($status === 'done' && $taskData['status'] !== 'done') {
            $assignee_id = (int)($taskData['assigned_to'] ?: $current_user_id);
            
            // Get dynamic points from rules
            $ruleResult = db_query("SELECT points FROM reputation_rules WHERE action_key = 'task_completed'");
            $award_points = ($ruleResult && $ruleResult->num_rows > 0) ? (int)$ruleResult->fetch_assoc()['points'] : 50;

            // Update user's points
            $updatePointsQuery = "UPDATE users SET points = points + ? WHERE id = ?";
            db_query($updatePointsQuery, [$award_points, $assignee_id], "ii");
            
            // Notify Project Creator
            $project_id = $taskData['project_id'];
            $projectDataRes = db_query("SELECT title, creator_id FROM projects WHERE id = ?", [$project_id], "i");
            if ($projectDataRes && $projectDataRes->num_rows > 0) {
                $projInfo = $projectDataRes->fetch_assoc();
                if ($projInfo['creator_id'] != $current_user_id) {
                    send_notification(
                        $projInfo['creator_id'],
                        "Task Completed",
                        "A task has been completed in your project '{$projInfo['title']}'.",
                        "../dashboard/tasks.php?project_id={$project_id}",
                        "project"
                    );
                }
            }
        }
    }
}

$redirect = isset($_POST['project_id']) && (int)$_POST['project_id'] > 0 ? '?project_id=' . (int)$_POST['project_id'] : '';
header("Location: ../dashboard/tasks.php" . $redirect);
exit();
?>
