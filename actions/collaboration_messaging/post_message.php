<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    if ($is_ajax) { echo json_encode(['success' => false, 'error' => 'Invalid request method']); exit(); }
    header("Location: ../dashboard/messages.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    if ($is_ajax) { echo json_encode(['success' => false, 'error' => 'Not logged in']); exit(); }
    header("Location: ../auth/login.php");
    exit();
}

if ($is_ajax) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['success' => false, 'error' => 'CSRF validation failed']); exit();
    }
} else {
    csrf_validate_or_die();
}

$current_user_id = (int)$_SESSION['user_id'];
$channel = trim((string)($_POST['channel'] ?? 'general'));
$message = trim((string)($_POST['message'] ?? ''));
$receiver_id = isset($_POST['receiver_id']) && $_POST['receiver_id'] !== '' ? (int)$_POST['receiver_id'] : null;

$has_file = isset($_FILES['chat_file']) && $_FILES['chat_file']['error'] === UPLOAD_ERR_OK;

// Early return if empty message and no file
if ($message === '' && !$has_file) {
    if ($is_ajax) { 
        echo json_encode(['success' => false, 'error' => 'Empty message']); 
        exit(); 
    }
    $redirect_url = $receiver_id ? "?user_id=" . $receiver_id : "?channel=" . urlencode($channel);
    header("Location: ../dashboard/messages.php" . $redirect_url);
    exit();
}

// 1. Keep channel alphanumeric for security purposes
if (!preg_match('/^[a-z0-9_-]{1,50}$/', $channel)) {
    $channel = 'general';
}

// 2. Handle file upload
$file_path = null;
$file_name = null;
if ($has_file) {
    $upload_directory = __DIR__ . '/../../uploads/chat/';
    if (!is_dir($upload_directory)) {
        mkdir($upload_directory, 0777, true);
    }
    
    $uploaded_file = $_FILES['chat_file'];
    $original_name = basename($uploaded_file['name']);
    $safe_base_name = preg_replace('/[^A-Za-z0-9._-]/', '_', $original_name);
    $new_file_name = time() . '_' . $safe_base_name;
    $target_file_path = $upload_directory . $new_file_name;
    
    if (move_uploaded_file($uploaded_file['tmp_name'], $target_file_path)) {
        $file_path = 'uploads/chat/' . $new_file_name;
        $file_name = $original_name;
    }
}

// 3. Insert Message
if ($receiver_id && $receiver_id > 0) {
    // DIRECT MESSAGE
    $insertDirectMessageQuery = "INSERT INTO messages (sender_id, receiver_id, channel, message, file_path, file_name) VALUES (?, ?, 'dm', ?, ?, ?)";
    db_query($insertDirectMessageQuery, [$current_user_id, $receiver_id, $message, $file_path, $file_name], "iisss");
    
    $message_id = $conn->insert_id;
    
    // Notify the receiver (only if they don't already have unread messages from this sender to prevent spam)
    $unreadCheck = db_query("SELECT COUNT(*) as cnt FROM messages WHERE sender_id = ? AND receiver_id = ? AND is_read = 0 AND id != ?", [$current_user_id, $receiver_id, $message_id], "iii")->fetch_assoc();
    
    if ($unreadCheck['cnt'] == 0) {
        $senderRes = db_query("SELECT full_name FROM users WHERE id = ?", [$current_user_id], "i")->fetch_assoc();
        $senderName = $senderRes['full_name'] ?? 'Someone';
        send_notification(
            $receiver_id,
            "New Direct Message",
            "{$senderName} sent you a message.",
            "../dashboard/messages.php?user_id=" . $current_user_id,
            "message"
        );
    }
    
    if ($is_ajax) { 
        echo json_encode(['success' => true, 'message_id' => $message_id, 'file_path' => $file_path, 'file_name' => $file_name]); 
        exit(); 
    }
    header("Location: ../dashboard/messages.php?user_id=" . $receiver_id);
    exit();
} else {
    // CHANNEL CHAT
    $insertChannelMessageQuery = "INSERT INTO messages (sender_id, receiver_id, channel, message, file_path, file_name) VALUES (?, NULL, ?, ?, ?, ?)";
    db_query($insertChannelMessageQuery, [$current_user_id, $channel, $message, $file_path, $file_name], "issss");
    
    $message_id = $conn->insert_id;
    
    if ($is_ajax) { 
        echo json_encode(['success' => true, 'message_id' => $message_id, 'file_path' => $file_path, 'file_name' => $file_name]); 
        exit(); 
    }
    header("Location: ../dashboard/messages.php?channel=" . urlencode($channel));
    exit();
}

