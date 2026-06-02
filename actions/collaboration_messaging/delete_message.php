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

    $user_id = (int)$_SESSION['user_id'];
    $message_id = (int)($_POST['message_id'] ?? 0);

    if ($message_id > 0) {
        // Ensure the user owns this message before allowing deletion
        $check = db_query("SELECT id FROM messages WHERE id = ? AND sender_id = ?", [$message_id, $user_id], "ii");
        if ($check && $check->num_rows > 0) {
            // Delete the message
            $del = db_query("DELETE FROM messages WHERE id = ?", [$message_id], "i");
            if ($del) {
                echo json_encode(['success' => true]);
                exit();
            }
        }
    }
    echo json_encode(['success' => false, 'message' => 'Permission denied or message not found']);
    exit();
}
