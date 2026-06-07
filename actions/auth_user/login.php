<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_validate_or_die();

    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');

    // Fetch user by email using simplified db_query
    $result = db_query("SELECT id, full_name, email, password, role, account_status FROM users WHERE email = ? LIMIT 1", [$email], "s");

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password hash
        if (password_verify($password, $user['password'])) {
            // Check if user is banned
            if (isset($user['account_status']) && $user['account_status'] === 'banned') {
                $_SESSION['error'] = "Your account has been suspended.";
                header("Location: ../auth/login.php");
                exit();
            }

            // Secure session regeneration
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];

            if (isset($_POST['remember_me'])) {
                // Set session cookie to last for 30 days
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

            $redirect = '../dashboard/index.php';
            header("Location: " . $redirect);
            exit();
        }
    }

    $_SESSION['error'] = "Invalid email or password.";
    header("Location: ../auth/login.php");
    exit();
}
?>
