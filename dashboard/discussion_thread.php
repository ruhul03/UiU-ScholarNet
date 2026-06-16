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
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];

    if (!empty($content)) {
        // Insert reply
        if (db_query("INSERT INTO discussion_replies (thread_id, user_id, content) VALUES (?, ?, ?)", [$thread_id, $user_id, $content], "iis")) {
            // Give reputation point for participating
            db_query("UPDATE users SET reputation = reputation + 1 WHERE id = ?", [$user_id], "i");
            
            // Notify thread owner if it's someone else replying
            $thread_owner_result = db_query("SELECT user_id, title FROM discussion_threads WHERE id = ?", [$thread_id], "i");
            $thread_owner = $thread_owner_result->fetch_assoc();
            
            if ($thread_owner && $thread_owner['user_id'] != $user_id) {
                $owner_id = $thread_owner['user_id'];
                $notif_title = "New Reply on Your Topic";
                $notif_msg = "Someone replied to your topic: " . $thread_owner['title'];
                send_notification($owner_id, $notif_title, $notif_msg, "../dashboard/discussion_thread.php?id=" . $thread_id, "discussion");
            }
            
            header("Location: discussion_thread.php?id=$thread_id&success=1");
            exit;
        }
    }
}

// Update views
db_query("UPDATE discussion_threads SET views = views + 1 WHERE id = ?", [$thread_id], "i");

// Fetch thread details
$result = db_query("
    SELECT t.*, u.full_name, u.role
    FROM discussion_threads t
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ?
", [$thread_id], "i");
$thread = $result->fetch_assoc();

if (!$thread) {
    header("Location: research_discussion.php");
    exit;
}

// Fetch replies
$replies = db_query("
    SELECT r.*, u.full_name, u.role
    FROM discussion_replies r
    JOIN users u ON r.user_id = u.id
    WHERE r.thread_id = ?
    ORDER BY r.created_at ASC
", [$thread_id], "i");

layout_header("Topic: " . htmlspecialchars($thread['title']) . " | UIU ScholarNet");
?>

    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <?php include('../includes/header.php'); ?>
        <?php include('../includes/alerts.php'); ?>

        <div class="discussion-container">
            <a href="research_discussion.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Discussions</a>
            
            <div class="discussion-header flex-row-between-center">
                <div class="flex-col-gap-sm">
                    <h1 class="thread-title"><?php echo htmlspecialchars($thread['title']); ?></h1>
                    <div class="thread-meta">
                        <span><i class="fa-solid fa-clock"></i> Started <?php echo date('M d, Y H:i', strtotime($thread['created_at'])); ?></span>
                        <span><i class="fa-solid fa-eye"></i> <?php echo $thread['views']; ?> Views</span>
                    </div>
                </div>
                <?php if ($thread['user_id'] == $_SESSION['user_id']): ?>
                    <form method="POST" action="../actions/delete_discussion_thread.php" onsubmit="return confirm('Are you sure you want to delete this topic completely?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="thread_id" value="<?php echo $thread['id']; ?>">
                        <button type="submit" class="btn btn-outline btn-danger-outline">
                            <i class="fa-solid fa-trash"></i> Delete Topic
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert-success">
                    Reply posted successfully!
                </div>
            <?php endif; ?>

            <div class="post-list">
                <!-- Original Post -->
                <div class="post-item post-item-original">
                    <div class="post-sidebar">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($thread['full_name']); ?>&background=0a1128&color=fff" alt="Avatar" class="post-avatar">
                        <div class="post-author"><?php echo htmlspecialchars($thread['full_name']); ?></div>
                        <div class="post-role"><?php echo ucfirst($thread['role']); ?></div>
                    </div>
                    <div class="post-content">
                        <div class="post-meta-flex">
                            <span class="text-meta"><i class="fa-solid fa-clock"></i> Posted on <?php echo date('M d, Y H:i', strtotime($thread['created_at'])); ?></span>
                            <?php if ($thread['user_id'] == $_SESSION['user_id']): ?>
                                <button type="button" class="btn btn-outline btn-sm" onclick="document.getElementById('edit-thread').style.display='block'; document.getElementById('view-thread').style.display='none';">
                                    <i class="fa-solid fa-pen"></i> Edit
                                </button>
                            <?php endif; ?>
                        </div>
                        <div id="view-thread" class="post-text">
                            <?php echo nl2br(htmlspecialchars($thread['content'])); ?>
                        </div>
                        <?php if ($thread['user_id'] == $_SESSION['user_id']): ?>
                            <form id="edit-thread" method="POST" action="../actions/edit_discussion_thread.php" style="display: none;" class="margin-top-md">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="thread_id" value="<?php echo $thread['id']; ?>">
                                <textarea name="content" rows="4" class="form-textarea" required><?php echo htmlspecialchars($thread['content']); ?></textarea>
                                <div class="flex-gap-sm">
                                    <button type="submit" class="btn btn-primary btn-md">Save</button>
                                    <button type="button" class="btn btn-outline btn-md" onclick="document.getElementById('edit-thread').style.display='none'; document.getElementById('view-thread').style.display='block';">Cancel</button>
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
                            <div class="post-meta-flex">
                                <span class="text-meta"><i class="fa-solid fa-clock"></i> Replied on <?php echo date('M d, Y H:i', strtotime($reply['created_at'])); ?></span>
                                <?php if ($reply['user_id'] == $_SESSION['user_id']): ?>
                                    <div class="flex-gap-sm">
                                        <button type="button" class="btn-icon-muted" title="Edit" onclick="document.getElementById('edit-reply-<?php echo $reply['id']; ?>').style.display='block'; document.getElementById('view-reply-<?php echo $reply['id']; ?>').style.display='none';">
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <form method="POST" action="../actions/delete_discussion_reply.php" onsubmit="return confirm('Are you sure you want to delete this reply?');" class="m-0">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                                            <input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>">
                                            <button type="submit" class="btn-icon-danger" title="Delete">
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
                                <form id="edit-reply-<?php echo $reply['id']; ?>" method="POST" action="../actions/edit_discussion_reply.php" style="display: none;" class="margin-top-md">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="reply_id" value="<?php echo $reply['id']; ?>">
                                    <input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>">
                                    <textarea name="content" rows="3" class="form-textarea" required><?php echo htmlspecialchars($reply['content']); ?></textarea>
                                    <div class="flex-gap-sm">
                                        <button type="submit" class="btn btn-primary btn-md">Save</button>
                                        <button type="button" class="btn btn-outline btn-md" onclick="document.getElementById('edit-reply-<?php echo $reply['id']; ?>').style.display='none'; document.getElementById('view-reply-<?php echo $reply['id']; ?>').style.display='block';">Cancel</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <div class="reply-box">
                <h3 class="margin-bottom-md">Post a Reply</h3>
                <form method="POST" action="discussion_thread.php?id=<?php echo $thread_id; ?>">
                    <div class="form-group margin-bottom-md">
                        <textarea name="content" rows="4" placeholder="Write your reply here..." required class="form-textarea"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Reply</button>
                </form>
            </div>
        </div>
    </main>
</div>
</body>
</html>
