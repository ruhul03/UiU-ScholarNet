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

    $current_user_id = (int)$_SESSION['user_id'];
    $application_id = (int)($_POST['application_id'] ?? 0);
    $action_type = (string)($_POST['action_type'] ?? '');

    // Early return for invalid action
    if ($application_id <= 0 || !in_array($action_type, ['accept', 'decline'], true)) {
        $_SESSION['error'] = "Invalid action.";
        header("Location: ../dashboard/collaboration.php");
        exit();
    }

    // 1. Verify ownership of the post
    $verifyOwnershipQuery = "
        SELECT ca.id, cp.user_id, cp.id AS post_id, cp.project_id, ca.user_id AS applicant_id, ca.status 
        FROM collaboration_applications ca
        JOIN collaboration_posts cp ON ca.post_id = cp.id
        WHERE ca.id = ? AND cp.user_id = ?
    ";
    
    $verifyResult = db_query($verifyOwnershipQuery, [$application_id, $current_user_id], "ii");
    $applicationData = $verifyResult ? $verifyResult->fetch_assoc() : null;

    // Early return if not authorized or application not found
    if (!$applicationData) {
        $_SESSION['error'] = "Unauthorized access or application not found.";
        header("Location: ../dashboard/collaboration.php");
        exit();
    }

    $new_status = ($action_type === 'accept') ? 'accepted' : 'declined';
    
    // 2. Update status
    $updateStatusQuery = "UPDATE collaboration_applications SET status = ? WHERE id = ?";
    $updateResult = db_query($updateStatusQuery, [$new_status, $application_id], "si");
    
    if ($updateResult) {
        $_SESSION['success'] = "Application " . $new_status . " successfully.";
        
        // 3. Auto-add to project if accepted and project linked
        if ($new_status === 'accepted' && !empty($applicationData['project_id'])) {
            $addMemberQuery = "INSERT IGNORE INTO project_members (project_id, user_id, role) VALUES (?, ?, 'member')";
            db_query($addMemberQuery, [$applicationData['project_id'], $applicationData['applicant_id']], "ii");
        }
    } else {
        $_SESSION['error'] = "Failed to update application.";
    }

    header("Location: ../dashboard/manage_collaboration.php?id=" . $applicationData['post_id']);
    exit();
}
