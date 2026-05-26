<?php
require_once(__DIR__ . '/includes/session.php');
start_secure_session();
$is_logged_in = isset($_SESSION['user_id']);
$user_data = null;
if ($is_logged_in) {
    require_once(__DIR__ . '/includes/db_connect.php');
    
    // Fetch the user's data using our helper function
    $user_id = (int)$_SESSION['user_id'];
    $user_result = db_query("SELECT * FROM users WHERE id = ?", [$user_id], "i");
    $user_data = $user_result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UIU ScholarNet | Research Collaboration Platform</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body class="landing-body">

    <!-- Header -->
    <header class="landing-header">
        <div class="landing-header-left">
            <div class="logo">UIU ScholarNet</div>
        </div>

        <nav class="landing-header-center">
            <?php if ($is_logged_in): ?>
                <a href="dashboard/index.php">Dashboard</a>
                <a href="dashboard/collaboration.php">Collaboration</a>
                <a href="dashboard/projects.php">Projects</a>
                <a href="dashboard/tasks.php">Tasks</a>
                <a href="dashboard/preprints.php">Preprints</a>
            <?php else: ?>
                <a href="#features">Features</a>
                <a href="#stats">Impact</a>
                <a href="auth/register.php">Register</a>
                <a href="auth/login.php">Explore</a>
            <?php endif; ?>
        </nav>

        <div class="landing-header-right">
            <div class="landing-header-icons" aria-label="Actions">
                <i class="fa-solid fa-magnifying-glass"></i>
                <i class="fa-regular fa-bell"></i>
            </div>
            <?php if ($is_logged_in && $user_data): ?>
                <a href="dashboard/index.php" class="btn btn-outline landing-signin landing-header-profile-btn">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name']); ?>&background=0a1128&color=fff" class="landing-header-profile-img">
                    <span>Dashboard</span>
                </a>
            <?php else: ?>
                <a href="auth/register.php" class="btn btn-outline landing-signin">
                    <i class="fa-regular fa-user"></i> Sign Up
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-label">UIU COLLABORATION PLATFORM</div>
        <h1>What brilliant minds build together</h1>
        <p>Connect with researchers, form teams, manage projects, and share knowledge across your university — all in one place.</p>

        <div class="hero-btns">
            <?php if ($is_logged_in): ?>
                <a href="dashboard/index.php" class="btn btn-primary hero-btn-primary">Go to Dashboard <i class="fa-solid fa-arrow-right"></i></a>
                <a href="dashboard/projects.php" class="btn btn-secondary hero-btn-secondary">My Workspace</a>
            <?php else: ?>
                <a href="auth/register.php" class="btn btn-primary hero-btn-primary">Get Started Free <i class="fa-solid fa-arrow-right"></i></a>
                <a href="auth/login.php" class="btn btn-secondary hero-btn-secondary">Log In</a>
            <?php endif; ?>
        </div>

        <div class="hero-divider"></div>
    </section>

    <!-- Features Section -->
    <section class="landing-features" id="features">
        <div class="feature-block">
            <div class="feature-icon"><i class="fa-solid fa-user-group"></i></div>
            <h2>Collaboration Finder</h2>
            <p>Connect with student and faculty researchers across different departments. Post opportunities, form multidisciplinary teams, and establish clear academic partnerships.</p>
            <a href="<?php echo $is_logged_in ? 'dashboard/collaboration.php' : 'auth/login.php'; ?>" class="btn btn-outline">Explore Board <i class="fa-solid fa-chevron-right"></i></a>
        </div>

        <div class="feature-block">
            <div class="feature-icon"><i class="fa-solid fa-list-check"></i></div>
            <h2>Kanban Logistics</h2>
            <p>Organize academic workflow, allocate milestones, assign deadlines, and track development. Keep your research team aligned and project momentum high.</p>
            <a href="<?php echo $is_logged_in ? 'dashboard/tasks.php' : 'auth/login.php'; ?>" class="btn btn-outline">Manage Tasks <i class="fa-solid fa-chevron-right"></i></a>
        </div>

        <div class="feature-block">
            <div class="feature-icon"><i class="fa-solid fa-file-pdf"></i></div>
            <h2>Preprint Registry</h2>
            <p>Share early-stage manuscripts and research drafts before formal publication. Receive structured peer reviews, comment feedback, and citation tracking from the community.</p>
            <a href="<?php echo $is_logged_in ? 'dashboard/preprints.php' : 'auth/login.php'; ?>" class="btn btn-outline">Read Preprints <i class="fa-solid fa-chevron-right"></i></a>
        </div>

        <div class="feature-block">
            <div class="feature-icon"><i class="fa-solid fa-book-bookmark"></i></div>
            <h2>Resource Hub</h2>
            <p>Access and download dataset archives, past reports, research templates, and general publications. Store resources in secure project storage with visibility controls.</p>
            <a href="<?php echo $is_logged_in ? 'dashboard/resources.php' : 'auth/login.php'; ?>" class="btn btn-outline">Browse Resources <i class="fa-solid fa-chevron-right"></i></a>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="big-stats" id="stats">
        <div class="stat-large">2.4k+</div>
        <div class="stat-label-bold">Academic Citations & Contributions Logged</div>
        
        <div class="stat-small-grid">
            <div class="stat-small-item">
                <div class="value">150+</div>
                <div class="label">Research Projects</div>
            </div>
            <div class="stat-small-item">
                <div class="value">420+</div>
                <div class="label">Active Researchers</div>
            </div>
            <div class="stat-small-item">
                <div class="value">85%</div>
                <div class="label">Task Completion Rate</div>
            </div>
        </div>
    </section>

    <!-- CTA Banner -->
    <section class="landing-cta cta-section">
        <h2 class="cta-heading">Ready to accelerate your research journey?</h2>
        <p class="cta-subheading">Connect with ScholarNet's brightest minds today, launch project workspaces, and publish findings.</p>
        <div>
            <?php if ($is_logged_in): ?>
                <a href="dashboard/index.php" class="btn btn-primary cta-btn">Go to Dashboard <i class="fa-solid fa-arrow-right"></i></a>
            <?php else: ?>
                <a href="auth/register.php" class="btn btn-primary cta-btn">Get Started Free <i class="fa-solid fa-arrow-right"></i></a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="landing-footer">
        <div class="footer-copyright">&copy; 2026 UIU ScholarNet. All rights reserved.</div>
        <div class="footer-links">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
            <a href="#">Contact Us</a>
        </div>
    </footer>

</body>
</html>
