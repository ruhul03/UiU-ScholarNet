<?php
require_once('../includes/db_connect.php');
require_once('../includes/auth_check.php');
require_once('../includes/layout.php');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: research_discussion.php");
    exit;
}

$thread_id = (int)$_GET['id'];

// Handle new reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));
    $user_id = $_SESSION['user_id'];

    if (!empty($content)) {
        $query = "INSERT INTO discussion_replies (thread_id, user_id, content) VALUES ('$thread_id', '$user_id', '$content')";
        if (mysqli_query($conn, $query)) {
            // Give reputation point for participating
            mysqli_query($conn, "UPDATE users SET reputation = reputation + 1 WHERE id = '$user_id'");
            
            // Notify thread owner if it's someone else replying
            $thread_owner_query = mysqli_query($conn, "SELECT user_id, title FROM discussion_threads WHERE id = '$thread_id'");
            $thread_owner = mysqli_fetch_assoc($thread_owner_query);
            if ($thread_owner && $thread_owner['user_id'] != $user_id) {
                $owner_id = $thread_owner['user_id'];
                $notif_title = mysqli_real_escape_string($conn, "New Reply on Your Topic");
                $notif_msg = mysqli_real_escape_string($conn, "Someone replied to your topic: " . $thread_owner['title']);
                mysqli_query($conn, "INSERT INTO notifications (user_id, type, title, message) VALUES ('$owner_id', 'collaboration', '$notif_title', '$notif_msg')");
            }
            
            header("Location: discussion_thread.php?id=$thread_id&success=1");
            exit;
        }
    }
}

// Update views
mysqli_query($conn, "UPDATE discussion_threads SET views = views + 1 WHERE id = '$thread_id'");

// Fetch thread details
$query = "
    SELECT t.*, u.full_name, u.role
    FROM discussion_threads t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = '$thread_id'
";
$result = mysqli_query($conn, $query);
$thread = mysqli_fetch_assoc($result);

if (!$thread) {
    header("Location: research_discussion.php");
    exit;
}

// Fetch replies
$replies_query = "
    SELECT r.*, u.full_name, u.role
    FROM discussion_replies r
    JOIN users u ON r.user_id = u.id
    WHERE r.thread_id = '$thread_id'
    ORDER BY r.created_at ASC
";
$replies = mysqli_query($conn, $replies_query);

layout_header("Topic: " . htmlspecialchars($thread['title']) . " | UIU ScholarNet");
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
            <a href="research_discussion.php" style="display: inline-block; margin-bottom: 1.5rem; color: #666; text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Back to Discussions</a>
            
            <div class="discussion-header" style="flex-direction: column; align-items: flex-start; gap: 0.5rem;">
                <h1 style="font-size: 2rem; font-family: var(--font-heading); color: var(--primary-color);"><?php echo htmlspecialchars($thread['title']); ?></h1>
                <div class="thread-meta">
                    <span><i class="fa-solid fa-clock"></i> Started <?php echo date('M d, Y H:i', strtotime($thread['created_at'])); ?></span>
                    <span><i class="fa-solid fa-eye"></i> <?php echo $thread['views']; ?> Views</span>
                </div>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div style="background: #e6f4ea; color: #1e8e3e; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                    Reply posted successfully!
                </div>
            <?php endif; ?>

            <div class="post-list">
                <!-- Original Post -->
                <div class="post-item" style="border-left: 4px solid var(--primary-color);">
                    <div class="post-sidebar">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($thread['full_name']); ?>&background=0a1128&color=fff" alt="Avatar" class="post-avatar">
                        <div class="post-author"><?php echo htmlspecialchars($thread['full_name']); ?></div>
                        <div class="post-role"><?php echo ucfirst($thread['role']); ?></div>
                    </div>
                    <div class="post-content">
                        <div class="post-meta">
                            <span>Posted on <?php echo date('M d, Y H:i', strtotime($thread['created_at'])); ?></span>
                        </div>
                        <div class="post-text">
                            <?php echo nl2br(htmlspecialchars($thread['content'])); ?>
                        </div>
                    </div>
                </div>

                <!-- Replies -->
                <?php while ($reply = mysqli_fetch_assoc($replies)): ?>
                    <div class="post-item">
                        <div class="post-sidebar">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($reply['full_name']); ?>&background=e2e8f0&color=333" alt="Avatar" class="post-avatar">
                            <div class="post-author"><?php echo htmlspecialchars($reply['full_name']); ?></div>
                            <div class="post-role"><?php echo ucfirst($reply['role']); ?></div>
                        </div>
                        <div class="post-content">
                            <div class="post-meta">
                                <span>Replied on <?php echo date('M d, Y H:i', strtotime($reply['created_at'])); ?></span>
                            </div>
                            <div class="post-text">
                                <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="reply-box">
                <h3 style="margin-bottom: 1rem;">Post a Reply</h3>
                <form method="POST" action="discussion_thread.php?id=<?php echo $thread_id; ?>">
                    <div class="form-group" style="margin-bottom: 1rem;">
                        <textarea name="content" rows="4" placeholder="Write your reply here..." required style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px; resize: vertical;"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Reply</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
