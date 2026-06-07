<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$project_id = (int)($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';
$user_id = (int)$_SESSION['user_id'];

if (!hash_equals(csrf_token(), $token)) {
    $_SESSION['error'] = "Invalid security token.";
    header("Location: ../dashboard/edit_project.php?id=" . $project_id);
    exit();
}

if ($project_id <= 0) {
    header("Location: ../dashboard/projects.php");
    exit();
}

// 1. Verify the user is the supervisor and the project exists
$projectResult = db_query("SELECT id, status FROM projects WHERE id = ? AND supervisor_id = ?", [$project_id, $user_id], "ii");
$project = $projectResult ? $projectResult->fetch_assoc() : null;

// Early return if not found or unauthorized
if (!$project) {
    $_SESSION['error'] = "Unauthorized or project not found.";
    header("Location: ../dashboard/edit_project.php?id=" . $project_id);
    exit();
}

// 2. Early return if the project is not in the review stage
if ($project['status'] !== 'review') {
    $_SESSION['error'] = "Project must be in the REVIEW stage to approve.";
    header("Location: ../dashboard/edit_project.php?id=" . $project_id);
    exit();
}

// 3. Approve the project
$updateResult = db_query("UPDATE projects SET supervisor_approved = 1 WHERE id = ?", [$project_id], "i");

if ($updateResult) {
    $_SESSION['success'] = "Project officially approved. The team can now move it to COMPLETED.";
} else {
    $_SESSION['error'] = "Failed to approve the project.";
}

header("Location: ../dashboard/edit_project.php?id=" . $project_id);
exit();
?>
