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
    $project_id = (int)($_POST['project_id'] ?? 0);

    if ($project_id > 0) {
        // First verify ownership
        $stmt = $conn->prepare("SELECT p.id FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? WHERE p.id = ? AND (p.creator_id = ? OR pm.role = 'owner') LIMIT 1");
        $stmt->bind_param("iii", $user_id, $project_id, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 1) {
            $delStmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
            $delStmt->bind_param("i", $project_id);
            
            if ($delStmt->execute()) {
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
