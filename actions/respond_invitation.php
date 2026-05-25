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
    if ($action === 'accept') {
        $stmt = $conn->prepare("UPDATE project_members SET status = 'active' WHERE project_id = ? AND user_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $project_id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['success'] = "Invitation accepted successfully.";
        } else {
            $_SESSION['error'] = "Invalid or expired invitation.";
        }
    } else if ($action === 'decline') {
        // Fetch project info to notify creator
        $p_stmt = $conn->prepare("SELECT title, creator_id FROM projects WHERE id = ?");
        $p_stmt->bind_param("i", $project_id);
        $p_stmt->execute();
        $proj = $p_stmt->get_result()->fetch_assoc();

        $stmt = $conn->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ? AND status = 'pending'");
        $stmt->bind_param("ii", $project_id, $user_id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $_SESSION['success'] = "Invitation declined.";
            
            if ($proj) {
                $msg = "A user has declined your invitation to join the project: " . $proj['title'];
                $notif = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'system', 'Invitation Declined', ?, '../dashboard/projects.php')");
                $notif->bind_param("is", $proj['creator_id'], $msg);
                $notif->execute();
            }
        } else {
            $_SESSION['error'] = "Invalid or expired invitation.";
        }
    }
}

header("Location: ../dashboard/projects.php");
exit();
?>
