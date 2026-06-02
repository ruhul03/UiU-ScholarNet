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

    if ($document_id <= 0) {
        $_SESSION['error'] = "Invalid document.";
        header("Location: ../dashboard/document_editor.php");
        exit();
    }

    // Verify ownership and get document
    $docRes = db_query("
        SELECT d.*, p.title as project_title 
        FROM documents d
        JOIN projects p ON p.id = d.project_id
        LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
        WHERE d.id = ? AND (p.creator_id = ? OR pm.role IN ('owner', 'editor'))
        LIMIT 1
    ", [$user_id, $document_id, $user_id], "iii");
    $doc = $docRes ? $docRes->fetch_assoc() : null;

    if (!$doc || empty($doc['project_id'])) {
        $_SESSION['error'] = "Document not found or not linked to a project.";
        header("Location: ../dashboard/document_editor.php?document_id=" . $document_id);
        exit();
    }

    // Generate HTML file for preprint
    $upload_dir = __DIR__ . '/../../uploads/preprints/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $filename = 'preprint_' . time() . '_' . rand(1000, 9999) . '.html';
    $filepath = $upload_dir . $filename;
    $db_filepath = 'uploads/preprints/' . $filename;

    // Use head stylesheet instead of inline styles for maximum compliance and clean generated HTML
    $html_content = "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>" . htmlspecialchars($doc['title']) . "</title><style>body { font-family: Arial, sans-serif; line-height: 1.6; padding: 2rem; max-width: 800px; margin: 0 auto; }</style></head><body>" . $doc['content'] . "</body></html>";
    
    file_put_contents($filepath, $html_content);

    // Extract abstract (first 500 chars of text content)
    $clean_text = strip_tags($doc['content']);
    $abstract = (strlen($clean_text) > 500) ? substr($clean_text, 0, 497) . '...' : $clean_text;
    if(empty(trim($abstract))) {
        $abstract = "No abstract provided.";
    }

    $keywords = "Project, " . $doc['project_title'];

    // Insert preprint metadata into the database
    $insert = db_query("
        INSERT INTO preprints (title, abstract, keywords, file_path, author_id, project_id, visibility) 
        VALUES (?, ?, ?, ?, ?, ?, 'public')
    ", [$doc['title'], $abstract, $keywords, $db_filepath, $user_id, $doc['project_id']], "ssssii");
    
    if ($insert) {
        $new_preprint_id = $conn->insert_id;
        $_SESSION['success'] = "Document successfully published as a preprint!";
        
        // Award points for publishing
        db_query("UPDATE users SET points = points + 100 WHERE id = ?", [$user_id], "i");

        header("Location: ../dashboard/preprint_details.php?id=" . $new_preprint_id);
        exit();
    } else {
        $_SESSION['error'] = "Failed to publish preprint.";
        header("Location: ../dashboard/document_editor.php?document_id=" . $document_id);
        exit();
    }
}
