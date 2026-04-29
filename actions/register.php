<?php
session_start();
require_once('../includes/db_connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $skills = mysqli_real_escape_string($conn, $_POST['skills']);

    // Check if email already exists
    $check_query = "SELECT id FROM users WHERE email = '$email'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['error'] = "Email already registered!";
        header("Location: ../auth/register.php");
        exit();
    }

    $query = "INSERT INTO users (full_name, email, password, department, skills) 
              VALUES ('$full_name', '$email', '$password', '$department', '$skills')";

    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: ../auth/login.php");
    } else {
        $_SESSION['error'] = "Registration failed. Please try again.";
        header("Location: ../auth/register.php");
    }
}
?>
