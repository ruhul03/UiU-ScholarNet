<?php
session_start();
require_once('../includes/db_connect.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $project_id = (int)$_POST['project_id'];
    $priority = mysqli_real_escape_string($conn, $_POST['priority']);
    $due_date = mysqli_real_escape_string($conn, $_POST['due_date']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $assigned_to = $_SESSION['user_id']; // Self-assign by default for now

    $query = "INSERT INTO tasks (project_id, title, description, assigned_to, priority, due_date) 
              VALUES ($project_id, '$title', '$description', $assigned_to, '$priority', '$due_date')";

    if (mysqli_query($conn, $query)) {
        header("Location: ../dashboard/tasks.php?project_id=$project_id&success=1");
    } else {
        header("Location: ../dashboard/tasks.php?error=1");
    }
}
?>
