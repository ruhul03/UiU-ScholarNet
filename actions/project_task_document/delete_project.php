<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_die();
    $current_user_id = (int)$_SESSION['user_id'];
    $project_id = (int)($_POST['project_id'] ?? 0);

    // Early return if invalid project ID
    if ($project_id <= 0) {
        header("Location: ../dashboard/projects.php");
        exit();
    }

    // 1. Verify ownership (only creator or an explicit 'owner' member can delete)
    $ownershipCheckQuery = "
        SELECT p.id 
        FROM projects p 
        LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? 
        WHERE p.id = ? AND (p.creator_id = ? OR pm.role = 'owner') 
        LIMIT 1
    ";
    
    $ownershipCheckResult = db_query($ownershipCheckQuery, [$current_user_id, $project_id, $current_user_id], "iii");
    
    // Early return if user lacks ownership permissions
    if (!$ownershipCheckResult || $ownershipCheckResult->num_rows !== 1) {
        $_SESSION['error'] = "Project not found or unauthorized.";
        header("Location: ../dashboard/projects.php");
        exit();
    }
    
    // 2. Delete the project
    $deleteResult = db_query("DELETE FROM projects WHERE id = ?", [$project_id], "i");
    
    if ($deleteResult) {
        $_SESSION['success'] = "Project removed successfully.";
    } else {
        $_SESSION['error'] = "Database error while removing project.";
    }
}

header("Location: ../dashboard/projects.php");
exit();
?>
