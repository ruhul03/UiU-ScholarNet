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
    'project_id' => "ALTER TABLE collaboration_posts ADD COLUMN project_id INT NULL"
];

foreach ($columnsToEnsure as $columnName => $alterSql) {
    $check = $conn->query("SHOW COLUMNS FROM collaboration_posts LIKE '" . $conn->real_escape_string($columnName) . "'");
    if ($check && $check->num_rows === 0) {
        $conn->query($alterSql);
        if ($columnName === 'project_id') {
            $conn->query("ALTER TABLE collaboration_posts ADD FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL");
        }
    }
}

// Fetch User's Projects for creating post dropdown
$userProjects = [];
if (isset($_SESSION['user_id'])) {
    $upRes = db_query("
        SELECT p.id, p.title 
        FROM projects p 
        LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = ? 
        WHERE p.creator_id = ? OR pm.role IN ('owner', 'editor')
        ORDER BY p.title ASC
    ", [$user_id, $user_id], "ii");
    while ($p = $upRes->fetch_assoc()) {
        $userProjects[] = $p;
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

// Fetch notification counts for header
$pending_tasks = (int)(db_query("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status != 'done'", [$user_id], "i")->fetch_assoc()['total'] ?? 0);
$collab_requests = (int)(db_query("SELECT COUNT(*) as total FROM collaboration_applications ca JOIN collaboration_posts cp ON ca.post_id = cp.id WHERE cp.user_id = ? AND ca.status = 'pending'", [$user_id], "i")->fetch_assoc()['total'] ?? 0);

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
$totalRowsResult = db_query($countSql, $whereValues, $whereTypes);
$totalRows = (int)($totalRowsResult->fetch_assoc()['total'] ?? 0);
$totalPages = ($totalRows > 0) ? (int)ceil($totalRows / $perPage) : 1;
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$postsSql = "SELECT cp.*, u.full_name, p.title AS linked_project_title,
                    COALESCE(a.total_applicants, 0) AS total_applicants,
                    COALESCE(a.pending_applicants, 0) AS pending_applicants,
                    COALESCE(a.accepted_applicants, 0) AS accepted_applicants,
                    ua.id AS user_applied, ua.status AS apply_status
             FROM collaboration_posts cp
             JOIN users u ON cp.user_id = u.id
             LEFT JOIN projects p ON cp.project_id = p.id
             LEFT JOIN (
                SELECT post_id, 
                       COUNT(*) AS total_applicants,
                       SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_applicants,
                       SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) AS accepted_applicants
                FROM collaboration_applications
                GROUP BY post_id
             ) a ON a.post_id = cp.id
             LEFT JOIN collaboration_applications ua
                ON ua.post_id = cp.id AND ua.user_id = ?
             " . $whereSql . "
             ORDER BY cp.created_at DESC
             LIMIT ? OFFSET ?";

$postTypes = 'i' . $whereTypes . 'ii';
$postValues = array_merge([$user_id], $whereValues, [$perPage, $offset]);
$postsResult = db_query($postsSql, $postValues, $postTypes);



$departments = [];
$depRes = db_query("SELECT name FROM departments ORDER BY name ASC");
if ($depRes) {
    while ($row = $depRes->fetch_assoc()) {
        $departments[] = $row['name'];
    }
}

$types = [];
$typeRes = db_query("SELECT name FROM opportunity_types ORDER BY name ASC");
if ($typeRes) {
    while ($row = $typeRes->fetch_assoc()) {
        $types[] = $row['name'];
    }
}

$commonSkills = [];
$skillRes = db_query("SELECT name FROM skills ORDER BY name ASC");
if ($skillRes) {
    while ($row = $skillRes->fetch_assoc()) {
        $commonSkills[] = $row['name'];
    }
}

$skillPool = [];
$skillsResult = db_query("SELECT skills_required FROM collaboration_posts WHERE skills_required IS NOT NULL AND skills_required <> '' ORDER BY created_at DESC LIMIT 100");
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
foreach ($commonSkills as $cs) {
    $skillPool[strtolower($cs)] = $cs;
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
        <?php include('../includes/header.php'); ?>

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

            <?php include('../includes/alerts.php'); ?>
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
                            <span class="meta-label">Slots</span>
                            <span class="meta-value"><?php echo (int)$row['accepted_applicants']; ?> / <?php echo (int)$row['slots_total']; ?> filled</span>
                        </div>
                        <?php if ($row['linked_project_title']): ?>
                        <div class="meta-block col-span-2">
                            <span class="meta-label">Linked Project</span>
                            <span class="meta-value text-secondary-bold">
                                <i class="fa-solid fa-link font-xs"></i> <?php echo htmlspecialchars((string)$row['linked_project_title']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ((int)$row['user_id'] === (int)$user_id): ?>
                        <div class="owner-actions flex-gap-sm-full">
                            <a href="manage_collaboration.php?id=<?php echo $row['id']; ?>" class="btn btn-apply btn-centered" style="position:relative;">
                                Manage Applicants
                                <?php if ($row['pending_applicants'] > 0): ?>
                                    <span class="badge" style="background:var(--danger-color);color:white;padding:2px 6px;border-radius:10px;font-size:0.75rem;margin-left:0.25rem;"><?php echo $row['pending_applicants']; ?></span>
                                <?php endif; ?>
                            </a>
                            <form action="../actions/delete_collaboration_post.php" method="POST" class="flex-none" onsubmit="return confirm('Permanently delete this collaboration request?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="post_id" value="<?php echo (int)$row['id']; ?>">
                                <button class="btn btn-outline btn-danger-outline" type="submit">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    <?php elseif (!empty($row['user_applied'])): ?>
                        <div class="flex-gap-sm-full">
                            <?php if ($row['apply_status'] === 'accepted'): ?>
                                <button class="btn btn-apply btn-success" type="button" disabled><i class="fa-solid fa-check"></i> Accepted</button>
                            <?php elseif ($row['apply_status'] === 'declined'): ?>
                                <button class="btn btn-apply btn-danger-light" type="button" disabled><i class="fa-solid fa-xmark"></i> Declined</button>
                            <?php else: ?>
                                <button class="btn btn-apply btn-applied flex-1" type="button" disabled><i class="fa-solid fa-clock"></i> Pending Review</button>
                            <?php endif; ?>
                            <a href="messages.php?user_id=<?php echo (int)$row['user_id']; ?>" class="btn btn-outline btn-icon-secondary" title="Message Poster"><i class="fa-regular fa-comment"></i></a>
                        </div>
                    <?php elseif ((int)$row['accepted_applicants'] >= (int)$row['slots_total']): ?>
                        <div class="flex-gap-sm-full">
                            <button class="btn btn-apply btn-danger-light w-100" type="button" disabled>Post Full</button>
                            <a href="messages.php?user_id=<?php echo (int)$row['user_id']; ?>" class="btn btn-outline btn-icon-secondary" title="Message Poster"><i class="fa-regular fa-comment"></i></a>
                        </div>
                    <?php else: ?>
                        <div class="flex-gap-sm-full">
                            <form action="../actions/apply_collaboration.php" method="POST" class="flex-1-display">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="post_id" value="<?php echo (int)$row['id']; ?>">
                                <button class="btn btn-apply w-100" type="submit">Apply to Collaborate</button>
                            </form>
                            <a href="messages.php?user_id=<?php echo (int)$row['user_id']; ?>" class="btn btn-outline btn-icon-secondary" title="Message Poster"><i class="fa-regular fa-comment"></i></a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>

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
                    <label>Collaboration Title</label>
                    <input type="text" name="title" placeholder="e.g. Seeking Data Scientist for AI Ethics Project" class="form-input-bordered" required>
                </div>

                <div class="form-group mb-1-5">
                    <label>Link to Existing Project (Optional)</label>
                    <select name="project_id" class="form-input-bordered">
                        <option value="">None (Standalone Request)</option>
                        <?php foreach ($userProjects as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department" class="form-input-bordered" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dep): ?>
                                <option value="<?php echo htmlspecialchars($dep); ?>"><?php echo htmlspecialchars($dep); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Opportunity Type</label>
                        <select name="opportunity_type" class="form-input-bordered" required>
                            <?php foreach ($types as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                            <?php endforeach; ?>
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
