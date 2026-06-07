<?php
require_once('../includes/auth_check.php');
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

$project_id = (int)($_GET['id'] ?? 0);
$user_id = (int)$_SESSION['user_id'];

// Fetch notification counts
$pending_tasks = (int)(db_query("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status != 'done'", [$user_id], "i")->fetch_assoc()['total'] ?? 0);
$collab_requests = (int)(db_query("SELECT COUNT(*) as total FROM collaboration_applications ca JOIN collaboration_posts cp ON ca.post_id = cp.id WHERE cp.user_id = ? AND ca.status = 'pending'", [$user_id], "i")->fetch_assoc()['total'] ?? 0);

// Fetch project details
$project = db_query("SELECT * FROM projects WHERE id = ? AND creator_id = ? LIMIT 1", [$project_id, $user_id], "ii")->fetch_assoc();

if (!$project) {
    header("Location: projects.php");
    exit();
}

$departments = [];
$depRes = db_query("SELECT name FROM departments ORDER BY name ASC");
if ($depRes) {
    while ($row = $depRes->fetch_assoc()) {
        $departments[] = $row['name'];
    }
}

// Fetch documents
$documents = db_query("SELECT id, title, updated_at FROM documents WHERE project_id = ? ORDER BY updated_at DESC", [$project_id], "i");

// Fetch tasks
$tasks = db_query("SELECT t.*, u.full_name AS assignee_name FROM tasks t LEFT JOIN users u ON t.assigned_to = u.id WHERE t.project_id = ? ORDER BY t.due_date ASC", [$project_id], "i");

