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

// Ensure project belongs to current user or user is an editor/owner
$pstmt = $conn->prepare("
    SELECT p.id 
    FROM projects p 
    LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ? 
    WHERE p.id = ? AND (p.creator_id = ? OR pm.role IN ('owner', 'editor')) 
    LIMIT 1
");
$pstmt->bind_param("iii", $user_id, $project_id, $user_id);
$pstmt->execute();
$pRes = $pstmt->get_result();
if (!$pRes || $pRes->num_rows !== 1) {
    http_response_code(403);
    die("Forbidden");
}

if ($document_id > 0) {
    // Ensure document belongs to one of user's accessible projects
    $dcheck = $conn->prepare("
        SELECT d.id
        FROM documents d
        JOIN projects p ON p.id = d.project_id
        LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
        WHERE d.id = ? AND (p.creator_id = ? OR pm.role IN ('owner', 'editor'))
        LIMIT 1
    ");
    $dcheck->bind_param("iii", $user_id, $document_id, $user_id);
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

// Create a version snapshot
$vcount_stmt = $conn->prepare("SELECT COUNT(*) as c FROM document_versions WHERE document_id = ?");
$vcount_stmt->bind_param("i", $document_id);
$vcount_stmt->execute();
$vcount_res = $vcount_stmt->get_result();
$vcount = $vcount_res ? (int)$vcount_res->fetch_assoc()['c'] : 0;
$version_name = 'v' . ($vcount + 1) . '.0';

$v_ins = $conn->prepare("INSERT INTO document_versions (document_id, version_name, content, created_by) VALUES (?, ?, ?, ?)");
$v_ins->bind_param("issi", $document_id, $version_name, $content, $user_id);
$v_ins->execute();

$_SESSION['success'] = "Document saved successfully.";
header("Location: ../dashboard/document_editor.php?document_id=" . urlencode((string)$document_id));
exit();

