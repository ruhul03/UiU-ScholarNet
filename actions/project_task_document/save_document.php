<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');
require_once(__DIR__ . '/../../includes/progress_helper.php');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../dashboard/document_editor.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

csrf_validate_or_die();

$user_id = (int)$_SESSION['user_id'];
$document_id = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$title = trim((string)($_POST['title'] ?? 'Untitled Document'));
$content = (string)($_POST['content'] ?? '');
$visibility = (string)($_POST['visibility'] ?? 'private');
if (!in_array($visibility, ['public', 'institution', 'private'], true)) {
    $visibility = 'private';
}

if ($project_id <= 0) {
    $_SESSION['error'] = "Please select a project.";
    header("Location: ../dashboard/document_editor.php");
    exit();
}

// Ensure project belongs to current user or user is an editor/owner
$pRes = db_query("
    SELECT p.id 
    FROM projects p 
    LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ? 
    WHERE p.id = ? AND (p.creator_id = ? OR pm.role IN ('owner', 'editor')) 
    LIMIT 1
", [$user_id, $project_id, $user_id], "iii");

if (!$pRes || $pRes->num_rows !== 1) {
    http_response_code(403);
    die("Forbidden");
}

if ($document_id > 0) {
    // Ensure document belongs to one of user's accessible projects
    $dRes = db_query("
        SELECT d.id
        FROM documents d
        JOIN projects p ON p.id = d.project_id
        LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
        WHERE d.id = ? AND (p.creator_id = ? OR pm.role IN ('owner', 'editor'))
        LIMIT 1
    ", [$user_id, $document_id, $user_id], "iii");

    if (!$dRes || $dRes->num_rows !== 1) {
        http_response_code(403);
        die("Forbidden");
    }

    // Update existing document
    db_query(
        "UPDATE documents SET project_id = ?, title = ?, content = ?, visibility = ?, last_edited_by = ? WHERE id = ?",
        [$project_id, $title, $content, $visibility, $user_id, $document_id],
        "isssii"
    );
} else {
    // Insert new document
    db_query(
        "INSERT INTO documents (project_id, title, content, visibility, created_by, last_edited_by) VALUES (?, ?, ?, ?, ?, ?)",
        [$project_id, $title, $content, $visibility, $user_id, $user_id],
        "isssii"
    );
    $document_id = (int)$conn->insert_id;
}

// Create a version snapshot for history
$vcount_res = db_query("SELECT COUNT(*) as c FROM document_versions WHERE document_id = ?", [$document_id], "i");
$vcount = ($vcount_res && $vcount_res->num_rows > 0) ? (int)$vcount_res->fetch_assoc()['c'] : 0;
$version_name = 'v' . ($vcount + 1) . '.0';

db_query(
    "INSERT INTO document_versions (document_id, version_name, content, created_by) VALUES (?, ?, ?, ?)",
    [$document_id, $version_name, $content, $user_id],
    "issi"
);

// Auto-update project progress since a document was edited
update_project_progress($conn, $project_id);

$_SESSION['success'] = "Document saved successfully.";
header("Location: ../dashboard/document_editor.php?document_id=" . urlencode((string)$document_id));
exit();

