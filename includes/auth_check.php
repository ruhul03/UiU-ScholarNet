<?php
require_once(__DIR__ . '/session.php');
start_secure_session();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once('db_connect.php');

$user_id = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_data = $user_result->fetch_assoc();

if (!$user_data || (isset($user_data['account_status']) && $user_data['account_status'] === 'banned')) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit();
}
?>
