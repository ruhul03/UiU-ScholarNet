<?php
require_once('../includes/auth_check.php');

// Fetch Preprints
$result = db_query("SELECT p.*, u.full_name, pr.title as project_title 
                        FROM preprints p 
                        JOIN users u ON p.author_id = u.id 
                        LEFT JOIN projects pr ON p.project_id = pr.id
                        ORDER BY p.created_at DESC");

// Fetch Projects for the upload modal
$proj_result = db_query("SELECT id, title FROM projects WHERE creator_id = ? OR id IN (SELECT project_id FROM tasks WHERE assigned_to = ?)", [$user_id, $user_id], "ii");
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
    <link rel="stylesheet" href="../assets/css/preprints.css">
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <?php include('../includes/header.php'); ?>
        <?php include('../includes/alerts.php'); ?>

        <section class="preprints-section">
            <div class="preprints-header">
                <div class="preprints-header-text">
                    <h1>Latest Preprints</h1>
                    <p>Discover and share early-stage research. Get feedback before formal publication.</p>
                </div>
                <button class="btn btn-primary btn-upload-white" onclick="openModal()">
                    <i class="fa-solid fa-upload"></i> Upload Preprint
                </button>
            </div>

            <div class="preprint-feed">
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <div class="preprint-card-simple">
                        <div class="preprint-card-main">
                            <a href="preprint_details.php?id=<?php echo $row['id']; ?>" class="preprint-card-title">
                                <?php echo htmlspecialchars($row['title']); ?>
                            </a>
                            <div class="preprint-card-meta">
                                <span><i class="fa-solid fa-user-circle"></i> <?php echo htmlspecialchars($row['full_name']); ?></span>
                                <span><i class="fa-regular fa-calendar"></i> <?php echo date('M j, Y', strtotime($row['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="preprint-card-actions">
                            <a href="../<?php echo htmlspecialchars($row['file_path']); ?>" target="_blank" class="btn btn-outline btn-outline-sm">
                                <i class="fa-solid fa-download"></i> PDF
                            </a>
                            
                            <?php if($row['author_id'] == $user_id): ?>
                            <form action="../actions/discussion_preprints/delete_preprint.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this preprint?');" class="form-inline">
                                <input type="hidden" name="preprint_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-outline btn-delete-sm">
                                    <i class="fa-solid fa-trash"></i> Delete
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-file-pdf"></i>
                        <h3>No Preprints Found</h3>
                        <p>Be the first to share your early-stage research with the community.</p>
                        <button class="btn btn-primary mt-1-btn" onclick="openModal()">Upload Your Preprint</button>
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
            <form action="../actions/discussion_preprints/upload_preprint.php" method="POST" enctype="multipart/form-data">
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
                <div class="form-group agreement-box">
                    <label class="agreement-label">
                        <input type="checkbox" name="accepted_copyright" required class="agreement-checkbox">
                        <span>
                            <strong>Copyright Agreement:</strong> I confirm this is my original work or I have permission to upload it, and it is not under restricted publication copyright. By uploading, I grant UIU ScholarNet permission to display it.
                        </span>
                    </label>
                </div>
                <div class="modal-footer-space">
                    <a href="copyright_policy.php" target="_blank" class="policy-link">Read Copyright Policy</a>
                    <div class="modal-actions">
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
