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
    $adminResult = db_query("SELECT role FROM users WHERE id = ?", [$_SESSION['user_id']], "i");
    $adminData = $adminResult ? $adminResult->fetch_assoc() : null;

    if (!$adminData || $adminData['role'] !== 'admin') {
        $_SESSION['error'] = "Unauthorized access.";
        header("Location: ../dashboard/index.php");
        exit();
    }

    $target_user_id = (int)($_POST['user_id'] ?? 0);

    if ($target_user_id > 0) {
        // Approve the user
        $update = db_query("UPDATE users SET is_verified = 1 WHERE id = ?", [$target_user_id], "i");
        
        if ($update) {
            $_SESSION['success'] = "User has been successfully verified.";
        } else {
            $_SESSION['error'] = "Failed to verify user.";
        }
    }

    header("Location: ../dashboard/admin.php");
    exit();
}
