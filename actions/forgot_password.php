<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/csrf.php');
require_once('../includes/db_connect.php');
require_once('../auth/email_service.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../auth/forgot_password.php');
    exit();
}

csrf_validate_or_die();

function redirect_forgot_password(): void
{
    header('Location: ../auth/forgot_password.php');
    exit();
}

function is_allowed_email(string $email): bool
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $atPos = strrpos($email, '@');
    $domain = ($atPos !== false) ? substr($email, $atPos + 1) : '';
    return $domain === 'uiu.ac.bd';
}

$action = trim((string)($_POST['action'] ?? 'request_code'));

if ($action === 'request_code') {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    if ($email === '' || !is_allowed_email($email)) {
        $_SESSION['error'] = 'Please provide a valid university email (uiu.ac.bd).';
        redirect_forgot_password();
    }

    $userStmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $userStmt->bind_param('s', $email);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult && $userResult->num_rows === 1) {
        $user = $userResult->fetch_assoc();
        $userId = (int)$user['id'];

        $recentStmt = $conn->prepare(
            "SELECT id FROM password_reset_codes
             WHERE email = ? AND used_at IS NULL AND created_at >= (NOW() - INTERVAL 60 SECOND)
             ORDER BY id DESC LIMIT 1"
        );
        $recentStmt->bind_param('s', $email);
        $recentStmt->execute();
        $recentResult = $recentStmt->get_result();
        if ($recentResult && $recentResult->num_rows > 0) {
            $_SESSION['error'] = 'Please wait 1 minute before requesting a new code.';
            $_SESSION['password_reset_pending_email'] = $email;
            redirect_forgot_password();
        }

        $code = (string)random_int(100000, 999999);
        $codeHash = hash('sha256', $code);
        $expiresAt = date('Y-m-d H:i:s', time() + (10 * 60));

        $invalidateStmt = $conn->prepare('UPDATE password_reset_codes SET used_at = NOW() WHERE email = ? AND used_at IS NULL');
        $invalidateStmt->bind_param('s', $email);
        $invalidateStmt->execute();

        $insertStmt = $conn->prepare(
            'INSERT INTO password_reset_codes (user_id, email, code_hash, expires_at) VALUES (?, ?, ?, ?)'
        );
        $insertStmt->bind_param('isss', $userId, $email, $codeHash, $expiresAt);

        if (!$insertStmt->execute()) {
            $_SESSION['error'] = 'Could not create reset code. Please try again.';
            redirect_forgot_password();
        }

        $mailError = '';
        $sent = uiu_send_password_reset_code($email, $code, 10, $mailError);
        if (!$sent) {
            $cleanupStmt = $conn->prepare('UPDATE password_reset_codes SET used_at = NOW() WHERE email = ? AND used_at IS NULL');
            $cleanupStmt->bind_param('s', $email);
            $cleanupStmt->execute();

            $_SESSION['error'] = 'Failed to send email code. Check EMAIL_USER/EMAIL_PASS and try again.';
            $_SESSION['password_reset_pending_email'] = $email;
            redirect_forgot_password();
        }

        $_SESSION['password_reset_pending_email'] = $email;
    }

    $_SESSION['success'] = 'If your account exists, a reset code was sent to your email.';
    redirect_forgot_password();
}

if ($action === 'reset_password') {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $code = trim((string)($_POST['code'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($email === '' || !is_allowed_email($email)) {
        $_SESSION['error'] = 'Invalid reset email.';
        redirect_forgot_password();
    }

    if (!preg_match('/^[0-9]{6}$/', $code)) {
        $_SESSION['error'] = 'Enter a valid 6-digit reset code.';
        $_SESSION['password_reset_pending_email'] = $email;
        redirect_forgot_password();
    }

    if (strlen($password) < 8) {
        $_SESSION['error'] = 'Password must be at least 8 characters.';
        $_SESSION['password_reset_pending_email'] = $email;
        redirect_forgot_password();
    }

    if (!hash_equals($password, $confirmPassword)) {
        $_SESSION['error'] = 'Passwords do not match.';
        $_SESSION['password_reset_pending_email'] = $email;
        redirect_forgot_password();
    }

    $codeStmt = $conn->prepare(
        "SELECT id, user_id, code_hash, attempts
         FROM password_reset_codes
         WHERE email = ? AND used_at IS NULL AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1"
    );
    $codeStmt->bind_param('s', $email);
    $codeStmt->execute();
    $codeResult = $codeStmt->get_result();

    if (!$codeResult || $codeResult->num_rows !== 1) {
        $_SESSION['error'] = 'Code invalid or expired. Please request a new code.';
        $_SESSION['password_reset_pending_email'] = $email;
        redirect_forgot_password();
    }

    $row = $codeResult->fetch_assoc();
    $resetId = (int)$row['id'];
    $userId = (int)$row['user_id'];
    $attempts = (int)$row['attempts'];
    $codeHash = (string)$row['code_hash'];

    if ($attempts >= 5) {
        $_SESSION['error'] = 'Too many failed attempts. Request a new reset code.';
        $_SESSION['password_reset_pending_email'] = $email;
        redirect_forgot_password();
    }

    $inputHash = hash('sha256', $code);
    if (!hash_equals($codeHash, $inputHash)) {
        $attemptStmt = $conn->prepare('UPDATE password_reset_codes SET attempts = attempts + 1 WHERE id = ? LIMIT 1');
        $attemptStmt->bind_param('i', $resetId);
        $attemptStmt->execute();

        $_SESSION['error'] = 'Wrong reset code.';
        $_SESSION['password_reset_pending_email'] = $email;
        redirect_forgot_password();
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $updateUserStmt = $conn->prepare('UPDATE users SET password = ? WHERE id = ? AND email = ? LIMIT 1');
    $updateUserStmt->bind_param('sis', $passwordHash, $userId, $email);

    if (!$updateUserStmt->execute()) {
        $_SESSION['error'] = 'Could not update password. Please try again.';
        $_SESSION['password_reset_pending_email'] = $email;
        redirect_forgot_password();
    }

    $markUsedStmt = $conn->prepare('UPDATE password_reset_codes SET used_at = NOW() WHERE email = ? AND used_at IS NULL');
    $markUsedStmt->bind_param('s', $email);
    $markUsedStmt->execute();

    unset($_SESSION['password_reset_pending_email']);
    $_SESSION['success'] = 'Password reset successful. Please sign in with your new password.';
    header('Location: ../auth/login.php');
    exit();
}

$_SESSION['error'] = 'Invalid action.';
redirect_forgot_password();
?>
