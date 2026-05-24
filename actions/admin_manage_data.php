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

    $table = (string)($_POST['table'] ?? '');
    $allowed_tables = ['departments', 'skills', 'opportunity_types'];

    if (!in_array($table, $allowed_tables, true)) {
        $_SESSION['error'] = "Invalid table.";
        header("Location: ../dashboard/admin.php");
        exit();
    }

    $name = trim((string)($_POST['name'] ?? ''));
    $delete_id = (int)($_POST['delete_id'] ?? 0);

    if ($delete_id > 0) {
        $stmt = $conn->prepare("DELETE FROM {$table} WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) $_SESSION['success'] = "Item removed successfully.";
    } elseif ($name !== '') {
        $stmt = $conn->prepare("INSERT INTO {$table} (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) $_SESSION['success'] = "Item added successfully.";
    }

    header("Location: ../dashboard/admin.php");
    exit();
}
