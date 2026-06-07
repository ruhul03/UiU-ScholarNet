<?php
// actions/get_user_profile.php
session_start();
require_once('../includes/db_connect.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($user_id <= 0) {
    echo json_encode(['error' => 'Invalid user ID']);
    exit;
}

// Fetch user data
$query = "SELECT full_name, email, role, department, reputation, created_at FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['error' => 'Database error']);
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['error' => 'User not found']);
    exit;
}

$user = $res->fetch_assoc();

// Fetch skills
$skills_query = "SELECT s.name FROM user_skills us JOIN skills s ON us.skill_id = s.id WHERE us.user_id = ?";
$stmt_skills = $conn->prepare($skills_query);
$stmt_skills->bind_param("i", $user_id);
$stmt_skills->execute();
$res_skills = $stmt_skills->get_result();

$skills = [];
while ($row = $res_skills->fetch_assoc()) {
    $skills[] = $row['name'];
}

$user['skills'] = $skills;
$user['avatar_url'] = "https://ui-avatars.com/api/?name=" . urlencode($user['full_name']) . "&background=0a1128&color=fff";
$user['joined_date'] = date('M Y', strtotime($user['created_at']));

echo json_encode($user);
exit;
