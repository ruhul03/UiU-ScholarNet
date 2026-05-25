<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

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

$sender_id = (int)$_SESSION['user_id'];
$channel = trim((string)($_POST['channel'] ?? 'general'));
$message = trim((string)($_POST['message'] ?? ''));
$receiver_id = isset($_POST['receiver_id']) && $_POST['receiver_id'] !== '' ? (int)$_POST['receiver_id'] : null;

if ($message === '') {
    if ($is_ajax) { echo json_encode(['success' => false, 'error' => 'Empty message']); exit(); }
    $redirect = $receiver_id ? "?user_id=" . $receiver_id : "?channel=" . urlencode($channel);
    header("Location: ../dashboard/messages.php" . $redirect);
    exit();
}

// Keep channel alphanumeric for security purposes
if (!preg_match('/^[a-z0-9_-]{1,50}$/', $channel)) {
    $channel = 'general';
}

// Handle file upload
$file_path = null;
$file_name = null;
if (isset($_FILES['chat_file']) && $_FILES['chat_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/chat/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file = $_FILES['chat_file'];
    $original_name = basename($file['name']);
    $safe_base = preg_replace('/[^A-Za-z0-9._-]/', '_', $original_name);
    $new_name = time() . '_' . $safe_base;
    $target_path = $upload_dir . $new_name;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        $file_path = 'uploads/chat/' . $new_name;
        $file_name = $original_name;
    }
}

if ($receiver_id && $receiver_id > 0) {
    // DIRECT MESSAGE
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, channel, message, file_path, file_name) VALUES (?, ?, 'dm', ?, ?, ?)");
    $stmt->bind_param("iisss", $sender_id, $receiver_id, $message, $file_path, $file_name);
    $stmt->execute();
    $message_id = $conn->insert_id;
    
    if ($is_ajax) { echo json_encode(['success' => true, 'message_id' => $message_id, 'file_path' => $file_path, 'file_name' => $file_name]); exit(); }
    header("Location: ../dashboard/messages.php?user_id=" . $receiver_id);
    exit();
} else {
    // CHANNEL CHAT
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, channel, message, file_path, file_name) VALUES (?, NULL, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $sender_id, $channel, $message, $file_path, $file_name);
    $stmt->execute();
    $message_id = $conn->insert_id;
    
    if ($is_ajax) { echo json_encode(['success' => true, 'message_id' => $message_id, 'file_path' => $file_path, 'file_name' => $file_name]); exit(); }
    header("Location: ../dashboard/messages.php?channel=" . urlencode($channel));
    exit();
}