// Fetch preprints
$preprints = db_query("SELECT id, title, created_at, views_count FROM preprints WHERE project_id = ? ORDER BY created_at DESC", [$project_id], "i");
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
        .edit-hero {
            position: relative;
            z-index: 2;
        }
        .edit-form-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 20px 40px rgba(10, 17, 40, 0.04), 0 1px 3px rgba(0,0,0,0.02);
            border: 1px solid rgba(255, 255, 255, 0.6);
            margin-top: 2rem;
            position: relative;
            overflow: hidden;
        }
        .edit-form-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--secondary-color), #f1c40f);
        }
        .form-section-title {
            font-size: 0.85rem;
            font-weight: 800;
            letter-spacing: 2px;
            color: var(--primary-color);
            margin-bottom: 2.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            text-transform: uppercase;
        }
        .form-section-title i {
            color: var(--secondary-color);
            font-size: 1.1rem;
        }
        .form-section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(90deg, rgba(10, 17, 40, 0.1), transparent);
        }
        .form-input-light {
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(10, 17, 40, 0.08);
            border-radius: 8px;
            padding: 1rem 1.2rem;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.01);
        }
        .form-input-light:focus {
            background: #fff;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 4px rgba(197, 160, 34, 0.1);
            transform: translateY(-1px);
        }
        .ecosystem-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.5);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .ecosystem-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body class="dashboard-page">
    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <?php include('../includes/header.php'); ?>
        <?php include('../includes/alerts.php'); ?>

        <section class="edit-hero mb-3">
            <div class="section-label">INSTITUTIONAL REPOSITORY / CURATION</div>
            <h1 class="page-title mt-0-5">Edit Project Details</h1>
            <p class="hero-subtitle">Refine the parameters of your research entry to ensure accurate representation in the ScholarNet index.</p>
        </section>

        <div class="edit-form-card">
            <form action="../actions/update_project.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">

                <div class="form-section-title"><i class="fa-solid fa-info-circle"></i> CORE INFORMATION</div>
                
                <div class="form-group margin-bottom-md">
                    <label class="form-label-bold">PROJECT TITLE</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" class="form-input-light input-lg-bold" placeholder="Enter formal research title..." required>
                </div>

                <div class="form-group margin-bottom-md">
                    <label class="form-label-bold">RESEARCH ABSTRACT / DESCRIPTION</label>
                    <textarea name="description" rows="4" class="form-input-light resize-none" placeholder="Provide a brief summary of the project goals..."><?php echo htmlspecialchars($project['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-row form-grid-2col-mb2">
                    <div class="form-group">
                        <label class="form-label-bold">DEPARTMENT</label>
                        <select name="department" class="form-input-light" required>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept; ?>" <?php echo ($project['department'] === $dept) ? 'selected' : ''; ?>><?php echo $dept; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label-bold">PROGRESS (%)</label>
                        <input type="number" name="progress" value="<?php echo $project['progress']; ?>" min="0" max="100" class="form-input-light" required>
                    </div>
                </div>

                <div class="form-section-title mt-3"><i class="fa-solid fa-shield-halved"></i> GOVERNANCE & STATUS</div>

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
                            <p class="stage-hint-text">Move to ACTIVE when you are ready to start executing tasks.</p>
                        <?php elseif ($project['status'] === 'active'): ?>
                            <li class="<?php echo ($project['progress'] >= 50) ? 'done' : 'pending'; ?>"><i class="fa-solid fa-<?php echo ($project['progress'] >= 50) ? 'check' : 'spinner'; ?>"></i> Reach 50%+ completion on tasks</li>
                            <li class="<?php echo ($documents->num_rows > 0) ? 'done' : 'pending'; ?>"><i class="fa-solid fa-<?php echo ($documents->num_rows > 0) ? 'check' : 'spinner'; ?>"></i> Upload at least 1 document or resource</li>
                            <p class="stage-hint-text">Move to REVIEW to invite faculty or peers to review your work.</p>
                        <?php elseif ($project['status'] === 'review'): ?>
                            <?php if (!empty($project['supervisor_id'])): ?>
                                <li class="<?php echo ($project['supervisor_approved']) ? 'done' : 'pending'; ?>"><i class="fa-solid fa-<?php echo ($project['supervisor_approved']) ? 'check' : 'spinner'; ?>"></i> Faculty Supervisor Approval</li>
                            <?php else: ?>
                                <li class="done"><i class="fa-solid fa-check"></i> Peer review phase</li>
                            <?php endif; ?>
                            <p class="stage-hint-text">Move to COMPLETED when all feedback is addressed.</p>
                        <?php elseif ($project['status'] === 'completed'): ?>
                            <li class="done"><i class="fa-solid fa-check"></i> Project finalized</li>
                            <p class="stage-hint-text">This project is now locked for editing. You can publish it as a preprint.</p>
                            <div class="mt-15px">
                                <a href="file_upload.php?project_id=<?php echo $project['id']; ?>&type=preprint" class="btn btn-outline btn-inline-link">
                                    <i class="fa-solid fa-upload"></i> PUBLISH PREPRINT
                                </a>
                            </div>
                        <?php endif; ?>
                    </ul>
                    
                    <?php if ($project['status'] === 'review' && $user_id == $project['supervisor_id'] && !$project['supervisor_approved']): ?>
                        <div class="mt-15px">
                            <a href="../actions/approve_project.php?id=<?php echo $project['id']; ?>&token=<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-approve btn-inline-link">
                                <i class="fa-solid fa-check-double"></i> APPROVE PROJECT
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-row form-grid-2col-mb3">
                    <div class="form-group">
                        <label class="form-label-bold">OVERRIDE STAGE</label>
                        <select name="status" class="form-input-light">
                            <option value="planning" <?php echo ($project['status'] === 'planning') ? 'selected' : ''; ?>>PLANNING</option>
                            <option value="active" <?php echo ($project['status'] === 'active') ? 'selected' : ''; ?>>ACTIVE</option>
                            <option value="review" <?php echo ($project['status'] === 'review') ? 'selected' : ''; ?>>REVIEW</option>
                            <option value="completed" <?php echo ($project['status'] === 'completed') ? 'selected' : ''; ?>>COMPLETED</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label-bold">VISIBILITY LEVEL</label>
                        <select name="visibility" class="form-input-light">
                            <option value="public" <?php echo ($project['visibility'] === 'public') ? 'selected' : ''; ?>>PUBLIC (Global Network)</option>
                            <option value="institution" <?php echo ($project['visibility'] === 'institution') ? 'selected' : ''; ?>>INSTITUTION (UIU Only)</option>
                            <option value="private" <?php echo ($project['visibility'] === 'private') ? 'selected' : ''; ?>>PRIVATE (Draft Mode)</option>
                        </select>
                    </div>
                </div>

                <div class="edit-actions form-actions-footer">
                    <button type="submit" class="btn btn-primary btn-lg">SAVE CHANGES</button>
                    <a href="projects.php" class="btn btn-outline btn-cancel-lg">CANCEL</a>
                </div>
            </form>
        </div>

        <h2 class="section-heading-ecosystem">Project Hub Ecosystem</h2>
        
        <div class="grid-ecosystem">
            <!-- Documents Section -->
            <div class="card ecosystem-card">
                <div class="ecosystem-card-header">
                    <h3 class="ecosystem-card-title"><i class="fa-solid fa-file-lines text-primary"></i> Documents</h3>
                    <a href="document_editor.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline btn-xs"><i class="fa-solid fa-plus"></i> New</a>
                </div>
                <?php if ($documents->num_rows > 0): ?>
                    <ul class="ecosystem-list">
                        <?php while($doc = $documents->fetch_assoc()): ?>
                            <li class="ecosystem-list-item">
                                <div class="flex-col">
                                    <span class="ecosystem-item-title"><?php echo htmlspecialchars($doc['title']); ?></span>
                                    <span class="ecosystem-item-meta">Updated: <?php echo date('M d', strtotime($doc['updated_at'])); ?></span>
                                </div>
                                <a href="document_editor.php?document_id=<?php echo $doc['id']; ?>" class="text-secondary"><i class="fa-solid fa-pen-to-square"></i></a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="ecosystem-empty">No documents linked.</p>
                <?php endif; ?>
            </div>

            <!-- Tasks Section -->
            <div class="card ecosystem-card">
                <div class="ecosystem-card-header">
                    <h3 class="ecosystem-card-title"><i class="fa-solid fa-list-check text-primary"></i> Tasks</h3>
                    <a href="tasks.php" class="btn btn-outline btn-xs">View All</a>
                </div>
                <?php if ($tasks->num_rows > 0): ?>
                    <ul class="ecosystem-list">
                        <?php while($t = $tasks->fetch_assoc()): ?>
                            <li class="ecosystem-task-item" style="border-left: 3px solid <?php echo $t['status']==='done'?'#1e8e3e':($t['priority']==='high'?'#d93025':'#f29900'); ?>;">
                                <div class="flex-col">
                                    <span class="ecosystem-item-title"><?php echo htmlspecialchars($t['title']); ?></span>
                                    <span class="ecosystem-item-meta"><?php echo htmlspecialchars($t['assignee_name'] ?? 'Unassigned'); ?></span>
                                </div>
                                <?php if($t['status']==='done'): ?>
                                    <i class="fa-solid fa-circle-check text-success"></i>
                                <?php else: ?>
                                    <i class="fa-regular fa-circle text-muted-light"></i>
                                <?php endif; ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="ecosystem-empty">No tasks linked.</p>
                <?php endif; ?>
            </div>

            <!-- Preprints Section -->
            <div class="card ecosystem-card">
                <div class="ecosystem-card-header">
                    <h3 class="ecosystem-card-title"><i class="fa-solid fa-book-open text-primary"></i> Published Preprints</h3>
                </div>
                <?php if ($preprints->num_rows > 0): ?>
                    <ul class="ecosystem-list">
                        <?php while($p = $preprints->fetch_assoc()): ?>
                            <li class="ecosystem-preprint-item">
                                <div class="flex-col">
                                    <a href="preprint_details.php?id=<?php echo $p['id']; ?>" class="ecosystem-link-title"><?php echo htmlspecialchars($p['title']); ?></a>
                                    <span class="ecosystem-item-meta">Published: <?php echo date('M d, Y', strtotime($p['created_at'])); ?> • <?php echo (int)$p['views_count']; ?> views</span>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="ecosystem-empty">No preprints published yet.</p>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <script src="../assets/js/projects.js"></script>
</body>
</html>
