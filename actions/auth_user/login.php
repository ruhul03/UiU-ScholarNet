<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_validate_or_die();

    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');

    // 1. Fetch user by email
    $fetchUserQuery = "SELECT id, full_name, email, password, role, account_status FROM users WHERE email = ? LIMIT 1";
    $fetchUserResult = db_query($fetchUserQuery, [$email], "s");

    // Early return if user not found
    if (!$fetchUserResult || $fetchUserResult->num_rows !== 1) {
        $_SESSION['error'] = "Invalid email or password.";
        header("Location: ../auth/login.php");
        exit();
    }
    
    $user = $fetchUserResult->fetch_assoc();

    // 2. Verify password hash
    if (!password_verify($password, $user['password'])) {
        $_SESSION['error'] = "Invalid email or password.";
        header("Location: ../auth/login.php");
        exit();
    }

    // 3. Check if user is banned
    if (isset($user['account_status']) && $user['account_status'] === 'banned') {
        $_SESSION['error'] = "Your account has been suspended.";
        header("Location: ../auth/login.php");
        exit();
    }

    // 4. Secure session regeneration
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];

    // 5. Remember Me functionality
    if (isset($_POST['remember_me'])) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            session_id(),
            time() + (30 * 24 * 60 * 60), // 30 days
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    header("Location: ../dashboard/index.php");
    exit();
}
?>
