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
    if ($action === 'accept') {
        // Accept invitation by updating status to active
        db_query(
            "UPDATE project_members SET status = 'active' WHERE project_id = ? AND user_id = ? AND status = 'pending'", 
            [$project_id, $user_id], 
            "ii"
        );
        if ($conn->affected_rows > 0) {
            $_SESSION['success'] = "Invitation accepted successfully.";
            
            // Notify creator
            $p_res = db_query("SELECT title, creator_id FROM projects WHERE id = ?", [$project_id], "i");
            if ($p_res && $proj = $p_res->fetch_assoc()) {
                $userRes = db_query("SELECT full_name FROM users WHERE id = ?", [$user_id], "i")->fetch_assoc();
                $userName = $userRes['full_name'] ?? 'A user';
                $msg = "{$userName} has accepted your invitation to join the project: " . $proj['title'];
                send_notification($proj['creator_id'], "Invitation Accepted", $msg, "../dashboard/projects.php", "system");
            }
        } else {
            $_SESSION['error'] = "Invalid or expired invitation.";
        }
    } else if ($action === 'decline') {
        // Fetch project info to notify creator
        $p_res = db_query("SELECT title, creator_id FROM projects WHERE id = ?", [$project_id], "i");
        $proj = $p_res ? $p_res->fetch_assoc() : null;

        // Decline invitation by deleting the pending membership
        db_query(
            "DELETE FROM project_members WHERE project_id = ? AND user_id = ? AND status = 'pending'", 
            [$project_id, $user_id], 
            "ii"
        );
        if ($conn->affected_rows > 0) {
            $_SESSION['success'] = "Invitation declined.";
            
            if ($proj) {
                $userRes = db_query("SELECT full_name FROM users WHERE id = ?", [$user_id], "i")->fetch_assoc();
                $userName = $userRes['full_name'] ?? 'A user';
                $msg = "{$userName} has declined your invitation to join the project: " . $proj['title'];
                send_notification($proj['creator_id'], "Invitation Declined", $msg, "../dashboard/projects.php", "system");
            }
        } else {
            $_SESSION['error'] = "Invalid or expired invitation.";
        }
    }
}

header("Location: ../dashboard/projects.php");
exit();
?>
