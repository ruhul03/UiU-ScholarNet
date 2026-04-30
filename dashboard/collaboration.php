<?php
require_once('../includes/auth_check.php');

// Fetch Collaboration Posts
$stmt = $conn->prepare("SELECT cp.*, u.full_name 
                        FROM collaboration_posts cp 
                        JOIN users u ON cp.user_id = u.id 
                        ORDER BY cp.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collaboration Finder | UIU ScholarNet</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Discovery Header -->
        <header class="dash-header dash-header-collab">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" placeholder="Search opportunities...">
            </div>
            <div class="header-actions">
                <div class="nav-links-row">
                    <a href="#" class="nav-link-active">Discovery</a>
                    <a href="#" class="nav-link-inactive">My Network</a>
                </div>
                <div class="header-icons">
                    <i class="fa-regular fa-bell header-icon"></i>
                    <i class="fa-regular fa-user header-icon"></i>
                </div>
            </div>
        </header>

        <section class="discovery-header">
            <div class="discovery-main">
                <div>
                    <h1 class="discovery-title">Collaboration Finder</h1>
                    <p class="discovery-desc">Discover research partners, project collaborators, and interdisciplinary opportunities across the university network.</p>
                </div>
                <button class="btn btn-primary btn-post-request" onclick="openModal()">
                    <i class="fa-solid fa-plus"></i> POST REQUEST
                </button>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="filter-group">
                    <select class="filter-select">
                        <option>All Departments</option>
                        <option>Computer Science</option>
                        <option>EEE</option>
                    </select>
                    <select class="filter-select">
                        <option>All Skills</option>
                        <option>AI/ML</option>
                        <option>Hardware</option>
                    </select>
                    <select class="filter-select">
                        <option>All Types</option>
                        <option>Research Paper</option>
                        <option>Software</option>
                    </select>
                </div>
                <div class="view-toggles">
                    <div class="view-btn active"><i class="fa-solid fa-table-cells-large"></i></div>
                    <div class="view-btn"><i class="fa-solid fa-list"></i></div>
                </div>
            </div>
        </section>

        <div class="collaboration-grid collab-grid-3">
            <?php while($row = $result->fetch_assoc()): ?>
            <!-- Dynamic High-Fidelity Card -->
            <div class="collab-card">
                <div class="card-tag">RESEARCH PAPER</div>
                
                <div class="card-author-info">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['full_name']); ?>&background=f5f5f5&color=0a1128" alt="Author">
                </div>

                <h3 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                <p class="card-desc">
                    <?php 
                        $desc = (string)($row['description'] ?? '');
                        echo htmlspecialchars((strlen($desc) > 120) ? substr($desc, 0, 120) . '...' : $desc);
                    ?>
                </p>

                <div class="meta-grid">
                    <div class="meta-block">
                        <span class="meta-label">POSTED BY</span>
                        <span class="meta-value"><?php echo htmlspecialchars($row['full_name']); ?></span>
                    </div>
                    <div class="meta-block">
                        <span class="meta-label">DEPARTMENT</span>
                        <span class="meta-value"><?php echo htmlspecialchars($row['department']); ?></span>
                    </div>
                </div>

                <button class="btn btn-apply">APPLY TO COLLABORATE</button>
            </div>
            <?php endwhile; ?>

            <!-- Spotlight Card Implementation (Static Mockup for visual parity) -->
            <div class="collab-card collab-spotlight">
                <div class="spotlight-header">
                    <span class="spotlight-badge">ACTIVE REQUEST</span>
                </div>
                <h3 class="spotlight-title">Hackathon Team</h3>
                <p class="spotlight-desc">Building a sustainable fintech app for the upcoming Inter-University challenge. 2 slots left!</p>

                <div style="margin-bottom: 2rem;">
                    <div class="applicants-row">
                        <span class="applicants-label">Total Applicants</span>
                        <span class="applicants-count">14 People</span>
                    </div>
                    <div class="progress-bar spotlight-progress">
                        <div class="progress-fill" style="width: 70%;"></div>
                    </div>
                </div>

                <div class="spotlight-actions">
                    <button class="btn btn-primary btn-view-details">VIEW DETAILS</button>
                    <button class="btn btn-outline btn-edit-white"><i class="fa-solid fa-pen-nib"></i></button>
                </div>
            </div>
        </div>

        <!-- Pagination / Load More -->
        <div class="pagination">
            <p class="pagination-info">Showing 6 of 124 available collaborations.</p>
            <button class="load-more-btn">Load More Opportunities</button>
        </div>
    </main>

    <!-- Post Collaboration Modal (Updated Design) -->
    <div class="modal-overlay modal-hidden" id="collabModal">
        <div class="modal-content modal-collab">
            <i class="fa-solid fa-xmark modal-close" onclick="closeModal()"></i>
            <h2 class="modal-collab-title">Post New Collaboration</h2>
            
            <form action="../actions/post_collaboration.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php 
                    require_once('../includes/csrf.php');
                    echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
                ?>">
                <div class="form-group">
                    <label>PROJECT TITLE</label>
                    <input type="text" name="title" placeholder="e.g. AI in Sustainable Architecture" class="form-input-bordered" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>DEPARTMENT</label>
                        <select name="department" class="form-input-bordered" required>
                            <option value="Computer Science">Computer Science</option>
                            <option value="EEE">EEE</option>
                            <option value="Economics">Economics</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>REQUIRED ROLES/SKILLS</label>
                        <input type="text" name="skills" placeholder="e.g. Python, UI/UX, Research" class="form-input-bordered">
                    </div>
                </div>

                <div class="form-group">
                    <label>COLLABORATION DESCRIPTION</label>
                    <textarea name="description" rows="5" class="textarea-bordered" placeholder="Describe your project and what you're looking for..."></textarea>
                </div>

                <div class="invite-preview">
                    <div class="invite-image-box">
                        <div class="cover-image">
                            <img src="https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&q=80&w=200">
                        </div>
                        <div>
                            <div class="cover-title">Project Cover Image</div>
                            <div class="cover-hint">Recommended: 1200 x 630px. High-resolution archival imagery preferred.</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline upload-btn">Upload</button>
                </div>

                <div class="modal-footer-between">
                    <a href="#" class="save-draft">SAVE AS DRAFT</a>
                    <button type="submit" class="btn btn-primary post-btn">POST COLLABORATION <i class="fa-solid fa-play play-icon"></i></button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/collaboration.js"></script>
</body>
</html>
