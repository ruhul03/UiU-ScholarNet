<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_validate_or_die();

    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    // Verify user is an admin
    $adminRes = db_query("SELECT role FROM users WHERE id = ?", [$_SESSION['user_id']], "i");
    $adminData = $adminRes ? $adminRes->fetch_assoc() : null;

    if (!$adminData || $adminData['role'] !== 'admin') {
        $_SESSION['error'] = "Unauthorized access.";
        header("Location: ../dashboard/index.php");
        exit();
    }

    $target_user_id = (int)($_POST['user_id'] ?? 0);
    $action_type = (string)($_POST['action_type'] ?? '');

    if ($target_user_id > 0) {
        if ($action_type === 'verify') {
            $stmt = db_query("UPDATE users SET is_verified = 1 WHERE id = ?", [$target_user_id], "i");
            if ($stmt) $_SESSION['success'] = "User verified successfully.";
        } elseif ($action_type === 'ban') {
            $stmt = db_query("UPDATE users SET account_status = 'banned' WHERE id = ?", [$target_user_id], "i");
            if ($stmt) $_SESSION['success'] = "User has been banned.";
        } elseif ($action_type === 'unban') {
            $stmt = db_query("UPDATE users SET account_status = 'active' WHERE id = ?", [$target_user_id], "i");
            if ($stmt) $_SESSION['success'] = "User has been unbanned.";
        } elseif ($action_type === 'delete') {
            $stmt = db_query("DELETE FROM users WHERE id = ?", [$target_user_id], "i");
            if ($stmt) $_SESSION['success'] = "User permanently deleted.";
        }
    }

    header("Location: ../dashboard/admin.php");
    exit();
}
