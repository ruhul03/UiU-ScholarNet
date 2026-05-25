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
    $title = trim((string)$_POST['title']);
    $description = trim((string)($_POST['description'] ?? ''));
    $department = trim((string)$_POST['department']);
    $progress = (int)($_POST['progress'] ?? 0);
    $visibility = trim((string)$_POST['visibility']);
    $status = trim((string)$_POST['status']);

    if ($project_id > 0 && !empty($title)) {
        // Verify ownership
        $stmt = $conn->prepare("SELECT p.id FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? WHERE p.id = ? AND (p.creator_id = ? OR pm.role IN ('owner', 'editor')) LIMIT 1");
        $stmt->bind_param("iii", $user_id, $project_id, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 1) {
            $updStmt = $conn->prepare("UPDATE projects SET title = ?, description = ?, department = ?, progress = ?, visibility = ?, status = ? WHERE id = ? AND creator_id = ?");
            $updStmt->bind_param("sssissii", $title, $description, $department, $progress, $visibility, $status, $project_id, $user_id);
            
            if ($updStmt->execute()) {
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
