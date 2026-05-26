<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');
require_once(__DIR__ . '/../../includes/progress_helper.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    csrf_validate_or_die();

    $title = trim((string)($_POST['title'] ?? ''));
    $project_id = (int)($_POST['project_id'] ?? 0);
    $priority = (string)($_POST['priority'] ?? 'medium');
    $due_date = (string)($_POST['due_date'] ?? '');
    $description = trim((string)($_POST['description'] ?? ''));
    $current_user = (int)$_SESSION['user_id'];
    $assigned_to = isset($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : $current_user;

    if ($title === '' || $project_id <= 0 || $due_date === '') {
        header("Location: ../dashboard/tasks.php?project_id=$project_id&error=1");
        exit();
    }

    $allowed_priority = ['low', 'medium', 'high'];
    if (!in_array($priority, $allowed_priority, true)) {
        $priority = 'medium';
    }

    // Ensure project belongs to current user or they are an editor
    $pRes = db_query("SELECT p.id FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? WHERE p.id = ? AND (p.creator_id = ? OR pm.role IN ('owner', 'editor')) LIMIT 1", [$current_user, $project_id, $current_user], "iii");
    if (!$pRes || $pRes->num_rows !== 1) {
        header("Location: ../dashboard/tasks.php?error=1");
        exit();
    }

    // Insert new task into database
    $insert = db_query(
        "INSERT INTO tasks (project_id, title, description, assigned_to, priority, due_date) VALUES (?, ?, ?, ?, ?, ?)",
        [$project_id, $title, $description, $assigned_to, $priority, $due_date],
        "ississ"
    );

    if ($insert) {
        // Automatically update project progress
        update_project_progress($conn, $project_id);
        header("Location: ../dashboard/tasks.php?project_id=$project_id&success=1");
        exit();
    }

    header("Location: ../dashboard/tasks.php?project_id=$project_id&error=1");
    exit();
}
?>
