<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

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
        $stmt = $conn->prepare("SELECT file_path FROM resources WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->bind_param("ii", $resource_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if ($res) {
            $file_path = '../' . $res['file_path'];
            
            // Delete record from DB
            $delStmt = $conn->prepare("DELETE FROM resources WHERE id = ? AND user_id = ?");
            $delStmt->bind_param("ii", $resource_id, $user_id);
            
            if ($delStmt->execute()) {
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
