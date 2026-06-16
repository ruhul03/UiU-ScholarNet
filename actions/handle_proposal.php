<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../dashboard/projects.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

csrf_validate_or_die();

$user_id = (int)$_SESSION['user_id'];
$version_id = isset($_POST['version_id']) ? (int)$_POST['version_id'] : 0;
$action_type = isset($_POST['action_type']) ? $_POST['action_type'] : '';

if ($version_id <= 0 || !in_array($action_type, ['accept', 'reject'])) {
    header("Location: ../dashboard/projects.php");
    exit();
}

// Ensure the user is the project creator or supervisor for the document this version belongs to
$permissionQuery = "
    SELECT v.id, v.document_id, v.content, d.project_id
    FROM document_versions v
    JOIN documents d ON v.document_id = d.id
    JOIN projects p ON d.project_id = p.id
    WHERE v.id = ? AND v.status = 'pending' AND (p.creator_id = ? OR p.supervisor_id = ?)
    LIMIT 1
";
$permRes = db_query($permissionQuery, [$version_id, $user_id, $user_id], "iii");

if (!$permRes || $permRes->num_rows !== 1) {
    $_SESSION['error'] = "You do not have permission to handle this proposal, or it is no longer pending.";
    header("Location: ../dashboard/projects.php");
    exit();
}

$versionData = $permRes->fetch_assoc();
$document_id = $versionData['document_id'];

if ($action_type === 'accept') {
    // 1. Update document_versions status to approved
    db_query("UPDATE document_versions SET status = 'approved' WHERE id = ?", [$version_id], "i");
    
    // 2. Update documents table with new content
    db_query("UPDATE documents SET content = ?, last_edited_by = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", 
        [$versionData['content'], $user_id, $document_id], "sii");
        
    $_SESSION['success'] = "Proposal accepted and merged into the document.";
} else {
    // 1. Update document_versions status to rejected
    db_query("UPDATE document_versions SET status = 'rejected' WHERE id = ?", [$version_id], "i");
    $_SESSION['success'] = "Proposal rejected.";
}

header("Location: ../dashboard/document_editor.php?document_id=" . $document_id);
exit();
?>
