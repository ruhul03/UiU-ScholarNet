<?php
require_once('../includes/auth_check.php');
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

$project_id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

// Fetch notification counts
$ptStmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status != 'done'");
$ptStmt->bind_param("i", $user_id);
$ptStmt->execute();
$pending_tasks = (int)($ptStmt->get_result()->fetch_assoc()['total'] ?? 0);

$crStmt = $conn->prepare("SELECT COUNT(*) as total FROM collaboration_applications ca JOIN collaboration_posts cp ON ca.post_id = cp.id WHERE cp.user_id = ? AND ca.status = 'pending'");
$crStmt->bind_param("i", $user_id);
$crStmt->execute();
$collab_requests = (int)($crStmt->get_result()->fetch_assoc()['total'] ?? 0);

// Fetch project details
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ? AND creator_id = ? LIMIT 1");
$stmt->bind_param("ii", $project_id, $user_id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    header("Location: projects.php");
    exit();
}

$departments = [
    "Computer Science & Engineering",
    "Electrical & Electronic Engineering",
    "Civil Engineering",
    "Business Administration",
    "Economics",
    "Data Science",
    "Biotechnology",
    "Pharmacy",
    "Mathematics",
    "English",
    "Media Studies & Journalism"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Project | UIU ScholarNet</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/projects.css">
    <style>
        .edit-form-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(0,0,0,0.05);
            margin-top: 2rem;
        }
        .form-section-title {
            font-size: 0.9rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: var(--secondary-color);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #eee;
        }
    </style>
</head>
<body class="dashboard-page">
    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <header class="dash-header dash-header-xl">
            <div class="breadcrumb-nav">
                <a href="projects.php">PROJECTS</a>
                <i class="fa-solid fa-chevron-right"></i>
                <span>EDIT PROJECT ENTRY</span>
            </div>
            <div class="header-actions">
                <a href="notifications.php" class="notification-icon" style="color: inherit; text-decoration: none; position: relative; margin-right: 15px;">
                    <i class="fa-regular fa-bell header-icon"></i>
                    <?php if ($collab_requests > 0 || $pending_tasks > 0): ?>
                        <span class="notification-dot" style="top: 0px; right: 2px;"></span>
                    <?php endif; ?>
                </a>
                <a href="profile.php" style="color: inherit; text-decoration: none;">
                    <i class="fa-regular fa-user header-icon"></i>
                </a>
            </div>
        </header>

        <section class="edit-hero" style="margin-bottom: 3rem;">
            <div class="section-label">INSTITUTIONAL REPOSITORY / CURATION</div>
            <h1 class="page-title" style="margin-top: 0.5rem;">Edit Project Details</h1>
            <p style="color: #666; font-size: 1.1rem; max-width: 600px;">Refine the parameters of your research entry to ensure accurate representation in the ScholarNet index.</p>
        </section>

        <div class="edit-form-card">
            <form action="../actions/update_project.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">

                <div class="form-section-title"><i class="fa-solid fa-info-circle"></i> CORE INFORMATION</div>
                
                <div class="form-group" style="margin-bottom: 2rem;">
                    <label style="font-weight: 700; font-size: 0.8rem; color: #888; margin-bottom: 10px; display: block;">PROJECT TITLE</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" class="form-input-light" placeholder="Enter formal research title..." required style="font-size: 1.2rem; font-weight: 700;">
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label style="font-weight: 700; font-size: 0.8rem; color: #888; margin-bottom: 10px; display: block;">RESEARCH ABSTRACT / DESCRIPTION</label>
                    <textarea name="description" rows="4" class="form-input-light" placeholder="Provide a brief summary of the project goals..." style="resize: none;"><?php echo htmlspecialchars($project['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                    <div class="form-group">
                        <label style="font-weight: 700; font-size: 0.8rem; color: #888; margin-bottom: 10px; display: block;">DEPARTMENT</label>
                        <select name="department" class="form-input-light" required>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept; ?>" <?php echo ($project['department'] === $dept) ? 'selected' : ''; ?>><?php echo $dept; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 700; font-size: 0.8rem; color: #888; margin-bottom: 10px; display: block;">PROGRESS (%)</label>
                        <input type="number" name="progress" value="<?php echo $project['progress']; ?>" min="0" max="100" class="form-input-light" required>
                    </div>
                </div>

                <div class="form-section-title" style="margin-top: 3rem;"><i class="fa-solid fa-shield-halved"></i> GOVERNANCE & STATUS</div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem;">
                    <div class="form-group">
                        <label style="font-weight: 700; font-size: 0.8rem; color: #888; margin-bottom: 10px; display: block;">CURRENT STATUS</label>
                        <select name="status" class="form-input-light">
                            <option value="planning" <?php echo ($project['status'] === 'planning') ? 'selected' : ''; ?>>PLANNING</option>
                            <option value="active" <?php echo ($project['status'] === 'active') ? 'selected' : ''; ?>>ACTIVE</option>
                            <option value="completed" <?php echo ($project['status'] === 'completed') ? 'selected' : ''; ?>>COMPLETED</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-weight: 700; font-size: 0.8rem; color: #888; margin-bottom: 10px; display: block;">VISIBILITY LEVEL</label>
                        <select name="visibility" class="form-input-light">
                            <option value="public" <?php echo ($project['visibility'] === 'public') ? 'selected' : ''; ?>>PUBLIC (Global Network)</option>
                            <option value="institution" <?php echo ($project['visibility'] === 'institution') ? 'selected' : ''; ?>>INSTITUTION (UIU Only)</option>
                            <option value="private" <?php echo ($project['visibility'] === 'private') ? 'selected' : ''; ?>>PRIVATE (Draft Mode)</option>
                        </select>
                    </div>
                </div>

                <div class="edit-actions" style="display: flex; gap: 1.5rem; align-items: center; border-top: 1px solid #eee; padding-top: 2rem;">
                    <button type="submit" class="btn btn-primary" style="padding: 1rem 3rem; font-size: 0.9rem;">SAVE CHANGES</button>
                    <a href="projects.php" class="btn btn-outline" style="padding: 1rem 2rem; font-size: 0.9rem; text-decoration: none; color: #666; border-color: #ddd;">CANCEL</a>
                </div>
            </form>
        </div>
    </main>

    <script src="../assets/js/projects.js"></script>
</body>
</html>
