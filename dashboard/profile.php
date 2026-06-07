<?php
require_once('../includes/auth_check.php');
require_once('../includes/csrf.php');
$profileCssVer = @filemtime(__DIR__ . '/../assets/css/profile.css');

$conn->query(
    "CREATE TABLE IF NOT EXISTS user_profiles (
        user_id INT PRIMARY KEY,
        institution VARCHAR(150) DEFAULT NULL,
        biography TEXT DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
);

$profileData = db_query("SELECT institution, biography FROM user_profiles WHERE user_id = ? LIMIT 1", [$user_id], "i")->fetch_assoc();

$projectsTotal = (int)(db_query("SELECT COUNT(DISTINCT p.id) AS total FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? WHERE p.creator_id = ? OR pm.user_id = ?", [$user_id, $user_id, $user_id], "iii")->fetch_assoc()['total'] ?? 0);

$preprintsTotal = (int)(db_query("SELECT COUNT(*) AS total FROM preprints WHERE author_id = ?", [$user_id], "i")->fetch_assoc()['total'] ?? 0);

$collabTotal = (int)(db_query("SELECT COUNT(*) AS total FROM collaboration_posts WHERE user_id = ?", [$user_id], "i")->fetch_assoc()['total'] ?? 0);

$resourcesTotal = (int)(db_query("SELECT COUNT(*) AS total FROM resources WHERE user_id = ?", [$user_id], "i")->fetch_assoc()['total'] ?? 0);

$displayInstitution = trim((string)($profileData['institution'] ?? ''));
if ($displayInstitution === '') {
    $displayInstitution = 'United International University';
}

$displayBio = trim((string)($profileData['biography'] ?? ''));
if ($displayBio === '') {
    $displayBio = 'No biography added yet.';
}

$interestTags = array_values(
    array_filter(
        array_map('trim', explode(',', (string)($user_data['interests'] ?? ''))),
        static function ($value) { return $value !== ''; }
    )
);

