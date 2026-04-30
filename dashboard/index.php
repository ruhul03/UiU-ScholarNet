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
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" placeholder="Search projects, researchers, or papers...">
            </div>
            <div class="nav-actions">
                <a href="#" class="notification-icon">
                    <i class="fa-regular fa-bell"></i>
                    <span class="notification-dot"></span>
                </a>
                <a href="#" class="btn btn-outline"><i class="fa-regular fa-user"></i> Account</a>
            </div>
        </header>

        <!-- Welcome -->
        <section class="greeting">
            <h1>Good morning, <?php echo htmlspecialchars(explode(' ', $user_data['full_name'])[0]); ?> 👋</h1>
            <p>You have 4 pending tasks and 2 new collaboration requests today.</p>
            
            <div class="dash-actions">
                <a href="collaboration.php" class="btn btn-primary btn-collab"><i class="fa-solid fa-plus"></i> Post Collaboration</a>
                <button class="btn btn-outline btn-view-projects" onclick="openProjectsModal()">View My Projects</button>
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
                <div class="stat-icon"><i class="fa-solid fa-check-double stat-icon-green"></i></div>
                <div class="stat-info">
                    <h4>TASKS DONE</h4>
                    <div class="value"><?php echo $tasks; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-star stat-icon-yellow"></i></div>
                <div class="stat-info">
                    <h4>REPUTATION</h4>
                    <div class="value"><?php echo number_format($user_data['points']); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-cloud-arrow-up stat-icon-blue"></i></div>
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
                <h3>Recent Activity <a href="#" class="timeline-link">View Timeline</a></h3>
                
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
                            <button class="btn btn-primary approve-btn">Approve</button>
                            <button class="btn btn-outline view-btn-small">View Profile</button>
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
                        <span><i class="fa-solid fa-file-arrow-up action-icon"></i> Upload research paper</span>
                        <i class="fa-solid fa-chevron-right chevron-icon"></i>
                    </a>
                    <a href="#" class="action-item">
                        <span><i class="fa-solid fa-user-group action-icon"></i> Invite team members</span>
                        <i class="fa-solid fa-chevron-right chevron-icon"></i>
                    </a>
                </section>

                <div class="spotlight-card">
                    <div class="status">IN PROGRESS</div>
                    <h4>Sustainable Urban Design</h4>
                    <div class="progress-bar spotlight-progress-bar">
                        <div class="progress-fill spotlight-progress"></div>
                    </div>
                    <div class="spotlight-progress-text">
                        <span>65% Completed</span>
                    </div>
                    <button class="btn btn-primary spotlight-btn">Open Workspace</button>
                </div>
            </aside>
        </div>
    </main>

    <!-- My Active Projects Modal -->
    <div class="modal-overlay modal-hidden" id="projectsModal">
        <div class="modal-content modal-wide">
            <i class="fa-solid fa-xmark modal-close" onclick="closeProjectsModal()"></i>
            <h2 class="modal-title">My Active Projects</h2>
            
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
                        <div class="progress-bar progress-height-sm progress-bg-light">
                            <div class="progress-fill progress-fill-dynamic" style="width: <?php echo $proj['progress']; ?>%;"></div>
                        </div>
                        <div class="percentage"><?php echo $proj['progress']; ?>%</div>
                    </div>
                    <a href="tasks.php?project_id=<?php echo (int)$proj['id']; ?>" class="popup-project-open">OPEN <i class="fa-solid fa-arrow-right arrow-sm"></i></a>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="centered-actions">
                <button class="btn btn-primary create-btn-small" onclick="closeProjectsModal(); openCreateModal();">
                    <i class="fa-solid fa-plus"></i> CREATE NEW PROJECT
                </button>
            </div>
        </div>
    </div>

    <!-- Create Project Modal (High-Fidelity) -->
    <div class="modal-overlay modal-hidden" id="createProjectModal">
        <div class="modal-content modal-wider">
            <i class="fa-solid fa-xmark modal-close" onclick="closeCreateModal()"></i>
            <h2 class="modal-title-large">Create New Project</h2>
            <p class="modal-subtitle">INSTITUTIONAL ARCHIVE ENTRY</p>
            
            <form action="../actions/create_project.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php 
                    require_once('../includes/csrf.php');
                    echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
                ?>">
                <div class="form-group">
                    <label>PROJECT TITLE</label>
                    <input type="text" name="title" placeholder="e.g. AI in Sustainable Architecture" class="form-input-light" required>
                </div>
                
                <div class="form-row form-row-align-end">
                    <div class="form-group form-group-wide">
                        <label>PRIMARY DEPARTMENT</label>
                        <select name="department" class="form-input-light" required>
                            <option value="">Select a Department</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="EEE">EEE</option>
                        </select>
                    </div>
                    <div class="form-group form-group-wider">
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
                    <textarea name="description" rows="4" class="textarea-light" placeholder="Briefly outline the scope and research objectives..."></textarea>
                </div>

                <div class="invite-researchers">
                    <label class="invite-label">INVITE RESEARCHERS</label>
                    <div class="researcher-tags">
                        <div class="researcher-tag">Dr. Julian Thorne <i class="fa-solid fa-xmark"></i></div>
                        <div class="researcher-tag">Prof. Elena Vance <i class="fa-solid fa-xmark"></i></div>
                    </div>
                    <div class="search-container search-container-wide">
                        <i class="fa-solid fa-user-plus" style="opacity: 0.3;"></i>
                        <input type="text" placeholder="Search by name or ORCID...">
                    </div>
                </div>

                <div class="modal-footer">
                    <a href="javascript:void(0)" onclick="closeCreateModal()" class="cancel-link">CANCEL</a>
                    <button type="submit" class="btn btn-primary create-btn">CREATE PROJECT +</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
