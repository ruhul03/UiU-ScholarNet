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
$usersStmt = $conn->prepare("SELECT id, full_name, email, role, department, is_verified, created_at FROM users ORDER BY created_at DESC");
$usersStmt->execute();
$usersRes = $usersStmt->get_result();

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
            <p>Manage users and verify faculty registrations.</p>
        </section>

        <div class="card" style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
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
                            <?php if ($u['role'] === 'admin'): ?>
                                <span style="background: #eef2ff; color: #4f46e5; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">ADMIN</span>
                            <?php elseif ($u['is_verified']): ?>
                                <span style="background: #e6f4ea; color: #1e8e3e; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">VERIFIED</span>
                            <?php else: ?>
                                <span style="background: #fce8e6; color: #d93025; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.7rem; font-weight: 700;">PENDING</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 1rem; text-align: right;">
                            <?php if ($u['role'] === 'faculty' && !$u['is_verified']): ?>
                                <form action="../actions/verify_user.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                    <button type="submit" class="btn" style="background: var(--secondary-color); color: var(--primary-color); padding: 0.4rem 0.8rem; font-size: 0.8rem;">Approve</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

</body>
</html>
