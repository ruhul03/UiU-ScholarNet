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
    }
}
?>
<header class="dash-header">
    <div class="search-container">
        <i class="fa-solid fa-magnifying-glass search-icon"></i>
        <input type="text" placeholder="Search UIU ScholarNet...">
    </div>
    <div class="header-actions" style="display: flex; align-items: center; justify-content: flex-end; gap: 1.5rem;">
        <a href="notifications.php" class="notification-icon" style="color: inherit; text-decoration: none; position: relative; display: flex; align-items: center; height: 36px;">
            <i class="fa-regular fa-bell header-icon" style="font-size: 1.2rem; line-height: 1;"></i>
            <?php if (($collab_requests ?? 0) > 0 || ($pending_tasks ?? 0) > 0): ?>
                <span class="notification-dot" style="top: 2px; right: -2px; position: absolute; width: 10px; height: 10px; background: var(--secondary-color); border-radius: 50%; border: 2px solid #fff;"></span>
            <?php endif; ?>
        </a>
        <a href="profile.php" style="color: inherit; text-decoration: none; display: flex; align-items: center;">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name'] ?? 'User'); ?>&background=0a1128&color=fff" alt="Avatar" style="width: 36px; height: 36px; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: block;">
        </a>
    </div>
</header>
