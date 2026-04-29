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

    $user_id = (int)$_SESSION['user_id'];
    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $department = trim((string)($_POST['department'] ?? ''));
    $visibility = (string)($_POST['visibility'] ?? 'public');
    $status = (string)($_POST['status'] ?? 'planning');
    $progress = isset($_POST['progress']) ? (int)$_POST['progress'] : 0;
    if ($progress < 0) $progress = 0;
    if ($progress > 100) $progress = 100;

    if ($title === '' || $department === '') {
        $_SESSION['error'] = "Project title and department are required.";
        header("Location: ../dashboard/projects.php");
        exit();
    }

    $allowed_visibility = ['public', 'institution', 'private'];
    if (!in_array($visibility, $allowed_visibility, true)) {
        $visibility = 'public';
    }
    $allowed_status = ['planning', 'active', 'review', 'completed'];
    if (!in_array($status, $allowed_status, true)) {
        $status = 'planning';
    }

    $stmt = $conn->prepare("INSERT INTO projects (title, description, department, visibility, status, progress, creator_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssii", $title, $description, $department, $visibility, $status, $progress, $user_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Project created successfully!";
        header("Location: ../dashboard/projects.php");
    } else {
        $_SESSION['error'] = "Failed to create project.";
        header("Location: ../dashboard/projects.php");
    }
    exit();
}
?>
