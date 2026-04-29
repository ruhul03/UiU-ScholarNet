<?php
require_once('../includes/auth_check.php');

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;

// Fetch Projects for the dropdown
$projects_query = "SELECT id, title FROM projects WHERE creator_id = $user_id";
$projects_result = mysqli_query($conn, $projects_query);

// Fetch Tasks
$task_query = "SELECT t.*, p.title as project_title FROM tasks t 
               JOIN projects p ON t.project_id = p.id 
               WHERE p.creator_id = $user_id";

if ($project_id) {
    $task_query .= " AND t.project_id = $project_id";
}

$task_result = mysqli_query($conn, $task_query);

$tasks_todo = [];
$tasks_progress = [];
$tasks_done = [];

while ($task = mysqli_fetch_assoc($task_result)) {
    if ($task['status'] == 'todo') $tasks_todo[] = $task;
    elseif ($task['status'] == 'inprogress') $tasks_progress[] = $task;
    elseif ($task['status'] == 'done') $tasks_done[] = $task;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Management | UIU ScholarNet</title>
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
            <h2 style="font-size: 2.5rem;">Task Management</h2>
            <div style="display: flex; gap: 1rem;">
                <form action="" method="GET" id="filterForm">
                    <select name="project_id" class="btn btn-outline" style="padding: 0.8rem; background: white;" onchange="document.getElementById('filterForm').submit()">
                        <option value="">All Projects</option>
                        <?php 
                        mysqli_data_seek($projects_result, 0);
                        while($p = mysqli_fetch_assoc($projects_result)): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($project_id == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo $p['title']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </form>
                <button class="btn btn-primary" onclick="openModal()"><i class="fa-solid fa-plus"></i> Add Task</button>
            </div>
        </header>

        <div class="kanban-board">
            <!-- To-Do -->
            <div class="kanban-column">
                <h3>To-Do <span style="opacity: 0.4;"><?php echo count($tasks_todo); ?></span></h3>
                
                <?php foreach($tasks_todo as $task): ?>
                <div class="task-card">
                    <span class="task-label label-<?php echo $task['priority']; ?>"><?php echo ucfirst($task['priority']); ?> Priority</span>
                    <h4 style="font-size: 1rem; margin-bottom: 0.5rem;"><?php echo $task['title']; ?></h4>
                    <p style="font-size: 0.85rem; opacity: 0.6; margin-bottom: 1rem;"><?php echo $task['description']; ?></p>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.7rem; font-weight: 700; opacity: 0.4;"><?php echo $task['project_title']; ?></span>
                        <span style="font-size: 0.75rem; opacity: 0.5;"><i class="fa-regular fa-calendar"></i> <?php echo date('M d', strtotime($task['due_date'])); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- In Progress -->
            <div class="kanban-column">
                <h3>In Progress <span style="opacity: 0.4;"><?php echo count($tasks_progress); ?></span></h3>
                
                <?php foreach($tasks_progress as $task): ?>
                <div class="task-card">
                    <span class="task-label label-<?php echo $task['priority']; ?>"><?php echo ucfirst($task['priority']); ?> Priority</span>
                    <h4 style="font-size: 1rem; margin-bottom: 0.5rem;"><?php echo $task['title']; ?></h4>
                    <p style="font-size: 0.85rem; opacity: 0.6; margin-bottom: 1rem;"><?php echo $task['description']; ?></p>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.7rem; font-weight: 700; opacity: 0.4;"><?php echo $task['project_title']; ?></span>
                        <span style="font-size: 0.75rem; opacity: 0.5;"><i class="fa-regular fa-calendar"></i> <?php echo date('M d', strtotime($task['due_date'])); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Done -->
            <div class="kanban-column">
                <h3>Done <span style="opacity: 0.4;"><?php echo count($tasks_done); ?></span></h3>
                
                <?php foreach($tasks_done as $task): ?>
                <div class="task-card" style="opacity: 0.7;">
                    <span class="task-label label-low">Completed</span>
                    <h4 style="font-size: 1rem; margin-bottom: 0.5rem;"><?php echo $task['title']; ?></h4>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <i class="fa-solid fa-circle-check" style="color: #4CAF50;"></i>
                        <span style="font-size: 0.75rem; opacity: 0.5;"><?php echo date('M d', strtotime($task['created_at'])); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Add Task Modal -->
    <div class="modal-overlay" id="taskModal" style="display: none;">
        <div class="modal-content">
            <i class="fa-solid fa-xmark modal-close" onclick="closeModal()"></i>
            <h2>Create New Task</h2>
            <p style="opacity: 0.5; margin-bottom: 2rem;">Assign milestones to your research team.</p>
            
            <form action="../actions/add_task.php" method="POST">
                <div class="form-group">
                    <label>Task Title</label>
                    <input type="text" name="title" placeholder="e.g. Literature Review" required>
                </div>
                <div class="form-group">
                    <label>Target Project</label>
                    <select name="project_id" required>
                        <?php 
                        mysqli_data_seek($projects_result, 0);
                        while($p = mysqli_fetch_assoc($projects_result)): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo $p['title']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Priority</label>
                        <select name="priority">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Due Date</label>
                        <input type="date" name="due_date" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Task Description</label>
                    <textarea name="description" rows="3" style="width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 6px;"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; background-color: var(--secondary-color); color: var(--primary-color);">ADD TASK</button>
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
    </script>

</body>
</html>
