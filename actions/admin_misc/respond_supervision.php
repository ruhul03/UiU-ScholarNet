<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($project_id > 0 && in_array($action, ['accept', 'decline'])) {
    // Check if user is the assigned supervisor
    $res = db_query("SELECT title, creator_id FROM projects WHERE id = ? AND supervisor_id = ? AND supervisor_approved = 0", [$project_id, $user_id], "ii");
    
    if ($res && $res->num_rows === 1) {
        $project = $res->fetch_assoc();
        
        if ($action === 'accept') {
            $upd = db_query("UPDATE projects SET supervisor_approved = 1 WHERE id = ?", [$project_id], "i");
            if ($upd) {
                $_SESSION['success'] = "Project supervision approved.";
                
                // Notify creator
                $msg = "Your project '" . $project['title'] . "' has been approved by the supervisor.";
                db_query(
                    "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'system', 'Supervision Approved', ?, '../dashboard/projects.php')", 
                    [$project['creator_id'], $msg], 
                    "is"
                );
            }
        } else if ($action === 'decline') {
            $upd = db_query("UPDATE projects SET supervisor_id = NULL WHERE id = ?", [$project_id], "i");
            if ($upd) {
                $_SESSION['success'] = "Project supervision declined.";
                
                // Notify creator
                $msg = "Your project '" . $project['title'] . "' was declined by the selected supervisor. Please select a new supervisor.";
                db_query(
                    "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'system', 'Supervision Declined', ?, '../dashboard/projects.php')", 
                    [$project['creator_id'], $msg], 
                    "is"
                );
            }
        }
    } else {
        $_SESSION['error'] = "Invalid project or you are not authorized to perform this action.";
    }
}

header("Location: ../dashboard/projects.php");
exit();
?>
