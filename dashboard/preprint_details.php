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
$stmt = $conn->prepare("SELECT p.*, u.full_name, u.role, pr.title as project_title 
                        FROM preprints p 
                        JOIN users u ON p.author_id = u.id 
                        LEFT JOIN projects pr ON p.project_id = pr.id
                        WHERE p.id = ?");
$stmt->bind_param("i", $preprint_id);
$stmt->execute();
$preprint = $stmt->get_result()->fetch_assoc();

if (!$preprint) {
    header("Location: preprints.php");
    exit();
}

// Fetch Comments
$c_stmt = $conn->prepare("SELECT c.*, u.full_name 
                          FROM preprint_comments c
                          JOIN users u ON c.user_id = u.id
                          WHERE c.preprint_id = ?
                          ORDER BY c.created_at ASC");
$c_stmt->bind_param("i", $preprint_id);
$c_stmt->execute();
$comments = $c_stmt->get_result();
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
    <style>
        .p-details-section { padding: 2rem; max-width: 900px; margin: 0 auto; }
        
        .p-header {
            background: white; border-radius: 16px; padding: 2.5rem; margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05); border-top: 6px solid #2f6690;
        }
        .p-meta-top { display: flex; gap: 1.5rem; margin-bottom: 1.5rem; color: #64748b; font-size: 0.95rem; }
        .p-meta-top span { display: flex; align-items: center; gap: 0.5rem; }
        .p-title { font-family: 'Playfair Display', serif; font-size: 2.5rem; color: #0a1128; margin-bottom: 1.5rem; line-height: 1.3; }
        .p-authors { display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem; }
        .p-author-avatar { width: 48px; height: 48px; border-radius: 50%; }
        .p-author-info { display: flex; flex-direction: column; }
        .p-author-name { font-weight: 600; color: #1e293b; font-size: 1.1rem; }
        .p-author-role { color: #64748b; font-size: 0.85rem; text-transform: capitalize; }
        
        .p-abstract-box {
            background: #f8fafc; border-radius: 12px; padding: 2rem; margin-bottom: 2rem;
            border-left: 4px solid #3b82f6;
        }
        .p-abstract-title { font-size: 1.2rem; font-weight: 700; color: #0a1128; margin-bottom: 1rem; }
        .p-abstract-content { color: #334155; line-height: 1.8; font-size: 1.05rem; }
        
        .p-actions { display: flex; gap: 1rem; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 1.5rem; margin-top: 1.5rem; }
        
        .comments-section {
            background: white; border-radius: 16px; padding: 2.5rem; box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }
        .comments-header { font-size: 1.5rem; font-weight: 700; color: #0a1128; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.8rem; }
        .comment-list { display: flex; flex-direction: column; gap: 1.5rem; margin-bottom: 2rem; }
        .comment-item { display: flex; gap: 1rem; padding-bottom: 1.5rem; border-bottom: 1px solid #f1f5f9; }
        .comment-item:last-child { border-bottom: none; padding-bottom: 0; }
        .comment-avatar { width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0; }
        .comment-content-wrap { flex-grow: 1; }
        .comment-meta { display: flex; justify-content: space-between; margin-bottom: 0.5rem; }
        .comment-author { font-weight: 600; color: #1e293b; }
        .comment-time { color: #94a3b8; font-size: 0.85rem; }
        .comment-text { color: #475569; line-height: 1.6; }
        
        .comment-form { display: flex; flex-direction: column; gap: 1rem; background: #f8fafc; padding: 1.5rem; border-radius: 12px; }
        .comment-form textarea {
            width: 100%; padding: 1rem; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 1rem;
            resize: vertical; min-height: 100px; transition: all 0.2s;
        }
        .comment-form textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .comment-form .btn-submit { align-self: flex-end; }
        
        .back-link { display: inline-flex; align-items: center; gap: 0.5rem; color: #64748b; text-decoration: none; font-weight: 500; margin-bottom: 1.5rem; transition: color 0.2s; }
        .back-link:hover { color: #2f6690; }
    </style>
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <header class="dash-header dash-header-resources">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" placeholder="Search...">
            </div>
            <div class="header-actions">
                <div class="user-profile-small">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name']); ?>&background=0a1128&color=fff" alt="User">
                </div>
            </div>
        </header>

        <section class="p-details-section">
            <a href="preprints.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Preprints</a>
            
            <div class="p-header">
                <div class="p-meta-top">
                    <span><i class="fa-regular fa-calendar"></i> Uploaded: <?php echo date('M j, Y', strtotime($preprint['created_at'])); ?></span>
                    <span><i class="fa-solid fa-code-branch"></i> Version <?php echo $preprint['version']; ?></span>
                    <span><i class="fa-solid fa-scale-balanced"></i> <?php echo htmlspecialchars($preprint['license_type']); ?></span>
                    <?php if($preprint['project_title']): ?>
                    <span style="color: #10b981;"><i class="fa-solid fa-folder"></i> Project: <?php echo htmlspecialchars($preprint['project_title']); ?></span>
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
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 2rem;">
                    <?php 
                    $tags = explode(',', $preprint['keywords']);
                    foreach($tags as $tag): 
                        if(trim($tag)):
                    ?>
                        <span style="background: #f1f5f9; color: #3b82f6; padding: 0.3rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600;"><?php echo htmlspecialchars(trim($tag)); ?></span>
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
                    <a href="../actions/download_preprint.php?id=<?php echo $preprint['id']; ?>" class="btn btn-primary" style="padding: 0.8rem 2rem; font-size: 1.1rem;">
                        <i class="fa-solid fa-download"></i> Download PDF (<?php echo $preprint['downloads_count']; ?>)
                    </a>
                    <?php if($preprint['author_id'] == $user_id): ?>
                    <button class="btn btn-outline" style="padding: 0.8rem 2rem; font-size: 1.1rem;">
                        <i class="fa-solid fa-upload"></i> Upload New Version
                    </button>
                    <?php else: ?>
                    <button class="btn btn-outline" style="padding: 0.8rem 2rem; font-size: 1.1rem; color: #ef4444; border-color: #fca5a5;" onclick="openReportModal()">
                        <i class="fa-regular fa-flag"></i> Report Content
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if(isset($_GET['reported']) && $_GET['reported'] == 1): ?>
            <div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: center; border: 1px solid #bbf7d0;">
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
                        <div style="text-align: center; color: #64748b; padding: 2rem 0;">
                            No feedback yet. Be the first to provide suggestions or ask a question!
                        </div>
                    <?php endif; ?>
                </div>
                
                <form action="../actions/add_preprint_comment.php" method="POST" class="comment-form">
                    <input type="hidden" name="preprint_id" value="<?php echo $preprint['id']; ?>">
                    <textarea name="comment" required placeholder="Suggest improvements, ask questions, or provide a review..."></textarea>
                    <button type="submit" class="btn btn-primary btn-submit">Post Feedback</button>
                </form>
            </div>
        </section>
    </main>

    <!-- Report Modal -->
    <div class="modal" id="reportModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal-content" style="background: white; padding: 2.5rem; border-radius: 16px; width: 100%; max-width: 500px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2 style="font-family: 'Playfair Display', serif; color: #0a1128; margin: 0;">Report Content</h2>
                <button onclick="closeReportModal()" style="background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <p style="color: #64748b; margin-bottom: 1.5rem; line-height: 1.5;">If you believe this content violates our Terms & Copyright Policy, please let us know. The administration will review it.</p>
            <form action="../actions/report_content.php" method="POST">
                <input type="hidden" name="item_id" value="<?php echo $preprint['id']; ?>">
                <input type="hidden" name="item_type" value="preprint">
                <input type="hidden" name="redirect_url" value="../dashboard/preprint_details.php?id=<?php echo $preprint['id']; ?>">
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Reason for Reporting</label>
                    <textarea name="reason" required style="width: 100%; padding: 1rem; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; resize: vertical; min-height: 100px;" placeholder="e.g., This is my copyrighted work uploaded without permission..."></textarea>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" class="btn btn-outline" onclick="closeReportModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="background: #ef4444; border-color: #ef4444;">Submit Report</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openReportModal() {
            document.getElementById('reportModal').style.display = 'flex';
        }
        function closeReportModal() {
            document.getElementById('reportModal').style.display = 'none';
        }
        document.getElementById('reportModal').addEventListener('click', function(e) {
            if(e.target === this) closeReportModal();
        });
    </script>
</body>
</html>
