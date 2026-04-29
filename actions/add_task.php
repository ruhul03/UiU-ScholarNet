<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

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
    $assigned_to = (int)$_SESSION['user_id']; // Self-assign by default for now

    if ($title === '' || $project_id <= 0 || $due_date === '') {
        header("Location: ../dashboard/tasks.php?project_id=$project_id&error=1");
        exit();
    }

    $allowed_priority = ['low', 'medium', 'high'];
    if (!in_array($priority, $allowed_priority, true)) {
        $priority = 'medium';
    }

    // Ensure project belongs to current user (basic access control)
    $pstmt = $conn->prepare("SELECT id FROM projects WHERE id = ? AND creator_id = ? LIMIT 1");
    $pstmt->bind_param("ii", $project_id, $assigned_to);
    $pstmt->execute();
    $pRes = $pstmt->get_result();
    if (!$pRes || $pRes->num_rows !== 1) {
        header("Location: ../dashboard/tasks.php?error=1");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO tasks (project_id, title, description, assigned_to, priority, due_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $project_id, $title, $description, $assigned_to, $priority, $due_date);

    if ($stmt->execute()) {
        header("Location: ../dashboard/tasks.php?project_id=$project_id&success=1");
        exit();
    }

    header("Location: ../dashboard/tasks.php?project_id=$project_id&error=1");
    exit();
}
?>
