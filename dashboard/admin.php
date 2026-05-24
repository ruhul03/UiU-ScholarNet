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
$usersStmt = $conn->prepare("SELECT id, full_name, email, role, department, is_verified, account_status, created_at FROM users ORDER BY created_at DESC");
$usersStmt->execute();
$usersRes = $usersStmt->get_result();

// Stats
$totalUsers = (int)$conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$pendingFac = (int)$conn->query("SELECT COUNT(*) as c FROM users WHERE role='faculty' AND is_verified=0")->fetch_assoc()['c'];
$totalProjects = (int)$conn->query("SELECT COUNT(*) as c FROM projects")->fetch_assoc()['c'];
$totalResources = (int)$conn->query("SELECT COUNT(*) as c FROM resources")->fetch_assoc()['c'];

// Data lists
$departments = $conn->query("SELECT id, name FROM departments ORDER BY name");
$skills = $conn->query("SELECT id, name FROM skills ORDER BY name");
$oppTypes = $conn->query("SELECT id, name FROM opportunity_types ORDER BY name");


layout_header("Admin Panel | UIU ScholarNet");
?>
    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <?php include('../includes/header.php'); ?>
        <?php include('../includes/alerts.php'); ?>

        <section class="greeting" style="margin-bottom: 2rem;">
            <h1>Admin Panel</h1>
            <p>Manage users, verify faculty, and handle platform data.</p>
        </section>

        <!-- Stats Section -->
        <section class="dash-stats" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-users" style="color: var(--secondary-color);"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-user-check" style="color: #1e8e3e;"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $pendingFac; ?></div>
                    <div class="stat-label">Pending Verifications</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-folder-tree" style="color: #4f46e5;"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $totalProjects; ?></div>
                    <div class="stat-label">Total Projects</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fa-solid fa-database" style="color: #e67e22;"></i></div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $totalResources; ?></div>
                    <div class="stat-label">Total Resources</div>
                </div>
            </div>
        </section>

        <div class="card" style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.02); margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1.5rem; font-family: var(--font-heading);">System Users</h3>
            
            <table class="leaderboard-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #eee;">
                        <th style="padding: 1rem; text-align: left;">Name</th>
                        <th style="padding: 1rem; text-align: left;">Email</th>
                        <th style="padding: 1rem; text-align: left;">Role</th>
                        <th style="padding: 1rem; text-align: left;">Department</th>
                        <th style="padding: 1rem; text-align: center;">Status</th>
                        <th style="padding: 1rem; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($u = $usersRes->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #f5f5f5;">
                        <td style="padding: 1rem;"><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                        <td style="padding: 1rem; opacity: 0.7;"><?php echo htmlspecialchars($u['email']); ?></td>
                        <td style="padding: 1rem;">
                            <span style="text-transform: uppercase; font-size: 0.75rem; font-weight: 800; color: var(--primary-color);">
                                <?php echo htmlspecialchars($u['role']); ?>
                            </span>
                        </td>
                        <td style="padding: 1rem; opacity: 0.7;"><?php echo htmlspecialchars($u['department']); ?></td>
                        <td style="padding: 1rem; text-align: center;">
                            <?php if ($u['account_status'] === 'banned'): ?>
                                <span style="background: #333; color: #fff; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">BANNED</span>
                            <?php elseif ($u['role'] === 'admin'): ?>
                                <span style="background: #eef2ff; color: #4f46e5; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">ADMIN</span>
                            <?php elseif ($u['is_verified']): ?>
                                <span style="background: #e6f4ea; color: #1e8e3e; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">VERIFIED</span>
                            <?php else: ?>
                                <span style="background: #fce8e6; color: #d93025; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">PENDING</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem; text-align: right;">
                            <?php if ($u['role'] !== 'admin'): ?>
                                <?php if ($u['role'] === 'faculty' && !$u['is_verified'] && $u['account_status'] !== 'banned'): ?>
                                    <form action="../actions/admin_user_action.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="action_type" value="verify">
                                        <button type="submit" class="btn" style="background: #e6f4ea; color: #1e8e3e; padding: 0.3rem 0.6rem; font-size: 0.75rem; border:1px solid #1e8e3e;">Approve</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($u['account_status'] !== 'banned'): ?>
                                    <form action="../actions/admin_user_action.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="action_type" value="ban">
                                        <button type="submit" class="btn" style="background: #fff; color: #d93025; padding: 0.3rem 0.6rem; font-size: 0.75rem; border:1px solid #d93025;">Ban</button>
                                    </form>
                                <?php else: ?>
                                    <form action="../actions/admin_user_action.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <input type="hidden" name="action_type" value="unban">
                                        <button type="submit" class="btn" style="background: #fff; color: #333; padding: 0.3rem 0.6rem; font-size: 0.75rem; border:1px solid #333;">Unban</button>
                                    </form>
                                <?php endif; ?>

                                <form action="../actions/admin_user_action.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <input type="hidden" name="action_type" value="delete">
                                    <button type="submit" class="btn" style="background: #d93025; color: #fff; padding: 0.3rem 0.6rem; font-size: 0.75rem; border:none;"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Dynamic Data Management -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <!-- Departments -->
            <div class="card" style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                <h4 style="margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">Departments</h4>
                <form action="../actions/admin_manage_data.php" method="POST" style="display:flex; gap:0.5rem; margin-bottom:1rem;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="table" value="departments">
                    <input type="text" name="name" placeholder="New Department" required style="flex:1; padding:0.5rem; border:1px solid #ccc; border-radius:4px;">
                    <button type="submit" class="btn btn-primary" style="padding:0.5rem 1rem;">Add</button>
                </form>
                <div style="max-height: 200px; overflow-y:auto; border:1px solid #eee; border-radius:4px; padding:0.5rem;">
                    <?php while($d = $departments->fetch_assoc()): ?>
                        <div style="display:flex; justify-content:space-between; padding:0.4rem; border-bottom:1px solid #f9f9f9;">
                            <span style="font-size:0.85rem;"><?php echo htmlspecialchars($d['name']); ?></span>
                            <form action="../actions/admin_manage_data.php" method="POST" style="display:inline;" onsubmit="return confirm('Remove?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="table" value="departments">
                                <input type="hidden" name="delete_id" value="<?php echo $d['id']; ?>">
                                <button type="submit" style="background:none; border:none; color:#d93025; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Skills -->
            <div class="card" style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                <h4 style="margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">Skills</h4>
                <form action="../actions/admin_manage_data.php" method="POST" style="display:flex; gap:0.5rem; margin-bottom:1rem;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="table" value="skills">
                    <input type="text" name="name" placeholder="New Skill" required style="flex:1; padding:0.5rem; border:1px solid #ccc; border-radius:4px;">
                    <button type="submit" class="btn btn-primary" style="padding:0.5rem 1rem;">Add</button>
                </form>
                <div style="max-height: 200px; overflow-y:auto; border:1px solid #eee; border-radius:4px; padding:0.5rem;">
                    <?php while($s = $skills->fetch_assoc()): ?>
                        <div style="display:flex; justify-content:space-between; padding:0.4rem; border-bottom:1px solid #f9f9f9;">
                            <span style="font-size:0.85rem;"><?php echo htmlspecialchars($s['name']); ?></span>
                            <form action="../actions/admin_manage_data.php" method="POST" style="display:inline;" onsubmit="return confirm('Remove?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="table" value="skills">
                                <input type="hidden" name="delete_id" value="<?php echo $s['id']; ?>">
                                <button type="submit" style="background:none; border:none; color:#d93025; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Opportunity Types -->
            <div class="card" style="background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                <h4 style="margin-bottom: 1rem; border-bottom: 1px solid #eee; padding-bottom: 0.5rem;">Opportunity Types</h4>
                <form action="../actions/admin_manage_data.php" method="POST" style="display:flex; gap:0.5rem; margin-bottom:1rem;">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="table" value="opportunity_types">
                    <input type="text" name="name" placeholder="New Type" required style="flex:1; padding:0.5rem; border:1px solid #ccc; border-radius:4px;">
                    <button type="submit" class="btn btn-primary" style="padding:0.5rem 1rem;">Add</button>
                </form>
                <div style="max-height: 200px; overflow-y:auto; border:1px solid #eee; border-radius:4px; padding:0.5rem;">
                    <?php while($o = $oppTypes->fetch_assoc()): ?>
                        <div style="display:flex; justify-content:space-between; padding:0.4rem; border-bottom:1px solid #f9f9f9;">
                            <span style="font-size:0.85rem;"><?php echo htmlspecialchars($o['name']); ?></span>
                            <form action="../actions/admin_manage_data.php" method="POST" style="display:inline;" onsubmit="return confirm('Remove?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="table" value="opportunity_types">
                                <input type="hidden" name="delete_id" value="<?php echo $o['id']; ?>">
                                <button type="submit" style="background:none; border:none; color:#d93025; cursor:pointer;"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

    </main>

</body>
</html>
