<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$last_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
$chat_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$channel = isset($_GET['channel']) ? $_GET['channel'] : '';

$messages = [];

if ($chat_user_id > 0) {
    // Direct Message Polling
    $stmt = $conn->prepare("
        SELECT m.*, u.full_name
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
          AND m.id > ?
        ORDER BY m.created_at ASC LIMIT 50
    ");
    $stmt->bind_param("iiiii", $user_id, $chat_user_id, $chat_user_id, $user_id, $last_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $messages[] = $row;
    }
} elseif (!empty($channel)) {
    // Channel Polling
    $stmt = $conn->prepare("
        SELECT m.*, u.full_name
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE m.channel = ? AND m.receiver_id IS NULL AND m.id > ?
        ORDER BY m.created_at ASC LIMIT 50
    ");
    $stmt->bind_param("si", $channel, $last_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $messages[] = $row;
    }
}

echo json_encode(['success' => true, 'messages' => $messages]);
exit();
