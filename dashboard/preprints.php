<?php
require_once('../includes/auth_check.php');

// Fetch Preprints
$stmt = $conn->prepare("SELECT p.*, u.full_name, pr.title as project_title 
                        FROM preprints p 
                        JOIN users u ON p.author_id = u.id 
                        LEFT JOIN projects pr ON p.project_id = pr.id
                        ORDER BY p.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();

// Fetch Projects for the upload modal
$proj_stmt = $conn->prepare("SELECT id, title FROM projects WHERE creator_id = ? OR id IN (SELECT project_id FROM tasks WHERE assigned_to = ?)");
$proj_stmt->bind_param("ii", $user_id, $user_id);
$proj_stmt->execute();
$proj_result = $proj_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preprints Feed | UIU ScholarNet</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .preprints-section { padding: 2rem; }
        .preprints-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;
            background: linear-gradient(135deg, #0a1128, #16425b); padding: 2.5rem; border-radius: 16px; color: white;
            box-shadow: 0 10px 30px rgba(10, 17, 40, 0.15);
            position: relative; overflow: hidden;
        }
        .preprints-header::before {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            animation: rotate 20s linear infinite; pointer-events: none;
        }
        @keyframes rotate { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .preprints-header-text h1 { font-family: 'Playfair Display', serif; font-size: 2.5rem; margin-bottom: 0.5rem; }
        .preprints-header-text p { color: rgba(255,255,255,0.8); font-size: 1.1rem; max-width: 600px; }
        
        .preprint-feed { display: flex; flex-direction: column; gap: 1.5rem; }
        .preprint-card {
            background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease; border-left: 5px solid #2f6690;
            display: flex; flex-direction: column; gap: 1rem;
        }
        .preprint-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .preprint-meta { display: flex; align-items: center; gap: 1rem; font-size: 0.9rem; color: #64748b; }
        .preprint-meta span { display: flex; align-items: center; gap: 0.4rem; }
        .preprint-title { font-size: 1.4rem; font-weight: 700; color: #0a1128; margin: 0; text-decoration: none; }
        .preprint-title:hover { color: #2f6690; }
        .preprint-abstract { color: #475569; font-size: 0.95rem; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .preprint-tags { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .preprint-tag { background: #f1f5f9; color: #3b82f6; padding: 0.2rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .preprint-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }
        .preprint-stats { display: flex; gap: 1.5rem; color: #64748b; font-size: 0.9rem; }
        .preprint-actions { display: flex; gap: 1rem; }
        
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); z-index: 1000; align-items: center; justify-content: center; }
        .modal.active { display: flex; }
        .modal-content { background: white; padding: 2.5rem; border-radius: 16px; width: 100%; max-width: 600px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-header h2 { font-family: 'Playfair Display', serif; color: #0a1128; margin: 0; }
        .close-modal { background: none; border: none; font-size: 1.5rem; color: #64748b; cursor: pointer; transition: color 0.2s; }
        .close-modal:hover { color: #ef4444; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #1e293b; }
        .form-control { width: 100%; padding: 0.75rem 1rem; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 1rem; transition: border-color 0.2s; }
        .form-control:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        textarea.form-control { resize: vertical; min-height: 100px; }
        
        .empty-state { text-align: center; padding: 4rem 2rem; background: white; border-radius: 12px; border: 2px dashed #cbd5e1; }
        .empty-state i { font-size: 4rem; color: #94a3b8; margin-bottom: 1.5rem; }
        .empty-state h3 { color: #0a1128; margin-bottom: 0.5rem; font-size: 1.5rem; }
        .empty-state p { color: #64748b; }
    </style>
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <header class="dash-header dash-header-resources">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" placeholder="Search preprints...">
            </div>
            <div class="header-actions">
                <i class="fa-regular fa-bell header-icon"></i>
                <div class="user-profile-small">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name']); ?>&background=0a1128&color=fff" alt="User">
                </div>
            </div>
        </header>

        <section class="preprints-section">
            <div class="preprints-header">
                <div class="preprints-header-text">
                    <h1>Latest Preprints</h1>
                    <p>Discover and share early-stage research. Get feedback before formal publication.</p>
                </div>
                <button class="btn btn-primary" onclick="openModal()" style="background: white; color: #0a1128; font-weight: 600; padding: 0.8rem 1.5rem; font-size: 1.05rem;">
                    <i class="fa-solid fa-upload"></i> Upload Preprint
                </button>
            </div>

            <div class="preprint-feed">
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="preprint-card">
                        <div class="preprint-meta">
                            <span><i class="fa-solid fa-user-circle"></i> <?php echo htmlspecialchars($row['full_name']); ?></span>
                            <span><i class="fa-regular fa-calendar"></i> <?php echo date('M j, Y', strtotime($row['created_at'])); ?></span>
                            <span><i class="fa-solid fa-code-branch"></i> v<?php echo $row['version']; ?></span>
                            <?php if($row['project_title']): ?>
                            <span style="color: #10b981;"><i class="fa-solid fa-folder"></i> <?php echo htmlspecialchars($row['project_title']); ?></span>
                            <?php endif; ?>
                            <?php if($row['visibility'] == 'private'): ?>
                            <span style="color: #ef4444;"><i class="fa-solid fa-lock"></i> Private</span>
                            <?php endif; ?>
                        </div>
                        
                        <a href="preprint_details.php?id=<?php echo $row['id']; ?>" class="preprint-title">
                            <?php echo htmlspecialchars($row['title']); ?>
                        </a>
                        
                        <div class="preprint-abstract">
                            <?php echo nl2br(htmlspecialchars($row['abstract'])); ?>
                        </div>
                        
                        <?php if($row['keywords']): ?>
                        <div class="preprint-tags">
                            <?php 
                            $tags = explode(',', $row['keywords']);
                            foreach($tags as $tag): 
                                if(trim($tag)):
                            ?>
                                <span class="preprint-tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="preprint-footer">
                            <div class="preprint-stats">
                                <span><i class="fa-regular fa-eye"></i> <?php echo $row['views_count']; ?> Views</span>
                                <span><i class="fa-solid fa-download"></i> <?php echo $row['downloads_count']; ?> Downloads</span>
                                <?php
                                // Get comment count
                                $c_stmt = $conn->prepare("SELECT COUNT(*) as c FROM preprint_comments WHERE preprint_id = ?");
                                $c_stmt->bind_param("i", $row['id']);
                                $c_stmt->execute();
                                $c_res = $c_stmt->get_result()->fetch_assoc();
                                ?>
                                <span><i class="fa-regular fa-comment"></i> <?php echo $c_res['c']; ?> Comments</span>
                            </div>
                            <div class="preprint-actions">
                                <a href="preprint_details.php?id=<?php echo $row['id']; ?>" class="btn btn-outline">
                                    <i class="fa-regular fa-comment-dots"></i> Feedback
                                </a>
                                <a href="../<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-primary">
                                    <i class="fa-solid fa-download"></i> PDF
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-file-pdf"></i>
                        <h3>No Preprints Found</h3>
                        <p>Be the first to share your early-stage research with the community.</p>
                        <button class="btn btn-primary" style="margin-top: 1rem;" onclick="openModal()">Upload Your Preprint</button>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <!-- Upload Modal -->
    <div class="modal" id="uploadModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Upload Preprint</h2>
                <button class="close-modal" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="../actions/upload_preprint.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g., A Novel Approach to LLM Optimization">
                </div>
                <div class="form-group">
                    <label>Abstract</label>
                    <textarea name="abstract" class="form-control" required placeholder="Brief summary of your research..."></textarea>
                </div>
                <div class="form-group">
                    <label>Keywords (Comma separated)</label>
                    <input type="text" name="keywords" class="form-control" placeholder="e.g., AI, Machine Learning, Optimization">
                </div>
                <div class="form-group">
                    <label>Link to Project (Optional)</label>
                    <select name="project_id" class="form-control">
                        <option value="">None</option>
                        <?php while($p = $proj_result->fetch_assoc()): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Visibility</label>
                    <select name="visibility" class="form-control">
                        <option value="public">Public (All Users)</option>
                        <option value="private">Private (Only Project Members)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>License Type</label>
                    <select name="license_type" class="form-control" required>
                        <option value="All Rights Reserved">All Rights Reserved</option>
                        <option value="CC BY (Credit Required)">Creative Commons: CC BY (Credit Required)</option>
                        <option value="CC BY-NC (Non-Commercial)">Creative Commons: CC BY-NC (Non-Commercial)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Upload PDF</label>
                    <input type="file" name="preprint_file" class="form-control" accept=".pdf" required>
                </div>
                <div class="form-group" style="background: #f8fafc; padding: 1rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 1rem;">
                    <label style="display: flex; align-items: flex-start; gap: 0.8rem; font-weight: normal; cursor: pointer; margin: 0;">
                        <input type="checkbox" name="accepted_copyright" required style="margin-top: 0.3rem; transform: scale(1.2);">
                        <span>
                            <strong>Copyright Agreement:</strong> I confirm this is my original work or I have permission to upload it, and it is not under restricted publication copyright. By uploading, I grant UIU ScholarNet permission to display it.
                        </span>
                    </label>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 2rem;">
                    <a href="copyright_policy.php" target="_blank" style="color: #3b82f6; font-size: 0.9rem; text-decoration: none;">Read Copyright Policy</a>
                    <div style="display: flex; gap: 1rem;">
                        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Publish Preprint</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('uploadModal').classList.add('active');
        }
        function closeModal() {
            document.getElementById('uploadModal').classList.remove('active');
        }
        // Close modal when clicking outside
        document.getElementById('uploadModal').addEventListener('click', function(e) {
            if(e.target === this) closeModal();
        });
    </script>
</body>
</html>
