<?php
require_once('../includes/db_connect.php');
require_once('../includes/auth_check.php');
require_once('../includes/layout.php');

// Handle new thread submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && isset($_POST['content'])) {
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));
    $user_id = $_SESSION['user_id'];

    if (!empty($title) && !empty($content)) {
        $query = "INSERT INTO discussion_threads (user_id, title, content) VALUES ('$user_id', '$title', '$content')";
        if (mysqli_query($conn, $query)) {
            // Give reputation point for starting a discussion
            mysqli_query($conn, "UPDATE users SET reputation = reputation + 2 WHERE id = '$user_id'");
            
            // Add notification
            $notif_title = mysqli_real_escape_string($conn, "New Discussion Thread");
            $notif_msg = mysqli_real_escape_string($conn, "You started a new research discussion.");
            mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message) VALUES ('$user_id', 'system', '$notif_title', '$notif_msg')");
            
            header("Location: research_discussion.php?success=1");
            exit;
        }
    }
}

// Fetch all threads with reply count
$query = "
    SELECT t.*, u.full_name, u.role, 
           (SELECT COUNT(*) FROM discussion_replies r WHERE r.thread_id = t.id) as reply_count
    FROM discussion_threads t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
";
$result = mysqli_query($conn, $query);

layout_header("Research Discussion | UIU ScholarNet");
?>

    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <header class="dash-header dash-header-resources">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" placeholder="Search discussions...">
            </div>
            <div class="nav-actions">
                <a href="notifications.php" class="notification-icon">
                    <i class="fa-regular fa-bell header-icon"></i>
                </a>
                <a href="profile.php" class="btn btn-outline"><i class="fa-regular fa-user"></i> Account</a>
            </div>
        </header>

        <div class="discussion-container">
            <div class="discussion-header">
                <div>
                    <h1 style="font-size: 2.2rem; font-family: var(--font-heading); color: var(--primary-color);">Research Discussion</h1>
                    <p style="opacity: 0.7; margin-top: 0.5rem;">Share ideas, ask questions, and collaborate openly.</p>
                </div>
                <button class="btn btn-primary" onclick="document.getElementById('newThreadForm').style.display='block'">+ New Topic</button>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div style="background: #e6f4ea; color: #1e8e3e; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    Discussion thread posted successfully!
                </div>
            <?php endif; ?>

            <div id="newThreadForm" style="display: none; background: #fdfcf8; padding: 2rem; border-radius: 8px; border: 1px solid #eee; margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem;">Start a New Topic</h3>
                <form method="POST" action="research_discussion.php">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <input type="text" name="title" placeholder="Discussion Title" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <textarea name="content" rows="4" placeholder="What's on your mind?" required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"></textarea>
                    </div>
                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" class="btn btn-primary">Post Topic</button>
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('newThreadForm').style.display='none'">Cancel</button>
                    </div>
                </form>
            </div>

            <div class="thread-list">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($thread = mysqli_fetch_assoc($result)): ?>
                        <a href="discussion_thread.php?id=<?php echo $thread['id']; ?>" class="thread-item">
                            <div class="thread-main">
                                <div class="thread-title"><?php echo htmlspecialchars($thread['title']); ?></div>
                                <div class="thread-meta">
                                    <span><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($thread['full_name']); ?> (<?php echo ucfirst($thread['role']); ?>)</span>
                                    <span><i class="fa-solid fa-clock"></i> <?php echo date('M d, Y H:i', strtotime($thread['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="thread-stats">
                                <div class="stat-box">
                                    <span><?php echo $thread['reply_count']; ?></span>
                                    <small>Replies</small>
                                </div>
                                <div class="stat-box">
                                    <span><?php echo $thread['views']; ?></span>
                                    <small>Views</small>
                                </div>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; padding: 3rem; color: #888;">No discussions yet. Be the first to start a topic!</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
