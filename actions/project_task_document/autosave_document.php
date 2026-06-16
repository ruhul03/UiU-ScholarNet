<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$document_id = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
$content = $_POST['content'] ?? '';

if ($document_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid document ID.']);
    exit();
}

// Ensure the user has permission to view/edit this document
$documentPermissionQuery = "
    SELECT d.locked_by, d.locked_at, p.id as project_id, p.creator_id
    FROM documents d
    JOIN projects p ON p.id = d.project_id
    LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
    WHERE d.id = ? AND (p.creator_id = ? OR pm.role IN ('owner', 'editor'))
    LIMIT 1
";
$documentPermissionResult = db_query($documentPermissionQuery, [$user_id, $document_id, $user_id], "iii");

if (!$documentPermissionResult || $documentPermissionResult->num_rows !== 1) {
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit();
}

$doc = $documentPermissionResult->fetch_assoc();

// Check if locked by someone else
$locked_by = $doc['locked_by'];
$locked_at = $doc['locked_at'];

if ($locked_by && $locked_by != $user_id) {
    $lock_time = strtotime($locked_at);
    if (time() - $lock_time < 300) {
        echo json_encode(['success' => false, 'error' => 'Document is locked by another user.']);
        exit();
    }
}

// Auto-save updates the main document only, does not create a version log entry.
db_query(
    "UPDATE documents SET content = ?, last_edited_by = ?, locked_by = ?, locked_at = NOW() WHERE id = ?",
    [$content, $user_id, $user_id, $document_id],
    "siii"
);

echo json_encode(['success' => true]);
exit();
