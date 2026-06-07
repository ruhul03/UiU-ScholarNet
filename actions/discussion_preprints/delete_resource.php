<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_die();
    $current_user_id = (int)$_SESSION['user_id'];
    $resource_id = (int)($_POST['resource_id'] ?? 0);

    // Early return if invalid resource ID
    if ($resource_id <= 0) {
        header("Location: ../dashboard/file_upload.php");
        exit();
    }

    // 1. Verify ownership before deleting
    $verifyOwnershipQuery = "SELECT file_path FROM resources WHERE id = ? AND user_id = ? LIMIT 1";
    $verifyResult = db_query($verifyOwnershipQuery, [$resource_id, $current_user_id], "ii");
    $resourceData = $verifyResult ? $verifyResult->fetch_assoc() : null;

    // Early return if unauthorized
    if (!$resourceData) {
        $_SESSION['error'] = "Resource not found or unauthorized.";
        header("Location: ../dashboard/file_upload.php");
        exit();
    }
    
    $absolute_file_path = __DIR__ . '/../../' . $resourceData['file_path'];
    
    // 2. Delete record from DB
    $deleteQuery = "DELETE FROM resources WHERE id = ? AND user_id = ?";
    $deleteResult = db_query($deleteQuery, [$resource_id, $current_user_id], "ii");
    
    if ($deleteResult) {
        // 3. Delete physical file
        if (file_exists($absolute_file_path)) {
            unlink($absolute_file_path);
        }
        $_SESSION['success'] = "Resource deleted successfully.";
    } else {
        $_SESSION['error'] = "Database error while deleting.";
    }
}

header("Location: ../dashboard/file_upload.php");
exit();
?>
