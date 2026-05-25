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

if ($receiver_id && $receiver_id > 0) {
    // DIRECT MESSAGE
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, channel, message) VALUES (?, ?, 'dm', ?)");
    $stmt->bind_param("iis", $sender_id, $receiver_id, $message);
    $stmt->execute();
    $message_id = $conn->insert_id;
    
    if ($is_ajax) { echo json_encode(['success' => true, 'message_id' => $message_id]); exit(); }
    header("Location: ../dashboard/messages.php?user_id=" . $receiver_id);
    exit();
} else {
    // CHANNEL CHAT
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, channel, message) VALUES (?, NULL, ?, ?)");
    $stmt->bind_param("iss", $sender_id, $channel, $message);
    $stmt->execute();
    $message_id = $conn->insert_id;
    
    if ($is_ajax) { echo json_encode(['success' => true, 'message_id' => $message_id]); exit(); }
    header("Location: ../dashboard/messages.php?channel=" . urlencode($channel));
    exit();
}

