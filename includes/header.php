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
        <div style="display: flex; flex-direction: column; align-items: flex-end; justify-content: center; margin-right: 0.5rem; line-height: 1.2;">
            <span style="font-size: 0.8rem; font-weight: 600; color: var(--text-color);"><?php echo htmlspecialchars($user_data['full_name'] ?? 'User'); ?></span>
            <?php if (isset($user_data['role'])): ?>
                <?php if ($user_data['role'] === 'admin'): ?>
                    <span style="font-size: 0.65rem; color: #4f46e5; font-weight: 700; background: #eef2ff; padding: 0.1rem 0.4rem; border-radius: 4px;">ADMIN</span>
                <?php elseif ($user_data['role'] === 'faculty' && isset($user_data['is_verified']) && $user_data['is_verified']): ?>
                    <span style="font-size: 0.65rem; color: #1e8e3e; font-weight: 700; background: #e6f4ea; padding: 0.1rem 0.4rem; border-radius: 4px;"><i class="fa-solid fa-circle-check"></i> FACULTY</span>
                <?php elseif ($user_data['role'] === 'faculty' && isset($user_data['is_verified']) && !$user_data['is_verified']): ?>
                    <span style="font-size: 0.65rem; color: #d93025; font-weight: 700; background: #fce8e6; padding: 0.1rem 0.4rem; border-radius: 4px;">UNVERIFIED</span>
                <?php else: ?>
                    <span style="font-size: 0.65rem; color: var(--text-light); text-transform: uppercase;"><?php echo htmlspecialchars($user_data['role']); ?></span>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <a href="profile.php" style="color: inherit; text-decoration: none; display: flex; align-items: center;">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name'] ?? 'User'); ?>&background=0a1128&color=fff" alt="Avatar" style="width: 36px; height: 36px; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.1); display: block;">
        </a>
    </div>
</header>
