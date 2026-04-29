<?php
require_once('../includes/auth_check.php');

// Fetch Resources
$query = "SELECT r.*, u.full_name 
          FROM resources r 
          JOIN users u ON r.user_id = u.id 
          ORDER BY r.created_at DESC";
$result = mysqli_query($conn, $query);
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <header class="dash-header">
            <h2 style="font-size: 2.5rem;">Resource Hub</h2>
            <button class="btn btn-primary" style="background-color: var(--secondary-color);"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Resource</button>
        </header>

        <div class="resource-filters">
            <button class="btn btn-outline active">All Materials</button>
            <button class="btn btn-outline">Thesis Papers</button>
            <button class="btn btn-outline">Lecture Notes</button>
            <button class="btn btn-outline">Research Datasets</button>
        </div>

        <div class="resource-grid">
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <div class="resource-card">
                <div class="resource-icon">
                    <i class="fa-solid fa-file-pdf"></i>
                </div>
                <div class="resource-info">
                    <h3><?php echo $row['title']; ?></h3>
                    <div class="resource-meta">
                        <span><?php echo ucfirst($row['type']); ?></span> • 
                        <span>Uploaded by <?php echo $row['full_name']; ?></span>
                    </div>
                </div>
                <a href="#" class="btn btn-outline" style="padding: 0.5rem;"><i class="fa-solid fa-download"></i></a>
            </div>
            <?php endwhile; ?>
        </div>
    </main>

</body>
</html>
