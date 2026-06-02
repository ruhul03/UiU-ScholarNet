<?php
require_once('../includes/auth_check.php');
require_once('../includes/layout.php');
require_once('../includes/csrf.php');

// Ensure only admins can access this page
if ($user_data['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}

// Fetch users for admin panel
$usersRes = db_query("SELECT id, full_name, email, role, department, is_verified, account_status, created_at FROM users ORDER BY created_at DESC", [], "");

// Stats
$totalUsers = (int)db_query("SELECT COUNT(*) as c FROM users", [], "")->fetch_assoc()['c'];
$pendingFac = (int)db_query("SELECT COUNT(*) as c FROM users WHERE role='faculty' AND is_verified=0", [], "")->fetch_assoc()['c'];
$totalProjects = (int)db_query("SELECT COUNT(*) as c FROM projects", [], "")->fetch_assoc()['c'];
$totalResources = (int)db_query("SELECT COUNT(*) as c FROM resources", [], "")->fetch_assoc()['c'];

// Data lists
$departments = db_query("SELECT id, name FROM departments ORDER BY name", [], "");
$skills = db_query("SELECT id, name FROM skills ORDER BY name", [], "");
$oppTypes = db_query("SELECT id, name FROM opportunity_types ORDER BY name", [], "");


layout_header("Admin Panel | UIU ScholarNet");
?>
    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include('../includes/header.php'); ?>
        <?php include('../includes/alerts.php'); ?>

        <section class="greeting mb-2" id="admin-overview">
            <h1>Admin Panel</h1>
            <p>Manage users, verify faculty, and handle platform data.</p>
        </section>

        <!-- Stats Section -->
        <section class="dash-stats mb-2">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-users text-secondary"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-user-check text-success-alt"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $pendingFac; ?></div>
                    <div class="stat-label">Pending Verifications</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-folder-tree text-primary-alt"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $totalProjects; ?></div>
                    <div class="stat-label">Total Projects</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-database text-warning"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $totalResources; ?></div>
                    <div class="stat-label">Total Resources</div>
                </div>
            </div>
        </section>

        <div class="card admin-card-main" id="admin-users">
            <h3 class="admin-card-title">System Users</h3>
            
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
                        <td class="admin-td"><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
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
        </div>

        <!-- Dynamic Data Management -->
        <div class="admin-grid" id="admin-data">
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

    </main>

</body>
</html>
