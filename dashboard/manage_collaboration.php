<?php
require_once('../includes/auth_check.php');
require_once('../includes/layout.php');
require_once('../includes/csrf.php');

$post_id = (int)($_GET['id'] ?? 0);

if ($post_id <= 0) {
    header("Location: collaboration.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT cp.*, p.title AS linked_project_title 
    FROM collaboration_posts cp
    LEFT JOIN projects p ON cp.project_id = p.id
    WHERE cp.id = ? AND cp.user_id = ?
");
$stmt->bind_param("ii", $post_id, $user_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    header("Location: collaboration.php");
    exit();
}

$appStmt = $conn->prepare("
    SELECT ca.*, u.full_name, u.department, u.role, u.points
    FROM collaboration_applications ca
    JOIN users u ON ca.user_id = u.id
    WHERE ca.post_id = ?
    ORDER BY ca.status = 'pending' DESC, ca.created_at ASC
");
$appStmt->bind_param("i", $post_id);
$appStmt->execute();
$applications = $appStmt->get_result();

layout_header("Manage Applicants | UIU ScholarNet");
?>
    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <?php include('../includes/header.php'); ?>
        <?php include('../includes/alerts.php'); ?>

        <section class="greeting" style="margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <a href="collaboration.php" style="color: var(--text-muted); text-decoration: none;"><i class="fa-solid fa-arrow-left"></i> Back</a>
                <h1 style="font-size: 1.5rem;">Manage Applicants</h1>
            </div>
            <p style="margin-top: 0.5rem; opacity: 0.8;">Review and manage applications for: <strong><?php echo htmlspecialchars($post['title']); ?></strong></p>
        </section>

        <div class="card" style="background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid #eee; padding-bottom: 1rem;">
                <div>
                    <span style="background: var(--secondary-color); color: var(--primary-color); font-weight: 800; font-size: 0.75rem; padding: 0.2rem 0.6rem; border-radius: 4px;"><?php echo strtoupper(htmlspecialchars($post['opportunity_type'])); ?></span>
                    <h3 style="margin-top: 0.5rem; font-family: var(--font-heading);"><?php echo htmlspecialchars($post['title']); ?></h3>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.85rem; color: #666;">Total Slots</div>
                    <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary-color);"><?php echo (int)$post['slots_total']; ?></div>
                </div>
            </div>

            <?php if ($applications->num_rows > 0): ?>
                <table class="leaderboard-table" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #eee;">
                            <th style="padding: 1rem; text-align: left;">Applicant Info</th>
                            <th style="padding: 1rem; text-align: left;">Reputation</th>
                            <th style="padding: 1rem; text-align: left;">Message</th>
                            <th style="padding: 1rem; text-align: center;">Status</th>
                            <th style="padding: 1rem; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($app = $applications->fetch_assoc()): ?>
                        <tr style="border-bottom: 1px solid #f5f5f5;">
                            <td style="padding: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($app['full_name']); ?>&background=random&color=fff" alt="Avatar" style="width: 32px; height: 32px; border-radius: 50%;">
                                    <div>
                                        <div style="font-weight: 700; font-size: 0.95rem;"><?php echo htmlspecialchars($app['full_name']); ?></div>
                                        <div style="font-size: 0.75rem; color: #666;"><?php echo htmlspecialchars($app['department']); ?> • <?php echo ucfirst(htmlspecialchars($app['role'])); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 1rem;">
                                <span style="font-weight: 700; color: #e67e22;"><i class="fa-solid fa-star"></i> <?php echo (int)$app['points']; ?></span>
                            </td>
                            <td style="padding: 1rem; opacity: 0.8; font-size: 0.85rem; max-width: 250px;">
                                <?php echo htmlspecialchars($app['message'] ?: 'No message provided.'); ?>
                            </td>
                            <td style="padding: 1rem; text-align: center;">
                                <?php if ($app['status'] === 'accepted'): ?>
                                    <span style="background: #e6f4ea; color: #1e8e3e; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">Accepted</span>
                                <?php elseif ($app['status'] === 'declined'): ?>
                                    <span style="background: #fce8e6; color: #d93025; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">Declined</span>
                                <?php else: ?>
                                    <span style="background: #fef7e0; color: #f29900; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.75rem; font-weight: 700;">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; text-align: right;">
                                <?php if ($app['status'] === 'pending'): ?>
                                    <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                        <form action="../actions/manage_application.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <input type="hidden" name="action_type" value="accept">
                                            <button type="submit" class="btn" style="background: #e6f4ea; color: #1e8e3e; padding: 0.3rem 0.6rem; font-size: 0.75rem; border:1px solid #1e8e3e;">Accept</button>
                                        </form>
                                        <form action="../actions/manage_application.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <input type="hidden" name="action_type" value="decline">
                                            <button type="submit" class="btn" style="background: #fff; color: #d93025; padding: 0.3rem 0.6rem; font-size: 0.75rem; border:1px solid #d93025;">Decline</button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem 1rem; color: #666;">
                    <i class="fa-solid fa-users-slash" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>No one has applied to this collaboration request yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
