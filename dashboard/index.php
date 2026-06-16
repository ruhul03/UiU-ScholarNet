<?php
require_once('../includes/auth_check.php');
require_once('../includes/layout.php');

// Fetch Home Stats using db_query
$projects_total = (int)(db_query("SELECT COUNT(DISTINCT p.id) as total FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? WHERE p.creator_id = ? OR pm.user_id = ?", [$user_id, $user_id, $user_id])->fetch_assoc()['total'] ?? 0);
$tasks = (int)(db_query("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status = 'done'", [$user_id])->fetch_assoc()['total'] ?? 0);
$files = (int)(db_query("SELECT COUNT(*) as total FROM resources WHERE user_id = ?", [$user_id])->fetch_assoc()['total'] ?? 0);

// Fetch Projects for the popup
$projects_list_result = db_query("SELECT p.* FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? WHERE p.creator_id = ? OR pm.user_id = ? ORDER BY p.created_at DESC LIMIT 4", [$user_id, $user_id, $user_id]);

// Pending tasks
$pending_tasks = (int)(db_query("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status != 'done'", [$user_id])->fetch_assoc()['total'] ?? 0);

// Collaboration requests
$collab_requests = (int)(db_query("SELECT COUNT(*) as total FROM collaboration_applications ca JOIN collaboration_posts cp ON ca.post_id = cp.id WHERE cp.user_id = ? AND ca.status = 'pending'", [$user_id])->fetch_assoc()['total'] ?? 0);


