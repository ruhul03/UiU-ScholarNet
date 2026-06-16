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
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

if ($project_id <= 0) {
    $_SESSION['error'] = "Invalid project ID.";
    header("Location: ../../dashboard/projects.php");
    exit();
}

// Ensure the user is the assigned supervisor
$checkQuery = "SELECT id, title, creator_id FROM projects WHERE id = ? AND supervisor_id = ? AND status = 'planning' LIMIT 1";
$checkResult = db_query($checkQuery, [$project_id, $user_id], "ii");

if (!$checkResult || $checkResult->num_rows === 0) {
    $_SESSION['error'] = "You do not have permission to approve this proposal, or it is not in planning state.";
    header("Location: ../../dashboard/projects.php");
    exit();
}

$project = $checkResult->fetch_assoc();

// Update status to 'active'
$updateResult = db_query("UPDATE projects SET status = 'active' WHERE id = ?", [$project_id], "i");

if ($updateResult) {
    // Notify the creator
    $msg = "Your project proposal for '" . $project['title'] . "' has been approved by the supervisor. It is now active.";
    send_notification($project['creator_id'], "Proposal Approved", $msg, "../dashboard/edit_project.php?id=" . $project_id, "system");
    
    $_SESSION['success'] = "Proposal approved. Project is now active.";
} else {
    $_SESSION['error'] = "Database error. Could not approve proposal.";
}

header("Location: ../../dashboard/edit_project.php?id=" . $project_id);
exit();
?>
