<?php
require_once('../includes/auth_check.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $item_id = intval($_POST['item_id']);
    $item_type = $_POST['item_type'];
    $reason = trim($_POST['reason']);
    $redirect_url = $_POST['redirect_url'];
    
    if (!empty($reason) && $item_id > 0) {
        $stmt = $conn->prepare("INSERT INTO reports (item_id, item_type, reported_by, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isis", $item_id, $item_type, $user_id, $reason);
        
        if ($stmt->execute()) {
            header("Location: " . $redirect_url . "&reported=1");
            exit();
        }
    }
    header("Location: " . $redirect_url . "&error=1");
    exit();
}
?>
