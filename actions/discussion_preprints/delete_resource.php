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
    $user_id = (int)$_SESSION['user_id'];
    $resource_id = (int)($_POST['resource_id'] ?? 0);

    if ($resource_id > 0) {
        // Verify ownership before deleting
        $resCheck = db_query("SELECT file_path FROM resources WHERE id = ? AND user_id = ? LIMIT 1", [$resource_id, $user_id], "ii");
        $res = $resCheck ? $resCheck->fetch_assoc() : null;

        if ($res) {
            $file_path = __DIR__ . '/../../' . $res['file_path'];
            
            // Delete record from DB
            $del = db_query("DELETE FROM resources WHERE id = ? AND user_id = ?", [$resource_id, $user_id], "ii");
            
            if ($del) {
                // Optionally delete physical file
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
                $_SESSION['success'] = "Resource deleted successfully.";
            } else {
                $_SESSION['error'] = "Database error while deleting.";
            }
        } else {
            $_SESSION['error'] = "Resource not found or unauthorized.";
        }
    }
}

header("Location: ../dashboard/file_upload.php");
exit();
?>
