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
    $action_type = (string)($_POST['action_type'] ?? '');

    if ($target_user_id > 0) {
        if ($action_type === 'verify') {
            $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
            $stmt->bind_param("i", $target_user_id);
            if ($stmt->execute()) $_SESSION['success'] = "User verified successfully.";
        } elseif ($action_type === 'ban') {
            $stmt = $conn->prepare("UPDATE users SET account_status = 'banned' WHERE id = ?");
            $stmt->bind_param("i", $target_user_id);
            if ($stmt->execute()) $_SESSION['success'] = "User has been banned.";
        } elseif ($action_type === 'unban') {
            $stmt = $conn->prepare("UPDATE users SET account_status = 'active' WHERE id = ?");
            $stmt->bind_param("i", $target_user_id);
            if ($stmt->execute()) $_SESSION['success'] = "User has been unbanned.";
        } elseif ($action_type === 'delete') {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $target_user_id);
            if ($stmt->execute()) $_SESSION['success'] = "User permanently deleted.";
        }
    }

    header("Location: ../dashboard/admin.php");
    exit();
}
