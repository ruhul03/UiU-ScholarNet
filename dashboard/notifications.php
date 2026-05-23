<?php
require_once('../includes/auth_check.php');
require_once('../includes/csrf.php');

// Create notifications table if it doesn't exist
$conn->query(
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(50) DEFAULT 'system',
        title VARCHAR(255) NOT NULL,
        message TEXT,
        is_read TINYINT(1) DEFAULT 0,
        link VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
);

// If action to mark as read is triggered
if (isset($_GET['mark_read'])) {
    $notif_id = (int)$_GET['mark_read'];
    if ($notif_id > 0) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notif_id, $user_id);
        $stmt->execute();
    } else if ($_GET['mark_read'] === 'all') {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    header("Location: notifications.php");
    exit();
}

// Ensure there are some dummy notifications for the beginner dev to see if empty
$checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?");
$checkStmt->bind_param("i", $user_id);
$checkStmt->execute();
$count = $checkStmt->get_result()->fetch_assoc()['count'];

if ($count == 0) {
    // Insert some welcome notifications
    $notifs = [
        ['system', 'Welcome to UIU ScholarNet!', 'Your account has been successfully created. Explore the directory and find collaborators.', 'profile.php'],
        ['reputation', 'Reputation System Active', 'You can now earn points by collaborating and publishing preprints.', 'reputation.php'],
        ['message', 'Join the General Discussion', 'Introduce yourself in the General Discussion channel.', 'messages.php?channel=general']
    ];
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)");
    foreach ($notifs as $n) {
        $stmt->bind_param("issss", $user_id, $n[0], $n[1], $n[2], $n[3]);
        $stmt->execute();
    }
}

// Fetch all notifications
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
if ($filter === 'unread') {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
} else {
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

function getNotifIcon($type) {
    switch ($type) {
        case 'message': return '<i class="fa-solid fa-envelope"></i>';
        case 'reputation': return '<i class="fa-solid fa-trophy"></i>';
        case 'collaboration': return '<i class="fa-solid fa-handshake"></i>';
        default: return '<i class="fa-solid fa-bell"></i>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | UIU ScholarNet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
</head>
<body class="dashboard-page">
    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <header class="dash-header">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" placeholder="Search archive...">
            </div>
            <div class="header-actions">
                <a href="settings.php" style="color: inherit;"><i class="fa-solid fa-gear"></i></a>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name']); ?>&background=0a1128&color=fff" class="header-avatar">
            </div>
        </header>

        <section class="page-heading" style="display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <h1>Notifications</h1>
                <p>Stay updated with your research network.</p>
            </div>
            <a href="?mark_read=all" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.8rem;">Mark all as read</a>
        </section>

        <div class="notifications-container">
            <div class="notification-filters">
                <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                <a href="?filter=unread" class="filter-btn <?php echo $filter === 'unread' ? 'active' : ''; ?>">Unread</a>
            </div>

            <div class="notification-list">
                <?php if ($notifications && $notifications->num_rows > 0): ?>
                    <?php while($notif = $notifications->fetch_assoc()): ?>
                        <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                            <div class="notif-icon <?php echo htmlspecialchars($notif['type']); ?>">
                                <?php echo getNotifIcon($notif['type']); ?>
                            </div>
                            <div class="notif-content">
                                <div class="notif-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                                <div class="notif-desc"><?php echo htmlspecialchars($notif['message']); ?></div>
                                <div class="notif-meta">
                                    <span><i class="fa-regular fa-clock"></i> <?php echo date('M j, Y, g:i a', strtotime($notif['created_at'])); ?></span>
                                    <?php if (!$notif['is_read']): ?>
                                        <span style="color: var(--secondary-color); font-weight: 800;">• NEW</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="notif-actions">
                                <?php if (!$notif['is_read']): ?>
                                    <a href="?mark_read=<?php echo $notif['id']; ?>" style="margin-right: 1rem;"><i class="fa-solid fa-check"></i> Mark Read</a>
                                <?php endif; ?>
                                <?php if ($notif['link']): ?>
                                    <a href="<?php echo htmlspecialchars($notif['link']); ?>"><i class="fa-solid fa-arrow-right"></i> View</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 4rem; opacity: 0.5;">
                        <i class="fa-regular fa-bell-slash" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>No notifications found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
</body>
</html>
