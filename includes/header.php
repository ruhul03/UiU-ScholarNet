<?php
// includes/header.php

// Ensure pending tasks / collab requests are available for the notification dot
if (!isset($pending_tasks) || !isset($collab_requests)) {
    $user_id = $_SESSION['user_id'] ?? 0;
    if ($user_id > 0 && isset($conn)) {
        if (!isset($pending_tasks)) {
            $pt_stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status != 'done'");
            $pt_stmt->bind_param("i", $user_id);
            $pt_stmt->execute();
            $pending_tasks = (int)($pt_stmt->get_result()->fetch_assoc()['total'] ?? 0);
        }
        if (!isset($collab_requests)) {
            $cr_stmt = $conn->prepare("SELECT COUNT(*) as total FROM collaboration_applications ca JOIN collaboration_posts cp ON ca.post_id = cp.id WHERE cp.user_id = ? AND ca.status = 'pending'");
            $cr_stmt->bind_param("i", $user_id);
            $cr_stmt->execute();
            $collab_requests = (int)($cr_stmt->get_result()->fetch_assoc()['total'] ?? 0);
        }
        if (!isset($unread_notifications)) {
            $un_stmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
            $un_stmt->bind_param("i", $user_id);
            $un_stmt->execute();
            $unread_notifications = (int)($un_stmt->get_result()->fetch_assoc()['total'] ?? 0);
        }
    }
}
?>
<header class="dash-header">
    <div class="search-container">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" placeholder="Search UIU ScholarNet...">
    </div>
    <div class="header-actions flex-center-end">
        <?php if ((!isset($is_admin_panel) || !$is_admin_panel) && (!isset($is_admin_sidebar) || !$is_admin_sidebar)): ?>
        <a href="notifications.php" class="notification-icon text-inherit relative flex-center h-36px">
            <i class="fa-regular fa-bell header-icon text-1-2rem"></i>
            <?php if (($collab_requests ?? 0) > 0 || ($pending_tasks ?? 0) > 0 || ($unread_notifications ?? 0) > 0): ?>
                <span class="notification-dot notification-dot-header"></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        <div class="flex-column-end">
            <span class="fw-600 text-0-8rem text-color-primary"><?php echo htmlspecialchars($user_data['full_name'] ?? 'User'); ?></span>
            <?php if (isset($user_data['role'])): ?>
                <?php if ($user_data['role'] === 'admin'): ?>
                    <span class="role-admin">ADMIN</span>
                <?php elseif ($user_data['role'] === 'faculty' && isset($user_data['is_verified']) && $user_data['is_verified']): ?>
                    <span class="role-faculty"><i class="fa-solid fa-circle-check"></i> FACULTY</span>
                <?php elseif ($user_data['role'] === 'faculty' && isset($user_data['is_verified']) && !$user_data['is_verified']): ?>
                    <span class="role-unverified">UNVERIFIED</span>
                <?php else: ?>
                    <span class="role-default"><?php echo htmlspecialchars($user_data['role']); ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <a href="profile.php" class="text-inherit flex-center">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name'] ?? 'User'); ?>&background=0a1128&color=fff" alt="Avatar" class="avatar-header">
        </a>
    </div>
</header>
