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

    if ($document_id <= 0) {
        $_SESSION['error'] = "Invalid document.";
        header("Location: ../dashboard/document_editor.php");
        exit();
    }

    // Verify ownership and get document
    $stmt = $conn->prepare("
        SELECT d.*, p.title as project_title 
        FROM documents d
        JOIN projects p ON p.id = d.project_id
        LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
        WHERE d.id = ? AND (p.creator_id = ? OR pm.role IN ('owner', 'editor'))
        LIMIT 1
    ");
    $stmt->bind_param("iii", $user_id, $document_id, $user_id);
    $stmt->execute();
    $doc = $stmt->get_result()->fetch_assoc();

    if (!$doc || empty($doc['project_id'])) {
        $_SESSION['error'] = "Document not found or not linked to a project.";
        header("Location: ../dashboard/document_editor.php?document_id=" . $document_id);
        exit();
    }

    // Generate HTML file for preprint
    $upload_dir = '../uploads/preprints/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $filename = 'preprint_' . time() . '_' . rand(1000, 9999) . '.html';
    $filepath = $upload_dir . $filename;
    $db_filepath = 'uploads/preprints/' . $filename;

    $html_content = "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>" . htmlspecialchars($doc['title']) . "</title></head><body style='font-family: Arial, sans-serif; line-height: 1.6; padding: 2rem; max-width: 800px; margin: 0 auto;'>" . $doc['content'] . "</body></html>";
    
    file_put_contents($filepath, $html_content);

    // Extract abstract (first 500 chars of text content)
    $clean_text = strip_tags($doc['content']);
    $abstract = (strlen($clean_text) > 500) ? substr($clean_text, 0, 497) . '...' : $clean_text;
    if(empty(trim($abstract))) {
        $abstract = "No abstract provided.";
    }

    $keywords = "Project, " . $doc['project_title'];

    // Insert into preprints
    $insertStmt = $conn->prepare("
        INSERT INTO preprints (title, abstract, keywords, file_path, author_id, project_id, visibility) 
        VALUES (?, ?, ?, ?, ?, ?, 'public')
    ");
    $insertStmt->bind_param("ssssii", $doc['title'], $abstract, $keywords, $db_filepath, $user_id, $doc['project_id']);
    
    if ($insertStmt->execute()) {
        $new_preprint_id = $conn->insert_id;
        $_SESSION['success'] = "Document successfully published as a preprint!";
        
        // Award points for publishing
        $ptsStmt = $conn->prepare("UPDATE users SET points = points + 100 WHERE id = ?");
        $ptsStmt->bind_param("i", $user_id);
        $ptsStmt->execute();

        header("Location: ../dashboard/preprint_details.php?id=" . $new_preprint_id);
        exit();
    } else {
        $_SESSION['error'] = "Failed to publish preprint.";
        header("Location: ../dashboard/document_editor.php?document_id=" . $document_id);
        exit();
    }
}
