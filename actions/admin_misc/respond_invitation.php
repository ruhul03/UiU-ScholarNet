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
                $msg = "A user has declined your invitation to join the project: " . $proj['title'];
                db_query(
                    "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'system', 'Invitation Declined', ?, '../dashboard/projects.php')", 
                    [$proj['creator_id'], $msg], 
                    "is"
                );
            }
        } else {
            $_SESSION['error'] = "Invalid or expired invitation.";
        }
    }
}

header("Location: ../dashboard/projects.php");
exit();
?>
