<?php
require_once('../includes/auth_check.php');

// Fetch Collaboration Posts
$query = "SELECT cp.*, u.full_name 
          FROM collaboration_posts cp 
          JOIN users u ON cp.user_id = u.id 
          ORDER BY cp.created_at DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collaboration Finder | UIU ScholarNet</title>
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
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass" style="opacity: 0.3;"></i>
                <input type="text" placeholder="Filter by skills or department...">
            </div>
            <button class="btn btn-primary" onclick="openModal()"><i class="fa-solid fa-plus"></i> Post Collaboration</button>
        </header>

        <section>
            <h2 style="font-size: 2rem; margin-bottom: 2rem;">Available Collaborations</h2>

            <div class="collaboration-grid">
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <!-- Dynamic Collab Card -->
                <div class="collab-card">
                    <div class="collab-header">
                        <div class="collab-dept"><?php echo $row['department']; ?></div>
                        <div style="font-size: 0.8rem; opacity: 0.5;">Posted <?php echo date('M d, Y', strtotime($row['created_at'])); ?></div>
                    </div>
                    <h3><?php echo $row['title']; ?></h3>
                    <p class="collab-desc"><?php echo $row['description']; ?></p>
                    <div class="collab-tags">
                        <?php 
                        $tags = explode(',', $row['skills_required']);
                        foreach($tags as $tag): ?>
                            <span class="collab-tag"><?php echo trim($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($row['full_name']); ?>&background=0a1128&color=fff" style="width: 30px; height: 30px; border-radius: 50%;">
                            <span style="font-size: 0.8rem; font-weight: 600;"><?php echo $row['full_name']; ?></span>
                        </div>
                        <button class="btn btn-outline" style="padding: 0.5rem 1.2rem; font-size: 0.8rem;">Apply to Join</button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </section>
    </main>

    <!-- Modal for Posting -->
    <div class="modal-overlay" id="collabModal" style="display: none;">
        <div class="modal-content">
            <i class="fa-solid fa-xmark modal-close" onclick="closeModal()"></i>
            <h2 style="margin-bottom: 0.5rem;">Post New Collaboration</h2>
            <p style="opacity: 0.5; margin-bottom: 2rem;">Looking for researchers or team members?</p>
            
            <form action="../actions/post_collaboration.php" method="POST">
                <div class="form-group">
                    <label>Project Title</label>
                    <input type="text" name="title" placeholder="e.g. AI in Sustainable Architecture" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department" required>
                            <option value="Computer Science">Computer Science</option>
                            <option value="EEE">EEE</option>
                            <option value="Economics">Economics</option>
                            <option value="Business">Business</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Required Skills</label>
                        <input type="text" name="skills" placeholder="e.g. Python, UI/UX, Research">
                    </div>
                </div>
                <div class="form-group">
                    <label>Collaboration Description</label>
                    <textarea name="description" rows="4" style="width: 100%; padding: 1rem; border: 1px solid #ddd; border-radius: 6px;" placeholder="Describe your project and what you're looking for..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; background-color: var(--secondary-color); color: var(--primary-color);">POST COLLABORATION</button>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('collabModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('collabModal').style.display = 'none';
        }
    </script>

</body>
</html>
