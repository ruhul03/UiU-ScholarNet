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
    csrf_validate_or_die();
    $current_user_id = (int)$_SESSION['user_id'];
    $project_id = (int)($_POST['project_id'] ?? 0);
    $title = trim((string)$_POST['title']);
    $description = trim((string)($_POST['description'] ?? ''));
    $department = trim((string)$_POST['department']);
    $progress = (int)($_POST['progress'] ?? 0);
    $visibility = trim((string)$_POST['visibility']);
    $status = trim((string)$_POST['status']);

    // Early return if missing required fields
    if ($project_id <= 0 || empty($title)) {
        header("Location: ../dashboard/projects.php");
        exit();
    }

    // 1. Verify ownership or editor role
    $permissionCheckQuery = "
        SELECT p.id 
        FROM projects p 
        LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? 
        WHERE p.id = ? AND (p.creator_id = ? OR pm.role IN ('owner', 'editor')) 
        LIMIT 1
    ";
    
    $permissionCheckResult = db_query($permissionCheckQuery, [$current_user_id, $project_id, $current_user_id], "iii");
    
    // Early return if user is not authorized
    if (!$permissionCheckResult || $permissionCheckResult->num_rows !== 1) {
        $_SESSION['error'] = "Unauthorized access.";
        header("Location: ../dashboard/projects.php");
        exit();
    }
    
    // 2. Update project details (Note: only creator can update main project details in this logic flow)
    $updateProjectQuery = "
        UPDATE projects 
        SET title = ?, description = ?, department = ?, visibility = ?, status = ? 
        WHERE id = ? AND creator_id = ?
    ";
    
    $updateResult = db_query(
        $updateProjectQuery,
        [$title, $description, $department, $visibility, $status, $project_id, $current_user_id],
        "sssssii"
    );
    
    if ($updateResult) {
        update_project_progress($conn, $project_id);
        $_SESSION['success'] = "Project updated successfully.";
    } else {
        $_SESSION['error'] = "Database error while updating.";
    }
}

header("Location: ../dashboard/projects.php");
exit();
?>
