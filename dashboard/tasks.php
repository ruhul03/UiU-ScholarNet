<?php
require_once('../includes/auth_check.php');

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;

// Fetch Projects for the dropdown
$pstmt = $conn->prepare("SELECT id, title FROM projects WHERE creator_id = ? ORDER BY created_at DESC");
$pstmt->bind_param("i", $user_id);
$pstmt->execute();
$projects_result = $pstmt->get_result();

// Fetch Tasks
$task_sql = "SELECT t.*, p.title as project_title FROM tasks t 
             JOIN projects p ON t.project_id = p.id 
             WHERE p.creator_id = ?";
if ($project_id) {
    $task_sql .= " AND t.project_id = ?";
    $tstmt = $conn->prepare($task_sql);
    $tstmt->bind_param("ii", $user_id, $project_id);
} else {
    $tstmt = $conn->prepare($task_sql);
    $tstmt->bind_param("i", $user_id);
}
$tstmt->execute();
$task_result = $tstmt->get_result();

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
        <header class="dash-header dash-header-xl">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" placeholder="Search archives and projects...">
            </div>
            <div class="header-actions">
                <i class="fa-regular fa-bell header-icon"></i>
                <i class="fa-regular fa-user header-icon"></i>
                <div class="divider-v"></div>
                <button class="btn btn-primary btn-new-project" onclick="openModal()">+ NEW TASKS</button>
            </div>
        </header>

        <section>
            <div class="section-label">PROJECT LOGISTICS / ACTIVE SPRINT</div>
            <h1 class="page-title">Tasks</h1>

            <div class="team-avatars">
                <div class="avatars-mask">
                    <img src="https://ui-avatars.com/api/?name=User+1&background=eee&color=0a1128" class="team-avatar">
                    <img src="https://ui-avatars.com/api/?name=User+2&background=ddd&color=0a1128" class="team-avatar">
                    <img src="https://ui-avatars.com/api/?name=User+3&background=ccc&color=0a1128" class="team-avatar">
                </div>
                <div class="share-board"><i class="fa-solid fa-user-plus"></i> Share Board</div>
            </div>

            <div class="kanban-board">
                <!-- To Do Column -->
                <div class="kanban-column">
                    <h3><span class="column-badge"><i class="fa-solid fa-circle dot-gold"></i> To Do (<?php echo count($tasks_todo); ?>)</span> <i class="fa-solid fa-ellipsis column-options"></i></h3>
                    
                    <?php foreach($tasks_todo as $task): ?>
                    <div class="task-card">
                        <div class="priority-badge"><?php echo $task['priority']; ?> PRIORITY</div>
                        <h4><?php echo htmlspecialchars($task['title']); ?></h4>
                        <p class="task-desc"><?php echo htmlspecialchars($task['description']); ?></p>
                        
                        <div class="task-meta-footer">
                            <span><i class="fa-regular fa-clock"></i> <?php echo date('M d', strtotime($task['due_date'])); ?></span>
                            <div class="task-assignee"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="create-project-box add-task-box" onclick="openModal()">
                        <div class="add-task-text">
                            <i class="fa-solid fa-plus"></i> ADD ANOTHER TASK
                        </div>
                    </div>
                </div>

                <!-- Done Column -->
                <div class="kanban-column">
                    <div class="done-header">
                        <h3 class="done-title"><span class="column-badge"><i class="fa-solid fa-circle dot-green"></i> Done (<?php echo count($tasks_done); ?>)</span></h3>
                        <span class="clear-all">CLEAR ALL</span>
                    </div>

                    <?php foreach($tasks_done as $task): ?>
                    <div class="task-completed-card">
                        <div class="completed-check">
                            <i class="fa-solid fa-circle-check check-icon"></i>
                            <span class="completed-date">COMPLETED SEP 12</span>
                        </div>
                        <h4><?php echo htmlspecialchars($task['title']); ?></h4>
                        <div class="completed-reviewer">Reviewed by Dr. Henderson</div>
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
                <input type="hidden" name="csrf_token" value="<?php 
                    require_once('../includes/csrf.php');
                    echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
                ?>">
                <div class="form-group">
                    <label>TASK TITLE</label>
                    <input type="text" name="title" placeholder="e.g., Finalize Literature Review" class="form-input-light" required>
                </div>
                
                <div class="form-group">
                    <label>PROJECT SELECTION</label>
                    <select name="project_id" class="form-input-light" required>
                        <option value="">Select Project</option>
                        <?php while($p = $projects_result->fetch_assoc()): ?>
                            <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></option>
                        <?php endwhile; ?>
                    </select>
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

                <div class="form-group" style="margin-top: 1rem;">
                    <label>TASK DESCRIPTION</label>
                    <textarea name="description" rows="5" class="textarea-task" placeholder="Briefly outline the task details..."></textarea>
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
