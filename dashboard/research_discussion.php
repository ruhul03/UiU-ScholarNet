<?php
require_once('../includes/db_connect.php');
require_once('../includes/auth_check.php');
require_once('../includes/layout.php');
require_once('../includes/csrf.php');

// Handle new thread submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title']) && isset($_POST['content']) && isset($_POST['category'])) {
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $content = mysqli_real_escape_string($conn, trim($_POST['content']));
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));
    $user_id = $_SESSION['user_id'];

    if (!empty($title) && !empty($content)) {
        $query = "INSERT INTO discussion_threads (user_id, title, category, content) VALUES (?, ?, ?, ?)";
        db_query($query, [$user_id, $_POST['title'], $_POST['category'], $_POST['content']], "isss");
        
        // Give reputation point for starting a discussion
        db_query("UPDATE users SET points = points + 2 WHERE id = ?", [$user_id], "i");
        
        // Add notification
        db_query("INSERT INTO notifications (user_id, type, title, message) VALUES (?, 'system', 'New Discussion Thread', 'You started a new research discussion.')", [$user_id], "i");
        
        header("Location: research_discussion.php?success=1");
        exit;
    }
}

// Handle search and filtering
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$filter_category = isset($_GET['category']) ? mysqli_real_escape_string($conn, trim($_GET['category'])) : '';

$params = [];
$types = "";
$where_clauses = [];
if (!empty($search)) {
    $where_clauses[] = "(t.title LIKE ? OR t.content LIKE ?)";
    $search_term = "%" . $_GET['search'] . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}
if (!empty($filter_category)) {
    $where_clauses[] = "t.category = ?";
    $params[] = $_GET['category'];
    $types .= "s";
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
$result = db_query($query, $params, $types);

$current_user_id = $_SESSION['user_id'];

layout_header("Research Discussion | UIU ScholarNet");
?>

    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <?php include('../includes/header.php'); ?>

        <div class="discussion-container">
            <div class="discussion-header">
                <div>
                    <h1 class="discussion-headline">Research Discussion</h1>
                    <p class="discussion-desc">Share ideas, ask questions, and collaborate openly.</p>
                </div>
                <button class="btn btn-primary" onclick="document.getElementById('newThreadForm').style.display='block'">+ New Topic</button>
            </div>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert-success-box">
                    Discussion thread posted successfully!
                </div>
            <?php endif; ?>

            <!-- Search and Filter Bar -->
            <div class="search-filter-bar">
                <form method="GET" action="research_discussion.php" class="form-flex-1">
                    <div class="flex-2">
                        <input type="text" name="search" placeholder="Search discussions..." value="<?php echo htmlspecialchars($search); ?>" class="input-search">
                    </div>
                    <div class="flex-1">
                        <select name="category" class="select-filter">
                            <option value="">All Categories</option>
                            <option value="Computer Science" <?php echo $filter_category === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                            <option value="Data Science & AI" <?php echo $filter_category === 'Data Science & AI' ? 'selected' : ''; ?>>Data Science & AI</option>
                            <option value="Engineering" <?php echo $filter_category === 'Engineering' ? 'selected' : ''; ?>>Engineering</option>
                            <option value="Business & Econ" <?php echo $filter_category === 'Business & Econ' ? 'selected' : ''; ?>>Business & Econ</option>
                            <option value="General" <?php echo $filter_category === 'General' ? 'selected' : ''; ?>>General</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-p-lg">Search</button>
                    <?php if(!empty($search) || !empty($filter_category)): ?>
                        <a href="research_discussion.php" class="btn btn-outline btn-clear-filter">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div id="newThreadForm" class="new-thread-form-box">
                <h3 class="mb-1">Start a New Topic</h3>
                <form method="POST" action="research_discussion.php">
                    <div class="form-group form-group-flex">
                        <input type="text" name="title" placeholder="Discussion Title" required class="input-thread-title">
                        <select name="category" required class="select-thread-category">
                            <option value="" disabled selected>Select Category</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Data Science & AI">Data Science & AI</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Business & Econ">Business & Econ</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                    <div class="form-group mb-1">
                        <textarea name="content" rows="4" placeholder="What's on your mind?" required class="textarea-thread-content"></textarea>
                    </div>
                    <div class="flex-gap-1">
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
                                <div class="thread-meta-top">
                                    <span class="badge-category"><?php echo htmlspecialchars($thread['category']); ?></span>
                                </div>
                                <div class="thread-title mb-0-3"><?php echo htmlspecialchars($thread['title']); ?></div>
                                <div class="thread-meta">
                                    <span><i class="fa-solid fa-user"></i> <?php echo htmlspecialchars($thread['full_name']); ?> (<?php echo ucfirst($thread['role']); ?>)</span>
                                    <span><i class="fa-solid fa-clock"></i> <?php echo date('M d, Y H:i', strtotime($thread['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="thread-stats thread-stats-flex">
                                <div class="stat-box">
                                    <span><?php echo $thread['reply_count']; ?></span>
                                    <small>Replies</small>
                                </div>
                                <div class="stat-box">
                                    <span><?php echo $thread['views']; ?></span>
                                    <small>Views</small>
                                </div>
                                <?php if ($thread['user_id'] == $current_user_id): ?>
                                    <form method="POST" action="../actions/delete_discussion_thread.php" onsubmit="return confirm('Are you sure you want to delete this topic?');" class="ml-1">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="thread_id" value="<?php echo $thread['id']; ?>">
                                        <button type="submit" class="btn-thread-delete" title="Delete Topic" onclick="event.stopPropagation();">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </a>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="empty-thread-state">No discussions yet. Be the first to start a topic!</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
