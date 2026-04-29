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
        <header class="dash-header" style="margin-bottom: 2rem;">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass" style="opacity: 0.3;"></i>
                <input type="text" placeholder="Search opportunities...">
            </div>
            <div style="display: flex; align-items: center; gap: 1.5rem;">
                <div class="nav-links" style="display: flex; gap: 2rem; font-weight: 700; font-size: 0.9rem;">
                    <a href="#" style="color: var(--secondary-color); border-bottom: 2px solid var(--secondary-color);">Discovery</a>
                    <a href="#" style="opacity: 0.5;">My Network</a>
                </div>
                <div style="display: flex; gap: 1rem; margin-left: 2rem;">
                    <i class="fa-regular fa-bell" style="font-size: 1.2rem; opacity: 0.5;"></i>
                    <i class="fa-regular fa-user" style="font-size: 1.2rem; opacity: 0.5;"></i>
                </div>
            </div>
        </header>

        <section class="discovery-header">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 3.5rem; margin-bottom: 0.5rem;">Collaboration Finder</h1>
                    <p style="opacity: 0.5; max-width: 600px; font-size: 1.1rem;">Discover research partners, project collaborators, and interdisciplinary opportunities across the university network.</p>
                </div>
                <button class="btn btn-primary" onclick="openModal()" style="background-color: #856a14; padding: 1rem 2.5rem; border-radius: 4px;">
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

        <div class="collaboration-grid" style="grid-template-columns: repeat(3, 1fr); gap: 2.5rem;">
            <?php while($row = $result->fetch_assoc()): ?>
            <!-- Dynamic High-Fidelity Card -->
            <div class="collab-card">
                <div class="card-tag">RESEARCH PAPER</div>
                
                <div class="card-author-info">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['full_name']); ?>&background=f5f5f5&color=0a1128" alt="Author">
                </div>

                <h3 style="font-size: 1.5rem; margin-bottom: 1rem;"><?php echo htmlspecialchars($row['title']); ?></h3>
                <p style="font-size: 0.9rem; opacity: 0.6; margin-bottom: 2rem; flex: 1;">
                    <?php 
                        $desc = (string)($row['description'] ?? '');
                        echo htmlspecialchars((strlen($desc) > 120) ? substr($desc, 0, 120) . '...' : $desc);
                    ?>
                </p>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
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
            <div class="collab-card" style="background: var(--primary-color); color: var(--white); border-left: none;">
                <div style="display: flex; justify-content: flex-end; margin-bottom: 2rem;">
                    <span style="background: #c5a022; color: #fff; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.6rem; font-weight: 800;">ACTIVE REQUEST</span>
                </div>
                <h3 style="color: var(--white); font-size: 1.8rem; margin-bottom: 1rem;">Hackathon Team</h3>
                <p style="opacity: 0.7; font-size: 0.9rem; margin-bottom: 2.5rem;">Building a sustainable fintech app for the upcoming Inter-University challenge. 2 slots left!</p>
                
                <div style="margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; font-size: 0.75rem; margin-bottom: 0.5rem;">
                        <span style="opacity: 0.6;">Total Applicants</span>
                        <span style="color: var(--secondary-color); font-weight: 700;">14 People</span>
                    </div>
                    <div class="progress-bar" style="background: rgba(255,255,255,0.1); height: 4px;">
                        <div class="progress-fill" style="width: 70%;"></div>
                    </div>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button class="btn btn-primary" style="flex: 1; justify-content: center; background: var(--secondary-color); color: var(--primary-color);">VIEW DETAILS</button>
                    <button class="btn btn-outline" style="border-color: rgba(255,255,255,0.2); color: #fff;"><i class="fa-solid fa-pen-nib"></i></button>
                </div>
            </div>
        </div>

        <!-- Pagination / Load More -->
        <div style="text-align: center; margin-top: 5rem; padding-bottom: 5rem;">
            <p style="font-size: 0.9rem; opacity: 0.4; margin-bottom: 1.5rem;">Showing 6 of 124 available collaborations.</p>
            <button style="background: none; border: none; font-weight: 700; color: var(--secondary-color); border-bottom: 2px solid var(--secondary-color); padding-bottom: 0.5rem; cursor: pointer;">Load More Opportunities</button>
        </div>
    </main>

    <!-- Post Collaboration Modal (Updated Design) -->
    <div class="modal-overlay" id="collabModal" style="display: none;">
        <div class="modal-content" style="max-width: 800px; padding: 4rem;">
            <i class="fa-solid fa-xmark modal-close" onclick="closeModal()"></i>
            <h2 style="font-size: 2.5rem; margin-bottom: 2.5rem;">Post New Collaboration</h2>
            
            <form action="../actions/post_collaboration.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php 
                    require_once('../includes/csrf.php');
                    echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
                ?>">
                <div class="form-group">
                    <label>PROJECT TITLE</label>
                    <input type="text" name="title" placeholder="e.g. AI in Sustainable Architecture" style="background: #fdfcf8; border-color: #eee;" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>DEPARTMENT</label>
                        <select name="department" style="background: #fdfcf8; border-color: #eee;" required>
                            <option value="Computer Science">Computer Science</option>
                            <option value="EEE">EEE</option>
                            <option value="Economics">Economics</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>REQUIRED ROLES/SKILLS</label>
                        <input type="text" name="skills" placeholder="e.g. Python, UI/UX, Research" style="background: #fdfcf8; border-color: #eee;">
                    </div>
                </div>

                <div class="form-group">
                    <label>COLLABORATION DESCRIPTION</label>
                    <textarea name="description" rows="5" style="width: 100%; padding: 1rem; border: 1px solid #eee; border-radius: 4px; background: #fdfcf8;" placeholder="Describe your project and what you're looking for..."></textarea>
                </div>

                <div style="background: #fdfcf8; padding: 1.5rem; border: 1px solid #eee; border-radius: 8px; border-left: 4px solid var(--secondary-color); margin-bottom: 3rem; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 1.5rem;">
                        <div style="width: 100px; height: 60px; background: #eee; border-radius: 4px; overflow: hidden;">
                            <img src="https://images.unsplash.com/photo-1517245386807-bb43f82c33c4?auto=format&fit=crop&q=80&w=200" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.6;">
                        </div>
                        <div>
                            <div style="font-weight: 700; font-size: 0.9rem;">Project Cover Image</div>
                            <div style="font-size: 0.75rem; opacity: 0.5;">Recommended: 1200 x 630px. High-resolution archival imagery preferred.</div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline" style="font-size: 0.75rem; border: none; font-weight: 700; color: var(--secondary-color);">Upload</button>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <a href="#" style="font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; color: #888;">SAVE AS DRAFT</a>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--secondary-color); color: var(--primary-color); padding: 1rem 3rem; font-size: 0.9rem;">POST COLLABORATION <i class="fa-solid fa-play" style="font-size: 0.7rem; margin-left: 0.5rem;"></i></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('collabModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('collabModal').style.display = 'none';
        }
    </script>

</body>
</html>