$skillTags = array_values(
    array_filter(
        array_map('trim', explode(',', (string)($user_data['skills'] ?? ''))),
        static function ($value) { return $value !== ''; }
    )
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | UIU ScholarNet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/profile.css?v=<?php echo (int)$profileCssVer; ?>">
</head>
<body class="dashboard-page">
    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <?php include('../includes/header.php'); ?>

        <section class="profile-headline">
            <h1>The Scholar's Profile</h1>
            <p>Registry Archive</p>
        </section>

        <?php include('../includes/alerts.php'); ?>

        <div class="profile-grid">
            <aside class="profile-summary-card">
                <div class="profile-avatar-wrap">
                    <img
                        src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name']); ?>&size=180&background=ffffff&color=0a1128&bold=true"
                        alt="Profile Avatar"
                    >
                </div>
                <h2><?php echo htmlspecialchars($user_data['full_name']); ?></h2>
                <div class="profile-role flex-center-gap-5">
                    <?php if ($user_data['role'] === 'admin'): ?>
                        <span class="badge-role-admin">ADMINISTRATOR</span>
                    <?php elseif ($user_data['role'] === 'faculty'): ?>
                        <?php if (isset($user_data['is_verified']) && $user_data['is_verified']): ?>
                            <span class="badge-role-verified"><i class="fa-solid fa-circle-check"></i> VERIFIED FACULTY</span>
                        <?php else: ?>
                            <span class="badge-role-unverified">UNVERIFIED FACULTY</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge-role-student">STUDENT</span>
                    <?php endif; ?>
                </div>

                <div class="profile-reputation">
                    <span><i class="fa-solid fa-trophy"></i> Reputation Level</span>
                    <strong><?php echo number_format((int)($user_data['points'] ?? 0)); ?></strong>
                </div>
            </aside>

            <section class="profile-details-card">
                <h3><i class="fa-solid fa-building-columns"></i> Institutional Credentials</h3>
                <div class="details-grid">
                    <div class="detail-item">
                        <span>Full Name</span>
                        <p><?php echo htmlspecialchars($user_data['full_name']); ?></p>
                    </div>
                    <div class="detail-item">
                        <span>Academic Institution</span>
                        <p><?php echo htmlspecialchars($displayInstitution); ?></p>
                    </div>
                    <div class="detail-item">
                        <span>Department</span>
                        <p><?php echo htmlspecialchars((string)($user_data['department'] ?? 'Not set')); ?></p>
                    </div>
                    <div class="detail-item">
                        <span>Email Address</span>
                        <p><?php echo htmlspecialchars((string)($user_data['email'] ?? '')); ?></p>
                    </div>
                </div>
                <div class="bio-block">
                    <span>Biography</span>
                    <p><?php echo nl2br(htmlspecialchars($displayBio)); ?></p>
                </div>
            </section>

            <section class="profile-metrics">
                <div class="metric-box">
                    <span>Projects</span>
                    <strong><?php echo $projectsTotal; ?></strong>
                </div>
                <div class="metric-box">
                    <span>Preprints</span>
                    <strong><?php echo $preprintsTotal; ?></strong>
                </div>
                <div class="metric-box">
                    <span>Collabs</span>
                    <strong><?php echo $collabTotal; ?></strong>
                </div>
                <div class="metric-box">
                    <span>Resources</span>
                    <strong><?php echo $resourcesTotal; ?></strong>
                </div>
            </section>

            <section class="profile-skill-card">
                <h3><i class="fa-solid fa-wand-magic-sparkles"></i> Proficiencies & Research Interests</h3>
                <div class="tag-section">
                    <span>Core Skills</span>
                    <div class="tag-wrap">
                        <?php if (count($skillTags) > 0): ?>
                            <?php foreach ($skillTags as $skill): ?>
                                <div class="tag-chip skill-chip"><?php echo htmlspecialchars($skill); ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-tag-note">No skills added yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="tag-section">
                    <span>Interests</span>
                    <div class="tag-wrap">
                        <?php if (count($interestTags) > 0): ?>
                            <?php foreach ($interestTags as $interest): ?>
                                <div class="tag-chip interest-chip"><?php echo htmlspecialchars($interest); ?></div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-tag-note">No interests added yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <div class="modal-overlay modal-hidden" id="profileEditModal">
        <div class="modal-content profile-modal-content">
            <i class="fa-solid fa-xmark modal-close" id="closeProfileEdit"></i>
            <h2 class="modal-title">Edit My Profile</h2>

            <form action="../actions/auth_user/update_profile.php" method="POST" class="profile-edit-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars((string)($user_data['full_name'] ?? '')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Institutional Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars((string)($user_data['email'] ?? '')); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department" value="<?php echo htmlspecialchars((string)($user_data['department'] ?? '')); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Academic Institution</label>
                        <input type="text" name="institution" value="<?php echo htmlspecialchars($displayInstitution); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Biography</label>
                    <textarea name="biography" rows="4" placeholder="Write a short biography..."><?php echo htmlspecialchars($displayBio === 'No biography added yet.' ? '' : $displayBio); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Research Interests (comma separated)</label>
                    <textarea name="interests" rows="2" placeholder="Machine Learning, Data Mining"><?php echo htmlspecialchars((string)($user_data['interests'] ?? '')); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Specialized Skills (comma separated)</label>
                    <textarea name="skills" rows="2" placeholder="Python, Statistical Analysis"><?php echo htmlspecialchars((string)($user_data['skills'] ?? '')); ?></textarea>
                </div>

                <div class="modal-footer">
                    <a href="javascript:void(0)" id="cancelProfileEdit" class="cancel-link">CANCEL</a>
                    <button type="submit" class="btn btn-primary create-btn">SAVE PROFILE</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/profile.js"></script>
</body>
</html>
