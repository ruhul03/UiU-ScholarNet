<?php
require_once(__DIR__ . '/../../includes/auth_check.php');

$preprint_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Early return for invalid ID
if ($preprint_id <= 0) {
    $_SESSION['error'] = "Invalid preprint ID.";
    header("Location: ../dashboard/preprints.php");
    exit();
}

// 1. Fetch file path
$fetchPathQuery = "SELECT file_path FROM preprints WHERE id = ?";
$fetchResult = db_query($fetchPathQuery, [$preprint_id], "i");

if (!$fetchResult || $fetchResult->num_rows <= 0) {
    $_SESSION['error'] = "Preprint not found.";
    header("Location: ../dashboard/preprints.php");
    exit();
}

$preprintData = $fetchResult->fetch_assoc();
$absolute_file_path = __DIR__ . '/../../' . $preprintData['file_path'];

// 2. Check if file exists on disk
if (!file_exists($absolute_file_path)) {
    $_SESSION['error'] = "File not found on server.";
    header("Location: ../dashboard/preprints.php");
    exit();
}

// 3. Increment downloads count
$incrementQuery = "UPDATE preprints SET downloads_count = downloads_count + 1 WHERE id = ?";
db_query($incrementQuery, [$preprint_id], "i");

// 4. Force download
header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($absolute_file_path) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($absolute_file_path));
readfile($absolute_file_path);
exit();
?>
