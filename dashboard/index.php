<?php
require_once('../includes/auth_check.php');

// Fetch Home Stats
$pstmt = $conn->prepare("SELECT COUNT(*) as total FROM projects WHERE creator_id = ?");
$pstmt->bind_param("i", $user_id);
$pstmt->execute();
$projects_total = (int)($pstmt->get_result()->fetch_assoc()['total'] ?? 0);

$tstmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status = 'done'");
$tstmt->bind_param("i", $user_id);
$tstmt->execute();
$tasks = (int)($tstmt->get_result()->fetch_assoc()['total'] ?? 0);

$fstmt = $conn->prepare("SELECT COUNT(*) as total FROM resources WHERE user_id = ?");
$fstmt->bind_param("i", $user_id);
$fstmt->execute();
$files = (int)($fstmt->get_result()->fetch_assoc()['total'] ?? 0);

// Fetch Projects for the popup
$listStmt = $conn->prepare("SELECT * FROM projects WHERE creator_id = ? ORDER BY created_at DESC LIMIT 4");
$listStmt->bind_param("i", $user_id);
$listStmt->execute();
$projects_list_result = $listStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | UIU ScholarNet</title>
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
        <!-- Header -->
        <header class="dash-header">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass" style="opacity: 0.3;"></i>
                <input type="text" placeholder="Search projects, researchers, or papers...">
            </div>
            <div class="nav-actions">
                <a href="#" style="font-size: 1.2rem; margin-right: 1.5rem; position: relative;">
                    <i class="fa-regular fa-bell"></i>
                    <span style="position: absolute; top: -5px; right: -5px; width: 8px; height: 8px; background: red; border-radius: 50%; border: 2px solid var(--white);"></span>
                </a>
                <a href="#" class="btn btn-outline"><i class="fa-regular fa-user"></i> Account</a>
            </div>
        </header>

        <!-- Welcome -->
        <section class="greeting">
            <h1>Good morning, <?php echo htmlspecialchars(explode(' ', $user_data['full_name'])[0]); ?> 👋</h1>
            <p>You have 4 pending tasks and 2 new collaboration requests today.</p>
            
            <div class="dash-actions">
                <a href="collaboration.php" class="btn btn-primary" style="background-color: var(--secondary-color); color: var(--primary-color);"><i class="fa-solid fa-plus"></i> Post Collaboration</a>
                <button class="btn btn-outline" onclick="openProjectsModal()" style="background: white; border-color: #000; font-weight: 700;">View My Projects</button>
            </div>
        </section>

        <!-- Stats -->
        <section class="dash-stats">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-folder-open" style="color: var(--secondary-color);"></i></div>
                <div class="stat-info">
                    <h4>PROJECTS</h4>
                    <div class="value"><?php echo $projects_total; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-check-double" style="color: #4CAF50;"></i></div>
                <div class="stat-info">
                    <h4>TASKS DONE</h4>
                    <div class="value"><?php echo $tasks; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-star" style="color: #FFC107;"></i></div>
                <div class="stat-info">
                    <h4>REPUTATION</h4>
                    <div class="value"><?php echo number_format($user_data['points']); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-cloud-arrow-up" style="color: #2196F3;"></i></div>
                <div class="stat-info">
                    <h4>FILES UPLOADED</h4>
                    <div class="value"><?php echo $files; ?></div>
                </div>
            </div>
        </section>

        <!-- Grid -->
        <div class="dash-grid">
            <!-- Left: Activity -->
            <section class="activity-feed">
                <h3>Recent Activity <a href="#" style="font-size: 0.8rem; color: var(--secondary-color); font-weight: 600;">View Timeline</a></h3>
                
                <div class="activity-card">
                    <div class="activity-icon"><i class="fa-solid fa-user-plus"></i></div>
                    <div class="activity-body">
                        <div class="activity-header">
                            <h4>Sarah Khan requested to join</h4>
                            <span class="time">2h ago</span>
                        </div>
                        <div class="activity-content">
                            "Quantum Computing & Ethics" project. Background in applied physics.
                        </div>
                        <div style="display: flex; gap: 0.5rem;">
                            <button class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.8rem; background-color: var(--secondary-color); color: var(--primary-color);">Approve</button>
                            <button class="btn btn-outline" style="padding: 0.4rem 1rem; font-size: 0.8rem;">View Profile</button>
                        </div>
                    </div>
                </div>

                <div class="activity-card">
                    <div class="activity-icon"><i class="fa-solid fa-file-pen"></i></div>
                    <div class="activity-body">
                        <div class="activity-header">
                            <h4>Document Updated</h4>
                            <span class="time">5h ago</span>
                        </div>
                        <div class="activity-content">
                            Draft_v2_Methodology.docx was edited by <strong>Dr. Marcus Thorne</strong> in Global Climate Modeling.
                        </div>
                    </div>
                </div>
            </section>

            <!-- Right: Quick Actions & Spotlight -->
            <aside class="dash-sidebar">
                <section class="quick-actions">
                    <h3>Quick Actions</h3>
                    <a href="resources.php" class="action-item">
                        <span><i class="fa-solid fa-file-arrow-up" style="color: var(--secondary-color); margin-right: 0.5rem;"></i> Upload research paper</span>
                        <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                    </a>
                    <a href="#" class="action-item">
                        <span><i class="fa-solid fa-user-group" style="color: var(--secondary-color); margin-right: 0.5rem;"></i> Invite team members</span>
                        <i class="fa-solid fa-chevron-right" style="font-size: 0.7rem; opacity: 0.3;"></i>
                    </a>
                </section>

                <div class="spotlight-card">
                    <div class="status">IN PROGRESS</div>
                    <h4>Sustainable Urban Design</h4>
                    <div class="progress-bar" style="background: rgba(255,255,255,0.1); height: 4px;">
                        <div class="progress-fill" style="width: 65%;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.7rem; opacity: 0.6; margin-bottom: 1.5rem;">
                        <span>65% Completed</span>
                    </div>
                    <button class="btn btn-primary" style="width: 100%; justify-content: center; background: var(--secondary-color); color: var(--primary-color);">Open Workspace</button>
                </div>
            </aside>
        </div>
    </main>

    <!-- My Active Projects Modal -->
    <div class="modal-overlay" id="projectsModal" style="display: none;">
        <div class="modal-content" style="max-width: 700px; padding: 3rem;">
            <i class="fa-solid fa-xmark modal-close" onclick="closeProjectsModal()"></i>
            <h2 style="font-size: 1.8rem; margin-bottom: 1rem;">My Active Projects</h2>
            
            <div class="popup-project-list">
                <?php while($proj = $projects_list_result->fetch_assoc()): ?>
                <div class="popup-project-item">
                    <div class="popup-project-info">
                        <div class="project-mini-icon">
                            <i class="fa-solid fa-microscope"></i>
                        </div>
                        <div class="project-details">
                            <h4><?php echo htmlspecialchars($proj['title']); ?></h4>
                            <div class="project-sub">
                                <span class="status"><?php echo strtoupper($proj['status']); ?></span>
                                <span class="time">Last edit 2h ago</span>
                            </div>
                        </div>
                    </div>
                    <div class="popup-project-progress">
                        <div class="progress-bar" style="height: 4px; background: #eee;">
                            <div class="progress-fill" style="width: <?php echo $proj['progress']; ?>%;"></div>
                        </div>
                        <div class="percentage"><?php echo $proj['progress']; ?>%</div>
                    </div>
                    <a href="tasks.php?project_id=<?php echo (int)$proj['id']; ?>" class="popup-project-open">OPEN <i class="fa-solid fa-arrow-right" style="font-size: 0.6rem; margin-left: 0.5rem;"></i></a>
                </div>
                <?php endwhile; ?>
            </div>

            <div style="text-align: center; margin-top: 3rem;">
                <button class="btn btn-primary" onclick="closeProjectsModal(); openCreateModal();" style="background-color: var(--secondary-color); color: var(--primary-color); padding: 0.8rem 2.5rem; font-size: 0.8rem;">
                    <i class="fa-solid fa-plus"></i> CREATE NEW PROJECT
                </button>
            </div>
        </div>
    </div>

    <!-- Create Project Modal (High-Fidelity) -->
    <div class="modal-overlay" id="createProjectModal" style="display: none;">
        <div class="modal-content" style="max-width: 750px; padding: 4rem;">
            <i class="fa-solid fa-xmark modal-close" onclick="closeCreateModal()"></i>
            <h2 style="font-size: 2.5rem; margin-bottom: 0.5rem;">Create New Project</h2>
            <p style="font-size: 0.7rem; font-weight: 800; color: #aaa; letter-spacing: 1px; margin-bottom: 3rem; text-transform: uppercase;">INSTITUTIONAL ARCHIVE ENTRY</p>
            
            <form action="../actions/create_project.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php 
                    require_once('../includes/csrf.php');
                    echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
                ?>">
                <div class="form-group">
                    <label>PROJECT TITLE</label>
                    <input type="text" name="title" placeholder="e.g. AI in Sustainable Architecture" style="background: #fdfcf8;" required>
                </div>
                
                <div class="form-row" style="align-items: flex-end; margin-bottom: 2rem;">
                    <div class="form-group" style="flex: 1.5;">
                        <label>PRIMARY DEPARTMENT</label>
                        <select name="department" style="background: #fdfcf8;" required>
                            <option value="">Select a Department</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="EEE">EEE</option>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 2; margin-bottom: 1.5rem;">
                        <label>VISIBILITY</label>
                        <div class="visibility-group">
                            <label class="visibility-item">
                                <input type="radio" name="visibility" value="public" checked> Public
                            </label>
                            <label class="visibility-item">
                                <input type="radio" name="visibility" value="institution"> Institution Only
                            </label>
                            <label class="visibility-item">
                                <input type="radio" name="visibility" value="private"> Private
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>PROJECT DESCRIPTION</label>
                    <textarea name="description" rows="4" style="width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 4px; background: #fdfcf8;" placeholder="Briefly outline the scope and research objectives..."></textarea>
                </div>

                <div class="invite-researchers">
                    <label style="display: block; font-size: 0.7rem; font-weight: 800; color: #aaa; margin-bottom: 1rem;">INVITE RESEARCHERS</label>
                    <div class="researcher-tags">
                        <div class="researcher-tag">Dr. Julian Thorne <i class="fa-solid fa-xmark"></i></div>
                        <div class="researcher-tag">Prof. Elena Vance <i class="fa-solid fa-xmark"></i></div>
                    </div>
                    <div class="search-container" style="max-width: 100%; background: #fff; border: 1px solid #ddd; padding: 0.6rem 1rem;">
                        <i class="fa-solid fa-user-plus" style="opacity: 0.3;"></i>
                        <input type="text" placeholder="Search by name or ORCID...">
                    </div>
                </div>

                <div class="modal-footer-actions">
                    <a href="javascript:void(0)" onclick="closeCreateModal()" style="font-weight: 700; font-size: 0.8rem; color: var(--primary-color);">CANCEL</a>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--secondary-color); color: var(--primary-color); padding: 1rem 2.5rem; font-size: 0.85rem; border-radius: 4px;">CREATE PROJECT +</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openProjectsModal() {
            document.getElementById('projectsModal').style.display = 'flex';
        }
        function closeProjectsModal() {
            document.getElementById('projectsModal').style.display = 'none';
        }
        function openCreateModal() {
            document.getElementById('createProjectModal').style.display = 'flex';
        }
        function closeCreateModal() {
            document.getElementById('createProjectModal').style.display = 'none';
        }
    </script>

</body>
</html>
