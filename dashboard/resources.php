<?php
require_once('../includes/auth_check.php');

// Fetch Resources
$stmt = $conn->prepare("SELECT r.*, u.full_name 
                        FROM resources r 
                        JOIN users u ON r.user_id = u.id 
                        ORDER BY r.created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
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
        <header class="dash-header dash-header-resources">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" placeholder="Search resources...">
            </div>
            <div class="header-actions">
                <i class="fa-regular fa-bell header-icon"></i>
                <button class="btn btn-primary btn-upload-resource">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Upload Resource
                </button>
            </div>
        </header>

        <section class="resources-section">
            <h1 class="resources-title">Resource Hub</h1>
            <p class="resources-desc">Access shared thesis papers, datasets, lecture notes, and research materials from across the university.</p>

            <div class="resource-filters">
                <button class="btn btn-outline filter-active">All Materials</button>
                <button class="btn btn-outline">Thesis Papers</button>
                <button class="btn btn-outline">Lecture Notes</button>
                <button class="btn btn-outline">Research Datasets</button>
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
                    <a href="<?php echo $row['file_path'] ? ('../' . htmlspecialchars($row['file_path'])) : '#'; ?>" class="btn btn-outline" style="width: 100%; justify-content: center; font-size: 0.8rem;">
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
