<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

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
$action = $_POST['action'] ?? 'acquire';

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

if ($action === 'release') {
    if ($doc['locked_by'] == $user_id) {
        db_query("UPDATE documents SET locked_by = NULL, locked_at = NULL WHERE id = ?", [$document_id], "i");
        echo json_encode(['success' => true]);
        exit();
    }
    echo json_encode(['success' => false, 'error' => 'Not locked by you.']);
    exit();
}

if ($action === 'acquire' || $action === 'renew') {
    // Check if locked by someone else
    $locked_by = $doc['locked_by'];
    $locked_at = $doc['locked_at'];

    // Lock expires after 5 minutes
    $is_locked = false;
    $locked_by_name = null;
    
    if ($locked_by && $locked_by != $user_id) {
        $lock_time = strtotime($locked_at);
        if (time() - $lock_time < 300) { // 300 seconds = 5 minutes
            $is_locked = true;
            // Fetch name
            $uRes = db_query("SELECT full_name FROM users WHERE id = ?", [$locked_by], "i");
            if ($uRes && $u = $uRes->fetch_assoc()) {
                $locked_by_name = $u['full_name'];
            }
        }
    }

    if ($is_locked) {
        echo json_encode(['success' => false, 'error' => 'Document is locked', 'locked_by' => $locked_by_name]);
        exit();
    }

    // Acquire or renew lock
    db_query("UPDATE documents SET locked_by = ?, locked_at = NOW() WHERE id = ?", [$user_id, $document_id], "ii");
    echo json_encode(['success' => true]);
    exit();
}

echo json_encode(['success' => false, 'error' => 'Unknown action.']);
exit();
