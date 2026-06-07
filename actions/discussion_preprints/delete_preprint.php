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
    $preprint_id = (int)($_POST['preprint_id'] ?? 0);

    // Early return if invalid preprint ID
    if ($preprint_id <= 0) {
        header("Location: ../dashboard/preprints.php");
        exit();
    }

    // 1. Verify ownership
    $verifyOwnershipQuery = "SELECT file_path FROM preprints WHERE id = ? AND author_id = ?";
    $verifyResult = db_query($verifyOwnershipQuery, [$preprint_id, $current_user_id], "ii");

    // Early return if not authorized or preprint not found
    if (!$verifyResult || $verifyResult->num_rows !== 1) {
        $_SESSION['error'] = "Unauthorized or preprint not found.";
        header("Location: ../dashboard/preprints.php");
        exit();
    }
    
    $preprintData = $verifyResult->fetch_assoc();
    $absolute_file_path = __DIR__ . '/../../' . $preprintData['file_path'];

    // 2. Delete from database
    $deleteQuery = "DELETE FROM preprints WHERE id = ?";
    $deleteResult = db_query($deleteQuery, [$preprint_id], "i");
    
    if ($deleteResult) {
        // 3. Delete file from disk if exists
        if (!empty($preprintData['file_path']) && file_exists($absolute_file_path)) {
            unlink($absolute_file_path);
        }
        $_SESSION['success'] = "Preprint deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete preprint.";
    }
}

header("Location: ../dashboard/preprints.php");
exit();
?>
