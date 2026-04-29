<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../dashboard/messages.php");
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

csrf_validate_or_die();

$sender_id = (int)$_SESSION['user_id'];
$channel = trim((string)($_POST['channel'] ?? 'general'));
$message = trim((string)($_POST['message'] ?? ''));

if ($message === '') {
    header("Location: ../dashboard/messages.php");
    exit();
}

// Keep channels simple and safe
if (!preg_match('/^[a-z0-9_-]{1,50}$/', $channel)) {
    $channel = 'general';
}

$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, channel, message) VALUES (?, NULL, ?, ?)");
$stmt->bind_param("iss", $sender_id, $channel, $message);
$stmt->execute();

header("Location: ../dashboard/messages.php?channel=" . urlencode($channel));
exit();

