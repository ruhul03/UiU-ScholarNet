<?php
require_once('../includes/auth_check.php');

// Fetch Home Stats
$project_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM projects WHERE creator_id = $user_id");
$projects = mysqli_fetch_assoc($project_count)['total'];

$tasks_done = mysqli_query($conn, "SELECT COUNT(*) as total FROM tasks WHERE assigned_to = $user_id AND status = 'done'");
$tasks = mysqli_fetch_assoc($tasks_done)['total'];

$files_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM resources WHERE user_id = $user_id");
$files = mysqli_fetch_assoc($files_count)['total'];
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
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
            <h1>Good morning, <?php echo explode(' ', $user_data['full_name'])[0]; ?> 👋</h1>
            <p>You have 4 pending tasks and 2 new collaboration requests today.</p>
            
            <div class="dash-actions">
                <a href="collaboration.php" class="btn btn-primary" style="background-color: var(--secondary-color);"><i class="fa-solid fa-plus"></i> Post Collaboration</a>
                <a href="projects.php" class="btn btn-outline" style="background: white; border-color: #000;">View My Projects</a>
            </div>
        </section>

        <!-- Stats -->
        <section class="dash-stats">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-folder-open" style="color: var(--secondary-color);"></i></div>
                <div class="stat-info">
                    <h4>PROJECTS</h4>
                    <div class="value"><?php echo $projects; ?></div>
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
                            <button class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.8rem;">Approve</button>
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
                    <a href="#" class="action-item">
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
                    <div class="progress-bar">
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

</body>
</html>
