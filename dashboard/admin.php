<?php
require_once('../includes/auth_check.php');
require_once('../includes/layout.php');
require_once('../includes/csrf.php');

// Ensure only admins can access this page
if ($user_data['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// User Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$whereClauses = [];
$params = [];
$types = "";

if ($search !== '') {
    $whereClauses[] = "(full_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

if ($roleFilter !== '') {
    $whereClauses[] = "role = ?";
    $params[] = $roleFilter;
    $types .= "s";
}

if ($statusFilter !== '') {
    if ($statusFilter === 'verified') {
        $whereClauses[] = "is_verified = 1 AND account_status != 'banned'";
    } elseif ($statusFilter === 'pending') {
        $whereClauses[] = "is_verified = 0 AND account_status != 'banned'";
    } elseif ($statusFilter === 'banned') {
        $whereClauses[] = "account_status = 'banned'";
    }
}

$whereSQL = "";
if (count($whereClauses) > 0) {
    $whereSQL = "WHERE " . implode(" AND ", $whereClauses);
}

// Count total matching for pagination
$countQuery = "SELECT COUNT(*) as c FROM users $whereSQL";
$totalFilteredUsers = (int)db_query($countQuery, count($params) ? $params : [], $types)->fetch_assoc()['c'];
$totalPages = ceil($totalFilteredUsers / $limit);

// Fetch users
$usersQuery = "SELECT id, full_name, email, role, department, is_verified, account_status, created_at FROM users $whereSQL ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$usersRes = db_query($usersQuery, $params, $types);

// Fetch recent projects for moderation
$projectsRes = db_query("
    SELECT p.id, p.title, p.status, p.visibility, p.created_at, u.full_name as creator_name
    FROM projects p
    LEFT JOIN users u ON p.creator_id = u.id
    ORDER BY p.created_at DESC
    LIMIT 20
", [], "");

// Global Activity Feed
$globalActivities = [];
// Fetch 15 newest users
$newUsers = db_query("SELECT id, full_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 15", [], "");
while($row = $newUsers->fetch_assoc()) {
    $globalActivities[] = [
        'type' => 'user',
        'icon' => 'fa-user-plus text-primary',
        'title' => 'New User Registration',
        'desc' => '<a href="#" class="user-profile-trigger" data-user-id="' . $row['id'] . '" style="color: inherit; text-decoration: none; font-weight: 500;">' . htmlspecialchars($row['full_name']) . '</a> joined as ' . htmlspecialchars($row['role']),
        'time' => $row['created_at']
    ];
}
$newProjects = db_query("SELECT p.title, p.created_at, u.id as creator_id, u.full_name FROM projects p LEFT JOIN users u ON p.creator_id = u.id ORDER BY p.created_at DESC LIMIT 15", [], "");
while($row = $newProjects->fetch_assoc()) {
    $globalActivities[] = [
        'type' => 'project',
        'icon' => 'fa-folder-plus text-success',
        'title' => 'New Project Created',
        'desc' => '<a href="#" class="user-profile-trigger" data-user-id="' . $row['creator_id'] . '" style="color: inherit; text-decoration: none; font-weight: 500;">' . htmlspecialchars($row['full_name']) . '</a> created \'' . htmlspecialchars($row['title']) . '\'',
        'time' => $row['created_at']
    ];
}
usort($globalActivities, function($a, $b) {
    return strtotime($b['time']) - strtotime($a['time']);
});
$globalActivities = array_slice($globalActivities, 0, 6);

// Helper function for time elapsed
if (!function_exists('time_elapsed_string')) {
    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
        $string = array('y' => 'year','m' => 'month','w' => 'week','d' => 'day','h' => 'hour','i' => 'minute','s' => 'second');
        foreach ($string as $k => &$v) {
            if ($diff->$k) { $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : ''); } 
            else { unset($string[$k]); }
        }
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}

$totalUsers = (int)db_query("SELECT COUNT(*) as c FROM users", [], "")->fetch_assoc()['c'];
$pendingFac = (int)db_query("SELECT COUNT(*) as c FROM users WHERE role='faculty' AND is_verified=0", [], "")->fetch_assoc()['c'];
$totalProjects = (int)db_query("SELECT COUNT(*) as c FROM projects", [], "")->fetch_assoc()['c'];
$totalResources = (int)db_query("SELECT COUNT(*) as c FROM resources", [], "")->fetch_assoc()['c'];

// Data lists
$departments = db_query("SELECT id, name FROM departments ORDER BY name", [], "");
$skills = db_query("SELECT id, name FROM skills ORDER BY name", [], "");
$oppTypes = db_query("SELECT id, name FROM opportunity_types ORDER BY name", [], "");


$is_admin_panel = true;
layout_header("Admin Panel | UIU ScholarNet");
?>
    <!-- Main Content -->
    <main class="main-content no-sidebar">
        <!-- Header -->
        <?php include('../includes/header.php'); ?>
        <?php include('../includes/alerts.php'); ?>

        <section class="greeting mb-2" id="admin-overview" style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <h1>Admin Panel</h1>
                <p>Manage users, verify faculty, and handle platform data.</p>
            </div>
            <a href="index.php" class="btn btn-outline" style="text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
        </section>

        <!-- Main Dashboard Layout Grid -->
        <div style="display: grid; grid-template-columns: 1fr 350px; gap: 1.5rem; align-items: start;">
            
            <!-- Left Content Column -->
            <div style="display: flex; flex-direction: column; gap: 2rem;">
                
                <!-- Stats Section -->
                <section class="dash-stats" style="margin-bottom: 0; grid-template-columns: repeat(4, 1fr); gap: 1rem;">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-users text-secondary"></i></div>
                        <div class="stat-info">
                            <h4>TOTAL USERS</h4>
                            <div class="value"><?php echo $totalUsers; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-user-check text-success-alt"></i></div>
                        <div class="stat-info">
                            <h4>PENDING VERIFICATIONS</h4>
                            <div class="value"><?php echo $pendingFac; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-folder-tree text-primary-alt"></i></div>
                        <div class="stat-info">
                            <h4>TOTAL PROJECTS</h4>
                            <div class="value"><?php echo $totalProjects; ?></div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-database text-warning"></i></div>
                        <div class="stat-info">
                            <h4>TOTAL RESOURCES</h4>
                            <div class="value"><?php echo $totalResources; ?></div>
                        </div>
                    </div>
                </section>

                <!-- Users Management -->
                <div class="card admin-card-main" id="admin-users" style="margin-bottom: 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                        <h3 class="admin-card-title" style="margin-bottom: 0;">System Users (<?php echo $totalFilteredUsers; ?>)</h3>
                        
                        <form method="GET" action="admin.php" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                            <input type="text" name="search" placeholder="Search name or email..." value="<?php echo htmlspecialchars($search); ?>" class="form-input-light" style="width: 250px; height: 36px; padding: 0 0.8rem; font-size: 0.85rem; border-radius: 6px; border: 1px solid #ddd; box-sizing: border-box;">
                            
                            <select name="role" class="form-input-light" style="width: auto; height: 36px; padding: 0 2rem 0 0.8rem; font-size: 0.85rem; border-radius: 6px; border: 1px solid #ddd; box-sizing: border-box; appearance: auto;">
                                <option value="">All Roles</option>
                                <option value="student" <?php if($roleFilter==='student') echo 'selected'; ?>>Student</option>
                                <option value="faculty" <?php if($roleFilter==='faculty') echo 'selected'; ?>>Faculty</option>
                                <option value="alumni" <?php if($roleFilter==='alumni') echo 'selected'; ?>>Alumni</option>
                                <option value="admin" <?php if($roleFilter==='admin') echo 'selected'; ?>>Admin</option>
                            </select>

                            <select name="status" class="form-input-light" style="width: auto; height: 36px; padding: 0 2rem 0 0.8rem; font-size: 0.85rem; border-radius: 6px; border: 1px solid #ddd; box-sizing: border-box; appearance: auto;">
                                <option value="">All Statuses</option>
                                <option value="verified" <?php if($statusFilter==='verified') echo 'selected'; ?>>Verified</option>
                                <option value="pending" <?php if($statusFilter==='pending') echo 'selected'; ?>>Pending</option>
                                <option value="banned" <?php if($statusFilter==='banned') echo 'selected'; ?>>Banned</option>
                            </select>

                            <button type="submit" class="btn btn-primary btn-sm" style="height: 36px; padding: 0 1rem; border-radius: 6px; display: inline-flex; align-items: center; gap: 0.4rem; box-sizing: border-box;"><i class="fa-solid fa-filter"></i> Filter</button>
                            <?php if ($search || $roleFilter || $statusFilter): ?>
                                <a href="admin.php" class="btn btn-outline btn-sm" style="height: 36px; padding: 0 1rem; border-radius: 6px; display: inline-flex; align-items: center; box-sizing: border-box;">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    
                    <table class="leaderboard-table admin-table">
                        <thead>
                            <tr class="admin-tr-header">
                                <th class="admin-th-left">Name</th>
                                <th class="admin-th-left">Email</th>
                                <th class="admin-th-left">Role</th>
                                <th class="admin-th-left">Department</th>
                                <th class="admin-th-center">Status</th>
                                <th class="admin-th-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($u = $usersRes->fetch_assoc()): ?>
                            <tr class="admin-tr-body">
                                <td class="admin-td"><strong><a href="#" class="user-profile-trigger" data-user-id="<?php echo $u['id']; ?>" style="color: inherit; text-decoration: none; border-bottom: 1px dashed #ccc; transition: opacity 0.2s;" onmouseover="this.style.opacity=0.7" onmouseout="this.style.opacity=1"><?php echo htmlspecialchars($u['full_name']); ?></a></strong></td>
                                <td class="admin-td-muted"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td class="admin-td">
                                    <span class="admin-role-text">
                                        <?php echo htmlspecialchars($u['role']); ?>
                                    </span>
                                </td>
                                <td class="admin-td-muted"><?php echo htmlspecialchars($u['department']); ?></td>
                                <td class="admin-td-center">
                                    <?php if ($u['account_status'] === 'banned'): ?>
                                        <span class="badge-banned">BANNED</span>
                                    <?php elseif ($u['role'] === 'admin'): ?>
                                        <span class="badge-admin">ADMIN</span>
                                    <?php elseif ($u['is_verified']): ?>
                                        <span class="badge-verified">VERIFIED</span>
                                    <?php else: ?>
                                        <span class="badge-pending">PENDING</span>
                                    <?php endif; ?>
                                </td>
                                <td class="admin-td-right">
                                    <?php if ($u['role'] !== 'admin'): ?>
                                        <?php if ($u['role'] === 'faculty' && !$u['is_verified'] && $u['account_status'] !== 'banned'): ?>
                                            <form action="../actions/admin_user_action.php" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="action_type" value="verify">
                                                <button type="submit" class="btn btn-sm-success">Approve</button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if ($u['account_status'] !== 'banned'): ?>
                                            <form action="../actions/admin_user_action.php" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="action_type" value="ban">
                                                <button type="submit" class="btn btn-sm-danger-outline">Ban</button>
                                            </form>
                                        <?php else: ?>
                                            <form action="../actions/admin_user_action.php" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="action_type" value="unban">
                                                <button type="submit" class="btn btn-sm-dark-outline">Unban</button>
                                            </form>
                                        <?php endif; ?>

                                        <form action="../actions/admin_user_action.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                            <input type="hidden" name="action_type" value="delete">
                                            <button type="submit" class="btn btn-sm-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($totalPages > 1): ?>
                        <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 1.5rem;">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="admin.php?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($roleFilter); ?>&status=<?php echo urlencode($statusFilter); ?>" 
                                   class="btn <?php echo ($i === $page) ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                                   <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Projects Moderation -->
                <div class="card admin-card-main" id="admin-projects" style="margin-bottom: 0;">
                    <h3 class="admin-card-title">Recent Projects (Moderation)</h3>
                    <table class="leaderboard-table admin-table">
                        <thead>
                            <tr class="admin-tr-header">
                                <th class="admin-th-left">Project Title</th>
                                <th class="admin-th-left">Creator</th>
                                <th class="admin-th-left">Status</th>
                                <th class="admin-th-left">Visibility</th>
                                <th class="admin-th-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($p = $projectsRes->fetch_assoc()): ?>
                            <tr class="admin-tr-body">
                                <td class="admin-td"><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                                <td class="admin-td-muted"><?php echo htmlspecialchars($p['creator_name']); ?></td>
                                <td class="admin-td-muted"><?php echo strtoupper(htmlspecialchars($p['status'])); ?></td>
                                <td class="admin-td-muted"><?php echo strtoupper(htmlspecialchars($p['visibility'])); ?></td>
                                <td class="admin-td-right">
                                    <form action="../actions/admin_project_action.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this project globally?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="project_id" value="<?php echo $p['id']; ?>">
                                        <input type="hidden" name="action_type" value="delete">
                                        <button type="submit" class="btn btn-sm-danger"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Dynamic Data Management -->
                <div class="admin-grid" id="admin-data" style="margin-bottom: 0;">
                    <!-- Departments -->
                    <div class="card admin-card-sub">
                        <h4 class="admin-card-sub-title">Departments</h4>
                        <form action="../actions/admin_manage_data.php" method="POST" class="admin-form-flex">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="table" value="departments">
                            <input type="text" name="name" placeholder="New Department" required class="admin-input-sm">
                            <button type="submit" class="btn btn-primary btn-p-sm">Add</button>
                        </form>
                        <div class="admin-list-container">
                            <?php while($d = $departments->fetch_assoc()): ?>
                                <div class="admin-list-item">
                                    <span class="admin-list-text"><?php echo htmlspecialchars($d['name']); ?></span>
                                    <form action="../actions/admin_manage_data.php" method="POST" class="d-inline" onsubmit="return confirm('Remove?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="table" value="departments">
                                        <input type="hidden" name="delete_id" value="<?php echo $d['id']; ?>">
                                        <button type="submit" class="btn-remove-sm"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Skills -->
                    <div class="card admin-card-sub">
                        <h4 class="admin-card-sub-title">Skills</h4>
                        <form action="../actions/admin_manage_data.php" method="POST" class="admin-form-flex">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="table" value="skills">
                            <input type="text" name="name" placeholder="New Skill" required class="admin-input-sm">
                            <button type="submit" class="btn btn-primary btn-p-sm">Add</button>
                        </form>
                        <div class="admin-list-container">
                            <?php while($s = $skills->fetch_assoc()): ?>
                                <div class="admin-list-item">
                                    <span class="admin-list-text"><?php echo htmlspecialchars($s['name']); ?></span>
                                    <form action="../actions/admin_manage_data.php" method="POST" class="d-inline" onsubmit="return confirm('Remove?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="table" value="skills">
                                        <input type="hidden" name="delete_id" value="<?php echo $s['id']; ?>">
                                        <button type="submit" class="btn-remove-sm"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Opportunity Types -->
                    <div class="card admin-card-sub">
                        <h4 class="admin-card-sub-title">Opportunity Types</h4>
                        <form action="../actions/admin_manage_data.php" method="POST" class="admin-form-flex">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="table" value="opportunity_types">
                            <input type="text" name="name" placeholder="New Type" required class="admin-input-sm">
                            <button type="submit" class="btn btn-primary btn-p-sm">Add</button>
                        </form>
                        <div class="admin-list-container">
                            <?php while($o = $oppTypes->fetch_assoc()): ?>
                                <div class="admin-list-item">
                                    <span class="admin-list-text"><?php echo htmlspecialchars($o['name']); ?></span>
                                    <form action="../actions/admin_manage_data.php" method="POST" class="d-inline" onsubmit="return confirm('Remove?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="table" value="opportunity_types">
                                        <input type="hidden" name="delete_id" value="<?php echo $o['id']; ?>">
                                        <button type="submit" class="btn-remove-sm"><i class="fa-solid fa-xmark"></i></button>
                                    </form>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div> <!-- End Left Content Column -->

            <!-- Right Column (Global Activity Feed) -->
            <div class="card admin-card-main" style="margin-bottom: 0; position: sticky; top: 2rem; max-height: calc(100vh - 4rem); display: flex; flex-direction: column;">
                <h3 class="admin-card-title" style="margin-bottom: 1rem; flex-shrink: 0;">System Activity</h3>
                <div style="display: flex; flex-direction: column; gap: 1rem; overflow-y: auto; padding-right: 0.5rem; flex-grow: 1;">
                    <?php foreach ($globalActivities as $act): ?>
                        <div style="display: flex; gap: 0.8rem; align-items: flex-start; padding-bottom: 0.8rem; border-bottom: 1px solid #eee;">
                            <div style="width: 32px; height: 32px; border-radius: 50%; background: #f8f9fa; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i class="fa-solid <?php echo $act['icon']; ?>" style="font-size: 0.9rem;"></i>
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 0.85rem; color: #333; margin-bottom: 2px;"><?php echo $act['title']; ?></div>
                                <div style="font-size: 0.75rem; color: #666; margin-bottom: 4px;"><?php echo $act['desc']; ?></div>
                                <div style="font-size: 0.65rem; color: #999;"><?php echo time_elapsed_string($act['time']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div> <!-- End Right Column -->
            
        </div> <!-- End Main Dashboard Layout Grid -->

    </main>

</body>
</html>
