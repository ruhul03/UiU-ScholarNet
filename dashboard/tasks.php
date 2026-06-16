<?php
require_once('../includes/auth_check.php');
require_once('../includes/csrf.php');

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;

// Fetch notification counts for header badges
$pending_tasks = (int)(db_query("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status != 'done'", [$user_id], "i")->fetch_assoc()['total'] ?? 0);
$collab_requests = (int)(db_query("SELECT COUNT(*) as total FROM collaboration_applications ca JOIN collaboration_posts cp ON ca.post_id = cp.id WHERE cp.user_id = ? AND ca.status = 'pending'", [$user_id], "i")->fetch_assoc()['total'] ?? 0);

// Fetch team members for task assignment dropdown
$team_members = db_query("
    SELECT DISTINCT u.id, u.full_name 
    FROM project_members pm
    JOIN projects p ON pm.project_id = p.id
    LEFT JOIN project_members me ON p.id = me.project_id AND me.user_id = ?
    JOIN users u ON pm.user_id = u.id
    WHERE p.creator_id = ? OR me.user_id = ?
", [$user_id, $user_id, $user_id], "iii");

// Fetch active projects to assign tasks to
$projects_result = db_query("
    SELECT p.id, p.title 
    FROM projects p
    LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ?
    WHERE p.creator_id = ? OR pm.user_id = ?
    ORDER BY p.created_at DESC
", [$user_id, $user_id, $user_id], "iii");

// Fetch all tasks or filter by project_id if specified in the URL
$task_sql = "SELECT t.*, p.title as project_title, p.status as project_status, p.creator_id FROM tasks t 
             JOIN projects p ON t.project_id = p.id 
             LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ?
             WHERE (p.creator_id = ? OR pm.user_id = ?)";
if ($project_id) {
    $task_sql .= " AND t.project_id = ?";
    $task_result = db_query($task_sql, [$user_id, $user_id, $user_id, $project_id], "iiii");
} else {
    $task_result = db_query($task_sql, [$user_id, $user_id, $user_id], "iii");
}

$tasks_todo = [];
$tasks_done = [];

while ($task = $task_result->fetch_assoc()) {
    if ($task['status'] == 'todo' || $task['status'] == 'inprogress') $tasks_todo[] = $task;
    elseif ($task['status'] == 'done') $tasks_done[] = $task;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks | UIU ScholarNet</title>
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
        <?php include('../includes/header.php'); ?>

        <section>
            <div class="section-label">PROJECT LOGISTICS / ACTIVE SPRINT</div>
            <h1 class="page-title">Tasks</h1>

            <?php include('../includes/alerts.php'); ?>

            <div class="kanban-board">
                <!-- To Do Column -->
                <div class="kanban-column">
                    <h3><span class="column-badge"><i class="fa-solid fa-circle dot-gold"></i> To Do (<?php echo count($tasks_todo); ?>)</span> <i class="fa-solid fa-ellipsis column-options"></i></h3>
                    
                    <?php foreach($tasks_todo as $task): ?>
                    <div class="task-card <?php echo !empty($task['is_milestone']) ? 'milestone-card' : ''; ?>" <?php echo !empty($task['is_milestone']) ? 'style="border: 1px solid #d4af37;"' : ''; ?>>
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
                            <div class="priority-badge"><?php echo $task['priority']; ?> PRIORITY</div>
                            <?php if (!empty($task['is_milestone'])): ?>
                                <span style="color: #d4af37; font-size: 0.8rem; font-weight: bold;"><i class="fa-solid fa-flag"></i> MILESTONE</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($task['pipeline_stage'])): ?>
                            <div style="font-size: 0.75rem; color: #1a73e8; font-weight: 600; margin-bottom: 0.2rem; text-transform: uppercase;">
                                <i class="fa-solid fa-microscope"></i> <?php echo str_replace('_', ' ', htmlspecialchars($task['pipeline_stage'])); ?>
                            </div>
                        <?php endif; ?>

                        <h4 style="margin-top: 0.2rem;"><?php echo htmlspecialchars($task['title']); ?></h4>
                        <p class="task-desc"><?php echo htmlspecialchars($task['description']); ?></p>
                        
                        <?php if (!empty($task['document_id'])): ?>
                            <a href="document_editor.php?document_id=<?php echo $task['document_id']; ?>" class="btn btn-outline" style="padding: 0.2rem 0.5rem; font-size: 0.75rem; display: inline-block; margin-bottom: 0.8rem; color: var(--primary-color);">
                                <i class="fa-solid fa-file-lines"></i> Attached Document
                            </a>
                        <?php endif; ?>
                        
                        <div class="task-meta-footer">
                            <span><i class="fa-regular fa-clock"></i> <?php echo date('M d', strtotime($task['due_date'])); ?></span>
                            <div class="task-actions margin-left-auto">
                                <?php if (!($task['project_status'] === 'completed' && $task['creator_id'] != $user_id)): ?>
                                <form method="POST" action="../actions/project_task_document/update_task_status.php" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <input type="hidden" name="status" value="done">
                                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check"></i> Finish</button>
                                </form>
                                <?php endif; ?>
                            </div>

                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php 
                    $hide_add_button = false;
                    if ($project_id) {
                        $p_check = db_query("SELECT status, creator_id FROM projects WHERE id = ?", [$project_id], "i")->fetch_assoc();
                        if ($p_check && $p_check['status'] === 'completed' && $p_check['creator_id'] != $user_id) {
                            $hide_add_button = true;
                        }
                    }
                    if (!$hide_add_button):
                    ?>
                    <div class="create-project-box add-task-box" onclick="openModal()">
                        <div class="add-task-text">
                            <i class="fa-solid fa-plus"></i> ADD ANOTHER TASK
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Done Column -->
                <div class="kanban-column">
                    <div class="done-header">
                        <h3 class="done-title"><span class="column-badge"><i class="fa-solid fa-circle dot-green"></i> Done (<?php echo count($tasks_done); ?>)</span></h3>
                        
                        <form action="../actions/clear_completed_tasks.php" method="POST" id="clearTasksForm" class="hidden">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php if ($project_id): ?>
                                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                            <?php endif; ?>
                        </form>
                        <span class="clear-all" onclick="if(confirm('Clear all completed tasks?')) document.getElementById('clearTasksForm').submit();">CLEAR ALL</span>
                    </div>

                    <?php foreach($tasks_done as $task): ?>
                    <div class="task-completed-card">
                        <div class="completed-check">
                            <i class="fa-solid fa-circle-check check-icon"></i>
                            <span class="completed-date">COMPLETED <?php echo strtoupper(date('M d', strtotime($task['created_at'] ?? 'now'))); ?></span>
                        </div>
                        <h4><?php echo htmlspecialchars($task['title']); ?></h4>
                        <div class="completed-reviewer">Project: <?php echo htmlspecialchars($task['project_title']); ?></div>
                    </div>
                    <?php endforeach; ?>

                    <div class="show-more">SHOW MORE COMPLETED</div>
                </div>
            </div>
        </section>
    </main>

    <!-- Add Task Modal (High-Fidelity) -->
    <div class="modal-overlay modal-hidden" id="taskModal">
        <div class="modal-content modal-task">
            <i class="fa-solid fa-xmark modal-close" onclick="closeModal()"></i>
            <h2 class="modal-task-title">Add New Task</h2>
            
            <form action="../actions/add_task.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label>TASK TITLE</label>
                    <input type="text" name="title" placeholder="e.g., Finalize Literature Review" class="form-input-light" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group flex-1">
                        <label>PROJECT SELECTION</label>
                        <select name="project_id" class="form-input-light" required>
                            <option value="">Select Project</option>
                            <?php 
                            // Reset the pointer if needed, though it's the first time we use it here
                            $projects_result->data_seek(0);
                            while($p = $projects_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group flex-1">
                        <label>ASSIGN TO COLLABORATOR</label>
                        <select name="assigned_to" class="form-input-light">
                            <option value="<?php echo $user_id; ?>">Assign to Myself</option>
                            <?php 
                            // Re-fetch or reuse team members
                            $team_members->data_seek(0);
                            while($m = $team_members->fetch_assoc()): 
                                if($m['id'] != $user_id):
                            ?>
                                <option value="<?php echo (int)$m['id']; ?>"><?php echo htmlspecialchars($m['full_name']); ?></option>
                            <?php 
                                endif;
                            endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>PRIORITY LEVEL</label>
                        <div class="priority-selector">
                            <input type="hidden" name="priority" id="task_priority" value="medium">
                            <div class="priority-btn" onclick="setPriority('low', this)">LOW</div>
                            <div class="priority-btn active" onclick="setPriority('medium', this)">MEDIUM</div>
                            <div class="priority-btn" onclick="setPriority('high', this)">HIGH</div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>SELECT DEADLINE</label>
                        <input type="date" name="due_date" class="form-input-light" required>
                    </div>
                </div>

                <div class="form-group margin-top-md">
                    <label>TASK DESCRIPTION</label>
                    <textarea name="description" rows="5" class="textarea-task" placeholder="Briefly outline the task details..."></textarea>
                </div>

                <div class="form-row margin-top-md">
                    <div class="form-group flex-1">
                        <label>PIPELINE STAGE</label>
                        <select name="pipeline_stage" class="form-input-light">
                            <option value="">None (General Task)</option>
                            <option value="literature_review">Literature Review</option>
                            <option value="gap_analysis">Gap Analysis</option>
                            <option value="methodology">Methodology</option>
                            <option value="implementation">Implementation</option>
                            <option value="experimentation">Experimentation</option>
                            <option value="drafting">Drafting</option>
                            <option value="publishing">Publishing</option>
                        </select>
                    </div>
                    <div class="form-group flex-1">
                        <label>LINK DOCUMENT (Optional)</label>
                        <select name="document_id" class="form-input-light">
                            <option value="">No Document Attached</option>
                            <?php 
                            // Fetch documents the user can access
                            $docs_result = db_query("
                                SELECT d.id, d.title, p.title as p_title 
                                FROM documents d
                                JOIN projects p ON d.project_id = p.id
                                LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ?
                                WHERE p.creator_id = ? OR pm.user_id = ?
                                ORDER BY d.updated_at DESC
                            ", [$user_id, $user_id, $user_id], "iii");
                            while($d = $docs_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo (int)$d['id']; ?>"><?php echo htmlspecialchars($d['p_title'] . ' - ' . $d['title']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group margin-top-md" style="display: flex; align-items: center; gap: 10px;">
                    <input type="checkbox" name="is_milestone" id="is_milestone" value="1" style="width: auto;">
                    <label for="is_milestone" style="margin: 0; color: #d4af37; font-weight: 700;"><i class="fa-solid fa-flag"></i> MARK AS MILESTONE</label>
                </div>

                <div class="modal-actions">
                    <a href="javascript:void(0)" onclick="closeModal()" class="cancel-btn">Cancel</a>
                    <button type="submit" class="btn btn-primary create-task-btn">Create Task</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/tasks.js"></script>
</body>
</html>
