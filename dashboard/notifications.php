<?php
require_once('../includes/auth_check.php');

// Mark all unread notifications as read
$upd = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
$upd->bind_param("i", $user_id);
$upd->execute();

// Fetch notifications
$notifications = db_query("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100", [$user_id], "i");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | UIU ScholarNet</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php include('../includes/header.php'); ?>

        <div class="notifications-container">
            <div class="page-header">
                <h1>Your Notifications</h1>
                <p class="text-light">Stay updated on your projects and tasks.</p>
            </div>

            <?php if ($notifications->num_rows === 0): ?>
                <div class="empty-state">
                    <i class="fa-regular fa-bell-slash"></i>
                    <h3>No Notifications Yet</h3>
                    <p>You're all caught up! When something happens, you'll see it here.</p>
                </div>
            <?php else: ?>
                <?php while ($notif = $notifications->fetch_assoc()): ?>
                    <div class="notif-card">
                        <div class="notif-icon">
                            <i class="fa-solid fa-circle-info"></i>
                        </div>
                        <div class="notif-content">
                            <div class="notif-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                            <div class="notif-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                            <div class="notif-time"><i class="fa-regular fa-clock"></i> <?php echo date('M d, Y g:i A', strtotime($notif['created_at'])); ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
