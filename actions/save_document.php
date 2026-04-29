<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

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

// Ensure project belongs to current user
$pstmt = $conn->prepare("SELECT id FROM projects WHERE id = ? AND creator_id = ? LIMIT 1");
$pstmt->bind_param("ii", $project_id, $user_id);
$pstmt->execute();
$pRes = $pstmt->get_result();
if (!$pRes || $pRes->num_rows !== 1) {
    http_response_code(403);
    die("Forbidden");
}

if ($document_id > 0) {
    // Ensure document belongs to one of user's projects
    $dcheck = $conn->prepare("
        SELECT d.id
        FROM documents d
        JOIN projects p ON p.id = d.project_id
        WHERE d.id = ? AND p.creator_id = ?
        LIMIT 1
    ");
    $dcheck->bind_param("ii", $document_id, $user_id);
    $dcheck->execute();
    $dRes = $dcheck->get_result();
    if (!$dRes || $dRes->num_rows !== 1) {
        http_response_code(403);
        die("Forbidden");
    }

    $upd = $conn->prepare("UPDATE documents SET project_id = ?, title = ?, content = ?, visibility = ?, last_edited_by = ? WHERE id = ?");
    $upd->bind_param("isssii", $project_id, $title, $content, $visibility, $user_id, $document_id);
    $upd->execute();
} else {
    $ins = $conn->prepare("INSERT INTO documents (project_id, title, content, visibility, created_by, last_edited_by) VALUES (?, ?, ?, ?, ?, ?)");
    $ins->bind_param("isssii", $project_id, $title, $content, $visibility, $user_id, $user_id);
    $ins->execute();
    $document_id = (int)$conn->insert_id;
}

$_SESSION['success'] = "Document saved.";
header("Location: ../dashboard/document_editor.php?document_id=" . urlencode((string)$document_id));
exit();

