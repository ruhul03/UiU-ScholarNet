<?php
// Start the session to handle error messages
session_start();

// Include CSRF token functions for security
require_once('../includes/csrf.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scholar Registration | UIU ScholarNet</title>
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
        <div class="auth-left auth-left-narrow">
            <div class="logo logo-white">UIU ScholarNet</div>
            <h2 class="auth-left-title">Join the Academic Atelier</h2>
            <p class="auth-left-desc">
                Connect with 2,400+ Researchers in a curated environment built for scientific rigor and breakthrough collaboration.
            </p>

            <div class="margin-top-auto">
                <div class="avatar-group">
                    <div class="avatar-placeholder"></div>
                    <div class="avatar-placeholder secondary"></div>
                    <div class="avatar-placeholder tertiary"></div>
                    <div class="avatar-count">+2K</div>
                </div>
                <p class="trusted-text">
                    TRUSTED BY FACULTY ACROSS ALL DEPARTMENTS
                </p>
            </div>
        </div>

        <!-- Right Side -->
        <div class="auth-right auth-right-wide">
            <div class="auth-card auth-card-wide">
                <h1>Scholar Registration</h1>
                <p>Please provide your institutional credentials to begin.</p>

                <!-- Display any error messages stored in the session -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert-error">
                        <i class="fa-solid fa-circle-exclamation"></i> 
                        <?php 
                            echo $_SESSION['error']; 
                            // Remove the error message from the session so it doesn't show again
                            unset($_SESSION['error']); 
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Registration form -->
                <form action="../actions/register.php" method="POST">
                    <!-- CSRF Token to protect against Cross-Site Request Forgery -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" placeholder="Sajjad Ahmed" required>
                        </div>
                        <div class="form-group">
                            <label>Institutional Email</label>
                            <input type="email" name="email" placeholder="scholar@uiu.ac.bd" required>
                            <small class="helper-text">Requires email ending with uiu.ac.bd (including subdomains)</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Secure Password</label>
                            <div class="password-field">
                                <input type="password" name="password" placeholder="••••••••" required>
                                <button type="button" class="password-toggle" aria-label="Show password" title="Show password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <select name="department" required>
                                <option value="">Select Domain</option>
                                <option value="CSE">Computer Science</option>
                                <option value="EEE">Electrical Engineering</option>
                                <option value="BBA">Business Administration</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group flex-1">
                            <label>Role</label>
                            <select name="role" required>
                                <option value="student">Student</option>
                                <option value="faculty">Faculty</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Research Interests</label>
                        <input type="hidden" name="interests" id="interestsHidden" value="Artificial Intelligence,Machine Learning">
                        <div class="tags-container" id="interestsContainer"></div>
                        <div class="interest-input-row">
                            <input type="text" id="interestInput" placeholder="Add an interest (e.g. NLP)">
                            <button type="button" class="tag-add" id="addInterestBtn">+ Add Interest</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Specialized Skills</label>
                        <input type="text" name="skills" placeholder="e.g. Python, LaTeX, Data Mining, Statistical Analysis">
                    </div>

                    <button type="submit" class="btn btn-secondary btn-full">
                        Register as Researcher <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>

                <div class="auth-footer">
                    Already part of the UIU ScholarNet? <a href="login.php">Back to Sign In</a>
                    <div class="terms-text">
                        By registering, you agree to our Institutional Repository Terms and Academic Integrity Policies.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/auth.js?v=20260602"></script>
</body>
</html>
