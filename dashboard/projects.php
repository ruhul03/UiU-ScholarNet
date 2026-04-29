<?php
require_once('../includes/auth_check.php');

// Fetch Projects
$query = "SELECT * FROM projects WHERE creator_id = $user_id ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
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
        <header class="dash-header">
            <h2 style="font-size: 2.5rem;">My Active Projects</h2>
            <button class="btn btn-primary" onclick="openModal()" style="background-color: var(--secondary-color);"><i class="fa-solid fa-plus"></i> Create New Project</button>
        </header>

        <div class="project-list-grid">
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <!-- Dynamic Project Card -->
            <div class="project-item-card">
                <div class="project-cover" style="background-color: <?php echo '#' . substr(md5($row['title']), 0, 6); ?>22;">
                    <div class="project-status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></div>
                </div>
                <div class="project-item-body">
                    <h3><?php echo $row['title']; ?></h3>
                    <div class="project-meta">Last edit <?php echo date('M d', strtotime($row['created_at'])); ?></div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $row['progress']; ?>%;"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem;">
                        <span style="font-size: 0.8rem; font-weight: 700;"><?php echo $row['progress']; ?>%</span>
                        <a href="tasks.php?project_id=<?php echo $row['id']; ?>" class="btn btn-outline" style="padding: 0.5rem 1rem; font-size: 0.8rem;">Open <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>

    <!-- Create Project Modal -->
    <div class="modal-overlay" id="projectModal" style="display: none;">
        <div class="modal-content">
            <i class="fa-solid fa-xmark modal-close" onclick="closeModal()"></i>
            <h2>Initiate New Research</h2>
            <p style="opacity: 0.5; margin-bottom: 2rem;">Define your project scope and objectives.</p>
            
            <form action="../actions/create_project.php" method="POST">
                <div class="form-group">
                    <label>Research Title</label>
                    <input type="text" name="title" placeholder="e.g. Quantum Computing in Finance" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" style="width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 6px;"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Initial Status</label>
                        <select name="status">
                            <option value="planning">Planning</option>
                            <option value="active">Active</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Initial Progress (%)</label>
                        <input type="number" name="progress" value="0" min="0" max="100">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; background-color: var(--secondary-color); color: var(--primary-color);">CREATE PROJECT</button>
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
