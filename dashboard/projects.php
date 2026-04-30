<?php
require_once('../includes/auth_check.php');

// Fetch Projects
$stmt = $conn->prepare("SELECT * FROM projects WHERE creator_id = ? ORDER BY created_at DESC");
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
                <input type="text" placeholder="Search archive, projects, or collaborators...">
            </div>
            <div class="header-actions">
                <i class="fa-regular fa-bell header-icon"></i>
                <div class="user-info">
                    <i class="fa-regular fa-user header-icon"></i> Personal Archive
                </div>
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
                            <span class="contributors-count"><i class="fa-solid fa-user-group"></i> 12 Contributors</span>
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
                    <div class="options-icon"><i class="fa-solid fa-ellipsis-vertical"></i></div>
                </div>
                <?php endwhile; ?>

                <!-- Create New Project Empty State Box -->
                <div class="create-project-box" onclick="openModal()">
                    <div class="create-project-icon">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <h3 class="create-project-title">Create New Project</h3>
                    <p class="create-project-subtitle">ARCHIVE A NEW RESEARCH DOMAIN</p>
                </div>
            </div>
        </section>

        <!-- Insights Section -->
        <section class="insights-grid">
            <div>
                <h2 class="insights-title">Archive Insights</h2>
                <div class="insights-list">
                    <div class="insights-item">
                        <span class="insights-label"><i class="fa-solid fa-circle stat-dot stat-dot-gold"></i> Research Domains</span>
                        <span class="insights-value">4 Active</span>
                    </div>
                    <div class="insights-item">
                        <span class="insights-label"><i class="fa-solid fa-circle stat-dot stat-dot-blue"></i> Peer Collaborators</span>
                        <span class="insights-value">42 Scientists</span>
                    </div>
                    <div class="insights-item">
                        <span class="insights-label"><i class="fa-solid fa-circle stat-dot stat-dot-brown"></i> Documentation Velocity</span>
                        <span class="insights-value">+12% this month</span>
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
                    <a href="javascript:void(0)" onclick="closeModal()" class="cancel-link">CANCEL</a>
                    <button type="submit" class="btn btn-primary create-btn">CREATE PROJECT +</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/projects.js"></script>
</body>
</html>
