<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard/profile.php');
    exit();
}

csrf_validate_or_die();

$current_user_id = (int)$_SESSION['user_id'];
$full_name = trim((string)($_POST['full_name'] ?? ''));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$department = trim((string)($_POST['department'] ?? ''));
$institution = trim((string)($_POST['institution'] ?? ''));
$biography = trim((string)($_POST['biography'] ?? ''));
$raw_interests = trim((string)($_POST['interests'] ?? ''));
$raw_skills = trim((string)($_POST['skills'] ?? ''));

// 1. Validate required fields
if ($full_name === '' || $email === '' || $department === '' || $institution === '') {
    $_SESSION['error'] = 'Please fill in all required profile fields.';
    header('Location: ../dashboard/profile.php');
    exit();
}

// 2. Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please provide a valid email address.';
    header('Location: ../dashboard/profile.php');
    exit();
}

// 3. Ensure UIU email domain
$atPos = strrpos($email, '@');
$emailDomain = ($atPos !== false) ? substr($email, $atPos + 1) : '';
$suffix = 'uiu.ac.bd';
if ($emailDomain === '' || substr($emailDomain, -strlen($suffix)) !== $suffix) {
    $_SESSION['error'] = 'Email must end with uiu.ac.bd.';
    header('Location: ../dashboard/profile.php');
    exit();
}

// 4. Check if the email belongs to another user
$checkDuplicateQuery = "SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1";
$duplicateResult = db_query($checkDuplicateQuery, [$email, $current_user_id], "si");

if ($duplicateResult && $duplicateResult->num_rows > 0) {
    $_SESSION['error'] = 'This email is already used by another account.';
    header('Location: ../dashboard/profile.php');
    exit();
}

$normalizeList = static function (string $raw): string {
    $items = array_filter(array_map('trim', explode(',', $raw)), static function ($value) {
        return $value !== '';
    });
    $items = array_slice(array_values(array_unique($items)), 0, 20);
    return implode(', ', $items);
};

$interests = $normalizeList($raw_interests);
$skills = $normalizeList($raw_skills);

// 5. Update main user record
$updateUserQuery = "
    UPDATE users 
    SET full_name = ?, email = ?, department = ?, interests = ?, skills = ? 
    WHERE id = ?
";
db_query($updateUserQuery, [$full_name, $email, $department, $interests, $skills, $current_user_id], "sssssi");

// Ensure the profile table exists
$createTableQuery = "
    CREATE TABLE IF NOT EXISTS user_profiles (
        user_id INT PRIMARY KEY,
        institution VARCHAR(150) DEFAULT NULL,
        biography TEXT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
";
db_query($createTableQuery);

// 6. Upsert the extended profile information
$upsertProfileQuery = "
    INSERT INTO user_profiles (user_id, institution, biography)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE institution = VALUES(institution), biography = VALUES(biography)
";
db_query($upsertProfileQuery, [$current_user_id, $institution, $biography], "iss");

$_SESSION['user_name'] = $full_name;
$_SESSION['success'] = 'Profile updated successfully.';
header('Location: ../dashboard/profile.php');
exit();
?>
