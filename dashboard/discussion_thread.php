<?php
require_once('../includes/db_connect.php');
require_once('../includes/auth_check.php');
require_once('../includes/layout.php');
require_once('../includes/csrf.php');

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
        <?php include('../includes/header.php'); ?>

        <div class="discussion-container">
            <a href="research_discussion.php" style="display: inline-block; margin-bottom: 1.5rem; color: #666; text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Back to Discussions</a>
            
            <div class="discussion-header" style="flex-direction: row; justify-content: space-between; align-items: center;">
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <h1 style="font-size: 2rem; font-family: var(--font-heading); color: var(--primary-color);"><?php echo htmlspecialchars($thread['title']); ?></h1>
                    <div class="thread-meta">
                        <span><i class="fa-solid fa-clock"></i> Started <?php echo date('M d, Y H:i', strtotime($thread['created_at'])); ?></span>
                        <span><i class="fa-solid fa-eye"></i> <?php echo $thread['views']; ?> Views</span>
                    </div>
                </div>
                <?php if ($thread['user_id'] == $_SESSION['user_id']): ?>
                    <form method="POST" action="../actions/delete_discussion_thread.php" onsubmit="return confirm('Are you sure you want to delete this topic completely?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="thread_id" value="<?php echo $thread['id']; ?>">
                        <button type="submit" class="btn btn-outline" style="color: #ef4444; border-color: #ef4444;">
                            <i class="fa-solid fa-trash"></i> Delete Topic
                        </button>
                    </form>
                <?php endif; ?>
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
                        <div class="post-meta" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-size: 0.8rem; color: #666;"><i class="fa-solid fa-clock"></i> Posted on <?php echo date('M d, Y H:i', strtotime($thread['created_at'])); ?></span>
                            <?php if ($thread['user_id'] == $_SESSION['user_id']): ?>
                                <button type="button" class="btn btn-outline" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;" onclick="document.getElementById('edit-thread').style.display='block'; document.getElementById('view-thread').style.display='none';">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>
                            <?php endif; ?>
                        </div>
                        <div id="view-thread" class="post-text">
                            <?php echo nl2br(htmlspecialchars($thread['content'])); ?>
                        </div>
                        <?php if ($thread['user_id'] == $_SESSION['user_id']): ?>
                            <form id="edit-thread" method="POST" action="../actions/edit_discussion_thread.php" style="display: none; margin-top: 1rem;">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="thread_id" value="<?php echo $thread['id']; ?>">
                                <textarea name="content" rows="4" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 0.5rem;" required><?php echo htmlspecialchars($thread['content']); ?></textarea>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button type="submit" class="btn btn-primary" style="padding: 0.3rem 1rem; font-size: 0.8rem;">Save</button>
                                    <button type="button" class="btn btn-outline" style="padding: 0.3rem 1rem; font-size: 0.8rem;" onclick="document.getElementById('edit-thread').style.display='none'; document.getElementById('view-thread').style.display='block';">Cancel</button>
                                </div>
                            </form>
                        <?php endif; ?>
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
                            <div class="post-meta" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span style="font-size: 0.8rem; color: #666;"><i class="fa-solid fa-clock"></i> Replied on <?php echo date('M d, Y H:i', strtotime($reply['created_at'])); ?></span>
                                <?php if ($reply['user_id'] == $_SESSION['user_id']): ?>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button type="button" style="background: none; border: none; color: #666; cursor: pointer;" title="Edit" onclick="document.getElementById('edit-reply-<?php echo $reply['id']; ?>').style.display='block'; document.getElementById('view-reply-<?php echo $reply['id']; ?>').style.display='none';">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form method="POST" action="../actions/delete_discussion_reply.php" onsubmit="return confirm('Are you sure you want to delete this reply?');" style="margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                                            <input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>">
                                            <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer;" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div id="view-reply-<?php echo $reply['id']; ?>" class="post-text">
                                <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                            </div>
                            <?php if ($reply['user_id'] == $_SESSION['user_id']): ?>
                                <form id="edit-reply-<?php echo $reply['id']; ?>" method="POST" action="../actions/edit_discussion_reply.php" style="display: none; margin-top: 1rem;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                                    <input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>">
                                    <textarea name="content" rows="3" style="width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 0.5rem;" required><?php echo htmlspecialchars($reply['content']); ?></textarea>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <button type="submit" class="btn btn-primary" style="padding: 0.3rem 1rem; font-size: 0.8rem;">Save</button>
                                        <button type="button" class="btn btn-outline" style="padding: 0.3rem 1rem; font-size: 0.8rem;" onclick="document.getElementById('edit-reply-<?php echo $reply['id']; ?>').style.display='none'; document.getElementById('view-reply-<?php echo $reply['id']; ?>').style.display='block';">Cancel</button>
                                    </div>
                                </form>
                            <?php endif; ?>
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
