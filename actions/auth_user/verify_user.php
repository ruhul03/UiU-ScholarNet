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

    $current_user_id = (int)$_SESSION['user_id'];

    // 1. Verify user is an admin
    $checkAdminQuery = "SELECT role FROM users WHERE id = ?";
    $adminResult = db_query($checkAdminQuery, [$current_user_id], "i");
    $adminData = $adminResult ? $adminResult->fetch_assoc() : null;

    if (!$adminData || $adminData['role'] !== 'admin') {
        $_SESSION['error'] = "Unauthorized access.";
        header("Location: ../dashboard/index.php");
        exit();
    }

    $target_user_id = (int)($_POST['user_id'] ?? 0);

    // Early return if invalid target user
    if ($target_user_id <= 0) {
        header("Location: ../dashboard/admin.php");
        exit();
    }

    // 2. Approve the user
    $approveUserQuery = "UPDATE users SET is_verified = 1 WHERE id = ?";
    $updateResult = db_query($approveUserQuery, [$target_user_id], "i");
    
    if ($updateResult) {
        $_SESSION['success'] = "User has been successfully verified.";
    } else {
        $_SESSION['error'] = "Failed to verify user.";
    }

    header("Location: ../dashboard/admin.php");
    exit();
}
