<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');
require_once(__DIR__ . '/../../includes/preprint_moderation.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard/admin.php#admin-preprints');
    exit();
}

csrf_validate_or_die();
ensure_preprint_moderation_schema();

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$adminCheck = db_query("SELECT role FROM users WHERE id = ?", [$currentUserId], "i")->fetch_assoc();

if (!$adminCheck || $adminCheck['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized action.";
    header("Location: ../dashboard/index.php");
    exit();
}

$preprintId = (int)($_POST['preprint_id'] ?? 0);
$actionType = trim((string)($_POST['action_type'] ?? ''));

if ($preprintId <= 0 || !in_array($actionType, ['approve', 'reject', 'delete'], true)) {
    $_SESSION['error'] = "Invalid preprint action.";
    header("Location: ../dashboard/admin.php#admin-preprints");
    exit();
}

$preprintResult = db_query(
    "SELECT id, title, author_id, file_path, moderation_status FROM preprints WHERE id = ? LIMIT 1",
    [$preprintId],
    "i"
);
$preprint = $preprintResult ? $preprintResult->fetch_assoc() : null;

if (!$preprint) {
    $_SESSION['error'] = "Preprint not found.";
    header("Location: ../dashboard/admin.php#admin-preprints");
    exit();
}

if ($actionType === 'delete') {
    $absoluteFilePath = __DIR__ . '/../../' . $preprint['file_path'];

    db_query("DELETE FROM preprints WHERE id = ?", [$preprintId], "i");
    db_query("UPDATE reports SET status = 'resolved' WHERE item_type = 'preprint' AND item_id = ?", [$preprintId], "i");

    if (!empty($preprint['file_path']) && file_exists($absoluteFilePath)) {
        unlink($absoluteFilePath);
    }

    send_notification(
        $preprint['author_id'],
        "Preprint Deleted",
        "Your preprint titled '{$preprint['title']}' has been deleted by an administrator.",
        "../dashboard/preprints.php",
        "system"
    );

    $_SESSION['success'] = "Preprint deleted successfully.";
    header("Location: ../dashboard/admin.php#admin-preprints");
    exit();
}

$newStatus = $actionType === 'approve' ? 'approved' : 'rejected';
db_query(
    "UPDATE preprints SET moderation_status = ?, moderated_by = ?, moderated_at = NOW() WHERE id = ?",
    [$newStatus, $currentUserId, $preprintId],
    "sii"
);
db_query("UPDATE reports SET status = 'resolved' WHERE item_type = 'preprint' AND item_id = ?", [$preprintId], "i");

send_notification(
    $preprint['author_id'],
    "Preprint " . ucfirst($newStatus),
    "Your preprint titled '{$preprint['title']}' has been {$newStatus} by moderation.",
    "../dashboard/preprints.php",
    "system"
);

$_SESSION['success'] = $newStatus === 'approved'
    ? "Preprint approved successfully."
    : "Preprint rejected successfully.";

header("Location: ../dashboard/admin.php#admin-preprints");
exit();

