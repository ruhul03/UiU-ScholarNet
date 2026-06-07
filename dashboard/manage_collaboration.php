<?php
require_once('../includes/auth_check.php');
require_once('../includes/layout.php');
require_once('../includes/csrf.php');

$post_id = (int)($_GET['id'] ?? 0);

if ($post_id <= 0) {
    header("Location: collaboration.php");
    exit();
}

$post = db_query("
    SELECT cp.*, p.title AS linked_project_title 
    FROM collaboration_posts cp
    LEFT JOIN projects p ON cp.project_id = p.id
    WHERE cp.id = ? AND cp.user_id = ?
", [$post_id, $user_id], "ii")->fetch_assoc();

if (!$post) {
    header("Location: collaboration.php");
    exit();
}

$applications = db_query("
    SELECT ca.*, u.full_name, u.department, u.role, u.points
    FROM collaboration_applications ca
    JOIN users u ON ca.user_id = u.id
    WHERE ca.post_id = ?
    ORDER BY ca.status = 'pending' DESC, ca.created_at ASC
", [$post_id], "i");

layout_header("Manage Applicants | UIU ScholarNet");
?>
    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <?php include('../includes/header.php'); ?>
        <?php include('../includes/alerts.php'); ?>

        <section class="greeting mb-2">
            <div class="flex-center-gap-1">
                <a href="collaboration.php" class="link-muted-no-under"><i class="fa-solid fa-arrow-left"></i> Back</a>
                <h1 class="text-1-5xl">Manage Applicants</h1>
            </div>
            <p class="mt-0-5-opacity-0-8">Review and manage applications for: <strong><?php echo htmlspecialchars($post['title']); ?></strong></p>
        </section>

        <div class="card card-manage-collab">
            <div class="manage-collab-header">
                <div>
                    <span class="badge-opp-type"><?php echo strtoupper(htmlspecialchars($post['opportunity_type'])); ?></span>
                    <h3 class="title-mt-0-5"><?php echo htmlspecialchars($post['title']); ?></h3>
                </div>
                <div class="text-right">
                    <div class="text-muted-sm">Total Slots</div>
                    <div class="slots-total-text"><?php echo (int)$post['slots_total']; ?></div>
                </div>
            </div>

            <?php if ($applications->num_rows > 0): ?>
                <table class="leaderboard-table table-w100">
                    <thead>
                        <tr class="border-b-2">
                            <th class="p-1-text-left">Applicant Info</th>
                            <th class="p-1-text-left">Reputation</th>
                            <th class="p-1-text-left">Message</th>
                            <th class="p-1-text-center">Status</th>
                            <th class="p-1-text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($app = $applications->fetch_assoc()): ?>
                        <tr class="border-b-1-light">
                            <td class="p-1">
                                <div class="flex-center-gap-0-75">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($app['full_name']); ?>&background=random&color=fff" alt="Avatar" class="avatar-32">
                                    <div>
                                        <div class="text-bold-0-95"><?php echo htmlspecialchars($app['full_name']); ?></div>
                                        <div class="text-muted-xs"><?php echo htmlspecialchars($app['department']); ?> • <?php echo ucfirst(htmlspecialchars($app['role'])); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-1">
                                <span class="rep-star-text"><i class="fa-solid fa-star"></i> <?php echo (int)$app['points']; ?></span>
                            </td>
                            <td class="p-1-message-col">
                                <?php echo htmlspecialchars($app['message'] ?: 'No message provided.'); ?>
                            </td>
                            <td class="p-1-text-center">
                                <?php if ($app['status'] === 'accepted'): ?>
                                    <span class="badge-status-accepted">Accepted</span>
                                <?php elseif ($app['status'] === 'declined'): ?>
                                    <span class="badge-status-declined">Declined</span>
                                <?php else: ?>
                                    <span class="badge-status-pending">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-1-text-right">
                                <?php if ($app['status'] === 'pending'): ?>
                                    <div class="flex-end-gap-0-5">
                                        <form action="../actions/collaboration_messaging/manage_application.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <input type="hidden" name="action_type" value="accept">
                                            <button type="submit" class="btn btn-action-accept">Accept</button>
                                        </form>
                                        <form action="../actions/collaboration_messaging/manage_application.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                            <input type="hidden" name="action_type" value="decline">
                                            <button type="submit" class="btn btn-action-decline">Decline</button>
                                        </form>
                                        <a href="messages.php?user_id=<?php echo $app['user_id']; ?>" class="btn btn-action-message"><i class="fa-regular fa-comment"></i></a>
                                    </div>
                                <?php else: ?>
                                    <div class="flex-end-gap-0-5">
                                        <a href="messages.php?user_id=<?php echo $app['user_id']; ?>" class="btn btn-action-message"><i class="fa-regular fa-comment"></i> Message</a>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state-manage">
                    <i class="fa-solid fa-users-slash empty-state-manage-icon"></i>
                    <p>No one has applied to this collaboration request yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
