<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }

    $current_user_id = (int)$_SESSION['user_id'];
    $message_id = (int)($_POST['message_id'] ?? 0);

    // Early return for invalid message ID
    if ($message_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
        exit();
    }

    // 1. Ensure the user owns this message before allowing deletion
    $verifyOwnershipQuery = "SELECT id FROM messages WHERE id = ? AND sender_id = ?";
    $verifyResult = db_query($verifyOwnershipQuery, [$message_id, $current_user_id], "ii");
    
    // Early return if not authorized
    if (!$verifyResult || $verifyResult->num_rows <= 0) {
        echo json_encode(['success' => false, 'message' => 'Permission denied or message not found']);
        exit();
    }
    
    // 2. Delete the message
    $deleteMessageQuery = "DELETE FROM messages WHERE id = ?";
    $deleteResult = db_query($deleteMessageQuery, [$message_id], "i");
    
    if ($deleteResult) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error while deleting message']);
    }
    exit();
}
