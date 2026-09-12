<?php
require_once('../includes/auth_check.php');

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
        <?php include('../includes/alerts.php'); ?>

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
                    <div class="notif-card <?php echo $notif['is_read'] == 0 ? 'unread' : ''; ?>" 
                         style="transition: transform 0.2s; cursor: pointer;" 
                         onmouseover="this.style.transform='scale(1.02)'" 
                         onmouseout="this.style.transform='scale(1)'"
                         data-id="<?php echo $notif['id']; ?>"
                         data-title="<?php echo htmlspecialchars($notif['title']); ?>"
                         data-message="<?php echo htmlspecialchars($notif['message']); ?>"
                         data-time="<?php echo date('M d, Y g:i A', strtotime($notif['created_at'])); ?>"
                         data-link="<?php echo htmlspecialchars($notif['link'] ?? ''); ?>"
                         onclick="openNotificationModal(this)">
                        <div class="notif-icon">
                            <i class="fa-solid fa-circle-info"></i>
                        </div>
                        <div class="notif-content">
                            <div class="notif-title">
                                <?php echo htmlspecialchars($notif['title']); ?>
                                <?php if ($notif['is_read'] == 0): ?>
                                    <span class="unread-dot"></span>
                                <?php endif; ?>
                            </div>
                            <div class="notif-message-preview"><?php echo htmlspecialchars($notif['message']); ?></div>
                            <div class="notif-time"><i class="fa-regular fa-clock"></i> <?php echo date('M d, Y g:i A', strtotime($notif['created_at'])); ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Notification Modal -->
    <div class="modal-overlay modal-hidden" id="notificationModal">
        <div class="modal-content modal-wide" style="max-width: 600px; padding: 2.5rem;">
            <i class="fa-solid fa-xmark modal-close" onclick="closeNotificationModal()"></i>
            
            <div class="view-notification-header">
                <div class="view-notification-icon">
                    <i class="fa-solid fa-circle-info"></i>
                </div>
                <div>
                    <div class="view-notification-title" id="modalNotifTitle">Title</div>
                    <div class="view-notification-time" id="modalNotifTime"><i class="fa-regular fa-clock"></i> Time</div>
                </div>
            </div>
            
            <div class="view-notification-body" id="modalNotifMessage" style="margin-top: 1.5rem; font-size: 1.05rem; line-height: 1.6;">
                Message
            </div>

            <div class="view-notification-actions" id="modalNotifActions" style="margin-top: 2rem; display: none;">
                <a href="#" id="modalNotifLink" class="btn btn-primary">
                    View Related Item <i class="fa-solid fa-arrow-right" style="margin-left: 0.5rem;"></i>
                </a>
            </div>
        </div>
    </div>

    <script>
        function openNotificationModal(element) {
            const id = element.getAttribute('data-id');
            const title = element.getAttribute('data-title');
            const message = element.getAttribute('data-message');
            const time = element.getAttribute('data-time');
            const link = element.getAttribute('data-link');

            // Populate modal
            document.getElementById('modalNotifTitle').innerText = title;
            document.getElementById('modalNotifMessage').innerText = message;
            document.getElementById('modalNotifTime').innerHTML = '<i class="fa-regular fa-clock"></i> ' + time;
            
            if (link) {
                document.getElementById('modalNotifActions').style.display = 'block';
                document.getElementById('modalNotifLink').href = link;
            } else {
                document.getElementById('modalNotifActions').style.display = 'none';
            }

            // Show modal
            document.getElementById('notificationModal').classList.remove('modal-hidden');

            // Mark as read via AJAX if it's unread
            if (element.classList.contains('unread')) {
                fetch('../actions/mark_notification_read.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                }).then(res => res.json()).then(data => {
                    if (data.success) {
                        element.classList.remove('unread');
                        const dot = element.querySelector('.unread-dot');
                        if (dot) dot.remove();
                        
                        // Update header dot if it exists and we're the last unread
                        const unreadCount = document.querySelectorAll('.notif-card.unread').length;
                        if (unreadCount === 0) {
                            const headerDot = document.querySelector('.notification-dot-header');
                            if (headerDot) headerDot.remove();
                        }
                    }
                }).catch(err => console.error(err));
            }
        }

        function closeNotificationModal() {
            document.getElementById('notificationModal').classList.add('modal-hidden');
        }

        // Close on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                closeNotificationModal();
            }
        });
    </script>
</body>
</html>
