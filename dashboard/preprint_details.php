<?php
require_once('../includes/auth_check.php');

$preprint_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($preprint_id == 0) {
    header("Location: preprints.php");
    exit();
}

// Increment views
$conn->query("UPDATE preprints SET views_count = views_count + 1 WHERE id = $preprint_id");

// Fetch Preprint
$preprint = db_query("SELECT p.*, u.full_name, u.role, pr.title as project_title 
                        FROM preprints p 
                        JOIN users u ON p.author_id = u.id 
                        LEFT JOIN projects pr ON p.project_id = pr.id
                        WHERE p.id = ?", [$preprint_id], "i")->fetch_assoc();

if (!$preprint) {
    header("Location: preprints.php");
    exit();
}

// Fetch Comments
$comments = db_query("SELECT c.*, u.full_name 
                          FROM preprint_comments c
                          JOIN users u ON c.user_id = u.id
                          WHERE c.preprint_id = ?
                          ORDER BY c.created_at ASC", [$preprint_id], "i");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($preprint['title']); ?> | Preprints</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/preprints.css">
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <?php include('../includes/header.php'); ?>
        <?php include('../includes/alerts.php'); ?>

        <section class="p-details-section">
            <a href="preprints.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Preprints</a>
            
            <div class="p-header">
                <div class="p-meta-top">
                    <span><i class="fa-regular fa-calendar"></i> Uploaded: <?php echo date('M j, Y', strtotime($preprint['created_at'])); ?></span>
                    <span><i class="fa-solid fa-code-branch"></i> Version <?php echo $preprint['version']; ?></span>
                    <span><i class="fa-solid fa-scale-balanced"></i> <?php echo htmlspecialchars($preprint['license_type']); ?></span>
                    <?php if($preprint['project_title']): ?>
                    <a href="edit_project.php?id=<?php echo $preprint['project_id']; ?>" class="text-success text-deco-none"><i class="fa-solid fa-folder"></i> Project: <?php echo htmlspecialchars($preprint['project_title']); ?></a>
                    <?php endif; ?>
                    <span><i class="fa-regular fa-eye"></i> <?php echo $preprint['views_count']; ?> Views</span>
                </div>
                
                <h1 class="p-title"><?php echo htmlspecialchars($preprint['title']); ?></h1>
                
                <div class="p-authors">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($preprint['full_name']); ?>&background=e2e8f0&color=475569" alt="Author" class="p-author-avatar">
                    <div class="p-author-info">
                        <span class="p-author-name"><?php echo htmlspecialchars($preprint['full_name']); ?></span>
                        <span class="p-author-role"><?php echo htmlspecialchars($preprint['role']); ?> • Primary Author</span>
                    </div>
                </div>
                
                <?php if($preprint['keywords']): ?>
                <div class="tag-container">
                    <?php 
                    $tags = explode(',', $preprint['keywords']);
                    foreach($tags as $tag): 
                        if(trim($tag)):
                    ?>
                        <span class="tag-keyword"><?php echo htmlspecialchars(trim($tag)); ?></span>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                <?php endif; ?>
                
                <div class="p-abstract-box">
                    <div class="p-abstract-title">Abstract</div>
                    <div class="p-abstract-content">
                        <?php echo nl2br(htmlspecialchars($preprint['abstract'])); ?>
                    </div>
                </div>
                
                <div class="p-actions">
                    <a href="../actions/download_preprint.php?id=<?php echo $preprint['id']; ?>" class="btn btn-primary btn-action-lg">
                        <i class="fa-solid fa-download"></i> Download PDF (<?php echo $preprint['downloads_count']; ?>)
                    </a>
                    <?php if($preprint['author_id'] == $user_id): ?>
                    <button class="btn btn-outline btn-action-lg">
                        <i class="fa-solid fa-upload"></i> Upload New Version
                    </button>
                    <?php else: ?>
                    <button class="btn btn-outline btn-action-danger" onclick="openReportModal()">
                        <i class="fa-regular fa-flag"></i> Report Content
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if(isset($_GET['reported']) && $_GET['reported'] == 1): ?>
            <div class="bg-success-light">
                <i class="fa-solid fa-circle-check"></i> Thank you. This content has been reported to the administration for review.
            </div>
            <?php endif; ?>
            
            <div class="comments-section">
                <div class="comments-header">
                    <i class="fa-regular fa-comments"></i> Academic Feedback (<?php echo $comments->num_rows; ?>)
                </div>
                
                <div class="comment-list">
                    <?php if($comments->num_rows > 0): ?>
                        <?php while($c = $comments->fetch_assoc()): ?>
                        <div class="comment-item">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($c['full_name']); ?>&background=f1f5f9&color=64748b" alt="User" class="comment-avatar">
                            <div class="comment-content-wrap">
                                <div class="comment-meta">
                                    <span class="comment-author"><?php echo htmlspecialchars($c['full_name']); ?></span>
                                    <span class="comment-time"><?php echo date('M j, Y g:i A', strtotime($c['created_at'])); ?></span>
                                </div>
                                <div class="comment-text">
                                    <?php echo nl2br(htmlspecialchars($c['comment'])); ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-comments">
                            No feedback yet. Be the first to provide suggestions or ask a question!
                        </div>
                    <?php endif; ?>
                </div>
                
                <form action="../actions/discussion_preprints/add_preprint_comment.php" method="POST" class="comment-form">
                    <input type="hidden" name="preprint_id" value="<?php echo $preprint['id']; ?>">
                    <textarea name="comment" required placeholder="Suggest improvements, ask questions, or provide a review..."></textarea>
                    <button type="submit" class="btn btn-primary btn-submit">Post Feedback</button>
                </form>
            </div>
        </section>
    </main>

    <!-- Report Modal -->
    <div class="modal modal-overlay" id="reportModal">
        <div class="modal-content modal-content-sm">
            <div class="modal-header">
                <h2 class="modal-title">Report Content</h2>
                <button onclick="closeReportModal()" class="modal-header-actions"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <p class="modal-desc-text">If you believe this content violates our Terms & Copyright Policy, please let us know. The administration will review it.</p>
            <form action="../actions/admin_misc/report_content.php" method="POST">
                <input type="hidden" name="item_id" value="<?php echo $preprint['id']; ?>">
                <input type="hidden" name="item_type" value="preprint">
                <input type="hidden" name="redirect_url" value="../dashboard/preprint_details.php?id=<?php echo $preprint['id']; ?>">
                <div class="form-group">
                    <label class="form-label-bold">Reason for Reporting</label>
                    <textarea name="reason" required class="form-textarea" placeholder="e.g., This is my copyrighted work uploaded without permission..."></textarea>
                </div>
                <div class="modal-footer-actions">
                    <button type="button" class="btn btn-outline" onclick="closeReportModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-danger-solid">Submit Report</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReportModal() {
            document.getElementById('reportModal').classList.add('active');
        }
        function closeReportModal() {
            document.getElementById('reportModal').classList.remove('active');
        }
        document.getElementById('reportModal').addEventListener('click', function(e) {
            if(e.target === this) closeReportModal();
        });
    </script>
</body>
</html>
