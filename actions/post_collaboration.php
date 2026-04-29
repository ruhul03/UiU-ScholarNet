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
    $department = trim((string)($_POST['department'] ?? ''));
    $skills = trim((string)($_POST['skills'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));

    if ($title === '' || $department === '') {
        header("Location: ../dashboard/collaboration.php?error=1");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO collaboration_posts (user_id, title, department, description, skills_required) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $title, $department, $description, $skills);

    if ($stmt->execute()) {
        header("Location: ../dashboard/collaboration.php?success=1");
    } else {
        header("Location: ../dashboard/collaboration.php?error=1");
    }
    exit();
}
?>
