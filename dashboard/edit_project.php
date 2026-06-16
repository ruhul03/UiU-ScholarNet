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

$is_archived = ($project['status'] === 'completed' && $user_id !== (int)$project['creator_id']);
$disabled_attr = $is_archived ? 'disabled' : '';
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
        .grid-ecosystem {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
            margin-bottom: 3rem;
        }
        .ecosystem-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.5);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .ecosystem-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
        }
        .ecosystem-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding-bottom: 0.75rem;
        }
        .ecosystem-card-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-color);
        }
        .ecosystem-empty {
            color: #666;
            font-size: 0.9rem;
            font-style: italic;
            text-align: center;
            padding: 2rem 0;
            margin: 0;
            background: rgba(0,0,0,0.02);
            border-radius: 8px;
            border: 1px dashed rgba(0,0,0,0.1);
        }
        .ecosystem-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .ecosystem-list-item, .ecosystem-task-item, .ecosystem-preprint-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background: #fff;
            border-radius: 8px;
            border: 1px solid rgba(0,0,0,0.05);
            transition: background 0.2s;
        }
        .ecosystem-list-item:hover, .ecosystem-task-item:hover, .ecosystem-preprint-item:hover {
            background: #fafafa;
        }
        .flex-col {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .ecosystem-item-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--primary-color);
        }
        .ecosystem-item-meta {
            font-size: 0.8rem;
            color: #777;
        }
        .ecosystem-link-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--primary-color);
            text-decoration: none;
        }
        .ecosystem-link-title:hover {
            text-decoration: underline;
        }
        .section-heading-ecosystem {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-top: 3rem;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(10, 17, 40, 0.1);
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
                    <input type="text" name="title" value="<?php echo htmlspecialchars($project['title']); ?>" class="form-input-light input-lg-bold" placeholder="Enter formal research title..." required <?php echo $disabled_attr; ?>>
                </div>

                <div class="form-group margin-bottom-md">
                    <label class="form-label-bold">RESEARCH ABSTRACT / DESCRIPTION</label>
                    <textarea name="description" rows="4" class="form-input-light resize-none" placeholder="Provide a brief summary of the project goals..." <?php echo $disabled_attr; ?>><?php echo htmlspecialchars($project['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-row form-grid-2col-mb2">
                    <div class="form-group">
                        <label class="form-label-bold">DEPARTMENT</label>
                        <select name="department" class="form-input-light" required <?php echo $disabled_attr; ?>>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept; ?>" <?php echo ($project['department'] === $dept) ? 'selected' : ''; ?>><?php echo $dept; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label-bold">PROGRESS (%)</label>
                        <input type="number" name="progress" value="<?php echo $project['progress']; ?>" min="0" max="100" class="form-input-light" required <?php echo $disabled_attr; ?>>
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
                            <p class="stage-hint-text">The project is waiting for the formal proposal to be approved by the supervisor to become ACTIVE.</p>
                            <?php if ($user_id == $project['supervisor_id']): ?>
                                <div class="mt-15px">
                                    <form action="../actions/project_task_document/approve_proposal.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                        <button type="submit" class="btn btn-approve btn-inline-link">
                                            <i class="fa-solid fa-file-signature"></i> APPROVE PROPOSAL
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
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
                        <select name="status" class="form-input-light" <?php echo $disabled_attr; ?>>
                            <option value="planning" <?php echo ($project['status'] === 'planning') ? 'selected' : ''; ?>>PLANNING</option>
                            <option value="active" <?php echo ($project['status'] === 'active') ? 'selected' : ''; ?>>ACTIVE</option>
                            <option value="review" <?php echo ($project['status'] === 'review') ? 'selected' : ''; ?>>REVIEW</option>
                            <option value="completed" <?php echo ($project['status'] === 'completed') ? 'selected' : ''; ?>>COMPLETED</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label-bold">VISIBILITY LEVEL</label>
                        <select name="visibility" class="form-input-light" <?php echo $disabled_attr; ?>>
                            <option value="public" <?php echo ($project['visibility'] === 'public') ? 'selected' : ''; ?>>PUBLIC (Global Network)</option>
                            <option value="institution" <?php echo ($project['visibility'] === 'institution') ? 'selected' : ''; ?>>INSTITUTION (UIU Only)</option>
                            <option value="private" <?php echo ($project['visibility'] === 'private') ? 'selected' : ''; ?>>PRIVATE (Draft Mode)</option>
                        </select>
                    </div>
                </div>

                <?php if ($is_archived): ?>
                    <div class="alert-error-editor mt-3" style="background: rgba(217, 48, 37, 0.1); border-left: 4px solid #d93025; padding: 1rem; border-radius: 4px; color: #d93025;">
                        <i class="fa-solid fa-box-archive"></i> <strong>Archived Project:</strong> This project is completed. Editing is strictly limited to the team leader.
                    </div>
                <?php else: ?>
                    <div class="edit-actions form-actions-footer">
                        <button type="submit" class="btn btn-primary btn-lg">SAVE CHANGES</button>
                        <a href="projects.php" class="btn btn-outline btn-cancel-lg">CANCEL</a>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <h2 class="section-heading-ecosystem">Project Hub Ecosystem</h2>
        
        <div class="grid-ecosystem">
            <!-- Documents Section -->
            <div class="card ecosystem-card">
                <div class="ecosystem-card-header">
                    <h3 class="ecosystem-card-title"><i class="fa-solid fa-file-lines text-primary"></i> Documents</h3>
                    <?php if (!$is_archived): ?>
                    <a href="document_editor.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline btn-xs"><i class="fa-solid fa-plus"></i> New</a>
                    <?php endif; ?>
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
                                    <span class="ecosystem-item-title">
                                        <?php if(isset($t['is_milestone']) && $t['is_milestone']): ?>
                                            <i class="fa-solid fa-flag text-secondary" title="Milestone"></i> 
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($t['title']); ?>
                                    </span>
                                    <span class="ecosystem-item-meta">
                                        <?php echo htmlspecialchars($t['assignee_name'] ?? 'Unassigned'); ?>
                                        <?php if(isset($t['is_milestone']) && $t['is_milestone']): ?>
                                            • <?php echo $t['supervisor_signed_off'] ? '<span class="text-success"><i class="fa-solid fa-check-double"></i> Signed Off</span>' : '<span class="text-muted">Awaiting Sign-Off</span>'; ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div>
                                    <?php if(isset($t['is_milestone']) && $t['is_milestone'] && !$t['supervisor_signed_off'] && $user_id == $project['supervisor_id']): ?>
                                        <form action="../actions/project_task_document/signoff_milestone.php" method="POST" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="task_id" value="<?php echo $t['id']; ?>">
                                            <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                            <button type="submit" class="btn btn-outline btn-xs" title="Sign Off Milestone"><i class="fa-solid fa-pen-nib"></i> Sign Off</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if($t['status']==='done'): ?>
                                        <i class="fa-solid fa-circle-check text-success" style="margin-left:8px;"></i>
                                    <?php else: ?>
                                        <i class="fa-regular fa-circle text-muted-light" style="margin-left:8px;"></i>
                                    <?php endif; ?>
                                </div>
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
