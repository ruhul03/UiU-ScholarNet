<!-- Sidebar Component -->
<?php
if (!isset($unread_messages_count)) {
    $um_stmt = $conn->prepare("SELECT COUNT(*) as total FROM messages WHERE receiver_id = ? AND is_read = 0");
    $um_stmt->bind_param("i", $user_id);
    $um_stmt->execute();
    $unread_messages_count = (int)($um_stmt->get_result()->fetch_assoc()['total'] ?? 0);
}
$is_admin_sidebar = isset($user_data['role']) && $user_data['role'] === 'admin';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="logo"><?php echo $is_admin_sidebar ? 'Admin Console' : 'UIU ScholarNet'; ?></div>
    <div class="sidebar-subtitle"><?php echo $is_admin_sidebar ? 'PLATFORM MANAGEMENT' : 'RESEARCH & COLLABORATION'; ?></div>

    <nav class="sidebar-menu">
        <?php if ($is_admin_sidebar): ?>
            <a href="admin.php#admin-overview" class="menu-item <?php echo ($current_page == 'admin.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge-high"></i> Overview
            </a>
            <a href="admin.php#admin-users" class="menu-item">
                <i class="fa-solid fa-users-gear"></i> User Management
            </a>
            <a href="admin.php#admin-data" class="menu-item">
                <i class="fa-solid fa-table-list"></i> Platform Data
            </a>
            <a href="notifications.php" class="menu-item <?php echo ($current_page == 'notifications.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-bell"></i> Notifications
            </a>
        <?php else: ?>
            <a href="index.php" class="menu-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-house"></i> Dashboard
            </a>
            <a href="collaboration.php" class="menu-item <?php echo ($current_page == 'collaboration.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-magnifying-glass"></i> Collaboration Finder
            </a>
            <a href="projects.php" class="menu-item <?php echo ($current_page == 'projects.php' || $current_page == 'edit_project.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-folder"></i> Projects
            </a>
            <a href="tasks.php" class="menu-item <?php echo ($current_page == 'tasks.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-square-check"></i> Tasks
            </a>
            <a href="documents.php" class="menu-item <?php echo ($current_page == 'documents.php' || $current_page == 'document_editor.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-file-lines"></i> Document Editor
            </a>
            <a href="messages.php" class="menu-item <?php echo ($current_page == 'messages.php' && (!isset($_GET['channel']) || $_GET['channel'] !== 'discussion')) ? 'active' : ''; ?>">
                <i class="fa-solid fa-message"></i> Messages
                <?php if (($unread_messages_count ?? 0) > 0): ?>
                    <span class="notification-dot notification-dot-sidebar"></span>
                <?php endif; ?>
            </a>
            <a href="file_upload.php" class="menu-item <?php echo ($current_page == 'file_upload.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-paperclip"></i> File Upload
            </a>
            <a href="resources.php" class="menu-item <?php echo ($current_page == 'resources.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-book"></i> Resource Hub
            </a>
            <a href="preprints.php" class="menu-item <?php echo ($current_page == 'preprints.php' || $current_page == 'preprint_details.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-file-pdf"></i> Preprints
            </a>
            <a href="reputation.php" class="menu-item <?php echo ($current_page == 'reputation.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-award"></i> Reputation
            </a>
            <a href="research_discussion.php" class="menu-item <?php echo ($current_page == 'research_discussion.php' || $current_page == 'discussion_thread.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-comments"></i> Research Discussion
            </a>
            <a href="profile.php" class="menu-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user"></i> My Profile
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="menu-item logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        
        <div class="user-profile-small">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name']); ?>&background=0a1128&color=fff" alt="User">
            <div>
                <div class="user-name"><?php echo htmlspecialchars($user_data['full_name']); ?></div>
                <div class="user-role text-0-75rem fw-bold mt-2px">
                    <?php if ($user_data['role'] === 'admin'): ?>
                        <span class="color-admin">ADMIN</span>
                    <?php elseif ($user_data['role'] === 'faculty'): ?>
                        <span class="<?php echo isset($user_data['is_verified']) && $user_data['is_verified'] ? 'color-faculty-verified' : 'color-faculty-unverified'; ?>">
                            <?php echo isset($user_data['is_verified']) && $user_data['is_verified'] ? 'VERIFIED FACULTY' : 'UNVERIFIED FACULTY'; ?>
                        </span>
                    <?php else: ?>
                        <span class="color-student">STUDENT</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</aside>

