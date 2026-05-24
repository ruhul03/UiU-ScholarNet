<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/csrf.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | UIU ScholarNet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-left">
            <div class="logo logo-white">UIU ScholarNet</div>
            <h2>Recover your account access</h2>
            <p class="auth-left-desc">Enter your institutional email and we will process a password reset request.</p>
            <div class="auth-left-footer">ACCOUNT RECOVERY PORTAL</div>
        </div>

        <div class="auth-right">
            <div class="auth-card">
                <h1>Forgot Password</h1>
                <p>Submit your university email to continue.</p>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="../actions/forgot_password.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-group">
                        <label>University Email</label>
                        <input type="email" name="email" placeholder="you@uiu.ac.bd" required>
                    </div>
                    
                    <p style="font-size: 0.8rem; color: #666; margin: 1rem 0;">For security, please verify your identity by entering your exact full name and department as they appear on your profile.</p>

                    <div class="form-group">
                        <label>Full Name (Verification)</label>
                        <input type="text" name="full_name" placeholder="John Doe" required>
                    </div>
                    <div class="form-group">
                        <label>Department (Verification)</label>
                        <select name="department" required>
                            <option value="">Select your department</option>
                            <option value="CSE">Computer Science and Engineering</option>
                            <option value="EEE">Electrical and Electronic Engineering</option>
                            <option value="BBA">Business Administration</option>
                            <option value="Data Science">Data Science</option>
                            <option value="Economics">Economics</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" placeholder="********" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" placeholder="********" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-secondary btn-full">
                        Reset Password <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>

                <div class="auth-footer">
                    <a href="login.php">Back to Sign In</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
