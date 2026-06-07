<?php
require_once(__DIR__ . '/../../includes/auth_check.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_validate_or_die();
    $preprint_id = intval($_POST['preprint_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    
    // Early return for invalid input
    if (empty($comment) || $preprint_id <= 0) {
        header("Location: ../dashboard/preprint_details.php?id=" . $preprint_id . "&error=1");
        exit();
    }
    
    // Insert the new comment
    $insertCommentQuery = "INSERT INTO preprint_comments (preprint_id, user_id, comment) VALUES (?, ?, ?)";
    $insertResult = db_query($insertCommentQuery, [$preprint_id, $user_id, $comment], "iis");
    
    if ($insertResult) {
        header("Location: ../dashboard/preprint_details.php?id=" . $preprint_id);
    } else {
        header("Location: ../dashboard/preprint_details.php?id=" . $preprint_id . "&error=1");
    }
    exit();
}
?>
