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

    $user_id = (int)$_SESSION['user_id'];
    $document_id = (int)($_POST['document_id'] ?? 0);

    if ($document_id > 0) {
        // Ensure user has permission to delete (must be project creator, owner, or document creator)
        $stmt = $conn->prepare("
            SELECT d.id 
            FROM documents d
            JOIN projects p ON p.id = d.project_id
            LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
            WHERE d.id = ? AND (p.creator_id = ? OR pm.role = 'owner')
            LIMIT 1
        ");
        $stmt->bind_param("iii", $user_id, $document_id, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $del = $conn->prepare("DELETE FROM documents WHERE id = ?");
            $del->bind_param("i", $document_id);
            if ($del->execute()) {
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
