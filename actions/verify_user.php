<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_validate_or_die();

    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    // Verify user is an admin
    $adminStmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $adminStmt->bind_param("i", $_SESSION['user_id']);
    $adminStmt->execute();
    $adminData = $adminStmt->get_result()->fetch_assoc();

    if (!$adminData || $adminData['role'] !== 'admin') {
        $_SESSION['error'] = "Unauthorized access.";
        header("Location: ../dashboard/index.php");
        exit();
    }

    $target_user_id = (int)($_POST['user_id'] ?? 0);

    if ($target_user_id > 0) {
        $updateStmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $updateStmt->bind_param("i", $target_user_id);
        if ($updateStmt->execute()) {
            $_SESSION['success'] = "User has been successfully verified.";
        } else {
            $_SESSION['error'] = "Failed to verify user.";
        }
    }

    header("Location: ../dashboard/admin.php");
    exit();
}
