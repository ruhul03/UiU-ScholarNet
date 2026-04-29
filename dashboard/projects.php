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
        <header class="dash-header" style="margin-bottom: 2rem;">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass" style="opacity: 0.3;"></i>
                <input type="text" placeholder="Search archive, projects, or collaborators...">
            </div>
            <div style="display: flex; gap: 1.5rem; align-items: center;">
                <i class="fa-regular fa-bell" style="font-size: 1.2rem; opacity: 0.5;"></i>
                <div style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; font-size: 0.85rem;">
                    <i class="fa-regular fa-user" style="opacity: 0.5;"></i> Personal Archive
                </div>
            </div>
        </header>

        <section style="margin-bottom: 4rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem;">
                <div>
                    <h1 style="font-size: 3.5rem; margin-bottom: 0.5rem;">My Projects</h1>
                    <p style="opacity: 0.5; max-width: 600px; font-size: 1.1rem;">Managing the digital corpus of ongoing research, collaborative ventures, and institutional initiatives.</p>
                </div>
                <button class="btn btn-primary" onclick="openModal()" style="background-color: var(--secondary-color); color: var(--primary-color); padding: 1rem 2rem;">
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
                            <span style="opacity: 0.4;"><i class="fa-solid fa-user-group"></i> 12 Contributors</span>
                        </div>
                    </div>
                    <div class="project-progress-block">
                        <div class="progress-label">
                            <span>RESEARCH PROGRESS</span>
                            <span><?php echo $row['progress']; ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $row['progress']; ?>%;"></div>
                        </div>
                    </div>
                    <div style="cursor: pointer; opacity: 0.3;"><i class="fa-solid fa-ellipsis-vertical"></i></div>
                </div>
                <?php endwhile; ?>

                <!-- Create New Project Empty State Box -->
                <div class="create-project-box" onclick="openModal()">
                    <div style="width: 50px; height: 50px; background: #eee; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <h3 style="font-size: 1.2rem; margin-bottom: 0.5rem;">Create New Project</h3>
                    <p style="opacity: 0.4; font-size: 0.85rem;">ARCHIVE A NEW RESEARCH DOMAIN</p>
                </div>
            </div>
        </section>

        <!-- Insights Section -->
        <section class="insights-grid">
            <div>
                <h2 style="font-size: 2rem; margin-bottom: 2rem;">Archive Insights</h2>
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.9rem; font-weight: 600;"><i class="fa-solid fa-circle" style="color: var(--secondary-color); font-size: 0.5rem; margin-right: 0.5rem;"></i> Research Domains</span>
                        <span style="font-weight: 800;">4 Active</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.9rem; font-weight: 600;"><i class="fa-solid fa-circle" style="color: #5c7c9c; font-size: 0.5rem; margin-right: 0.5rem;"></i> Peer Collaborators</span>
                        <span style="font-weight: 800;">42 Scientists</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.9rem; font-weight: 600;"><i class="fa-solid fa-circle" style="color: #8b5d44; font-size: 0.5rem; margin-right: 0.5rem;"></i> Documentation Velocity</span>
                        <span style="font-weight: 800;">+12% this month</span>
                    </div>
                </div>
            </div>
            <div style="background: var(--primary-color); padding: 3rem; border-radius: 8px; color: white; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; position: relative; overflow: hidden;">
                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0.1; background: url('https://www.transparenttextures.com/patterns/cubes.png');"></div>
                <div style="font-size: 0.65rem; font-weight: 800; color: var(--secondary-color); margin-bottom: 0.5rem; letter-spacing: 1px;">COLLABORATION HEAT</div>
                <h3 style="color: white; font-size: 2rem; margin-bottom: 0.5rem;">High Intensity</h3>
                <p style="opacity: 0.5; font-size: 0.8rem;">Peak interaction at 14:00 GMT</p>
            </div>
        </section>
    </main>

    <!-- Create Project Modal (High-Fidelity) -->
    <div class="modal-overlay" id="projectModal" style="display: none;">
        <div class="modal-content" style="max-width: 750px; padding: 4rem;">
            <i class="fa-solid fa-xmark modal-close" onclick="closeModal()"></i>
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
                    <a href="javascript:void(0)" onclick="closeModal()" style="font-weight: 700; font-size: 0.8rem; color: var(--primary-color);">CANCEL</a>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--secondary-color); color: var(--primary-color); padding: 1rem 2.5rem; font-size: 0.85rem; border-radius: 4px;">CREATE PROJECT +</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('projectModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('projectModal').style.display = 'none';
        }
    </script>

</body>
</html>
