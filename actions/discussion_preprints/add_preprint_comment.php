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
        $prepData = db_query("SELECT title, author_id FROM preprints WHERE id = ?", [$preprint_id], "i")->fetch_assoc();
        if ($prepData && $prepData['author_id'] != $user_id) {
            $commenterRes = db_query("SELECT full_name FROM users WHERE id = ?", [$user_id], "i")->fetch_assoc();
            $commenterName = $commenterRes['full_name'] ?? 'Someone';
            send_notification(
                $prepData['author_id'],
                "New Preprint Comment",
                "{$commenterName} commented on your preprint '{$prepData['title']}'.",
                "../dashboard/preprint_details.php?id=" . $preprint_id,
                "general"
            );
        }
        header("Location: ../dashboard/preprint_details.php?id=" . $preprint_id);
    } else {
        header("Location: ../dashboard/preprint_details.php?id=" . $preprint_id . "&error=1");
    }
    exit();
}
?>
