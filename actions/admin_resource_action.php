<?php
require_once(__DIR__ . '/../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../includes/db_connect.php');
require_once(__DIR__ . '/../includes/csrf.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate_or_die();
    $action_type = $_POST['action_type'] ?? '';
    $resource_id = (int)($_POST['resource_id'] ?? 0);

    if ($action_type === 'delete' && $resource_id > 0) {
        $verifyQuery = "SELECT file_path FROM resources WHERE id = ?";
        $verifyResult = db_query($verifyQuery, [$resource_id], "i");
        $resourceData = $verifyResult ? $verifyResult->fetch_assoc() : null;

        if ($resourceData) {
            $deleteResult = db_query("DELETE FROM resources WHERE id = ?", [$resource_id], "i");
            if ($deleteResult) {
                $absolute_file_path = __DIR__ . '/../' . $resourceData['file_path'];
                if (file_exists($absolute_file_path)) {
                    unlink($absolute_file_path);
                }
                $_SESSION['success'] = "Resource successfully deleted by admin.";
            } else {
                $_SESSION['error'] = "Failed to delete resource.";
            }
        }
    }
}

header("Location: ../dashboard/admin.php");
exit();
?>
