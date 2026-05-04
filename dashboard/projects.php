<?php
require_once('../includes/auth_check.php');
require_once('../includes/csrf.php');

// Fetch notification counts
$ptStmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status != 'done'");
$ptStmt->bind_param("i", $user_id);
$ptStmt->execute();
$pending_tasks = (int)($ptStmt->get_result()->fetch_assoc()['total'] ?? 0);

$crStmt = $conn->prepare("SELECT COUNT(*) as total FROM collaboration_applications ca JOIN collaboration_posts cp ON ca.post_id = cp.id WHERE cp.user_id = ? AND ca.status = 'pending'");
$crStmt->bind_param("i", $user_id);
$crStmt->execute();
$collab_requests = (int)($crStmt->get_result()->fetch_assoc()['total'] ?? 0);

// Insights queries
$activeProjStmt = $conn->prepare("SELECT COUNT(*) as total FROM projects WHERE creator_id = ? AND status = 'active'");
$activeProjStmt->bind_param("i", $user_id);
$activeProjStmt->execute();
$active_projects = (int)($activeProjStmt->get_result()->fetch_assoc()['total'] ?? 0);

$collabStmt = $conn->prepare("
    SELECT COUNT(DISTINCT t.assigned_to) as total 
    FROM tasks t 
    JOIN projects p ON t.project_id = p.id 
    WHERE p.creator_id = ? AND t.assigned_to != ? AND t.assigned_to IS NOT NULL
");
$collabStmt->bind_param("ii", $user_id, $user_id);
$collabStmt->execute();
$peer_collaborators = (int)($collabStmt->get_result()->fetch_assoc()['total'] ?? 0);

// Fetch Projects
$stmt = $conn->prepare("
    SELECT p.*, 
           (SELECT COUNT(DISTINCT assigned_to) FROM tasks WHERE project_id = p.id AND assigned_to IS NOT NULL) as contributors_count,
           (SELECT COUNT(*) FROM collaboration_posts WHERE project_id = p.id AND status = 'open') as active_collabs
    FROM projects p 
    WHERE p.creator_id = ? 
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects | UIU ScholarNet</title>
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
        <header class="dash-header dash-header-lg">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" placeholder="Search repository, projects, or collaborators...">
            </div>
            <div class="header-actions">
                <a href="#" class="notification-icon" style="color: inherit; text-decoration: none; position: relative; margin-right: 15px;">
                    <i class="fa-regular fa-bell header-icon"></i>
                    <?php if ($collab_requests > 0 || $pending_tasks > 0): ?>
                        <span class="notification-dot" style="top: 0px; right: 2px;"></span>
                    <?php endif; ?>
                </a>
                <a href="profile.php" class="user-info" style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-regular fa-user header-icon"></i>
                </a>
            </div>
        </header>

        <section class="projects-section">
            <div class="projects-header">
                <div>
                    <h1 class="projects-title">My Projects</h1>
                    <p class="projects-desc">Managing the digital corpus of ongoing research, collaborative ventures, and institutional initiatives.</p>
                </div>
                <button class="btn btn-primary btn-new-project" onclick="openModal()">
                    <i class="fa-solid fa-plus"></i> NEW PROJECT
                </button>
            </div>

            <?php include('../includes/alerts.php'); ?>

            <div class="project-horizontal-list">
                <?php while($row = $result->fetch_assoc()): ?>
                <!-- Dynamic Horizontal Card -->
                <div class="project-horizontal-card">
                    <div class="project-brand">
                        <i class="fa-solid fa-brain"></i>
                    </div>
                    <div class="project-main-info">
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="project-stats-row">
                            <span class="status-chip status-<?php echo $row['status']; ?>"><?php echo strtoupper($row['status']); ?></span>
                            <?php if ($row['active_collabs'] > 0): ?>
                                <span class="status-chip" style="background: #e3f2fd; color: #1565c0;"><i class="fa-solid fa-users-viewfinder"></i> COLLAB ACTIVE</span>
                            <?php endif; ?>
                            <span class="contributors-count"><i class="fa-solid fa-user-group"></i> <?php echo (int)$row['contributors_count']; ?> Contributors</span>
                        </div>
                    </div>
                    <div class="project-progress-block">
                        <div class="progress-label">
                            <span>RESEARCH PROGRESS</span>
                            <span><?php echo $row['progress']; ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill progress-fill-dynamic" style="width: <?php echo $row['progress']; ?>%;"></div>
                        </div>
                    </div>
                    <div class="project-actions" style="position: relative; display: flex; align-items: center; gap: 1rem;">
                        <a href="preprints.php" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.4rem 0.8rem;"><i class="fa-solid fa-file-pdf"></i> Publish as Preprint</a>
                        <div class="options-wrapper">
                            <div class="options-icon" onclick="toggleProjectOptions(event, <?php echo $row['id']; ?>)">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </div>
                            <div class="options-dropdown" id="dropdown-<?php echo $row['id']; ?>">
                                <a href="edit_project.php?id=<?php echo $row['id']; ?>"><i class="fa-regular fa-pen-to-square"></i> Edit Details</a>
                                
                                <div class="dropdown-divider"></div>
                                
                                <form action="../actions/delete_project.php" method="POST" id="delete-form-<?php echo $row['id']; ?>" style="display:none;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="project_id" value="<?php echo $row['id']; ?>">
                                </form>
                                <a href="javascript:void(0)" class="delete-option delete-trigger" style="color: #ff4d4d;" data-id="<?php echo $row['id']; ?>">
                                    <i class="fa-regular fa-trash-can"></i> Remove Project
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>

                <!-- Create New Project Empty State Box -->
                <div class="create-project-box" onclick="openModal()">
                    <div class="create-project-icon">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <h3 class="create-project-title">Create New Project</h3>
                    <p class="create-project-subtitle">CREATE A NEW RESEARCH DOMAIN</p>
                </div>
            </div>
        </section>

        <!-- Insights Section -->
        <section class="insights-grid">
            <div>
                <h2 class="insights-title">Project Insights</h2>
                <div class="insights-list">
                    <div class="insights-item">
                        <span class="insights-label"><i class="fa-solid fa-circle stat-dot stat-dot-gold"></i> Research Domains</span>
                        <span class="insights-value"><?php echo $active_projects; ?> Active</span>
                    </div>
                    <div class="insights-item">
                        <span class="insights-label"><i class="fa-solid fa-circle stat-dot stat-dot-blue"></i> Peer Collaborators</span>
                        <span class="insights-value"><?php echo $peer_collaborators; ?> Scientists</span>
                    </div>
                    <div class="insights-item">
                        <span class="insights-label"><i class="fa-solid fa-circle stat-dot stat-dot-brown"></i> Platform Updates</span>
                        <span class="insights-value">Syncing</span>
                    </div>
                </div>
            </div>
            <div class="collab-heat-card">
                <div class="collab-heat-pattern"></div>
                <div class="collab-heat-label">COLLABORATION HEAT</div>
                <h3 class="collab-heat-title">High Intensity</h3>
                <p class="collab-heat-desc">Peak interaction at 14:00 GMT</p>
            </div>
        </section>
    </main>

    <!-- Create Project Modal (High-Fidelity) -->
    <div class="modal-overlay modal-hidden" id="projectModal">
        <div class="modal-content modal-wider">
            <i class="fa-solid fa-xmark modal-close" onclick="closeModal()"></i>
            <h2 class="modal-title-large">Create New Project</h2>
            <p class="modal-subtitle">INSTITUTIONAL ARCHIVE ENTRY</p>
            
            <form action="../actions/create_project.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label>PROJECT TITLE</label>
                    <input type="text" name="title" placeholder="e.g. AI in Sustainable Architecture" class="form-input-light" required>
                </div>
                
                <div class="form-row form-row-align-end">
                    <div class="form-group form-group-wide">
                        <label>PRIMARY DEPARTMENT</label>
                        <select name="department" class="form-input-light" required>
                            <option value="">Select a Department</option>
                            <option value="Computer Science & Engineering">Computer Science & Engineering (CSE)</option>
                            <option value="Electrical & Electronic Engineering">Electrical & Electronic Engineering (EEE)</option>
                            <option value="Civil Engineering">Civil Engineering (CE)</option>
                            <option value="Business Administration">Business Administration (BBA)</option>
                            <option value="Economics">Economics</option>
                            <option value="Data Science">Data Science</option>
                            <option value="Biotechnology">Biotechnology</option>
                            <option value="Pharmacy">Pharmacy</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="English">English</option>
                            <option value="Media Studies & Journalism">Media Studies & Journalism (MSJ)</option>
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
                    <div class="researcher-tags" id="invitedResearchersProject">
                        <!-- Dynamic tags will appear here -->
                    </div>
                    <div class="search-container search-container-wide">
                        <i class="fa-solid fa-user-plus" style="opacity: 0.3;"></i>
                        <input type="text" placeholder="Search by name or ORCID...">
                    </div>
                </div>

                <div class="modal-footer">
                    <a href="javascript:void(0)" onclick="closeModal()" class="cancel-link">CANCEL</a>
                    <button type="submit" class="btn btn-primary create-btn">CREATE PROJECT +</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/projects.js"></script>
</body>
</html>
