<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');
require_once(__DIR__ . '/../../includes/progress_helper.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Verify user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    // 2. Protect against CSRF attacks
    csrf_validate_or_die();

    // 3. Gather and sanitize input
    $title = trim((string)($_POST['title'] ?? ''));
    $project_id = (int)($_POST['project_id'] ?? 0);
    $priority = (string)($_POST['priority'] ?? 'medium');
    $due_date = (string)($_POST['due_date'] ?? '');
    $description = trim((string)($_POST['description'] ?? ''));
    $is_milestone = isset($_POST['is_milestone']) && $_POST['is_milestone'] == '1' ? 1 : 0;
    $pipeline_stage = !empty($_POST['pipeline_stage']) ? $_POST['pipeline_stage'] : null;
    $document_id = !empty($_POST['document_id']) ? (int)$_POST['document_id'] : null;
    
    $current_user_id = (int)$_SESSION['user_id'];
    $assigned_to_user_id = isset($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : $current_user_id;

    // 4. Validate required fields
    if ($title === '' || $project_id <= 0 || $due_date === '') {
        header("Location: ../dashboard/tasks.php?project_id=$project_id&error=1");
        exit();
    }

    $allowed_priorities = ['low', 'medium', 'high'];
    if (!in_array($priority, $allowed_priorities, true)) {
        $priority = 'medium';
    }

    // 5. Check if user has permission to add tasks (Must be owner or editor)
    $permissionCheckQuery = "
        SELECT p.id, p.status, p.creator_id 
        FROM projects p 
        LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? 
        WHERE p.id = ? 
        AND (p.creator_id = ? OR pm.role IN ('owner', 'editor')) 
        LIMIT 1
    ";
    
    $projectCheckResult = db_query($permissionCheckQuery, [$current_user_id, $project_id, $current_user_id], "iii");
    
    // Early return if user is not authorized
    if (!$projectCheckResult || $projectCheckResult->num_rows !== 1) {
        header("Location: ../dashboard/tasks.php?error=1");
        exit();
    }
    
    $pData = $projectCheckResult->fetch_assoc();
    if ($pData['status'] === 'completed' && $pData['creator_id'] !== $current_user_id) {
        header("Location: ../dashboard/tasks.php?error=archived");
        exit();
    }

    // 6. Insert new task into database
    $insertQuery = "
        INSERT INTO tasks (project_id, title, description, pipeline_stage, document_id, assigned_to, priority, due_date, is_milestone) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $taskInsertResult = db_query($insertQuery, [$project_id, $title, $description, $pipeline_stage, $document_id, $assigned_to_user_id, $priority, $due_date, $is_milestone], "isssiissi");

    if ($taskInsertResult && $assigned_to_user_id !== $current_user_id) {
        $projectDataResult = db_query("SELECT title FROM projects WHERE id = ?", [$project_id], "i");
        $projectData = $projectDataResult ? $projectDataResult->fetch_assoc() : null;
        $projectName = $projectData['title'] ?? 'a project';
        
        send_notification(
            $assigned_to_user_id,
            "New Task Assigned",
            "You have been assigned a new task: '{$title}' in project '{$projectName}'.",
            "../dashboard/tasks.php?project_id={$project_id}",
            "project"
        );
    }

    // 7. Handle success or failure
    if ($taskInsertResult) {
        header("Location: ../dashboard/tasks.php?project_id=$project_id&success=1");
        exit();
    } else {
        header("Location: ../dashboard/tasks.php?project_id=$project_id&error=1");
        exit();
    }
}
?>
