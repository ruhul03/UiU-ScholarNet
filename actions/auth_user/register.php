<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    csrf_validate_or_die();

    $full_name = trim((string)($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $raw_password = (string)($_POST['password'] ?? '');
    $department = trim((string)($_POST['department'] ?? ''));
    $raw_interests = trim((string)($_POST['interests'] ?? ''));
    $skills = trim((string)($_POST['skills'] ?? ''));

    $interests_list = array_filter(array_map('trim', explode(',', $raw_interests)), static function ($value) {
        return $value !== '';
    });
    $interests_list = array_slice(array_values($interests_list), 0, 20);
    $interests = implode(', ', $interests_list);

    // Validate required fields
    if ($full_name === '' || $email === '' || $raw_password === '' || $department === '') {
        $_SESSION['error'] = "Please fill in all required fields.";
        header("Location: ../auth/register.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please provide a valid email address.";
        header("Location: ../auth/register.php");
        exit();
    }

    // Ensure UIU email domain
    $atPos = strrpos($email, '@');
    $emailDomain = ($atPos !== false) ? substr($email, $atPos + 1) : '';
    $suffix = "uiu.ac.bd";
    if ($emailDomain === '' || substr($emailDomain, -strlen($suffix)) !== $suffix) {
        $_SESSION['error'] = "Registration requires an email ending with uiu.ac.bd.";
        header("Location: ../auth/register.php");
        exit();
    }

    if (strlen($raw_password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters.";
        header("Location: ../auth/register.php");
        exit();
    }

    // Check if email already exists
    $existing = db_query("SELECT id FROM users WHERE email = ? LIMIT 1", [$email], "s");
    
    if ($existing && $existing->num_rows > 0) {
        $_SESSION['error'] = "Email already registered!";
        header("Location: ../auth/register.php");
        exit();
    }

    $password = password_hash($raw_password, PASSWORD_DEFAULT);
    $role = (isset($_POST['role']) && $_POST['role'] === 'faculty') ? 'faculty' : 'student';
    $is_verified = ($role === 'faculty') ? 0 : 1;

    // Insert new user into database
    $insert = db_query(
        "INSERT INTO users (full_name, email, password, department, interests, skills, role, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
        [$full_name, $email, $password, $department, $interests, $skills, $role, $is_verified],
        "sssssssi"
    );

    if ($insert) {
        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: ../auth/login.php");
        exit();
    }

    $_SESSION['error'] = "Registration failed. Please try again.";
    header("Location: ../auth/register.php");
    exit();
}
?>
