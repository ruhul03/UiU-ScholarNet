<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/csrf.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../auth/forgot_password.php');
    exit();
}

csrf_validate_or_die();

require_once('../includes/db_connect.php');

$email = strtolower(trim((string)($_POST['email'] ?? '')));
$new_password = (string)($_POST['new_password'] ?? '');
$confirm_password = (string)($_POST['confirm_password'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please provide a valid university email.';
    header('Location: ../auth/forgot_password.php');
    exit();
}

if (strlen($new_password) < 8) {
    $_SESSION['error'] = 'Password must be at least 8 characters long.';
    header('Location: ../auth/forgot_password.php');
    exit();
}

if ($new_password !== $confirm_password) {
    $_SESSION['error'] = 'Passwords do not match.';
    header('Location: ../auth/forgot_password.php');
    exit();
}

$full_name = trim((string)($_POST['full_name'] ?? ''));
$department = trim((string)($_POST['department'] ?? ''));

if ($full_name === '' || $department === '') {
    $_SESSION['error'] = 'Please provide your full name and department for verification.';
    header('Location: ../auth/forgot_password.php');
    exit();
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND full_name = ? AND department = ? LIMIT 1");
$stmt->bind_param("sss", $email, $full_name, $department);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $hashed_pw = password_hash($new_password, PASSWORD_DEFAULT);
    $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $updateStmt->bind_param("ss", $hashed_pw, $email);
    $updateStmt->execute();
    
    $_SESSION['success'] = 'Password updated successfully. You can now log in.';
    header('Location: ../auth/login.php');
    exit();
} else {
    $_SESSION['error'] = 'Email not found in our records.';
    header('Location: ../auth/forgot_password.php');
    exit();
}
?>
