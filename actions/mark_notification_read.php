<?php
require_once('../includes/db_connect.php');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $notif_id = isset($data['id']) ? (int)$data['id'] : 0;
    
    if ($notif_id > 0) {
        $user_id = $_SESSION['user_id'];
        $upd = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $upd->bind_param("ii", $notif_id, $user_id);
        $upd->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
}
