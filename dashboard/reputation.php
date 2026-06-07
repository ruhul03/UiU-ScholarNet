<?php
require_once('../includes/auth_check.php');
require_once('../includes/layout.php');
require_once('../includes/csrf.php');

$leadRes = db_query("SELECT id, full_name, role, department, points FROM users ORDER BY points DESC LIMIT 100", [], "");

$ranked_users = [];
while ($row = $leadRes->fetch_assoc()) {
    $ranked_users[] = $row;
}

$reputation_rules = db_query("SELECT title, description, points, icon FROM reputation_rules ORDER BY points DESC", [], "");

$top_1 = isset($ranked_users[0]) ? $ranked_users[0] : null;
$top_2 = isset($ranked_users[1]) ? $ranked_users[1] : null;
$top_3 = isset($ranked_users[2]) ? $ranked_users[2] : null;

layout_header("Reputation Leaderboard | UIU ScholarNet", ["../assets/css/reputation.css"]);
?>

    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <?php include('../includes/header.php'); ?>

        <div class="reputation-container">
            <section class="reputation-headline">
                <p>Registry Leaderboard</p>
                <h1>Academic Reputation</h1>
            </section>

            <div class="podium-grid">
                <?php if ($top_2): ?>
                <div class="podium-card">
                    <div class="medal-badge">🥈</div>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($top_2['full_name']); ?>&background=eee&color=0a1128&bold=true" class="podium-avatar" alt="Avatar">
                    <div class="podium-name"><a href="#" class="user-profile-trigger" data-user-id="<?php echo $top_2['id']; ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($top_2['full_name']); ?></a></div>
                    <div class="podium-role"><?php echo htmlspecialchars($top_2['role']); ?> • <?php echo htmlspecialchars($top_2['department']); ?></div>
                    <div class="podium-points"><?php echo number_format($top_2['points']); ?></div>
                    <div class="podium-points-label">REP POINTS</div>
                </div>
                <?php endif; ?>

                <?php if ($top_1): ?>
                <div class="podium-card podium-1st">
                    <div class="medal-badge">🥇</div>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($top_1['full_name']); ?>&background=0a1128&color=fff&bold=true" class="podium-avatar" alt="Avatar">
                    <div class="podium-name"><a href="#" class="user-profile-trigger" data-user-id="<?php echo $top_1['id']; ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($top_1['full_name']); ?></a></div>
                    <div class="podium-role"><?php echo htmlspecialchars($top_1['role']); ?> • <?php echo htmlspecialchars($top_1['department']); ?></div>
                    <div class="podium-points"><?php echo number_format($top_1['points']); ?></div>
                    <div class="podium-points-label">REP POINTS</div>
                </div>
                <?php endif; ?>

                <?php if ($top_3): ?>
                <div class="podium-card">
                    <div class="medal-badge">🥉</div>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($top_3['full_name']); ?>&background=eee&color=0a1128&bold=true" class="podium-avatar" alt="Avatar">
                    <div class="podium-name"><a href="#" class="user-profile-trigger" data-user-id="<?php echo $top_3['id']; ?>" style="color: inherit; text-decoration: none;"><?php echo htmlspecialchars($top_3['full_name']); ?></a></div>
                    <div class="podium-role"><?php echo htmlspecialchars($top_3['role']); ?> • <?php echo htmlspecialchars($top_3['department']); ?></div>
                    <div class="podium-points"><?php echo number_format($top_3['points']); ?></div>
                    <div class="podium-points-label">REP POINTS</div>
                </div>
                <?php endif; ?>
            </div>

            <div class="leaderboard-section">
                <h3>Scholar Ranking</h3>
                <table class="leaderboard-table">
                    <thead>
                        <tr>
                            <th class="th-rank-w">Rank</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th class="th-points-w">Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = count($ranked_users);
                        if ($count > 3):
                            for ($i = 3; $i < $count; $i++):
                                $user = $ranked_users[$i];
                        ?>
                            <tr>
                                <td class="rank-number">#<?php echo ($i + 1); ?></td>
                                <td class="user-name-col"><a href="#" class="user-profile-trigger" data-user-id="<?php echo $user['id']; ?>" style="color: inherit; text-decoration: none; font-weight: 500; border-bottom: 1px dashed #ccc; padding-bottom: 2px; transition: opacity 0.2s;" onmouseover="this.style.opacity=0.7" onmouseout="this.style.opacity=1"><?php echo htmlspecialchars($user['full_name']); ?></a></td>
                                <td class="table-role-badge"><?php echo htmlspecialchars($user['role']); ?></td>
                                <td class="opacity-70"><?php echo htmlspecialchars($user['department']); ?></td>
                                <td class="table-points-col"><?php echo number_format($user['points']); ?></td>
                            </tr>
                        <?php 
                            endfor;
                        else:
                            if ($count <= 3):
                        ?>
                            <tr>
                                <td colspan="5" class="td-empty-ranked">No other ranked researchers yet. Keep building!</td>
                            </tr>
                        <?php 
                            endif;
                        endif; 
                        ?>
                    </tbody>
                </table>
            </div>

            <h3 class="rep-guide-title">How to Earn Reputation</h3>
            <div class="earning-guide">
                <?php while ($rule = $reputation_rules->fetch_assoc()): ?>
                <div class="guide-card">
                    <i class="fa-solid <?php echo htmlspecialchars($rule['icon']); ?>"></i>
                    <h4><?php echo htmlspecialchars($rule['title']); ?></h4>
                    <p><?php echo htmlspecialchars($rule['description']); ?></p>
                    <span class="guide-points">+<?php echo number_format($rule['points']); ?> Points</span>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>

<?php layout_footer(); ?>
