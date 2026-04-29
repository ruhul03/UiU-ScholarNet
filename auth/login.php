<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | UIU ScholarNet</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <div class="auth-page">
        <!-- Left Side -->
        <div class="auth-left">
            <div class="logo" style="color: white; margin-bottom: 2rem;">UIU ScholarNet</div>
            <h2>Welcome back to your research hub</h2>
            
            <div class="auth-features">
                <div class="auth-feature-item">
                    <div class="auth-feature-icon"><i class="fa-solid fa-rocket"></i></div>
                    <p>Track your project progress</p>
                </div>
                <div class="auth-feature-item">
                    <div class="auth-feature-icon"><i class="fa-solid fa-medal"></i></div>
                    <p>Complete tasks & earn points</p>
                </div>
                <div class="auth-feature-item">
                    <div class="auth-feature-icon"><i class="fa-solid fa-book-open"></i></div>
                    <p>Access the resource library</p>
                </div>
                <div class="auth-feature-item">
                    <div class="auth-feature-icon"><i class="fa-solid fa-ranking-star"></i></div>
                    <p>Climb the reputation board</p>
                </div>
            </div>

            <div style="margin-top: auto; opacity: 0.5; font-size: 0.8rem; letter-spacing: 1px;">
                ESTABLISHED FOR ACADEMIC EXCELLENCE
            </div>
        </div>

        <!-- Right Side -->
        <div class="auth-right">
            <div class="auth-card">
                <h1>Sign In</h1>
                <p>Enter your credentials to continue</p>

                <?php 
                session_start();
                if(isset($_SESSION['error'])): ?>
                    <div style="background: #fdecea; color: #d32f2f; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.85rem; font-weight: 600;">
                        <i class="fa-solid fa-circle-exclamation"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['success'])): ?>
                    <div style="background: #e8f5e9; color: #2e7d32; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.85rem; font-weight: 600;">
                        <i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <div class="auth-toggle">
                    <button class="toggle-btn active" id="studentToggle">
                        <i class="fa-solid fa-graduation-cap"></i> Student
                    </button>
                    <button class="toggle-btn" id="facultyToggle">
                        <i class="fa-solid fa-user-tie"></i> Faculty
                    </button>
                </div>

                <form action="../actions/login.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php 
                        require_once('../includes/csrf.php');
                        echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
                    ?>">
                    <div class="form-group">
                        <label>University Email</label>
                        <input type="email" name="email" placeholder="you@university.edu" required>
                    </div>
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <label>Password</label>
                            <a href="#" style="font-size: 0.7rem; color: var(--secondary-color); font-weight: 700;">FORGOT PASSWORD?</a>
                        </div>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>

                    <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center; padding: 1.2rem; margin-top: 1rem;">
                        SIGN IN <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>

                <div class="auth-footer">
                    New to UIU ScholarNet? <a href="register.php">Create Account</a>
                    <div style="margin-top: 1.5rem; opacity: 0.5;">
                        <a href="../index.php" style="color: inherit; font-weight: 500;"><i class="fa-solid fa-arrow-left"></i> Back to homepage</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const studentToggle = document.getElementById('studentToggle');
        const facultyToggle = document.getElementById('facultyToggle');

        studentToggle.addEventListener('click', () => {
            studentToggle.classList.add('active');
            facultyToggle.classList.remove('active');
        });

        facultyToggle.addEventListener('click', () => {
            facultyToggle.classList.add('active');
            studentToggle.classList.remove('active');
        });
    </script>

</body>
</html>
