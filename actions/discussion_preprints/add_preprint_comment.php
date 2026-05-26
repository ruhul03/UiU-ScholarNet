<?php
require_once(__DIR__ . '/../../includes/auth_check.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $preprint_id = intval($_POST['preprint_id']);
    $comment = trim($_POST['comment']);
    
    if (!empty($comment) && $preprint_id > 0) {
        $insert = db_query("INSERT INTO preprint_comments (preprint_id, user_id, comment) VALUES (?, ?, ?)", [$preprint_id, $user_id, $comment], "iis");
        
        if ($insert) {
            header("Location: ../dashboard/preprint_details.php?id=" . $preprint_id);
            exit();
        }
    }
    header("Location: ../dashboard/preprint_details.php?id=" . $preprint_id . "&error=1");
    exit();
}
?>
