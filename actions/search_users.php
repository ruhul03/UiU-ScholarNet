<?php
// actions/search_users.php
require_once(__DIR__ . '/../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../includes/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$user_id = (int)$_SESSION['user_id'];

if (strlen($q) < 2) {
    echo json_encode(['success' => true, 'users' => []]);
    exit();
}

$like_q = "%" . $q . "%";
$res = db_query("SELECT id, full_name, role FROM users WHERE id != ? AND (full_name LIKE ? OR email LIKE ?) ORDER BY full_name ASC LIMIT 10", [$user_id, $like_q, $like_q], "iss");

$users = [];
while ($row = $res->fetch_assoc()) {
    $users[] = [
        'id' => $row['id'],
        'name' => htmlspecialchars($row['full_name']),
        'role' => htmlspecialchars($row['role'])
    ];
}

echo json_encode(['success' => true, 'users' => $users]);
exit();
