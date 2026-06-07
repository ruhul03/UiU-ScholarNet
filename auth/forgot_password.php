<?php
// Initialize session securely
require_once('../includes/session.php');
start_secure_session();

// Include CSRF token functions for security
require_once('../includes/csrf.php');

$pendingEmail = strtolower(trim((string)($_SESSION['password_reset_pending_email'] ?? '')));
$showResetForm = ($pendingEmail !== '');
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
    <link rel="stylesheet" href="../assets/css/style.css?v=20260602">
</head>
<body>
    <div class="auth-page">
        <div class="auth-left">
            <div class="logo logo-white">UIU ScholarNet</div>
            <h2>Recover your account access</h2>
            <p class="auth-left-desc">Enter your email and we will process a password reset request.</p>
            <div class="auth-left-footer">ACCOUNT RECOVERY PORTAL</div>
        </div>

        <div class="auth-right">
            <div class="auth-card">
                <h1>Forgot Password</h1>
                <p>Submit your email to continue.</p>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <form action="../actions/auth_user/forgot_password.php" method="POST" class="password-reset-request-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="request_code">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($pendingEmail, ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <button type="submit" class="btn btn-secondary btn-full">
                        Send Reset Code <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>

                <?php if ($showResetForm): ?>
                    <form action="../actions/auth_user/forgot_password.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($pendingEmail, ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="form-group">
                            <label>Reset Code</label>
                            <input type="text" name="code" placeholder="Enter 6-digit code" pattern="[0-9]{6}" maxlength="6" required>
                        </div>

                        <div class="form-group">
                            <label>New Password</label>
                            <div class="password-field">
                                <input type="password" name="password" placeholder="Minimum 8 characters" required>
                                <button type="button" class="password-toggle" aria-label="Show password" title="Show password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <div class="password-field">
                                <input type="password" name="confirm_password" placeholder="Retype new password" required>
                                <button type="button" class="password-toggle" aria-label="Show password" title="Show password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-secondary btn-full">
                            Verify Code & Reset <i class="fa-solid fa-check"></i>
                        </button>
                    </form>
                <?php endif; ?>

                <div class="auth-footer">
                    <a href="login.php">Back to Sign In</a>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/auth.js?v=20260602"></script>
</body>
</html>
