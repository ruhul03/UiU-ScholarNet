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

    $user_id = (int)$_SESSION['user_id'];
    $document_id = (int)($_POST['document_id'] ?? 0);

    // Early return if no valid document ID
    if ($document_id <= 0) {
        header("Location: ../dashboard/documents.php");
        exit();
    }

    // 1. Check if user has permission to delete (must be project creator or an owner)
    $permissionCheckQuery = "
        SELECT d.id 
        FROM documents d
        JOIN projects p ON p.id = d.project_id
        LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
        WHERE d.id = ? AND (p.creator_id = ? OR pm.role = 'owner')
        LIMIT 1
    ";
    
    $permissionCheckResult = db_query($permissionCheckQuery, [$user_id, $document_id, $user_id], "iii");
    
    // Early return if not authorized
    if (!$permissionCheckResult || $permissionCheckResult->num_rows === 0) {
        $_SESSION['error'] = "You do not have permission to delete this document.";
        header("Location: ../dashboard/documents.php");
        exit();
    }
    
    // 2. Delete the document
    $deleteResult = db_query("DELETE FROM documents WHERE id = ?", [$document_id], "i");
    
    if ($deleteResult) {
        $_SESSION['success'] = "Document deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete document.";
    }
    
    header("Location: ../dashboard/documents.php");
    exit();
}
