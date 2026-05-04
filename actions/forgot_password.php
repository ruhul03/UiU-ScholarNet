<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/csrf.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../auth/forgot_password.php');
    exit();
}

csrf_validate_or_die();

$email = strtolower(trim((string)($_POST['email'] ?? '')));
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please provide a valid university email.';
    header('Location: ../auth/forgot_password.php');
    exit();
}

$_SESSION['success'] = 'If this email exists, a password reset instruction will be sent.';
header('Location: ../auth/login.php');
exit();
?>
