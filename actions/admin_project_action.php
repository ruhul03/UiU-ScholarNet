<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_validate_or_die();

    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Admin check
    $adminCheck = db_query("SELECT role FROM users WHERE id = ?", [$user_id], "i")->fetch_assoc();
    if (!$adminCheck || $adminCheck['role'] !== 'admin') {
        $_SESSION['error'] = "Unauthorized action.";
        header("Location: ../dashboard/index.php");
        exit();
    }

    $project_id = (int)$_POST['project_id'];
    $action_type = $_POST['action_type'];

    if ($action_type === 'delete') {
        // Delete project (cascading deletes will handle tasks, documents, etc. if DB is configured properly,
        // otherwise we should delete them manually. For MVP, we delete the project and its members.)
        db_query("DELETE FROM projects WHERE id = ?", [$project_id], "i");
        db_query("DELETE FROM project_members WHERE project_id = ?", [$project_id], "i");
        $_SESSION['success'] = "Project globally deleted.";
    }

    header("Location: ../dashboard/admin.php#admin-projects");
    exit();
}

header("Location: ../dashboard/admin.php");
exit();
