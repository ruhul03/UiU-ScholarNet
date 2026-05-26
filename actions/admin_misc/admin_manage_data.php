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
        $del = db_query("DELETE FROM {$table} WHERE id = ?", [$delete_id], "i");
        if ($del) {
            $_SESSION['success'] = "Item removed successfully.";
        }
    } elseif ($name !== '') {
        $ins = db_query("INSERT INTO {$table} (name) VALUES (?)", [$name], "s");
        if ($ins) {
            $_SESSION['success'] = "Item added successfully.";
        }
    }

    header("Location: ../dashboard/admin.php");
    exit();
}
