<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

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
    $verifyStmt = $conn->prepare("
        SELECT ca.id, cp.user_id, cp.id AS post_id, cp.project_id, ca.user_id AS applicant_id, ca.status 
        FROM collaboration_applications ca
        JOIN collaboration_posts cp ON ca.post_id = cp.id
        WHERE ca.id = ? AND cp.user_id = ?
    ");
    $verifyStmt->bind_param("ii", $application_id, $owner_id);
    $verifyStmt->execute();
    $appData = $verifyStmt->get_result()->fetch_assoc();

    if (!$appData) {
        $_SESSION['error'] = "Unauthorized access or application not found.";
        header("Location: ../dashboard/collaboration.php");
        exit();
    }

    $new_status = ($action_type === 'accept') ? 'accepted' : 'declined';
    
    // Update status
    $updateStmt = $conn->prepare("UPDATE collaboration_applications SET status = ? WHERE id = ?");
    $updateStmt->bind_param("si", $new_status, $application_id);
    
    if ($updateStmt->execute()) {
        $_SESSION['success'] = "Application " . $new_status . " successfully.";
        
        // Auto-add to project if accepted and project linked
        if ($new_status === 'accepted' && !empty($appData['project_id'])) {
            $insertMember = $conn->prepare("INSERT IGNORE INTO project_members (project_id, user_id, role) VALUES (?, ?, 'member')");
            $insertMember->bind_param("ii", $appData['project_id'], $appData['applicant_id']);
            $insertMember->execute();
        }
    } else {
        $_SESSION['error'] = "Failed to update application.";
    }

    header("Location: ../dashboard/manage_collaboration.php?id=" . $appData['post_id']);
    exit();
}
