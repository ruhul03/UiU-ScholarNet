<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UIU ScholarNet | Where Brilliant Minds Build Together</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <header>
        <div class="container">
            <nav>
                <div class="logo">UIU ScholarNet</div>
                <ul class="nav-links">
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="#">Resources</a></li>
                    <li><a href="#">Directory</a></li>
                </ul>
                <div class="nav-actions">
                    <a href="#"><i class="fa-solid fa-magnifying-glass"></i></a>
                    <a href="#"><i class="fa-regular fa-bell"></i></a>
                    <a href="auth/login.php" class="btn btn-outline">
                        <i class="fa-regular fa-user"></i> Sign In
                    </a>
                </div>
            </nav>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="badge">UIU Collaboration Platform</div>
                <h1>Where <span>brilliant</span> minds build together</h1>
                <p>Connect with researchers, form teams, manage projects, and share knowledge across your university — all in one place.</p>
                <div class="hero-btns">
                    <a href="auth/register.php" class="btn btn-primary">Get Started Free <i class="fa-solid fa-arrow-right"></i></a>
                    <a href="auth/login.php" class="btn btn-secondary">Log In</a>
                </div>
            </div>
        </section>

        <!-- Feature Section -->
        <section class="features">
            <div class="container">
                <div class="feature-grid">
                    <div class="feature-card large">
                        <img src="assets/images/hero-hub.png" alt="Interdisciplinary Hub">
                        <div class="feature-content">
                            <div style="width: 50px; height: 3px; background: var(--secondary-color); margin-bottom: 1.5rem;"></div>
                            <h2>Interdisciplinary Hub</h2>
                            <p>Break the silos between departments. Our platform allows every majors to build their projects seamlessly.</p>
                        </div>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 2rem;">
                        <div class="feature-card small">
                            <i class="fa-solid fa-folder-open" style="font-size: 2rem; color: var(--secondary-color);"></i>
                            <div>
                                <h3>Resource Vault</h3>
                                <p style="font-size: 0.9rem; opacity: 0.7;">Shared institutional storage for data and papers.</p>
                            </div>
                        </div>
                        <div class="feature-card small stats">
                            <div>
                                <h3 style="font-size: 3rem; font-family: var(--font-body);">12K</h3>
                                <p style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 2px; font-weight: 700;">Tasks Completed<br>This Semester</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Bar -->
        <section class="stats-bar">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-item">
                        <h3>2,400+</h3>
                        <p>Students</p>
                    </div>
                    <div class="stat-item">
                        <h3>380+</h3>
                        <p>Projects</p>
                    </div>
                    <div class="stat-item">
                        <h3>48</h3>
                        <p>Departments</p>
                    </div>
                    <div class="stat-item">
                        <h3>12K+</h3>
                        <p>Tasks Done</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="logo" style="font-size: 1.2rem;">UIU ScholarNet</div>
                <div class="footer-links">
                    <a href="#">Privacy</a>
                    <a href="#">Terms</a>
                    <a href="#">Help</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
