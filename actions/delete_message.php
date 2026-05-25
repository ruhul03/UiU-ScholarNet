<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }

    $user_id = (int)$_SESSION['user_id'];
    $message_id = (int)($_POST['message_id'] ?? 0);

    if ($message_id > 0) {
        // Ensure the user owns this message
        $stmt = $conn->prepare("SELECT id FROM messages WHERE id = ? AND sender_id = ?");
        $stmt->bind_param("ii", $message_id, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $del = $conn->prepare("DELETE FROM messages WHERE id = ?");
            $del->bind_param("i", $message_id);
            if ($del->execute()) {
                echo json_encode(['success' => true]);
                exit();
            }
        }
    }
    echo json_encode(['success' => false, 'message' => 'Permission denied or error']);
    exit();
}
