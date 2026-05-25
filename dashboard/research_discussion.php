<?php
require_once('../includes/db_connect.php');
require_once('../includes/auth_check.php');
require_once('../includes/layout.php');

// Handle new thread submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && isset($_POST['content']) && isset($_POST['category'])) {
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    $user_id = $_SESSION['user_id'];

    if (!empty($title) && !empty($content)) {
        $query = "INSERT INTO discussion_threads (user_id, title, category, content) VALUES ('$user_id', '$title', '$category', '$content')";
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

// Handle search and filtering
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$filter_category = isset($_GET['category']) ? mysqli_real_escape_string($conn, trim($_GET['category'])) : '';

$where_clauses = [];
if (!empty($search)) {
    $where_clauses[] = "(t.title LIKE '%$search%' OR t.content LIKE '%$search%')";
}
if (!empty($filter_category)) {
    $where_clauses[] = "t.category = '$filter_category'";
}
$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Fetch all threads with reply count
$query = "
    SELECT t.*, u.full_name, u.role, 
           (SELECT COUNT(*) FROM discussion_replies r WHERE r.thread_id = t.id) as reply_count
    FROM discussion_threads t
    JOIN users u ON t.user_id = u.id
    $where_sql
    ORDER BY t.created_at DESC
";
$result = mysqli_query($conn, $query);

$current_user_id = $_SESSION['user_id'];

layout_header("Research Discussion | UIU ScholarNet");
?>

    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <?php include('../includes/header.php'); ?>

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

            <!-- Search and Filter Bar -->
            <div style="background: var(--white); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--accent-color); margin-bottom: 2rem; display: flex; gap: 1rem; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                <form method="GET" action="research_discussion.php" style="display: flex; gap: 1rem; flex: 1; margin: 0;">
                    <div style="flex: 2;">
                        <input type="text" name="search" placeholder="Search discussions..." value="<?php echo htmlspecialchars($search); ?>" style="width: 100%; padding: 0.8rem 1rem; border: 1px solid #ddd; border-radius: 4px; font-family: var(--font-body);">
                    </div>
                    <div style="flex: 1;">
                        <select name="category" style="width: 100%; padding: 0.8rem 1rem; border: 1px solid #ddd; border-radius: 4px; background: white; font-family: var(--font-body);">
                            <option value="">All Categories</option>
                            <option value="Computer Science" <?php echo $filter_category === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                            <option value="Data Science & AI" <?php echo $filter_category === 'Data Science & AI' ? 'selected' : ''; ?>>Data Science & AI</option>
                            <option value="Engineering" <?php echo $filter_category === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                            <option value="Business & Econ" <?php echo $filter_category === 'Business & Econ' ? 'selected' : ''; ?>>Business & Econ</option>
                            <option value="General" <?php echo $filter_category === 'General' ? 'selected' : ''; ?>>General</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding: 0.8rem 1.5rem;">Search</button>
                    <?php if(!empty($search) || !empty($filter_category)): ?>
                        <a href="research_discussion.php" class="btn btn-outline" style="padding: 0.8rem 1.5rem; text-decoration: none; line-height: 1.5;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div id="newThreadForm" style="display: none; background: #fdfcf8; padding: 2rem; border-radius: 8px; border: 1px solid #eee; margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem;">Start a New Topic</h3>
                <form method="POST" action="research_discussion.php">
                    <div class="form-group" style="margin-bottom: 1rem; display: flex; gap: 1rem;">
                        <input type="text" name="title" placeholder="Discussion Title" required style="flex: 2; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px;">
                        <select name="category" required style="flex: 1; padding: 0.8rem; border: 1px solid #ddd; border-radius: 4px; background: white;">
                            <option value="" disabled selected>Select Category</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Data Science & AI">Data Science & AI</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Business & Econ">Business & Econ</option>
                            <option value="General">General</option>
                        </select>
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
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.3rem;">
                                    <span style="background: #e2e8f0; color: #475569; font-size: 0.7rem; padding: 0.2rem 0.6rem; border-radius: 12px; font-weight: 600;"><?php echo htmlspecialchars($thread['category']); ?></span>
                                </div>
                                <div class="thread-title" style="margin-bottom: 0.3rem;"><?php echo htmlspecialchars($thread['title']); ?></div>
                                <div class="thread-meta">
                                    <span><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($thread['full_name']); ?> (<?php echo ucfirst($thread['role']); ?>)</span>
                                    <span><i class="fa-solid fa-clock"></i> <?php echo date('M d, Y H:i', strtotime($thread['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="thread-stats" style="display: flex; align-items: center; gap: 1rem;">
                                <div class="stat-box">
                                    <span><?php echo $thread['reply_count']; ?></span>
                                    <small>Replies</small>
                                </div>
                                <div class="stat-box">
                                    <span><?php echo $thread['views']; ?></span>
                                    <small>Views</small>
                                </div>
                                <?php if ($thread['user_id'] == $current_user_id): ?>
                                    <form method="POST" action="../actions/delete_discussion_thread.php" onsubmit="return confirm('Are you sure you want to delete this topic?');" style="margin-left: 1rem;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="thread_id" value="<?php echo $thread['id']; ?>">
                                        <button type="submit" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 0.5rem;" title="Delete Topic" onclick="event.stopPropagation();">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
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
