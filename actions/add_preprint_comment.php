<?php
require_once('../includes/auth_check.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $preprint_id = intval($_POST['preprint_id']);
    $comment = trim($_POST['comment']);
    
    if (!empty($comment) && $preprint_id > 0) {
        $stmt = $conn->prepare("INSERT INTO preprint_comments (preprint_id, user_id, comment) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $preprint_id, $user_id, $comment);
        
        if ($stmt->execute()) {
            header("Location: ../dashboard/preprint_details.php?id=" . $preprint_id);
            exit();
        }
    }
    header("Location: ../dashboard/preprint_details.php?id=" . $preprint_id . "&error=1");
    exit();
}
?>
