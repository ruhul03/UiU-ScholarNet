<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$last_message_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
$chat_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$channel = isset($_GET['channel']) ? trim($_GET['channel']) : '';

$messages = [];

if ($chat_user_id > 0) {
    // 1. Mark messages from this sender as read
    $markReadQuery = "UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
    db_query($markReadQuery, [$chat_user_id, $current_user_id], "ii");

    // 2. Fetch direct messages between the two users that are newer than the last received message ID
    $fetchDirectMessagesQuery = "
        SELECT m.*, u.full_name
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
          AND m.id > ?
        ORDER BY m.created_at ASC 
        LIMIT 50
    ";
    
    $messagesResult = db_query($fetchDirectMessagesQuery, [$current_user_id, $chat_user_id, $chat_user_id, $current_user_id, $last_message_id], "iiiii");
    
    if ($messagesResult) {
        while ($row = $messagesResult->fetch_assoc()) {
            $messages[] = $row;
        }
    }
} elseif (!empty($channel)) {
    // 3. Fetch group/channel messages that are newer than the last received message ID
    $fetchChannelMessagesQuery = "
        SELECT m.*, u.full_name
        FROM messages m
        JOIN users u ON u.id = m.sender_id
        WHERE m.channel = ? AND m.receiver_id IS NULL AND m.id > ?
        ORDER BY m.created_at ASC 
        LIMIT 50
    ";
    
    $messagesResult = db_query($fetchChannelMessagesQuery, [$channel, $last_message_id], "si");
    
    if ($messagesResult) {
        while ($row = $messagesResult->fetch_assoc()) {
            $messages[] = $row;
        }
    }
}

echo json_encode(['success' => true, 'messages' => $messages]);
exit();
