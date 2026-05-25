<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');
require_once('../includes/progress_helper.php');

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

    // Ensure project belongs to current user (basic access control)
    $pstmt = $conn->prepare("SELECT p.id FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? WHERE p.id = ? AND (p.creator_id = ? OR pm.role IN ('owner', 'editor')) LIMIT 1");
    $pstmt->bind_param("iii", $current_user, $project_id, $current_user);
    $pstmt->execute();
    $pRes = $pstmt->get_result();
    if (!$pRes || $pRes->num_rows !== 1) {
        header("Location: ../dashboard/tasks.php?error=1");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO tasks (project_id, title, description, assigned_to, priority, due_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $project_id, $title, $description, $assigned_to, $priority, $due_date);

    if ($stmt->execute()) {
        update_project_progress($conn, $project_id);
        header("Location: ../dashboard/tasks.php?project_id=$project_id&success=1");
        exit();
    }

    header("Location: ../dashboard/tasks.php?project_id=$project_id&error=1");
    exit();
}
?>
