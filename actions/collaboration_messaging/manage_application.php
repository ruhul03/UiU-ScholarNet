<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_validate_or_die();

    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    $owner_id = (int)$_SESSION['user_id'];
    $application_id = (int)($_POST['application_id'] ?? 0);
    $action_type = (string)($_POST['action_type'] ?? '');

    if ($application_id <= 0 || !in_array($action_type, ['accept', 'decline'], true)) {
        $_SESSION['error'] = "Invalid action.";
        header("Location: ../dashboard/collaboration.php");
        exit();
    }

    // Verify ownership
    $verifyRes = db_query("
        SELECT ca.id, cp.user_id, cp.id AS post_id, cp.project_id, ca.user_id AS applicant_id, ca.status 
        FROM collaboration_applications ca
        JOIN collaboration_posts cp ON ca.post_id = cp.id
        WHERE ca.id = ? AND cp.user_id = ?
    ", [$application_id, $owner_id], "ii");
    $appData = $verifyRes ? $verifyRes->fetch_assoc() : null;

    if (!$appData) {
        $_SESSION['error'] = "Unauthorized access or application not found.";
        header("Location: ../dashboard/collaboration.php");
        exit();
    }

    $new_status = ($action_type === 'accept') ? 'accepted' : 'declined';
    
    // Update status
    $update = db_query("UPDATE collaboration_applications SET status = ? WHERE id = ?", [$new_status, $application_id], "si");
    
    if ($update) {
        $_SESSION['success'] = "Application " . $new_status . " successfully.";
        
        // Auto-add to project if accepted and project linked
        if ($new_status === 'accepted' && !empty($appData['project_id'])) {
            db_query("INSERT IGNORE INTO project_members (project_id, user_id, role) VALUES (?, ?, 'member')", [$appData['project_id'], $appData['applicant_id']], "ii");
        }
    } else {
        $_SESSION['error'] = "Failed to update application.";
    }

    header("Location: ../dashboard/manage_collaboration.php?id=" . $appData['post_id']);
    exit();
}
