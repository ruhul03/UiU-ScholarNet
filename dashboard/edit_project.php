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

// Fetch project members
$members = db_query("SELECT pm.user_id, pm.role, pm.status, u.full_name, u.department, u.role as u_role FROM project_members pm JOIN users u ON pm.user_id = u.id WHERE pm.project_id = ? ORDER BY pm.role = 'owner' DESC, pm.added_at ASC", [$project_id], "i");

// Also fetch all possible users to invite, excluding existing members
$all_users = db_query("SELECT id, full_name, department, role FROM users WHERE id NOT IN (SELECT user_id FROM project_members WHERE project_id = ?) AND account_status = 'active' ORDER BY full_name ASC", [$project_id], "i");

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

                <!-- Old Governance Section Removed -->
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($project['status']); ?>">
                <div class="form-row form-grid-2col-mb3">
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

        <!-- Scientific Research Pipeline Card -->
        <div class="edit-form-card" style="margin-top: 2rem;">
            <div class="form-section-title"><i class="fa-solid fa-microscope"></i> SCIENTIFIC RESEARCH PIPELINE</div>
            
            <?php
            $research_phases = [
                'literature_review' => 'Literature Review',
                'gap_analysis' => 'Gap Analysis',
                'methodology' => 'Methodology',
                'implementation' => 'Implementation',
                'experimentation' => 'Experimentation',
                'drafting' => 'Drafting',
                'publishing' => 'Publishing'
            ];
            $current_res_phase = $project['research_phase'] ?? 'literature_review';
            $res_phase_keys = array_keys($research_phases);
            $res_current_idx = array_search($current_res_phase, $res_phase_keys);
            ?>
            <div class="lifecycle-pipeline" style="margin-bottom: 2rem;">
                <div class="lifecycle-line">
                    <div class="lifecycle-progress" style="width: <?php echo ($res_current_idx / (count($res_phase_keys)-1)) * 100; ?>%; background: linear-gradient(90deg, #1a73e8, #c5a022);"></div>
                </div>
                <?php foreach ($res_phase_keys as $idx => $r_key): 
                    $r_status_class = '';
                    if ($idx < $res_current_idx) $r_status_class = 'completed';
                    elseif ($idx === $res_current_idx) $r_status_class = 'active';
                    
                    $r_icons = [
                        'literature_review' => 'fa-book-open-reader',
                        'gap_analysis' => 'fa-magnifying-glass-arrow-right',
                        'methodology' => 'fa-bezier-curve',
                        'implementation' => 'fa-laptop-code',
                        'experimentation' => 'fa-flask',
                        'drafting' => 'fa-pen-nib',
                        'publishing' => 'fa-upload'
                    ];
                ?>
                <div class="lifecycle-stage <?php echo $r_status_class; ?>" style="flex: 1; position: relative;">
                    <div class="stage-icon" style="<?php echo $idx === $res_current_idx ? 'border-color: #c5a022; color: #c5a022; transform: scale(1.1);' : ''; ?>"><i class="fa-solid <?php echo $r_icons[$r_key]; ?>"></i></div>
                    <div class="stage-name" style="font-size: 0.75rem; white-space: normal; text-align: center; margin-top: 8px;"><?php echo $research_phases[$r_key]; ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$is_archived && $user_id == $project['creator_id']): ?>
                <form action="../actions/project_task_document/update_research_phase.php" method="POST" style="background: rgba(0,0,0,0.02); padding: 1.5rem; border-radius: 8px; border: 1px solid rgba(0,0,0,0.05); display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                    
                    <div style="flex: 1; min-width: 200px;">
                        <label class="form-label-bold" style="margin-bottom: 0.5rem; display: block;">UPDATE PIPELINE STAGE</label>
                        <select name="research_phase" class="form-input-light" style="width: 100%;">
                            <?php foreach ($research_phases as $key => $label): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($current_res_phase === $key) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="align-self: flex-end;">
                        <button type="submit" class="btn btn-primary" style="padding: 0.8rem 1.5rem;"><i class="fa-solid fa-arrow-right-arrow-left"></i> UPDATE STAGE</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="stage-hint-text text-center mt-2">Only the project creator can update the research pipeline stage.</div>
            <?php endif; ?>
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

            <!-- Team Members Section -->
            <div class="card ecosystem-card">
                <div class="ecosystem-card-header">
                    <h3 class="ecosystem-card-title"><i class="fa-solid fa-users text-primary"></i> Team Members</h3>
                    <?php if (!$is_archived && $user_id == $project['creator_id']): ?>
                        <button class="btn btn-outline btn-xs" onclick="var form = document.getElementById('add-member-form'); form.style.display = (form.style.display === 'none' ? 'block' : 'none');"><i class="fa-solid fa-user-plus"></i> Add</button>
                    <?php endif; ?>
                </div>
                
                <?php if (!$is_archived && $user_id == $project['creator_id']): ?>
                <div id="add-member-form" style="display:none; margin-top:10px; padding-bottom: 10px; border-bottom: 1px solid rgba(0,0,0,0.05);">
                    <form action="../actions/project_task_document/add_member.php" method="POST" style="display:flex; gap:10px;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                        <select name="invited_users[]" class="form-input-light" style="flex:1; padding: 0.5rem;" required>
                            <option value="">Select user to add...</option>
                            <?php while($u = $all_users->fetch_assoc()): ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?> (<?php echo htmlspecialchars($u['department']); ?>)</option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-xs" style="padding: 0.5rem 1rem;">Add</button>
                    </form>
                </div>
                <?php endif; ?>

                <?php if ($members->num_rows > 0): ?>
                    <ul class="ecosystem-list">
                        <?php while($m = $members->fetch_assoc()): ?>
                            <li class="ecosystem-list-item">
                                <div class="flex-col">
                                    <span class="ecosystem-item-title">
                                        <?php echo htmlspecialchars($m['full_name']); ?>
                                        <?php if ($m['role'] === 'owner'): ?>
                                            <span class="status-chip status-chip-blue" style="font-size: 0.6rem; padding: 2px 6px; margin-left: 5px;">LEADER</span>
                                        <?php endif; ?>
                                        <?php if (isset($m['u_role']) && $m['u_role'] === 'faculty'): ?>
                                            <span class="status-chip" style="font-size: 0.6rem; padding: 2px 6px; background: #6c5ce7; color: white; border: none; margin-left: 5px;">FACULTY</span>
                                        <?php endif; ?>
                                        <?php if ($m['status'] === 'pending'): ?>
                                            <span class="status-chip" style="font-size: 0.6rem; padding: 2px 6px; background: #f29900; color: white; border: none; margin-left: 5px;">PENDING</span>
                                        <?php endif; ?>
                                    </span>
                                    <span class="ecosystem-item-meta"><?php echo htmlspecialchars($m['department']); ?></span>
                                </div>
                                <?php if (!$is_archived && $user_id == $project['creator_id'] && $m['role'] !== 'owner'): ?>
                                    <form action="../actions/project_task_document/remove_member.php" method="POST" style="display:inline;" onsubmit="return confirm('Remove this member?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                        <input type="hidden" name="member_id" value="<?php echo $m['user_id']; ?>">
                                        <button type="submit" class="btn btn-outline btn-xs text-danger" style="border-color: #d93025; padding: 4px 8px; color: #d93025;" title="Remove Member"><i class="fa-solid fa-trash-can"></i></button>
                                    </form>
                                <?php endif; ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <p class="ecosystem-empty">No team members.</p>
                <?php endif; ?>
            </div>
        </div>

    </main>

    <script src="../assets/js/projects.js"></script>
</body>
</html>
