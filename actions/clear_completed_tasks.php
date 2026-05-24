<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_die();
    $user_id = (int)$_SESSION['user_id'];
    $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

    // We only clear tasks from projects owned by the user
    if ($project_id) {
        $stmt = $conn->prepare("DELETE t FROM tasks t JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ? WHERE t.status = 'done' AND t.project_id = ? AND (p.creator_id = ? OR pm.role IN ('owner', 'editor'))");
        $stmt->bind_param("iii", $user_id, $project_id, $user_id);
    } else {
        $stmt = $conn->prepare("DELETE t FROM tasks t JOIN projects p ON t.project_id = p.id LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ? WHERE t.status = 'done' AND (p.creator_id = ? OR pm.role IN ('owner', 'editor'))");
        $stmt->bind_param("ii", $user_id, $user_id);
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = "Completed tasks cleared.";
    } else {
        $_SESSION['error'] = "Error clearing tasks.";
    }
}

$redirect = "../dashboard/tasks.php";
if (isset($_POST['project_id']) && (int)$_POST['project_id'] > 0) {
    $redirect .= "?project_id=" . (int)$_POST['project_id'];
}
header("Location: $redirect");
exit();
?>
