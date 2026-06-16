<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../../dashboard/projects.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit();
}

csrf_validate_or_die();

$user_id = (int)$_SESSION['user_id'];
$task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

if ($task_id <= 0 || $project_id <= 0) {
    $_SESSION['error'] = "Invalid parameters.";
    header("Location: ../../dashboard/projects.php");
    exit();
}

// Ensure the user is the assigned supervisor
$checkQuery = "SELECT id, title, creator_id FROM projects WHERE id = ? AND supervisor_id = ? LIMIT 1";
$checkResult = db_query($checkQuery, [$project_id, $user_id], "ii");

if (!$checkResult || $checkResult->num_rows === 0) {
    $_SESSION['error'] = "You do not have permission to sign off on milestones for this project.";
    header("Location: ../../dashboard/edit_project.php?id=" . $project_id);
    exit();
}

$project = $checkResult->fetch_assoc();

// Update milestone task
$updateResult = db_query("UPDATE tasks SET supervisor_signed_off = 1, status = 'done' WHERE id = ? AND project_id = ? AND is_milestone = 1", [$task_id, $project_id], "ii");

if ($updateResult && $conn->affected_rows > 0) {
    // Notify the creator
    $msg = "A milestone in your project '" . $project['title'] . "' has been formally signed off by the supervisor.";
    send_notification($project['creator_id'], "Milestone Signed Off", $msg, "../dashboard/edit_project.php?id=" . $project_id, "system");
    
    $_SESSION['success'] = "Milestone successfully signed off.";
} else {
    $_SESSION['error'] = "Could not sign off milestone. It may not exist or is already signed off.";
}

header("Location: ../../dashboard/edit_project.php?id=" . $project_id);
exit();
?>
