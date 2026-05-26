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
    $user_id = (int)$_SESSION['user_id'];
    $project_id = (int)($_POST['project_id'] ?? 0);
    $title = trim((string)$_POST['title']);
    $description = trim((string)($_POST['description'] ?? ''));
    $department = trim((string)$_POST['department']);
    $progress = (int)($_POST['progress'] ?? 0);
    $visibility = trim((string)$_POST['visibility']);
    $status = trim((string)$_POST['status']);

    if ($project_id > 0 && !empty($title)) {
        // Verify ownership or editor role
        $accessCheck = db_query("SELECT p.id FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? WHERE p.id = ? AND (p.creator_id = ? OR pm.role IN ('owner', 'editor')) LIMIT 1", [$user_id, $project_id, $user_id], "iii");
        
        if ($accessCheck && $accessCheck->num_rows === 1) {
            // Update project details (restricted to creator)
            $update = db_query(
                "UPDATE projects SET title = ?, description = ?, department = ?, visibility = ?, status = ? WHERE id = ? AND creator_id = ?",
                [$title, $description, $department, $visibility, $status, $project_id, $user_id],
                "sssssii"
            );
            
            if ($update) {
                update_project_progress($conn, $project_id);
                $_SESSION['success'] = "Project updated successfully.";
            } else {
                $_SESSION['error'] = "Database error while updating.";
            }
        } else {
            $_SESSION['error'] = "Unauthorized access.";
        }
    }
}

header("Location: ../dashboard/projects.php");
exit();
?>
