<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

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
        $checkRes = db_query("SELECT id FROM collaboration_posts WHERE id = ? AND user_id = ? LIMIT 1", [$post_id, $user_id], "ii");
        
        if ($checkRes && $checkRes->num_rows === 1) {
            // Delete the post
            $del = db_query("DELETE FROM collaboration_posts WHERE id = ? AND user_id = ?", [$post_id, $user_id], "ii");
            
            if ($del) {
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
