<!-- Sidebar Component -->
<aside class="sidebar">
    <div class="logo">UIU ScholarNet</div>
    <div class="sidebar-subtitle">RESEARCH & COLLABORATION</div>

    <nav class="sidebar-menu">
        <a href="index.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>
        <a href="collaboration.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'collaboration.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-magnifying-glass"></i> Collaboration Finder
        </a>
        <a href="projects.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'projects.php' || basename($_SERVER['PHP_SELF']) == 'edit_project.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-folder"></i> Projects
        </a>
        <a href="tasks.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'tasks.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-square-check"></i> Tasks
        </a>
        <a href="document_editor.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'document_editor.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-lines"></i> Document Editor
        </a>
        <a href="messages.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'messages.php' && (!isset($_GET['channel']) || $_GET['channel'] !== 'discussion')) ? 'active' : ''; ?>">
            <i class="fa-solid fa-message"></i> Messages
        </a>
        <a href="file_upload.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'file_upload.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-paperclip"></i> File Upload
        </a>
        <a href="resources.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'resources.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-book"></i> Resource Hub
        </a>
        <a href="preprints.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'preprints.php' || basename($_SERVER['PHP_SELF']) == 'preprint_details.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-pdf"></i> Preprints
        </a>
        <a href="reputation.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'reputation.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-award"></i> Reputation
        </a>
        <a href="research_discussion.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'research_discussion.php' || basename($_SERVER['PHP_SELF']) == 'discussion_thread.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-comments"></i> Research Discussion
        </a>
        <a href="profile.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-user"></i> My Profile
        </a>
        <?php if (isset($user_data['role']) && $user_data['role'] === 'admin'): ?>
        <a href="admin.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'admin.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-shield-halved"></i> Admin Panel
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="menu-item logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        
        <div class="user-profile-small">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name']); ?>&background=0a1128&color=fff" alt="User">
            <div>
                <div class="user-name"><?php echo htmlspecialchars($user_data['full_name']); ?></div>
                <div class="user-role" style="font-size: 0.75rem; font-weight: bold; margin-top: 2px;">
                    <?php if ($user_data['role'] === 'admin'): ?>
                        <span style="color: #4f46e5;">ADMIN</span>
                    <?php elseif ($user_data['role'] === 'faculty'): ?>
                        <span style="color: <?php echo isset($user_data['is_verified']) && $user_data['is_verified'] ? '#1e8e3e' : '#d93025'; ?>;">
                            <?php echo isset($user_data['is_verified']) && $user_data['is_verified'] ? 'VERIFIED FACULTY' : 'UNVERIFIED FACULTY'; ?>
                        </span>
                    <?php else: ?>
                        <span style="color: #888;">STUDENT</span>
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
<div id="notificationPopup" style="display:none; position:fixed; right:30px; top:80px; width:320px; background:#fff; box-shadow:0 10px 30px rgba(0,0,0,0.15); border-radius:12px; z-index:9999; padding:15px; border:1px solid #eee;">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:10px;">
        <h3 style="margin:0; font-size:1.1rem; color:var(--text-color);">Notifications</h3>
        <a href="?mark_read=all" style="font-size:0.75rem; color:var(--primary-color); text-decoration:none;">Mark all read</a>
    </div>
    <div style="max-height: 350px; overflow-y: auto;">
        <?php if ($popup_notifs && $popup_notifs->num_rows > 0): ?>
            <?php while($n = $popup_notifs->fetch_assoc()): ?>
                <div style="padding:10px 0; border-bottom:1px solid #f5f5f5; <?php echo $n['is_read'] ? 'opacity:0.6;' : 'background:#f8f7f2; padding:10px; border-radius:8px; margin-bottom:5px;'; ?>">
                    <div style="font-size:0.85rem; color:#0a1128; font-weight:600;"><?php echo getSidebarNotifIcon($n['type']); ?> <?php echo htmlspecialchars($n['title']); ?></div>
                    <div style="font-size:0.75rem; color:#666; margin-top:5px; line-height:1.4;"><?php echo htmlspecialchars($n['message']); ?></div>
                    <div style="margin-top:8px; display:flex; justify-content:space-between;">
                        <span style="font-size:0.7rem; color:#999;"><?php echo date('M j, g:i a', strtotime($n['created_at'])); ?></span>
                        <?php if ($n['link']): ?>
                            <a href="<?php echo htmlspecialchars($n['link']); ?>" style="font-size:0.75rem; color:var(--secondary-color); text-decoration:none; font-weight:600;">View</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="font-size:0.85rem; color:#666; text-align:center; padding:30px 0;">
                <i class="fa-regular fa-bell-slash" style="font-size: 2rem; opacity:0.5; margin-bottom:10px; display:block;"></i>
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
