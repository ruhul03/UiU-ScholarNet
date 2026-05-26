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

    if ($document_id > 0) {
        // Check if user has permission to delete (must be project creator or an owner)
        $checkRes = db_query("
            SELECT d.id 
            FROM documents d
            JOIN projects p ON p.id = d.project_id
            LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
            WHERE d.id = ? AND (p.creator_id = ? OR pm.role = 'owner')
            LIMIT 1
        ", [$user_id, $document_id, $user_id], "iii");
        
        if ($checkRes && $checkRes->num_rows > 0) {
            // Delete the document
            $del = db_query("DELETE FROM documents WHERE id = ?", [$document_id], "i");
            if ($del) {
                $_SESSION['success'] = "Document deleted successfully.";
            } else {
                $_SESSION['error'] = "Failed to delete document.";
            }
        } else {
            $_SESSION['error'] = "You do not have permission to delete this document.";
        }
    }
    
    header("Location: ../dashboard/documents.php");
    exit();
}
