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
</head>
<body class="landing-body">

    <!-- Header -->
    <header class="landing-header">
        <div class="landing-header-left">
            <div class="logo">UIU ScholarNet</div>
        </div>

        <nav class="landing-header-center" aria-label="Primary">
            <a href="index.php" class="active">Home</a>
            <a href="#" >Resources</a>
            <a href="#">Directory</a>
        </nav>

        <div class="landing-header-right">
            <div class="landing-header-icons" aria-label="Actions">
                <i class="fa-solid fa-magnifying-glass"></i>
                <i class="fa-regular fa-bell"></i>
            </div>
            <a href="auth/login.php" class="btn btn-outline landing-signin">
                <i class="fa-regular fa-user"></i> Sign In
            </a>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-label">UIU COLLABORATION PLATFORM</div>
        <h1>What brilliant minds build together</h1>
        <p>Connect with researchers, form teams, manage projects, and share knowledge across your university — all in one place.</p>
        
        <div class="hero-btns">
            <a href="auth/register.php" class="btn btn-primary hero-btn-primary">Get Started Free <i class="fa-solid fa-arrow-right"></i></a>
            <a href="auth/login.php" class="btn btn-secondary hero-btn-secondary">Log In</a>
        </div>

        <div class="hero-divider"></div>
    </section>

    <!-- Features Section -->
    <section class="landing-features">
        <div class="feature-block">
            <h2>Interdisciplinary Hub</h2>
            <p>Break the silos between departments. Our platform allows every majors to build their projects seamlessly.</p>
            <div class="feature-icon"><i class="fa-solid fa-folder-open"></i></div>
        </div>
        <div class="feature-block">
            <h2 class="feature-faded">Resource Vault</h2>
            <p class="feature-faded">Shared institutional storage for data and papers.</p>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="big-stats">
        <div class="stat-large">12K</div>
        <div class="stat-label-bold">TASKS COMPLETED <br> THIS SEMESTER</div>

        <div class="stat-small-grid">
            <div class="stat-small-item">
                <div class="value">2,400+</div>
                <div class="label">Students</div>
            </div>
            <div class="stat-small-item">
                <div class="value">380+</div>
                <div class="label">Projects</div>
            </div>
            <div class="stat-small-item">
                <div class="value">48</div>
                <div class="label">Departments</div>
            </div>
        </div>
    </section>

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
