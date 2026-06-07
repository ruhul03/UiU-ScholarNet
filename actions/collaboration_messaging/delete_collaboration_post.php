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
    $current_user_id = (int)$_SESSION['user_id'];
    $post_id = (int)($_POST['post_id'] ?? 0);

    // Early return for invalid post ID
    if ($post_id <= 0) {
        header("Location: ../dashboard/collaboration.php");
        exit();
    }

    // 1. Verify ownership
    $verifyOwnershipQuery = "SELECT id FROM collaboration_posts WHERE id = ? AND user_id = ? LIMIT 1";
    $verifyResult = db_query($verifyOwnershipQuery, [$post_id, $current_user_id], "ii");
    
    // Early return if not authorized or post not found
    if (!$verifyResult || $verifyResult->num_rows !== 1) {
        $_SESSION['error'] = "Request not found or unauthorized.";
        header("Location: ../dashboard/collaboration.php");
        exit();
    }
    
    // 2. Delete the post
    $deletePostQuery = "DELETE FROM collaboration_posts WHERE id = ? AND user_id = ?";
    $deleteResult = db_query($deletePostQuery, [$post_id, $current_user_id], "ii");
    
    if ($deleteResult) {
        $_SESSION['success'] = "Collaboration request deleted successfully.";
    } else {
        $_SESSION['error'] = "Database error while deleting request.";
    }
}

header("Location: ../dashboard/collaboration.php");
exit();
?>
