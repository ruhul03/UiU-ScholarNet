<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$project_id = (int)($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';
$user_id = (int)$_SESSION['user_id'];

if (!hash_equals(csrf_token(), $token)) {
    $_SESSION['error'] = "Invalid security token.";
    header("Location: ../dashboard/edit_project.php?id=" . $project_id);
    exit();
}

if ($project_id > 0) {
    // Verify the user is the supervisor and the project is in review
    $stmt = $conn->prepare("SELECT id, status FROM projects WHERE id = ? AND supervisor_id = ?");
    $stmt->bind_param("ii", $project_id, $user_id);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();

    if ($project) {
        if ($project['status'] === 'review') {
            $upd = $conn->prepare("UPDATE projects SET supervisor_approved = 1 WHERE id = ?");
            $upd->bind_param("i", $project_id);
            if ($upd->execute()) {
                $_SESSION['success'] = "Project officially approved. The team can now move it to COMPLETED.";
            } else {
                $_SESSION['error'] = "Failed to approve the project.";
            }
        } else {
            $_SESSION['error'] = "Project must be in the REVIEW stage to approve.";
        }
    } else {
        $_SESSION['error'] = "Unauthorized or project not found.";
    }
}

header("Location: ../dashboard/edit_project.php?id=" . $project_id);
exit();
?>
