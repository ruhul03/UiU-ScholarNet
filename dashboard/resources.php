<?php
require_once('../includes/auth_check.php');

$filter = $_GET['filter'] ?? 'all';
$whereSql = '';

if ($filter === 'thesis') {
    $whereSql = "WHERE r.category IN ('Thesis', 'Research Paper')";
} elseif ($filter === 'lecture') {
    $whereSql = "WHERE r.category IN ('Lecture Notes', 'Presentation')";
} elseif ($filter === 'dataset') {
    $whereSql = "WHERE r.category = 'Dataset' OR r.resource_type IN ('Dataset', 'CSV')";
}

// Fetch Resources
$sql = "SELECT r.*, u.full_name 
        FROM resources r 
        JOIN users u ON r.user_id = u.id 
        $whereSql
        ORDER BY r.created_at DESC";
$result = db_query($sql, [], "");

// Fetch notification counts
$pending_tasks = (int)(db_query("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status != 'done'", [$user_id], "i")->fetch_assoc()['total'] ?? 0);
$collab_requests = (int)(db_query("SELECT COUNT(*) as total FROM collaboration_applications ca JOIN collaboration_posts cp ON ca.post_id = cp.id WHERE cp.user_id = ? AND ca.status = 'pending'", [$user_id], "i")->fetch_assoc()['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource Hub | UIU ScholarNet</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php include('../includes/header.php'); ?>
        <?php include('../includes/alerts.php'); ?>

        <section class="resources-section">
            <h1 class="resources-title">Resource Hub</h1>
            <p class="resources-desc">Access shared thesis papers, datasets, lecture notes, and research materials from across the university.</p>

            <div class="resource-filters">
                <a href="?filter=all" class="btn btn-outline text-deco-none <?php echo ($filter === 'all') ? 'filter-active' : ''; ?>">All Materials</a>
                <a href="?filter=thesis" class="btn btn-outline text-deco-none <?php echo ($filter === 'thesis') ? 'filter-active' : ''; ?>">Thesis Papers</a>
                <a href="?filter=lecture" class="btn btn-outline text-deco-none <?php echo ($filter === 'lecture') ? 'filter-active' : ''; ?>">Lecture Notes</a>
                <a href="?filter=dataset" class="btn btn-outline text-deco-none <?php echo ($filter === 'dataset') ? 'filter-active' : ''; ?>">Research Datasets</a>
            </div>
        </section>

        <div class="resource-grid">
            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <div class="resource-card">
                    <div class="resource-icon">
                        <?php
                        $icon = 'fa-file';
                        switch($row['resource_type']) {
                            case 'PDF': $icon = 'fa-file-pdf'; break;
                            case 'Dataset': case 'CSV': $icon = 'fa-table'; break;
                            case 'Report': $icon = 'fa-file-lines'; break;
                            case 'Paper': $icon = 'fa-book'; break;
                            case 'Image': $icon = 'fa-image'; break;
                            case 'Archive': $icon = 'fa-file-zipper'; break;
                        }
                        ?>
                        <i class="fa-solid <?php echo $icon; ?>"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                    <div class="resource-meta">
                        <span><?php echo $row['resource_type']; ?></span> • 
                        <span>By <?php echo htmlspecialchars($row['full_name']); ?></span>
                        <?php if($row['file_size']): ?>
                            • <span><?php echo $row['file_size']; ?></span>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo $row['file_path'] ? ('../' . htmlspecialchars($row['file_path'])) : '#'; ?>" class="btn btn-outline btn-full-sm">
                        <i class="fa-solid fa-download"></i> Download
                    </a>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-folder-open empty-icon"></i>
                    <h3 class="empty-title">No resources uploaded yet</h3>
                    <p class="empty-desc">Start sharing your research materials with the community.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
