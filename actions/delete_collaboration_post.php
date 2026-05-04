<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_die();
    $user_id = (int)$_SESSION['user_id'];
    $post_id = (int)($_POST['post_id'] ?? 0);

    if ($post_id > 0) {
        // Verify ownership
        $stmt = $conn->prepare("SELECT id FROM collaboration_posts WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 1) {
            $delStmt = $conn->prepare("DELETE FROM collaboration_posts WHERE id = ? AND user_id = ?");
            $delStmt->bind_param("ii", $post_id, $user_id);
            
            if ($delStmt->execute()) {
                $_SESSION['success'] = "Collaboration request deleted successfully.";
            } else {
                $_SESSION['error'] = "Database error while deleting request.";
            }
        } else {
            $_SESSION['error'] = "Request not found or unauthorized.";
        }
    }
}

header("Location: ../dashboard/collaboration.php");
exit();
?>
