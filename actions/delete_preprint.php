<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_SESSION['user_id'];
    $preprint_id = (int)($_POST['preprint_id'] ?? 0);

    if ($preprint_id > 0) {
        // Verify ownership
        $stmt = $conn->prepare("SELECT file_path FROM preprints WHERE id = ? AND author_id = ?");
        $stmt->bind_param("ii", $preprint_id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $row = $res->fetch_assoc();
            $file_path = '../' . $row['file_path'];

            // Delete from database
            $del_stmt = $conn->prepare("DELETE FROM preprints WHERE id = ?");
            $del_stmt->bind_param("i", $preprint_id);
            
            if ($del_stmt->execute()) {
                // Delete file from disk if exists
                if (!empty($row['file_path']) && file_exists($file_path)) {
                    unlink($file_path);
                }
                $_SESSION['success'] = "Preprint deleted successfully.";
            } else {
                $_SESSION['error'] = "Failed to delete preprint.";
            }
        } else {
            $_SESSION['error'] = "Unauthorized or preprint not found.";
        }
    }
}

header("Location: ../dashboard/preprints.php");
exit();
?>
