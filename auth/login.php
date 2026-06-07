<?php
// Initialize session securely
require_once('../includes/session.php');
start_secure_session();

// Include CSRF token functions for security
require_once('../includes/csrf.php');
?>
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
    <link rel="stylesheet" href="../assets/css/style.css?v=20260602">
</head>
<body>

    <div class="auth-page">
        <!-- Left Side -->
        <div class="auth-left">
            <div class="logo logo-white">UIU ScholarNet</div>
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

            <div class="auth-left-footer">
                ESTABLISHED FOR ACADEMIC EXCELLENCE
            </div>
        </div>

        <!-- Right Side -->
        <div class="auth-right">
            <div class="auth-card">
                <h1>Sign In</h1>
                <p>Enter your credentials to continue</p>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i> 
                        <?php 
                            echo $_SESSION['error']; 
                            unset($_SESSION['error']); 
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert-success">
                        <i class="fa-solid fa-circle-check"></i> 
                        <?php 
                            echo $_SESSION['success']; 
                            unset($_SESSION['success']); 
                        ?>
                    </div>
                <?php endif; ?>

                <form action="../actions/login.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-group">
                        <label>Email or Admin Username</label>
                        <input type="text" name="email" placeholder="you@university.edu or admin" required>
                    </div>
                    <div class="form-group">
                        <div class="form-label-row">
                            <label>Password</label>
                            <a href="forgot_password.php" class="forgot-link">FORGOT PASSWORD?</a>
                        </div>
                        <div class="password-field">
                            <input type="password" name="password" placeholder="********" required>
                            <button type="button" class="password-toggle" aria-label="Show password" title="Show password">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="remember-me-container">
                        <input type="checkbox" name="remember_me" id="rememberMe" class="remember-me-checkbox">
                        <label for="rememberMe" class="remember-me-label">Stay logged in for 30 days</label>
                    </div>

                    <button type="submit" class="btn btn-secondary btn-full">
                        SIGN IN <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>

                <div class="auth-footer">
                    New to UIU ScholarNet? <a href="register.php">Create Account</a>
                    <div class="back-link">
                        <a href="../index.php"><i class="fa-solid fa-arrow-left"></i> Back to homepage</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/auth.js?v=20260602"></script>
</body>
</html>
