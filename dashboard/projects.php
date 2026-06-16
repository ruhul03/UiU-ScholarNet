<?php
require_once('../includes/auth_check.php');
require_once('../includes/csrf.php');

// Fetch verified faculty to populate the supervisor dropdown for student projects
$faculty_res = db_query("SELECT id, full_name, department FROM users WHERE role = 'faculty' AND is_verified = 1 ORDER BY full_name ASC");
$faculty_list = [];
while ($f = $faculty_res->fetch_assoc()) {
    $faculty_list[] = $f;
}

// Fetch counts for notification badges
$pending_tasks = (int)(db_query("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status != 'done'", [$user_id], "i")->fetch_assoc()['total'] ?? 0);
$collab_requests = (int)(db_query("SELECT COUNT(*) as total FROM collaboration_applications ca JOIN collaboration_posts cp ON ca.post_id = cp.id WHERE cp.user_id = ? AND ca.status = 'pending'", [$user_id], "i")->fetch_assoc()['total'] ?? 0);

// Fetch stats for the dashboard insight section
$active_projects = (int)(db_query("
    SELECT COUNT(DISTINCT p.id) as total 
    FROM projects p 
    LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? 
    WHERE (p.creator_id = ? OR pm.user_id = ?) AND p.status = 'active'
", [$user_id, $user_id, $user_id], "iii")->fetch_assoc()['total'] ?? 0);

$peer_collaborators = (int)(db_query("
    SELECT COUNT(DISTINCT m.user_id) as total 
    FROM project_members m 
    JOIN project_members me ON m.project_id = me.project_id 
    WHERE me.user_id = ? AND m.user_id != ?
", [$user_id, $user_id], "ii")->fetch_assoc()['total'] ?? 0);

// Fetch Project Invitations where the user has been invited to join a team
$pending_invites = db_query("
    SELECT p.*, pm.role 
    FROM projects p 
    JOIN project_members pm ON pm.project_id = p.id 
    WHERE pm.user_id = ? AND pm.status = 'pending'
    ORDER BY p.created_at DESC
", [$user_id], "i");

// Fetch Pending Supervision Requests if the user is a faculty member
$pending_supervisions = null;
if (isset($user_data['role']) && $user_data['role'] === 'faculty') {
    $pending_supervisions = db_query("
        SELECT p.*, u.full_name as creator_name
        FROM projects p
        JOIN users u ON p.creator_id = u.id
        WHERE p.supervisor_id = ? AND p.supervision_accepted = 0
        ORDER BY p.created_at DESC
    ", [$user_id], "i");
}

// Fetch all active projects where the user is either the creator or an active team member
$result = db_query("
    SELECT p.*, 
           (SELECT COUNT(DISTINCT user_id) FROM project_members WHERE project_id = p.id AND status = 'active') as contributors_count,
           (SELECT GROUP_CONCAT(CONCAT(u.full_name, '::', pm2.role, '::', u.role) SEPARATOR '||') FROM project_members pm2 JOIN users u ON pm2.user_id = u.id WHERE pm2.project_id = p.id AND pm2.status = 'active') as contributor_data,
           (SELECT COUNT(*) FROM collaboration_posts WHERE project_id = p.id AND status = 'open') as active_collabs
    FROM projects p 
    LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
    WHERE p.creator_id = ? OR (pm.user_id = ? AND pm.status = 'active')
    ORDER BY p.created_at DESC
", [$user_id, $user_id, $user_id], "iii");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects | UIU ScholarNet</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/lifecycle.css">
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php include('../includes/header.php'); ?>

        <section class="projects-section">
            <div class="projects-header">
                <div>
                    <h1 class="projects-title">My Projects</h1>
                    <p class="projects-desc">Managing the digital corpus of ongoing research, collaborative ventures, and institutional initiatives.</p>
                </div>
                <button class="btn btn-primary btn-new-project" onclick="openModal()">
                    <i class="fa-solid fa-plus"></i> NEW PROJECT
                </button>
            </div>

            <?php include('../includes/alerts.php'); ?>

            <?php if ($pending_invites && $pending_invites->num_rows > 0): ?>
            <div class="pending-section">
                <h3 class="pending-section-title">Pending Project Invitations</h3>
                <div class="pending-list">
                    <?php while($inv = $pending_invites->fetch_assoc()): ?>
                    <div class="pending-card">
                        <div>
                            <h4 class="pending-card-title"><?php echo htmlspecialchars($inv['title']); ?></h4>
                            <p class="pending-card-subtitle"><i class="fa-solid fa-building margin-right-sm"></i> <?php echo htmlspecialchars($inv['department']); ?></p>
                        </div>
                        <div class="pending-actions">
                            <a href="../actions/respond_invitation.php?project_id=<?php echo $inv['id']; ?>&action=accept" class="btn btn-accept"><i class="fa-solid fa-check"></i> Accept</a>
                            <a href="../actions/respond_invitation.php?project_id=<?php echo $inv['id']; ?>&action=decline" class="btn btn-decline"><i class="fa-solid fa-xmark"></i> Decline</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($pending_supervisions && $pending_supervisions->num_rows > 0): ?>
            <div class="pending-section">
                <h3 class="pending-section-title">Pending Supervision Requests</h3>
                <div class="pending-list">
                    <?php while($sup = $pending_supervisions->fetch_assoc()): ?>
                    <div class="pending-card">
                        <div>
                            <h4 class="pending-card-title"><?php echo htmlspecialchars($sup['title']); ?></h4>
                            <p class="pending-card-subtitle"><i class="fa-solid fa-user margin-right-sm"></i> Created by: <?php echo htmlspecialchars($sup['creator_name']); ?> &middot; <?php echo htmlspecialchars($sup['department']); ?></p>
                        </div>
                        <div class="pending-actions">
                            <a href="../actions/respond_supervision.php?project_id=<?php echo $sup['id']; ?>&action=accept" class="btn btn-accept"><i class="fa-solid fa-check"></i> Approve</a>
                            <a href="../actions/respond_supervision.php?project_id=<?php echo $sup['id']; ?>&action=decline" class="btn btn-decline"><i class="fa-solid fa-xmark"></i> Reject</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="project-horizontal-list">
                <?php while($row = $result->fetch_assoc()): ?>
                <!-- Dynamic Horizontal Card -->
                <div class="project-horizontal-card">
                    <div class="project-brand">
                        <i class="fa-solid fa-brain"></i>
                    </div>
                    <div class="project-main-info">
                        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                        <div class="project-stats-row">
                            <?php 
                            $r_phases = [
                                'literature_review' => 'Literature Review',
                                'gap_analysis' => 'Gap Analysis',
                                'methodology' => 'Methodology',
                                'implementation' => 'Implementation',
                                'experimentation' => 'Experimentation',
                                'drafting' => 'Drafting',
                                'publishing' => 'Publishing'
                            ];
                            $disp_phase = $r_phases[$row['research_phase'] ?? 'literature_review'];
                            ?>
                            <span class="status-chip" style="background: linear-gradient(90deg, rgba(26, 115, 232, 0.1), rgba(197, 160, 34, 0.1)); border: 1px solid rgba(197, 160, 34, 0.5); color: var(--primary-color);"><i class="fa-solid fa-microscope" style="color: #c5a022;"></i> <?php echo strtoupper($disp_phase); ?></span>
                            <?php if ($row['active_collabs'] > 0): ?>
                                <span class="status-chip status-chip-blue"><i class="fa-solid fa-users-viewfinder"></i> COLLAB ACTIVE</span>
                            <?php endif; ?>
                            <span class="contributors-count"><i class="fa-solid fa-user-group"></i> <?php echo (int)$row['contributors_count']; ?> Contributors</span>
                        </div>
                        <div class="project-team-info" style="display: flex; flex-wrap: wrap; align-items: center; gap: 4px;">
                            <strong>Team:</strong> 
                            <?php 
                            if (!empty($row['contributor_data'])) {
                                $members = explode('||', $row['contributor_data']);
                                $member_html = [];
                                foreach ($members as $m) {
                                    $parts = explode('::', $m);
                                    if (count($parts) >= 2) {
                                        $name = htmlspecialchars($parts[0]);
                                        $pm_role = $parts[1] ?? '';
                                        $u_role = $parts[2] ?? '';
                                        
                                        $badges = '';
                                        if ($pm_role === 'owner') {
                                            $badges .= ' <span class="status-chip status-chip-blue" style="font-size: 0.55rem; padding: 2px 4px; margin-left: 4px;">LEADER</span>';
                                        }
                                        if ($u_role === 'faculty') {
                                            $badges .= ' <span class="status-chip" style="font-size: 0.55rem; padding: 2px 4px; background: #6c5ce7; color: white; border: none; margin-left: 4px;">FACULTY</span>';
                                        }
                                        $member_html[] = '<span style="display:inline-flex; align-items: center;">' . $name . $badges . '</span>';
                                    }
                                }
                                echo implode(' <span style="color: #ccc; margin: 0 4px;">&bull;</span> ', $member_html);
                            } else {
                                echo '<span>Just you</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="project-progress-block">
                        <div class="progress-label">
                            <span>RESEARCH PROGRESS</span>
                            <span><?php echo $row['progress']; ?>%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill progress-fill-dynamic" style="width: <?php echo $row['progress']; ?>%;"></div>
                        </div>
                    </div>
                    <div class="project-actions project-actions-container">
                        <div class="options-wrapper">
                            <div class="options-icon" onclick="toggleProjectOptions(event, <?php echo $row['id']; ?>)">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </div>
                            <div class="options-dropdown" id="dropdown-<?php echo $row['id']; ?>">
                                <a href="edit_project.php?id=<?php echo $row['id']; ?>"><i class="fa-regular fa-pen-to-square"></i> Edit Details</a>
                                
                                <div class="dropdown-divider"></div>
                                
                                <form action="../actions/delete_project.php" method="POST" id="delete-form-<?php echo $row['id']; ?>" class="hidden">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="project_id" value="<?php echo $row['id']; ?>">
                                </form>
                                <a href="javascript:void(0)" class="delete-option delete-trigger text-danger" data-id="<?php echo $row['id']; ?>">
                                    <i class="fa-regular fa-trash-can"></i> Remove Project
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>

                <?php if ($result->num_rows === 0): ?>
                <!-- Create New Project Empty State Box -->
                <div class="create-project-box" onclick="openModal()">
                    <div class="create-project-icon">
                        <i class="fa-solid fa-plus"></i>
                    </div>
                    <h3 class="create-project-title">Create New Project</h3>
                    <p class="create-project-subtitle">CREATE A NEW RESEARCH DOMAIN</p>
                </div>
                <?php endif; ?>
            </div>
        </section>

    </main>

    <!-- Create Project Modal (High-Fidelity) -->
    <div class="modal-overlay modal-hidden" id="projectModal">
        <div class="modal-content modal-wider">
            <i class="fa-solid fa-xmark modal-close" onclick="closeModal()"></i>
            <h2 class="modal-title-large">Create New Project</h2>
            <p class="modal-subtitle">INSTITUTIONAL ARCHIVE ENTRY</p>
            
            <form action="../actions/create_project.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label>PROJECT TITLE</label>
                    <input type="text" name="title" placeholder="e.g. AI in Sustainable Architecture" class="form-input-light" required>
                </div>
                
                <div class="form-row form-row-align-end">
                    <div class="form-group form-group-wide">
                        <label>PRIMARY DEPARTMENT</label>
                        <select name="department" class="form-input-light" required>
                            <option value="">Select a Department</option>
                            <option value="Computer Science & Engineering">Computer Science & Engineering (CSE)</option>
                            <option value="Electrical & Electronic Engineering">Electrical & Electronic Engineering (EEE)</option>
                            <option value="Civil Engineering">Civil Engineering (CE)</option>
                            <option value="Business Administration">Business Administration (BBA)</option>
                            <option value="Economics">Economics</option>
                            <option value="Data Science">Data Science</option>
                            <option value="Biotechnology">Biotechnology</option>
                            <option value="Pharmacy">Pharmacy</option>
                            <option value="Mathematics">Mathematics</option>
                            <option value="English">English</option>
                            <option value="Media Studies & Journalism">Media Studies & Journalism (MSJ)</option>
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

                <?php if ($user_data['role'] === 'student'): ?>
                <div class="form-group">
                    <label>FACULTY SUPERVISOR</label>
                    <select name="supervisor_id" class="form-input-light" required>
                        <option value="">Select a Verified Faculty</option>
                        <?php foreach ($faculty_list as $fac): ?>
                            <option value="<?php echo $fac['id']; ?>"><?php echo htmlspecialchars($fac['full_name']); ?> (<?php echo htmlspecialchars($fac['department']); ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="invite-researchers">
                    <label class="invite-label">INVITE RESEARCHERS</label>
                    <div class="custom-multi-select">
                        <input type="text" id="researcherSearch" placeholder="Search by name..." class="form-input-light custom-multi-select-input" onkeyup="filterResearchers()">
                        <div class="researcher-list" id="researcherList">
                            <?php 
                            // Fetch users to invite to the new project
                            $users_res = db_query("SELECT id, full_name, role FROM users WHERE id != ? ORDER BY full_name ASC", [$user_id], "i");
                            while($u = $users_res->fetch_assoc()):
                            ?>
                                <label class="researcher-item">
                                    <input type="checkbox" name="invited_users[]" value="<?php echo $u['id']; ?>">
                                    <span class="researcher-name"><?php echo htmlspecialchars($u['full_name']); ?></span> <small class="researcher-role">(<?php echo ucfirst($u['role']); ?>)</small>
                                </label>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <a href="javascript:void(0)" onclick="closeModal()" class="cancel-link">CANCEL</a>
                    <button type="submit" class="btn btn-primary create-btn">CREATE PROJECT +</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/projects.js"></script>
    <script>
    function filterResearchers() {
        var input = document.getElementById('researcherSearch');
        var filter = input.value.toUpperCase();
        var list = document.getElementById('researcherList');
        var items = list.getElementsByClassName('researcher-item');
        for (var i = 0; i < items.length; i++) {
            var label = items[i].getElementsByClassName('researcher-name')[0];
            var txtValue = label.textContent || label.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                items[i].style.display = "block";
            } else {
                items[i].style.display = "none";
            }
        }
    }
    </script>
</body>
</html>
