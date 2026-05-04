<?php
require_once('../includes/auth_check.php');
require_once('../includes/csrf.php');

// Fetch Collaboration Posts
$stmt = $conn->query(
    "CREATE TABLE IF NOT EXISTS collaboration_applications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        user_id INT NOT NULL,
        message TEXT NULL,
        status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_post_user (post_id, user_id),
        FOREIGN KEY (post_id) REFERENCES collaboration_posts(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
);

$columnsToEnsure = [
    'opportunity_type' => "ALTER TABLE collaboration_posts ADD COLUMN opportunity_type VARCHAR(50) NOT NULL DEFAULT 'Research'",
    'status' => "ALTER TABLE collaboration_posts ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'open'",
    'slots_total' => "ALTER TABLE collaboration_posts ADD COLUMN slots_total INT NOT NULL DEFAULT 10",
];

foreach ($columnsToEnsure as $columnName => $alterSql) {
    $check = $conn->query("SHOW COLUMNS FROM collaboration_posts LIKE '" . $conn->real_escape_string($columnName) . "'");
    if ($check && $check->num_rows === 0) {
        $conn->query($alterSql);
    }
}

$search = trim((string)($_GET['q'] ?? ''));
$department = trim((string)($_GET['department'] ?? ''));
$skill = trim((string)($_GET['skill'] ?? ''));
$type = trim((string)($_GET['type'] ?? ''));
$view = trim((string)($_GET['view'] ?? 'grid'));
$view = ($view === 'list') ? 'list' : 'grid';
$tab = trim((string)($_GET['tab'] ?? 'discovery'));
$page = (int)($_GET['page'] ?? 1);
$perPage = 9;
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $perPage;

// Fetch notification counts
$ptStmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status != 'done'");
$ptStmt->bind_param("i", $user_id);
$ptStmt->execute();
$pending_tasks = (int)($ptStmt->get_result()->fetch_assoc()['total'] ?? 0);

$crStmt = $conn->prepare("SELECT COUNT(*) as total FROM collaboration_applications ca JOIN collaboration_posts cp ON ca.post_id = cp.id WHERE cp.user_id = ? AND ca.status = 'pending'");
$crStmt->bind_param("i", $user_id);
$crStmt->execute();
$collab_requests = (int)($crStmt->get_result()->fetch_assoc()['total'] ?? 0);

$bindStmt = static function (mysqli_stmt $stmt, string $types, array $params): void {
    if ($types === '' || count($params) === 0) {
        return;
    }
    $refs = [];
    foreach ($params as $i => $value) {
        $refs[$i] = &$params[$i];
    }
    array_unshift($refs, $types);
    call_user_func_array([$stmt, 'bind_param'], $refs);
};

$where = [];
$whereTypes = '';
$whereValues = [];

if ($search !== '') {
    $where[] = "(cp.title LIKE ? OR cp.description LIKE ? OR cp.skills_required LIKE ?)";
    $term = '%' . $search . '%';
    $whereTypes .= 'sss';
    $whereValues[] = $term;
    $whereValues[] = $term;
    $whereValues[] = $term;
}

if ($department !== '' && $department !== 'all') {
    $where[] = "cp.department = ?";
    $whereTypes .= 's';
    $whereValues[] = $department;
}

if ($skill !== '' && $skill !== 'all') {
    $where[] = "cp.skills_required LIKE ?";
    $whereTypes .= 's';
    $whereValues[] = '%' . $skill . '%';
}

if ($type !== '' && $type !== 'all') {
    $where[] = "cp.opportunity_type = ?";
    $whereTypes .= 's';
    $whereValues[] = $type;
}

if ($tab === 'network') {
    $where[] = "(cp.user_id = ? OR cp.id IN (SELECT post_id FROM collaboration_applications WHERE user_id = ?))";
    $whereTypes .= 'ii';
    $whereValues[] = $user_id;
    $whereValues[] = $user_id;
}

$whereSql = '';
if (count($where) > 0) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

$countSql = "SELECT COUNT(*) AS total
             FROM collaboration_posts cp" . $whereSql;
$countStmt = $conn->prepare($countSql);
$bindStmt($countStmt, $whereTypes, $whereValues);
$countStmt->execute();
$totalRows = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$totalPages = ($totalRows > 0) ? (int)ceil($totalRows / $perPage) : 1;
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$postsSql = "SELECT cp.*, u.full_name,
                    COALESCE(a.total_applicants, 0) AS total_applicants,
                    ua.id AS user_applied
             FROM collaboration_posts cp
             JOIN users u ON cp.user_id = u.id
             LEFT JOIN (
                SELECT post_id, COUNT(*) AS total_applicants
                FROM collaboration_applications
                GROUP BY post_id
             ) a ON a.post_id = cp.id
             LEFT JOIN collaboration_applications ua
                ON ua.post_id = cp.id AND ua.user_id = ?
             " . $whereSql . "
             ORDER BY cp.created_at DESC
             LIMIT ? OFFSET ?";

$postsStmt = $conn->prepare($postsSql);
$postTypes = 'i' . $whereTypes . 'ii';
$postValues = array_merge([$user_id], $whereValues, [$perPage, $offset]);
$bindStmt($postsStmt, $postTypes, $postValues);
$postsStmt->execute();
$postsResult = $postsStmt->get_result();

$spotlightStmt = $conn->prepare(
    "SELECT cp.*, u.full_name, COALESCE(a.total_applicants, 0) AS total_applicants
     FROM collaboration_posts cp
     JOIN users u ON cp.user_id = u.id
     LEFT JOIN (
        SELECT post_id, COUNT(*) AS total_applicants
        FROM collaboration_applications
        GROUP BY post_id
     ) a ON a.post_id = cp.id
     WHERE cp.status = 'open'
     ORDER BY total_applicants DESC, cp.created_at DESC
     LIMIT 1"
);
$spotlightStmt->execute();
$spotlight = $spotlightStmt->get_result()->fetch_assoc();

$deptResult = $conn->query("SELECT DISTINCT department FROM collaboration_posts WHERE department IS NOT NULL AND department <> '' ORDER BY department ASC");
$departments = [];
if ($deptResult) {
    while ($d = $deptResult->fetch_assoc()) {
        $departments[] = (string)$d['department'];
    }
}

$typeResult = $conn->query("SELECT DISTINCT opportunity_type FROM collaboration_posts WHERE opportunity_type IS NOT NULL AND opportunity_type <> '' ORDER BY opportunity_type ASC");
$types = [];
if ($typeResult) {
    while ($t = $typeResult->fetch_assoc()) {
        $types[] = (string)$t['opportunity_type'];
    }
}

$skillPool = [];
$skillsResult = $conn->query("SELECT skills_required FROM collaboration_posts WHERE skills_required IS NOT NULL AND skills_required <> '' ORDER BY created_at DESC LIMIT 200");
if ($skillsResult) {
    while ($s = $skillsResult->fetch_assoc()) {
        $parts = explode(',', (string)$s['skills_required']);
        foreach ($parts as $part) {
            $clean = trim($part);
            if ($clean !== '') {
                $key = strtolower($clean);
                $skillPool[$key] = $clean;
            }
        }
    }
}
$skills = array_values($skillPool);
sort($skills, SORT_NATURAL | SORT_FLAG_CASE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collaboration Finder | UIU ScholarNet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">
    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content">
        <header class="dash-header dash-header-collab">
            <form method="GET" class="search-container collab-search-form">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search opportunities...">
                <input type="hidden" name="department" value="<?php echo htmlspecialchars($department); ?>">
                <input type="hidden" name="skill" value="<?php echo htmlspecialchars($skill); ?>">
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
            </form>
            <div class="header-actions">
                <div class="nav-links-row">
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['tab' => 'discovery', 'page' => 1])); ?>" class="<?php echo ($tab === 'discovery') ? 'nav-link-active' : 'nav-link-inactive'; ?>">Discovery</a>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['tab' => 'network', 'page' => 1])); ?>" class="<?php echo ($tab === 'network') ? 'nav-link-active' : 'nav-link-inactive'; ?>">My Network</a>
                </div>
                <div class="header-icons">
                    <a href="#" class="notification-icon" style="color: inherit; text-decoration: none; position: relative;">
                        <i class="fa-regular fa-bell header-icon"></i>
                        <?php if ($collab_requests > 0 || $pending_tasks > 0): ?>
                            <span class="notification-dot" style="top: 0px; right: 2px;"></span>
                        <?php endif; ?>
                    </a>
                    <a href="profile.php" style="color: inherit; text-decoration: none;">
                        <i class="fa-regular fa-user header-icon"></i>
                    </a>
                </div>
            </div>
        </header>

        <section class="discovery-header">
            <div class="discovery-main">
                <div>
                    <h1 class="discovery-title">Collaboration Finder</h1>
                    <p class="discovery-desc">Discover research partners, project collaborators, and interdisciplinary opportunities across the university network.</p>
                </div>
                <button class="btn btn-primary btn-post-request" onclick="openModal()">
                    <i class="fa-solid fa-plus"></i> POST REQUEST
                </button>
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] === '1'): ?>
                <div class="alert-success collab-alert">
                    <i class="fa-solid fa-circle-check"></i> Collaboration request posted successfully.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['applied']) && $_GET['applied'] === '1'): ?>
                <div class="alert-success collab-alert">
                    <i class="fa-solid fa-circle-check"></i> Application submitted successfully.
                </div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] === '1'): ?>
                <div class="alert-error collab-alert">
                    <i class="fa-solid fa-circle-exclamation"></i> Request failed. Please check your input and try again.
                </div>
            <?php endif; ?>

            <form method="GET" class="filter-bar" id="collabFilterForm">
                <div class="filter-group">
                    <input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>">
                    <input type="hidden" name="view" id="viewInput" value="<?php echo htmlspecialchars($view); ?>">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
                    <select class="filter-select" name="department">
                        <option value="all">All Departments</option>
                        <?php foreach ($departments as $dep): ?>
                            <option value="<?php echo htmlspecialchars($dep); ?>" <?php echo ($department === $dep) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dep); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" name="skill">
                        <option value="all">All Skills</option>
                        <?php foreach ($skills as $skillOption): ?>
                            <option value="<?php echo htmlspecialchars($skillOption); ?>" <?php echo ($skill === $skillOption) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($skillOption); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select class="filter-select" name="type">
                        <option value="all">All Types</option>
                        <?php foreach ($types as $typeOption): ?>
                            <option value="<?php echo htmlspecialchars($typeOption); ?>" <?php echo ($type === $typeOption) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($typeOption); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="view-toggles">
                    <button type="button" class="view-btn <?php echo ($view === 'grid') ? 'active' : ''; ?>" data-view="grid" aria-label="Grid view">
                        <i class="fa-solid fa-table-cells-large"></i>
                    </button>
                    <button type="button" class="view-btn <?php echo ($view === 'list') ? 'active' : ''; ?>" data-view="list" aria-label="List view">
                        <i class="fa-solid fa-list"></i>
                    </button>
                </div>
            </form>
        </section>
        <div class="collaboration-grid collab-grid-3 <?php echo ($view === 'list') ? 'collab-list-view' : ''; ?>">
            <?php while ($row = $postsResult->fetch_assoc()): ?>
                <div class="collab-card">
                    <div class="card-tag"><?php echo strtoupper(htmlspecialchars((string)($row['opportunity_type'] ?? 'Research'))); ?></div>

                    <div class="card-author-info">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['full_name']); ?>&background=f5f5f5&color=0a1128" alt="Author">
                    </div>

                    <h3 class="card-title"><?php echo htmlspecialchars((string)$row['title']); ?></h3>
                    <p class="card-desc">
                        <?php                        $desc = (string)($row['description'] ?? '');
                        echo htmlspecialchars((strlen($desc) > 150) ? substr($desc, 0, 150) . '...' : $desc);
                        ?>
                    </p>

                    <div class="meta-grid">
                        <div class="meta-block">
                            <span class="meta-label">Posted By</span>
                            <span class="meta-value"><?php echo htmlspecialchars((string)$row['full_name']); ?></span>
                        </div>
                        <div class="meta-block">
                            <span class="meta-label">Department</span>
                            <span class="meta-value"><?php echo htmlspecialchars((string)$row['department']); ?></span>
                        </div>
                        <div class="meta-block">
                            <span class="meta-label">Skills Required</span>
                            <span class="meta-value"><?php echo htmlspecialchars((string)($row['skills_required'] ?: 'Not specified')); ?></span>
                        </div>
                        <div class="meta-block">
                            <span class="meta-label">Applicants</span>
                            <span class="meta-value"><?php echo (int)$row['total_applicants']; ?> people</span>
                        </div>
                    </div>

                    <?php if ((int)$row['user_id'] === (int)$user_id): ?>
                        <button class="btn btn-apply" type="button" disabled>Your Post</button>
                    <?php elseif (!empty($row['user_applied'])): ?>
                        <button class="btn btn-apply btn-applied" type="button" disabled>Applied</button>
                    <?php else: ?>
                        <form action="../actions/apply_collaboration.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="post_id" value="<?php echo (int)$row['id']; ?>">
                            <button class="btn btn-apply" type="submit">Apply to Collaborate</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>

            <?php if ($spotlight): ?>
                <div class="collab-card collab-spotlight">
                    <div class="spotlight-header">
                        <span class="spotlight-badge">ACTIVE REQUEST</span>
                    </div>
                    <h3 class="spotlight-title"><?php echo htmlspecialchars((string)$spotlight['title']); ?></h3>
                    <p class="spotlight-desc">
                        <?php
                        $spotDesc = (string)($spotlight['description'] ?? '');
                        echo htmlspecialchars((strlen($spotDesc) > 140) ? substr($spotDesc, 0, 140) . '...' : $spotDesc);
                        ?>
                    </p>

                    <div class="spotlight-mb">
                        <div class="applicants-row">
                            <span class="applicants-label">Total Applicants</span>
                            <span class="applicants-count"><?php echo (int)$spotlight['total_applicants']; ?> People</span>
                        </div>
                        <div class="progress-bar spotlight-progress">
                            <?php
                            $slots = max(1, (int)($spotlight['slots_total'] ?? 10));
                            $ratio = min(100, (int)round(((int)$spotlight['total_applicants'] / $slots) * 100));
                            ?>
                            <div class="progress-fill" style="width: <?php echo $ratio; ?>%;"></div>
                        </div>
                    </div>

                    <div class="spotlight-actions">
                        <a href="?q=<?php echo urlencode((string)$spotlight['title']); ?>" class="btn btn-primary btn-view-details">VIEW DETAILS</a>
                        <button class="btn btn-outline btn-edit-white" type="button" disabled><i class="fa-solid fa-pen-nib"></i></button>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($totalRows === 0): ?>
            <div class="collab-empty-state">
                <i class="fa-regular fa-folder-open"></i>
                <h3>No collaboration opportunities found</h3>
                <p>Try changing your search keywords or filters.</p>
            </div>
        <?php endif; ?>

        <div class="pagination">
            <p class="pagination-info">Showing <?php echo min($perPage, max(0, $totalRows - $offset)); ?> of <?php echo $totalRows; ?> opportunities.</p>
            <div class="pagination-links">
                <?php if ($page > 1): ?>
                    <a class="load-more-btn" href="?<?php echo http_build_query([
                        'q' => $search,
                        'department' => $department,
                        'skill' => $skill,
                        'type' => $type,
                        'view' => $view,
                        'tab' => $tab,
                        'page' => $page - 1,
                    ]); ?>">Previous</a>
                <?php endif; ?>
                <?php if ($page < $totalPages): ?>
                    <a class="load-more-btn" href="?<?php echo http_build_query([
                        'q' => $search,
                        'department' => $department,
                        'skill' => $skill,
                        'type' => $type,
                        'view' => $view,
                        'tab' => $tab,
                        'page' => $page + 1,
                    ]); ?>">Next</a>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <div class="modal-overlay modal-hidden" id="collabModal">
        <div class="modal-content modal-collab">
            <i class="fa-solid fa-xmark modal-close" onclick="closeModal()"></i>
            <h2 class="modal-collab-title">Post New Collaboration</h2>

            <form action="../actions/post_collaboration.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label>Project Title</label>
                    <input type="text" name="title" placeholder="e.g. AI in Sustainable Architecture" class="form-input-bordered" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department" class="form-input-bordered" required>
                            <option value="">Select Department</option>
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
                    <div class="form-group">
                        <label>Opportunity Type</label>
                        <select name="opportunity_type" class="form-input-bordered" required>
                            <option value="Research">Research</option>
                            <option value="Software">Software</option>
                            <option value="Dataset">Dataset</option>
                            <option value="Paper">Paper</option>
                            <option value="Thesis">Thesis</option>
                            <option value="Case Study">Case Study</option>
                            <option value="Survey">Survey</option>
                            <option value="Experiment">Experiment</option>
                            <option value="Analysis">Analysis</option>
                            <option value="Prototyping">Prototyping</option>
                            <option value="Field Work">Field Work</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Required Skills</label>
                        <input type="text" name="skills" placeholder="e.g. Python, LaTeX, SPSS, Qualitative Analysis, Prototyping" class="form-input-bordered">
                    </div>
                    <div class="form-group">
                        <label>Total Slots</label>
                        <input type="number" name="slots_total" min="1" max="100" value="10" class="form-input-bordered" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Collaboration Description</label>
                    <textarea name="description" rows="5" class="textarea-bordered" placeholder="Describe your project and what you're looking for..."></textarea>
                </div>

                <div class="modal-footer-between">
                    <a href="javascript:void(0)" class="save-draft" onclick="closeModal()">Cancel</a>
                    <button type="submit" class="btn btn-primary post-btn">POST COLLABORATION <i class="fa-solid fa-play play-icon"></i></button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/collaboration.js"></script>
</body>
</html>
