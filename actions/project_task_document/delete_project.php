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
    $user_id = (int)$_SESSION['user_id'];
    $project_id = (int)($_POST['project_id'] ?? 0);

    if ($project_id > 0) {
        // Verify ownership (only creator or an explicit 'owner' member can delete)
        $accessCheck = db_query("SELECT p.id FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? WHERE p.id = ? AND (p.creator_id = ? OR pm.role = 'owner') LIMIT 1", [$user_id, $project_id, $user_id], "iii");
        
        if ($accessCheck && $accessCheck->num_rows === 1) {
            // Delete the project
            $delete = db_query("DELETE FROM projects WHERE id = ?", [$project_id], "i");
            
            if ($delete) {
                $_SESSION['success'] = "Project removed successfully.";
            } else {
                $_SESSION['error'] = "Database error while removing project.";
            }
        } else {
            $_SESSION['error'] = "Project not found or unauthorized.";
        }
    }
}

header("Location: ../dashboard/projects.php");
exit();
?>
