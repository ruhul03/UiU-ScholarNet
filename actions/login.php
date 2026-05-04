<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_validate_or_die();

    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $role = trim((string)($_POST['role'] ?? 'student'));

    if (!in_array($role, ['student', 'faculty'], true)) {
        $_SESSION['error'] = "Invalid login role selected.";
        header("Location: ../auth/login.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ? AND role = ? LIMIT 1");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            header("Location: ../dashboard/index.php");
            exit();
        }
    }

    $_SESSION['error'] = "Invalid email or password.";
    header("Location: ../auth/login.php");
    exit();
}
?>