<?php
// Ensure table exists for notifications
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) DEFAULT 'system',
    title VARCHAR(255) NOT NULL,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    link VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

// Handle mark as read
if (isset($_GET['mark_read'])) {
    if ($_GET['mark_read'] === 'all') {
        $mrStmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $mrStmt->bind_param("i", $user_id);
        $mrStmt->execute();
    } else {
        $notif_id = (int)$_GET['mark_read'];
        $mrStmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $mrStmt->bind_param("ii", $notif_id, $user_id);
        $mrStmt->execute();
    }
    // Remove query param to prevent resubmission
    $url = strtok($_SERVER["REQUEST_URI"], '?');
    header("Location: " . $url);
    exit;
}

// Fetch latest notifications for the popup
$notifStmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$notifStmt->bind_param("i", $user_id);
$notifStmt->execute();
$popup_notifs = $notifStmt->get_result();

if (!function_exists('getSidebarNotifIcon')) {
    function getSidebarNotifIcon($type) {
        switch ($type) {
            case 'message': return '<i class="fa-solid fa-envelope"></i>';
            case 'reputation': return '<i class="fa-solid fa-trophy"></i>';
            case 'collaboration': return '<i class="fa-solid fa-handshake"></i>';
            default: return '<i class="fa-solid fa-bell"></i>';
        }
    }
}
?>
<!-- Notification Popup Container -->
<div id="notificationPopup" class="popup-container">
    <div class="popup-header">
        <h3 class="popup-title">Notifications</h3>
        <a href="?mark_read=all" class="popup-link">Mark all read</a>
    </div>
    <div class="popup-body">
        <?php if ($popup_notifs && $popup_notifs->num_rows > 0): ?>
            <?php while($n = $popup_notifs->fetch_assoc()): ?>
                <div class="popup-item <?php echo $n['is_read'] ? 'read' : 'unread'; ?>">
                    <div class="popup-item-title"><?php echo getSidebarNotifIcon($n['type']); ?> <?php echo htmlspecialchars($n['title']); ?></div>
                    <div class="popup-item-desc"><?php echo htmlspecialchars($n['message']); ?></div>
                    <div class="popup-item-meta">
                        <span class="popup-item-time"><?php echo date('M j, g:i a', strtotime($n['created_at'])); ?></span>
                        <?php if ($n['link']): ?>
                            <a href="<?php echo htmlspecialchars($n['link']); ?>" class="popup-item-action">View</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="popup-empty">
                <i class="fa-regular fa-bell-slash popup-empty-icon"></i>
                No new notifications.
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notifIcons = document.querySelectorAll('.notification-icon');
    const popup = document.getElementById('notificationPopup');
    
    notifIcons.forEach(icon => {
        icon.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Position near icon
            const rect = this.getBoundingClientRect();
            popup.style.top = (rect.bottom + 15) + 'px';
            popup.style.right = (window.innerWidth - rect.right - 10) + 'px';
            
            if (popup.style.display === 'none') {
                popup.style.display = 'block';
            } else {
                popup.style.display = 'none';
            }
        });
    });
    
    document.addEventListener('click', function(e) {
        if (popup && popup.style.display === 'block' && !popup.contains(e.target) && !e.target.closest('.notification-icon')) {
            popup.style.display = 'none';
        }
    });
});
</script>