// Recent Activity (Applications)
$recent_activities = db_query("SELECT ca.created_at, applicant.full_name AS applicant_name, owner.full_name AS owner_name, cp.title, cp.id AS post_id, ca.status, ca.user_id AS applicant_id, cp.user_id AS post_owner_id 
                               FROM collaboration_applications ca 
                               JOIN collaboration_posts cp ON ca.post_id = cp.id 
                               JOIN users applicant ON ca.user_id = applicant.id 
                               JOIN users owner ON cp.user_id = owner.id 
                               WHERE cp.user_id = ? OR ca.user_id = ?
                               ORDER BY ca.created_at DESC LIMIT 4", [$user_id, $user_id]);

// Recent Tasks
$recent_tasks = db_query("SELECT title, created_at, status FROM tasks WHERE assigned_to = ? ORDER BY created_at DESC LIMIT 2", [$user_id]);

// Merge activities
$activities = [];
while ($row = $recent_activities->fetch_assoc()) {
    if ($row['post_owner_id'] == $user_id) {
        $title = htmlspecialchars($row['applicant_name']) . ' requested to join';
        if ($row['status'] === 'accepted') $title = 'You accepted ' . htmlspecialchars($row['applicant_name']);
        if ($row['status'] === 'declined') $title = 'You declined ' . htmlspecialchars($row['applicant_name']);
        $desc = 'Applied to "' . htmlspecialchars($row['title']) . '"';
    } else {
        $title = 'You applied to collaborate';
        if ($row['status'] === 'accepted') $title = 'Your application was accepted!';
        if ($row['status'] === 'declined') $title = 'Your application was declined';
        $desc = 'For "' . htmlspecialchars($row['title']) . '" by ' . htmlspecialchars($row['owner_name']);
    }

    $activities[] = [
        'type' => 'collab',
        'title' => $title,
        'desc' => $desc,
        'time' => $row['created_at'],
        'post_id' => $row['post_id'],
        'post_owner_id' => $row['post_owner_id']
    ];
}
while ($row = $recent_tasks->fetch_assoc()) {
    $activities[] = [
        'type' => 'task',
        'title' => 'Task Updated',
        'desc' => htmlspecialchars($row['title']) . ' is now ' . htmlspecialchars($row['status']),
        'time' => $row['created_at']
    ];
}
usort($activities, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
layout_header("Dashboard | UIU ScholarNet");
?>

    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include('../includes/header.php'); ?>

        <?php include('../includes/alerts.php'); ?>

        <!-- Welcome -->
        <section class="greeting">
            <?php
            $hour = date('H');
            if ($hour < 12) {
                $greeting = "Good morning";
            } elseif ($hour < 18) {
                $greeting = "Good afternoon";
            } else {
                $greeting = "Good evening";
            }
            ?>
            <h1><?php echo $greeting; ?>, <?php echo htmlspecialchars(explode(' ', $user_data['full_name'])[0]); ?> 👋</h1>
            <p>You have <?php echo $pending_tasks; ?> pending tasks and <?php echo $collab_requests; ?> pending collaboration requests to review.</p>

            <div class="dash-actions">
                <a href="collaboration.php" class="btn btn-primary btn-collab"><i class="fa-solid fa-plus"></i> Post Collaboration</a>
                <button class="btn btn-outline btn-view-projects" onclick="openProjectsModal()">View My Projects</button>
            </div>
        </section>

        <!-- Stats -->
        <section class="dash-stats">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-folder-open" style="color: var(--secondary-color);"></i></div>
                <div class="stat-info">
                    <h4>PROJECTS</h4>
                    <div class="value"><?php echo $projects_total; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-check-double stat-icon-green"></i></div>
                <div class="stat-info">
                    <h4>TASKS DONE</h4>
                    <div class="value"><?php echo $tasks; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-star stat-icon-yellow"></i></div>
                <div class="stat-info">
                    <h4>REPUTATION</h4>
                    <div class="value"><?php echo number_format($user_data['points']); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-cloud-arrow-up stat-icon-blue"></i></div>
                <div class="stat-info">
                    <h4>FILES UPLOADED</h4>
                    <div class="value"><?php echo $files; ?></div>
                </div>
            </div>
        </section>

        <!-- Grid -->
        <div class="dash-grid">
            <!-- Left: Activity -->
            <section class="activity-feed">
                <h3>Recent Activity</h3>

                <?php if (empty($activities)): ?>
                    <p class="text-muted" style="color: var(--text-light); margin-top: 1rem;">No recent activity to show.</p>
                <?php else: ?>
                    <?php foreach (array_slice($activities, 0, 4) as $activity): ?>
                        <div class="activity-card">
                            <div class="activity-icon">
                                <?php if ($activity['type'] === 'collab'): ?>
                                    <i class="fa-solid fa-user-plus"></i>
                                <?php else: ?>
                                    <i class="fa-solid fa-tasks"></i>
                                <?php endif; ?>
                            </div>
                            <div class="activity-body">
                                <div class="activity-header">
                                    <h4><?php echo $activity['title']; ?></h4>
                                    <span class="time"><?php echo time_elapsed_string($activity['time']); ?></span>
                                </div>
                                <div class="activity-content">
                                    <?php echo $activity['desc']; ?>
                                </div>
                                <?php if ($activity['type'] === 'collab'): ?>
                                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                                    <?php if ($activity['post_owner_id'] == $user_id): ?>
                                        <a href="manage_collaboration.php?id=<?php echo $activity['post_id']; ?>" class="btn btn-primary approve-btn" style="text-decoration:none;">Manage</a>
                                    <?php else: ?>
                                        <a href="collaboration.php" class="btn btn-outline" style="text-decoration:none;">View Board</a>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

            <!-- Right: Quick Actions & Spotlight -->
            <aside class="dash-sidebar">
                <section class="quick-actions">
                    <h3>Quick Actions</h3>
                    <a href="file_upload.php" class="action-item">
                        <span><i class="fa-solid fa-file-arrow-up action-icon"></i> Upload research paper</span>
                        <i class="fa-solid fa-chevron-right chevron-icon"></i>
                    </a>
                    <a href="collaboration.php" class="action-item">
                        <span><i class="fa-solid fa-user-group action-icon"></i> Invite team members</span>
                        <i class="fa-solid fa-chevron-right chevron-icon"></i>
                    </a>
                </section>


            </aside>
        </div>
    </main>

    <!-- My Active Projects Modal -->
    <div class="modal-overlay modal-hidden" id="projectsModal">
        <div class="modal-content modal-wide">
            <i class="fa-solid fa-xmark modal-close" onclick="closeProjectsModal()"></i>
            <h2 class="modal-title">My Active Projects</h2>

            <div class="popup-project-list">
                <?php while($proj = $projects_list_result->fetch_assoc()): ?>
                <div class="popup-project-item">
                    <div class="popup-project-info">
                        <div class="project-mini-icon">
                            <i class="fa-solid fa-microscope"></i>
                        </div>
                        <div class="project-details">
                            <h4><?php echo htmlspecialchars($proj['title']); ?></h4>
                            <div class="project-sub">
                                <span class="status"><?php echo strtoupper($proj['status']); ?></span>
                                <span class="time"><?php echo time_elapsed_string($proj['created_at']); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="popup-project-progress">
                        <div class="progress-bar progress-height-sm progress-bg-light">
                            <div class="progress-fill progress-fill-dynamic" style="width: <?php echo $proj['progress']; ?>%;"></div>
                        </div>
                        <div class="percentage"><?php echo $proj['progress']; ?>%</div>
                    </div>
                    <a href="tasks.php?project_id=<?php echo (int)$proj['id']; ?>" class="popup-project-open">OPEN <i class="fa-solid fa-arrow-right arrow-sm"></i></a>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="centered-actions">
                <button class="btn btn-primary create-btn-small" onclick="closeProjectsModal(); openCreateModal();">
                    <i class="fa-solid fa-plus"></i> CREATE NEW PROJECT
                </button>
            </div>
        </div>
    </div>

    <!-- Create Project Modal (High-Fidelity) -->
    <div class="modal-overlay modal-hidden" id="createProjectModal">
        <div class="modal-content modal-wider">
            <i class="fa-solid fa-xmark modal-close" onclick="closeCreateModal()"></i>
            <h2 class="modal-title-large">Create New Project</h2>
            <p class="modal-subtitle">INSTITUTIONAL ARCHIVE ENTRY</p>
            
            <form action="../actions/create_project.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php 
                    require_once('../includes/csrf.php');
                    echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
                ?>">
                <div class="form-group">
                    <label>PROJECT TITLE</label>
                    <input type="text" name="title" placeholder="e.g. AI in Sustainable Architecture" class="form-input-light" required>
                </div>
                
                <div class="form-row form-row-align-end">
                    <div class="form-group form-group-wide">
                        <label>PRIMARY DEPARTMENT</label>
                        <select name="department" class="form-input-light" required>
                            <option value="">Select a Department</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="EEE">EEE</option>
                        </select>
                    </div>
                    <div class="form-group form-group-wider">
                        <label>VISIBILITY</label>
                        <div class="visibility-group">
                            <label class="visibility-item">
                                <input type="radio" name="visibility" value="public" checked> Public
                            </label>
                            <label class="visibility-item">
                                <input type="radio" name="visibility" value="institution"> Institution Only
                            </label>
                            <label class="visibility-item">
                                <input type="radio" name="visibility" value="private"> Private
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>PROJECT DESCRIPTION</label>
                    <textarea name="description" rows="4" class="textarea-light" placeholder="Briefly outline the scope and research objectives..."></textarea>
                </div>

                <div class="invite-researchers">
                    <label class="invite-label">INVITE RESEARCHERS</label>
                    <div class="search-container search-container-wide">
                        <select name="invited_users[]" class="form-input-light" multiple style="height: auto; min-height: 100px;">
                            <?php 
                            $users_res = db_query("SELECT id, full_name, role FROM users WHERE id != ? ORDER BY full_name ASC", [$user_id]);
                            while($u = $users_res->fetch_assoc()):
                            ?>
                                <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['full_name']); ?> (<?php echo ucfirst($u['role']); ?>)</option>
                            <?php endwhile; ?>
                        </select>
                        <small style="color:#666; font-size:0.75rem; margin-top:5px; display:block;">Hold Ctrl (Windows) or Command (Mac) to select multiple users.</small>
                    </div>
                </div>

                <div class="modal-footer">
                    <a href="javascript:void(0)" onclick="closeCreateModal()" class="cancel-link">CANCEL</a>
                    <button type="submit" class="btn btn-primary create-btn">CREATE PROJECT +</button>
                </div>
            </form>
        </div>
    </div>

    <?php layout_footer(['../assets/js/dashboard.js']); ?>
