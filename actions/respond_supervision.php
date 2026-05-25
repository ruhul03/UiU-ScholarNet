<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($project_id > 0 && in_array($action, ['accept', 'decline'])) {
    // Check if user is the assigned supervisor
    $stmt = $conn->prepare("SELECT title, creator_id FROM projects WHERE id = ? AND supervisor_id = ? AND supervisor_approved = 0");
    $stmt->bind_param("ii", $project_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 1) {
        $project = $res->fetch_assoc();
        
        if ($action === 'accept') {
            $upd = $conn->prepare("UPDATE projects SET supervisor_approved = 1 WHERE id = ?");
            $upd->bind_param("i", $project_id);
            if ($upd->execute()) {
                $_SESSION['success'] = "Project supervision approved.";
                
                // Notify creator
                $msg = "Your project '" . $project['title'] . "' has been approved by the supervisor.";
                $notif = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'system', 'Supervision Approved', ?, '../dashboard/projects.php')");
                $notif->bind_param("is", $project['creator_id'], $msg);
                $notif->execute();
            }
        } else if ($action === 'decline') {
            $upd = $conn->prepare("UPDATE projects SET supervisor_id = NULL WHERE id = ?");
            $upd->bind_param("i", $project_id);
            if ($upd->execute()) {
                $_SESSION['success'] = "Project supervision declined.";
                
                // Notify creator
                $msg = "Your project '" . $project['title'] . "' was declined by the selected supervisor. Please select a new supervisor.";
                $notif = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'system', 'Supervision Declined', ?, '../dashboard/projects.php')");
                $notif->bind_param("is", $project['creator_id'], $msg);
                $notif->execute();
            }
        }
    } else {
        $_SESSION['error'] = "Invalid project or you are not authorized to perform this action.";
    }
}

header("Location: ../dashboard/projects.php");
exit();
?>
