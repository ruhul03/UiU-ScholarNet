<?php
// Include authentication check to ensure only signed-in users see the leaderboard
require_once('../includes/auth_check.php');
require_once('../includes/csrf.php');

// QUERY: Fetch all researchers (users) from the database sorted by reputation points descending
$leadStmt = $conn->prepare("SELECT full_name, role, department, points FROM users ORDER BY points DESC LIMIT 100");
$leadStmt->execute();
$leadRes = $leadStmt->get_result();

// Store users into a list for easy ranking splits (podium vs list table)
$ranked_users = [];
while ($row = $leadRes->fetch_assoc()) {
    $ranked_users[] = $row;
}

// Get the top 3 for the podium blocks
$top_1 = isset($ranked_users[0]) ? $ranked_users[0] : null;
$top_2 = isset($ranked_users[1]) ? $ranked_users[1] : null;
$top_3 = isset($ranked_users[2]) ? $ranked_users[2] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reputation Leaderboard | UIU ScholarNet</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/reputation.css">
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Header -->
        <header class="dash-header">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" placeholder="Search leaderboard...">
            </div>
            <div class="header-actions">
                <a href="profile.php" style="color: inherit; text-decoration: none;">
                    <i class="fa-regular fa-user header-icon"></i>
                </a>
            </div>
        </header>

        <div class="reputation-container">
            <section class="reputation-headline">
                <p>Registry Leaderboard</p>
                <h1>Academic Reputation</h1>
            </section>

            <!-- Dynamic Podium for Top 3 Users -->
            <div class="podium-grid">
                <!-- 2nd Place -->
                <?php if ($top_2): ?>
                <div class="podium-card">
                    <div class="medal-badge">🥈</div>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($top_2['full_name']); ?>&background=eee&color=0a1128&bold=true" class="podium-avatar" alt="Avatar">
                    <div class="podium-name"><?php echo htmlspecialchars($top_2['full_name']); ?></div>
                    <div class="podium-role"><?php echo htmlspecialchars($top_2['role']); ?> • <?php echo htmlspecialchars($top_2['department']); ?></div>
                    <div class="podium-points"><?php echo number_format($top_2['points']); ?></div>
                    <div class="podium-points-label">REP POINTS</div>
                </div>
                <?php endif; ?>

                <!-- 1st Place -->
                <?php if ($top_1): ?>
                <div class="podium-card podium-1st">
                    <div class="medal-badge">🥇</div>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($top_1['full_name']); ?>&background=0a1128&color=fff&bold=true" class="podium-avatar" alt="Avatar">
                    <div class="podium-name"><?php echo htmlspecialchars($top_1['full_name']); ?></div>
                    <div class="podium-role"><?php echo htmlspecialchars($top_1['role']); ?> • <?php echo htmlspecialchars($top_1['department']); ?></div>
                    <div class="podium-points"><?php echo number_format($top_1['points']); ?></div>
                    <div class="podium-points-label">REP POINTS</div>
                </div>
                <?php endif; ?>

                <!-- 3rd Place -->
                <?php if ($top_3): ?>
                <div class="podium-card">
                    <div class="medal-badge">🥉</div>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($top_3['full_name']); ?>&background=eee&color=0a1128&bold=true" class="podium-avatar" alt="Avatar">
                    <div class="podium-name"><?php echo htmlspecialchars($top_3['full_name']); ?></div>
                    <div class="podium-role"><?php echo htmlspecialchars($top_3['role']); ?> • <?php echo htmlspecialchars($top_3['department']); ?></div>
                    <div class="podium-points"><?php echo number_format($top_3['points']); ?></div>
                    <div class="podium-points-label">REP POINTS</div>
                </div>
                <?php endif; ?>
            </div>

            <!-- List Table for Rank 4+ -->
            <div class="leaderboard-section">
                <h3>Scholar Ranking</h3>
                <table class="leaderboard-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">Rank</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th style="text-align: right; width: 150px;">Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Loop through all ranked users starting from rank index 3 (4th place)
                        $count = count($ranked_users);
                        if ($count > 3):
                            for ($i = 3; $i < $count; $i++):
                                $user = $ranked_users[$i];
                        ?>
                            <tr>
                                <td class="rank-number">#<?php echo ($i + 1); ?></td>
                                <td class="user-name-col"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td class="table-role-badge"><?php echo htmlspecialchars($user['role']); ?></td>
                                <td style="opacity: 0.7;"><?php echo htmlspecialchars($user['department']); ?></td>
                                <td class="table-points-col"><?php echo number_format($user['points']); ?></td>
                            </tr>
                        <?php 
                            endfor;
                        else:
                            if ($count <= 3):
                        ?>
                            <tr>
                                <td colspan="5" style="text-align: center; opacity: 0.5; padding: 2rem;">No other ranked researchers yet. Keep building!</td>
                            </tr>
                        <?php 
                            endif;
                        endif; 
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Guide on how to earn reputation -->
            <h3 style="margin-bottom: 2rem; color: var(--primary-color);">How to Earn Reputation</h3>
            <div class="earning-guide">
                <div class="guide-card">
                    <i class="fa-solid fa-list-check"></i>
                    <h4>Complete Tasks</h4>
                    <p>Finish tasks assigned to you on project Kanban logistics boards.</p>
                    <span class="guide-points">+50 Points</span>
                </div>
                <div class="guide-card">
                    <i class="fa-solid fa-file-arrow-up"></i>
                    <h4>Publish Preprints</h4>
                    <p>Upload early-stage academic manuscripts to share with the community.</p>
                    <span class="guide-points">+100 Points</span>
                </div>
                <div class="guide-card">
                    <i class="fa-solid fa-user-plus"></i>
                    <h4>Invite Collaborators</h4>
                    <p>Post interdisciplinary research collaboration listings on the board.</p>
                    <span class="guide-points">+20 Points</span>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
