<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

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

if ($version_id <= 0) {
    $_SESSION['error'] = "Invalid version ID.";
    header("Location: ../dashboard/projects.php");
    exit();
}

// 1. Fetch version and document info
$vRes = db_query("SELECT v.*, d.project_id FROM document_versions v JOIN documents d ON d.id = v.document_id WHERE v.id = ?", [$version_id], "i");
if (!$vRes || $vRes->num_rows === 0) {
    $_SESSION['error'] = "Version not found.";
    header("Location: ../dashboard/projects.php");
    exit();
}

$version = $vRes->fetch_assoc();
$document_id = $version['document_id'];
$project_id = $version['project_id'];

// 2. Permission check (Must be owner or editor)
$projectPermissionQuery = "
    SELECT p.id 
    FROM projects p 
    LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ? 
    WHERE p.id = ? AND (p.creator_id = ? OR pm.role IN ('owner', 'editor')) 
    LIMIT 1
";
$projectPermissionResult = db_query($projectPermissionQuery, [$user_id, $project_id, $user_id], "iii");

if (!$projectPermissionResult || $projectPermissionResult->num_rows !== 1) {
    http_response_code(403);
    die("Forbidden");
}

// 3. Check locks
$docRes = db_query("SELECT locked_by, locked_at FROM documents WHERE id = ?", [$document_id], "i");
if ($docRes && $doc = $docRes->fetch_assoc()) {
    $locked_by = $doc['locked_by'];
    $locked_at = $doc['locked_at'];
    if ($locked_by && $locked_by != $user_id) {
        $lock_time = strtotime($locked_at);
        if (time() - $lock_time < 300) {
            $_SESSION['error'] = "Document is currently locked by another user. Cannot restore version.";
            header("Location: ../dashboard/document_editor.php?document_id=" . $document_id);
            exit();
        }
    }
}

// 4. Update the current document
db_query(
    "UPDATE documents SET content = ?, last_edited_by = ? WHERE id = ?",
    [$version['content'], $user_id, $document_id],
    "sii"
);

// 5. Create new version entry based on restored content
$vcount_res = db_query("SELECT COUNT(*) as c FROM document_versions WHERE document_id = ?", [$document_id], "i");
$vcount = ($vcount_res && $vcount_res->num_rows > 0) ? (int)$vcount_res->fetch_assoc()['c'] : 0;
$version_name = 'v' . ($vcount + 1) . '.0';
$commit_msg = "Restored from " . $version['version_name'];

db_query(
    "INSERT INTO document_versions (document_id, version_name, content, created_by, commit_message) VALUES (?, ?, ?, ?, ?)",
    [$document_id, $version_name, $version['content'], $user_id, $commit_msg],
    "issis"
);

$_SESSION['success'] = "Document restored to " . htmlspecialchars($version['version_name']) . ".";
header("Location: ../dashboard/document_editor.php?document_id=" . $document_id);
exit();
