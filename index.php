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
<body style="background-color: #f8f7f2;">

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
        <div style="font-size: 0.85rem; font-weight: 700; opacity: 0.5; margin-bottom: 1.5rem;">UIU COLLABORATION PLATFORM</div>
        <h1>What brilliant minds build together</h1>
        <p>Connect with researchers, form teams, manage projects, and share knowledge across your university — all in one place.</p>
        
        <div class="hero-btns">
            <a href="auth/register.php" class="btn btn-primary" style="background-color: var(--secondary-color); color: var(--primary-color); padding: 1rem 2.5rem; font-size: 1rem;">Get Started Free <i class="fa-solid fa-arrow-right" style="margin-left: 0.5rem;"></i></a>
            <a href="auth/login.php" class="btn btn-secondary" style="background-color: var(--primary-color); color: white; padding: 1rem 2.5rem; font-size: 1rem;">Log In</a>
        </div>

        <div style="width: 100%; height: 2px; background: #eee; margin-top: 4rem;"></div>
    </section>

    <!-- Features Section -->
    <section class="landing-features">
        <div class="feature-block">
            <h2>Interdisciplinary Hub</h2>
            <p>Break the silos between departments. Our platform allows every majors to build their projects seamlessly.</p>
            <div style="font-size: 3rem; color: var(--secondary-color);"><i class="fa-solid fa-folder-open"></i></div>
        </div>
        <div class="feature-block">
            <h2 style="opacity: 0.4;">Resource Vault</h2>
            <p style="opacity: 0.4;">Shared institutional storage for data and papers.</p>
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

    <footer style="padding: 5rem; border-top: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
        <div style="font-size: 0.8rem; font-weight: 700; opacity: 0.4;">&copy; 2026 UIU ScholarNet. All rights reserved.</div>
        <div style="display: flex; gap: 3rem; font-size: 0.75rem; font-weight: 800; opacity: 0.4; letter-spacing: 1px; text-transform: uppercase;">
            <a href="#">Privacy Policy</a>
            <a href="#">Terms of Service</a>
            <a href="#">Contact Us</a>
        </div>
    </footer>

</body>
</html>
