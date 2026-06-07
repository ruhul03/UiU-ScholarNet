<?php
require_once(__DIR__ . '/../../includes/auth_check.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_validate_or_die();
    $item_id = intval($_POST['item_id']);
    $item_type = $_POST['item_type'];
    $reason = trim($_POST['reason']);
    $redirect_url = $_POST['redirect_url'];
    
    if (!empty($reason) && $item_id > 0) {
        $insert = db_query("INSERT INTO reports (item_id, item_type, reported_by, reason) VALUES (?, ?, ?, ?)", [$item_id, $item_type, $user_id, $reason], "isis");
        
        if ($insert) {
            header("Location: " . $redirect_url . "&reported=1");
            exit();
        }
    }
    header("Location: " . $redirect_url . "&error=1");
    exit();
}
?>
