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
        <header class="dash-header" style="margin-bottom: 3rem;">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass" style="opacity: 0.3;"></i>
                <input type="text" placeholder="Search archives and projects...">
            </div>
            <div style="display: flex; gap: 1.5rem; align-items: center;">
                <i class="fa-regular fa-bell" style="font-size: 1.2rem; opacity: 0.5;"></i>
                <i class="fa-regular fa-user" style="font-size: 1.2rem; opacity: 0.5;"></i>
                <div style="width: 1px; height: 30px; background: #eee; margin: 0 1rem;"></div>
                <button class="btn btn-primary" onclick="openModal()" style="background-color: var(--secondary-color); color: var(--primary-color); border-radius: 4px; padding: 0.8rem 2rem;">+ NEW TASKS</button>
            </div>
        </header>

        <section>
            <div style="font-size: 0.75rem; font-weight: 800; color: #aaa; letter-spacing: 1px; margin-bottom: 0.5rem; text-transform: uppercase;">PROJECT LOGISTICS / ACTIVE SPRINT</div>
            <h1 style="font-size: 4rem; margin-bottom: 2rem;">Tasks</h1>
            
            <div style="display: flex; align-items: center; gap: 2rem; margin-bottom: 4rem;">
                <div style="display: flex; -webkit-mask-image: linear-gradient(to right, black 80%, transparent 100%);">
                    <img src="https://ui-avatars.com/api/?name=User+1&background=eee&color=0a1128" style="width: 35px; height: 35px; border-radius: 50%; border: 2px solid white; margin-right: -10px;">
                    <img src="https://ui-avatars.com/api/?name=User+2&background=ddd&color=0a1128" style="width: 35px; height: 35px; border-radius: 50%; border: 2px solid white; margin-right: -10px;">
                    <img src="https://ui-avatars.com/api/?name=User+3&background=ccc&color=0a1128" style="width: 35px; height: 35px; border-radius: 50%; border: 2px solid white;">
                </div>
                <div style="font-size: 0.85rem; font-weight: 700; opacity: 0.6; cursor: pointer;"><i class="fa-solid fa-user-plus"></i> Share Board</div>
            </div>

            <div class="kanban-board">
                <!-- To Do Column -->
                <div class="kanban-column">
                    <h3><span style="display: flex; align-items: center; gap: 0.5rem;"><i class="fa-solid fa-circle" style="color: var(--secondary-color); font-size: 0.6rem;"></i> To Do (<?php echo count($tasks_todo); ?>)</span> <i class="fa-solid fa-ellipsis" style="opacity: 0.3;"></i></h3>
                    
                    <?php foreach($tasks_todo as $task): ?>
                    <div class="task-card">
                        <div style="background: #fff3e0; color: #ef6c00; font-size: 0.6rem; font-weight: 800; padding: 0.2rem 0.6rem; border-radius: 2px; display: inline-block; margin-bottom: 1rem; text-transform: uppercase;"><?php echo $task['priority']; ?> PRIORITY</div>
                        <h4><?php echo htmlspecialchars($task['title']); ?></h4>
                        <p style="font-size: 0.9rem; opacity: 0.5; margin-bottom: 2rem;"><?php echo htmlspecialchars($task['description']); ?></p>
                        
                        <div class="task-meta-footer">
                            <span><i class="fa-regular fa-clock"></i> <?php echo date('M d', strtotime($task['due_date'])); ?></span>
                            <div style="width: 20px; height: 20px; background: #000; border-radius: 2px;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="create-project-box" style="padding: 1.5rem; margin-top: 2rem; border-style: dashed; border-width: 1px;" onclick="openModal()">
                        <div style="font-size: 0.8rem; font-weight: 800; opacity: 0.5; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                            <i class="fa-solid fa-plus"></i> ADD ANOTHER TASK
                        </div>
                    </div>
                </div>

                <!-- Done Column -->
                <div class="kanban-column">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 2px solid #eee; padding-bottom: 1rem;">
                        <h3 style="margin-bottom: 0; border: none; padding: 0;"><span style="display: flex; align-items: center; gap: 0.5rem;"><i class="fa-solid fa-circle" style="color: #4CAF50; font-size: 0.6rem;"></i> Done (<?php echo count($tasks_done); ?>)</span></h3>
                        <span style="font-size: 0.75rem; font-weight: 800; color: #aaa; cursor: pointer;">CLEAR ALL</span>
                    </div>

                    <?php foreach($tasks_done as $task): ?>
                    <div class="task-completed-card">
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                            <i class="fa-solid fa-circle-check" style="color: #4CAF50;"></i>
                            <span style="font-size: 0.65rem; font-weight: 800; color: #aaa; text-transform: uppercase;">COMPLETED SEP 12</span>
                        </div>
                        <h4><?php echo htmlspecialchars($task['title']); ?></h4>
                        <div style="font-size: 0.75rem; opacity: 0.4; margin-top: 0.5rem;">Reviewed by Dr. Henderson</div>
                    </div>
                    <?php endforeach; ?>

                    <div style="text-align: center; margin-top: 2rem; font-size: 0.8rem; font-weight: 700; opacity: 0.4; cursor: pointer;">SHOW MORE COMPLETED</div>
                </div>
            </div>
        </section>
    </main>

    <!-- Add Task Modal (High-Fidelity) -->
    <div class="modal-overlay" id="taskModal" style="display: none;">
        <div class="modal-content" style="max-width: 600px; padding: 4rem;">
            <i class="fa-solid fa-xmark modal-close" onclick="closeModal()"></i>
            <h2 style="font-size: 2rem; margin-bottom: 2.5rem;">Add New Task</h2>
            
            <form action="../actions/add_task.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php 
                    require_once('../includes/csrf.php');
                    echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
                ?>">
                <div class="form-group">
                    <label>TASK TITLE</label>
                    <input type="text" name="title" placeholder="e.g., Finalize Literature Review" style="background: #fdfcf8;" required>
                </div>
                
                <div class="form-group">
                    <label>PROJECT SELECTION</label>
                    <select name="project_id" style="background: #fdfcf8;" required>
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
                        <input type="date" name="due_date" style="background: #fdfcf8;" required>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label>TASK DESCRIPTION</label>
                    <textarea name="description" rows="5" style="width: 100%; padding: 1rem; border: 1px solid #eee; border-radius: 4px; background: #fdfcf8;" placeholder="Briefly outline the task details..."></textarea>
                </div>

                <div style="display: flex; justify-content: flex-end; align-items: center; gap: 2rem; margin-top: 3rem;">
                    <a href="javascript:void(0)" onclick="closeModal()" style="font-weight: 700; font-size: 0.8rem; color: #888;">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="background-color: var(--secondary-color); color: var(--primary-color); padding: 1rem 3rem; font-size: 0.9rem; border-radius: 4px;">Create Task</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('taskModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('taskModal').style.display = 'none';
        }

        function setPriority(level, btn) {
            document.getElementById('task_priority').value = level;
            const btns = document.querySelectorAll('.priority-btn');
            btns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }
    </script>

</body>
</html>
