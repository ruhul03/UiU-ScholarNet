<?php
session_start();
require_once('../includes/db_connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $skills = mysqli_real_escape_string($conn, $_POST['skills']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);

    $query = "INSERT INTO collaboration_posts (user_id, title, department, description, skills_required) 
              VALUES ($user_id, '$title', '$department', '$description', '$skills')";

    if (mysqli_query($conn, $query)) {
        header("Location: ../dashboard/collaboration.php?success=1");
    } else {
        header("Location: ../dashboard/collaboration.php?error=1");
    }
}
?>
