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

$departments = [];
$depRes = $conn->query("SELECT name FROM departments ORDER BY name ASC");
if ($depRes) {
    while ($row = $depRes->fetch_assoc()) {
        $departments[] = $row['name'];
    }
}

// Fetch documents
$docStmt = $conn->prepare("SELECT id, title, updated_at FROM documents WHERE project_id = ? ORDER BY updated_at DESC");
$docStmt->bind_param("i", $project_id);
$docStmt->execute();
$documents = $docStmt->get_result();

// Fetch tasks
$taskStmt = $conn->prepare("SELECT t.*, u.full_name AS assignee_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.project_id = ? ORDER BY t.due_date ASC");
$taskStmt->bind_param("i", $project_id);
$taskStmt->execute();
$tasks = $taskStmt->get_result();

// Fetch preprints
$prepStmt = $conn->prepare("SELECT id, title, created_at, views_count FROM preprints WHERE project_id = ? ORDER BY created_at DESC");
$prepStmt->bind_param("i", $project_id);
$prepStmt->execute();
$preprints = $prepStmt->get_result();
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
    <link rel="stylesheet" href="../assets/css/lifecycle.css">
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
        <?php include('../includes/header.php'); ?>

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

                <!-- Lifecycle Pipeline Component -->
                <?php
                    $stages = ['planning', 'active', 'review', 'completed'];
                    $current_stage_idx = array_search($project['status'], $stages);
                ?>
                <div class="lifecycle-pipeline">
                    <div class="lifecycle-line">
                        <div class="lifecycle-progress" style="width: <?php echo ($current_stage_idx / (count($stages)-1)) * 100; ?>%;"></div>
                    </div>
                    <?php foreach ($stages as $idx => $stage): 
                        $status_class = '';
                        if ($idx < $current_stage_idx) $status_class = 'completed';
                        elseif ($idx === $current_stage_idx) $status_class = 'active';
                        
                        $icons = ['planning' => 'fa-lightbulb', 'active' => 'fa-person-digging', 'review' => 'fa-magnifying-glass-chart', 'completed' => 'fa-flag-checkered'];
                    ?>
                    <div class="lifecycle-stage <?php echo $status_class; ?>">
                        <div class="stage-icon"><i class="fa-solid <?php echo $icons[$stage]; ?>"></i></div>
                        <div class="stage-name"><?php echo $stage; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="lifecycle-checklist">
                    <h4><i class="fa-solid fa-list-check"></i> Stage Progression (Recommended)</h4>
                    <ul class="checklist-items">
                        <?php if ($project['status'] === 'planning'): ?>
                            <li class="done"><i class="fa-solid fa-check"></i> Define project title and abstract</li>
                            <li class="<?php echo ($tasks->num_rows > 0) ? 'done' : 'pending'; ?>"><i class="fa-solid fa-<?php echo ($tasks->num_rows > 0) ? 'check' : 'spinner'; ?>"></i> Create at least 1 task</li>
                            <p style="font-size: 0.85rem; color: #888; margin-top: 10px;">Move to ACTIVE when you are ready to start executing tasks.</p>
                        <?php elseif ($project['status'] === 'active'): ?>
                            <li class="<?php echo ($project['progress'] >= 50) ? 'done' : 'pending'; ?>"><i class="fa-solid fa-<?php echo ($project['progress'] >= 50) ? 'check' : 'spinner'; ?>"></i> Reach 50%+ completion on tasks</li>
                            <li class="<?php echo ($documents->num_rows > 0) ? 'done' : 'pending'; ?>"><i class="fa-solid fa-<?php echo ($documents->num_rows > 0) ? 'check' : 'spinner'; ?>"></i> Upload at least 1 document or resource</li>
                            <p style="font-size: 0.85rem; color: #888; margin-top: 10px;">Move to REVIEW to invite faculty or peers to review your work.</p>
                        <?php elseif ($project['status'] === 'review'): ?>
                            <?php if (!empty($project['supervisor_id'])): ?>
                                <li class="<?php echo ($project['supervisor_approved']) ? 'done' : 'pending'; ?>"><i class="fa-solid fa-<?php echo ($project['supervisor_approved']) ? 'check' : 'spinner'; ?>"></i> Faculty Supervisor Approval</li>
                            <?php else: ?>
                                <li class="done"><i class="fa-solid fa-check"></i> Peer review phase</li>
                            <?php endif; ?>
                            <p style="font-size: 0.85rem; color: #888; margin-top: 10px;">Move to COMPLETED when all feedback is addressed.</p>
                        <?php elseif ($project['status'] === 'completed'): ?>
                            <li class="done"><i class="fa-solid fa-check"></i> Project finalized</li>
                            <p style="font-size: 0.85rem; color: #888; margin-top: 10px;">This project is now locked for editing. You can publish it as a preprint.</p>
                            <div style="margin-top: 15px;">
                                <a href="file_upload.php?project_id=<?php echo $project['id']; ?>&type=preprint" class="btn btn-outline" style="padding: 0.5rem 1rem; text-decoration: none; display: inline-block;">
                                    <i class="fa-solid fa-upload"></i> PUBLISH PREPRINT
                                </a>
                            </div>
                        <?php endif; ?>
                    </ul>
                    
                    <?php if ($project['status'] === 'review' && $user_id == $project['supervisor_id'] && !$project['supervisor_approved']): ?>
                        <div style="margin-top: 15px;">
                            <a href="../actions/approve_project.php?id=<?php echo $project['id']; ?>&token=<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-approve" style="padding: 0.5rem 1rem; text-decoration: none; display: inline-block;">
                                <i class="fa-solid fa-check-double"></i> APPROVE PROJECT
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 3rem;">
                    <div class="form-group">
                        <label style="font-weight: 700; font-size: 0.8rem; color: #888; margin-bottom: 10px; display: block;">OVERRIDE STAGE</label>
                        <select name="status" class="form-input-light">
                            <option value="planning" <?php echo ($project['status'] === 'planning') ? 'selected' : ''; ?>>PLANNING</option>
                            <option value="active" <?php echo ($project['status'] === 'active') ? 'selected' : ''; ?>>ACTIVE</option>
                            <option value="review" <?php echo ($project['status'] === 'review') ? 'selected' : ''; ?>>REVIEW</option>
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

        <h2 style="margin-top: 4rem; margin-bottom: 1.5rem; font-family: var(--font-heading); font-size: 1.5rem;">Project Hub Ecosystem</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 4rem;">
            <!-- Documents Section -->
            <div class="card" style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.1rem; margin: 0;"><i class="fa-solid fa-file-lines" style="color: var(--primary-color);"></i> Documents</h3>
                    <a href="document_editor.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline" style="padding: 0.3rem 0.8rem; font-size: 0.75rem;"><i class="fa-solid fa-plus"></i> New</a>
                </div>
                <?php if ($documents->num_rows > 0): ?>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.8rem;">
                        <?php while($doc = $documents->fetch_assoc()): ?>
                            <li style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem;">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($doc['title']); ?></span>
                                    <span style="font-size: 0.75rem; color: #888;">Updated: <?php echo date('M d', strtotime($doc['updated_at'])); ?></span>
                                </div>
                                <a href="document_editor.php?document_id=<?php echo $doc['id']; ?>" style="color: var(--secondary-color);"><i class="fa-solid fa-pen-to-square"></i></a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p style="color: #888; font-size: 0.85rem; text-align: center; margin-top: 2rem;">No documents linked.</p>
                <?php endif; ?>
            </div>

            <!-- Tasks Section -->
            <div class="card" style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.1rem; margin: 0;"><i class="fa-solid fa-list-check" style="color: var(--primary-color);"></i> Tasks</h3>
                    <a href="tasks.php" class="btn btn-outline" style="padding: 0.3rem 0.8rem; font-size: 0.75rem;">View All</a>
                </div>
                <?php if ($tasks->num_rows > 0): ?>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.8rem;">
                        <?php while($t = $tasks->fetch_assoc()): ?>
                            <li style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; padding: 0.5rem; background: #f9f9f9; border-radius: 6px; border-left: 3px solid <?php echo $t['status']==='done'?'#1e8e3e':($t['priority']==='high'?'#d93025':'#f29900'); ?>;">
                                <div style="display: flex; flex-direction: column;">
                                    <span style="font-weight: 600;"><?php echo htmlspecialchars($t['title']); ?></span>
                                    <span style="font-size: 0.75rem; color: #888;"><?php echo htmlspecialchars($t['assignee_name'] ?? 'Unassigned'); ?></span>
                                </div>
                                <?php if($t['status']==='done'): ?>
                                    <i class="fa-solid fa-circle-check" style="color: #1e8e3e;"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-circle" style="color: #ccc;"></i>
                                <?php endif; ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p style="color: #888; font-size: 0.85rem; text-align: center; margin-top: 2rem;">No tasks linked.</p>
                <?php endif; ?>
            </div>

            <!-- Preprints Section -->
            <div class="card" style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 1rem; margin-bottom: 1rem;">
                    <h3 style="font-size: 1.1rem; margin: 0;"><i class="fa-solid fa-book-open" style="color: var(--primary-color);"></i> Published Preprints</h3>
                </div>
                <?php if ($preprints->num_rows > 0): ?>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.8rem;">
                        <?php while($p = $preprints->fetch_assoc()): ?>
                            <li style="display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; padding-bottom: 0.5rem; border-bottom: 1px dashed #eee;">
                                <div style="display: flex; flex-direction: column;">
                                    <a href="preprint_details.php?id=<?php echo $p['id']; ?>" style="font-weight: 600; text-decoration: none; color: var(--text-color);"><?php echo htmlspecialchars($p['title']); ?></a>
                                    <span style="font-size: 0.75rem; color: #888;">Published: <?php echo date('M d, Y', strtotime($p['created_at'])); ?> • <?php echo (int)$p['views_count']; ?> views</span>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p style="color: #888; font-size: 0.85rem; text-align: center; margin-top: 2rem;">No preprints published yet.</p>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <script src="../assets/js/projects.js"></script>
</body>
</html>
