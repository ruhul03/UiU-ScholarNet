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
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $progress = (int)$_POST['progress'];

    $query = "INSERT INTO projects (title, description, status, progress, creator_id) 
              VALUES ('$title', '$description', '$status', $progress, $user_id)";

    if (mysqli_query($conn, $query)) {
        header("Location: ../dashboard/projects.php?success=1");
    } else {
        header("Location: ../dashboard/projects.php?error=1");
    }
}
?>
