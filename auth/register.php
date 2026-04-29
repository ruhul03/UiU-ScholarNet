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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <div class="auth-page">
        <!-- Left Side -->
        <div class="auth-left" style="flex: 0.8; padding: 4rem;">
            <div class="logo" style="color: white; margin-bottom: 2rem;">UIU ScholarNet</div>
            <h2 style="font-size: 4.5rem;">Join the Academic Atelier</h2>
            <p style="opacity: 0.8; font-size: 1.1rem; max-width: 400px; margin-bottom: 3rem;">
                Connect with 2,400+ Researchers in a curated environment built for scientific rigor and breakthrough collaboration.
            </p>
            
            <div style="margin-top: auto;">
                <div style="display: flex; gap: -10px; margin-bottom: 1rem;">
                    <!-- Avatars placeholder -->
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #ddd; border: 2px solid var(--primary-color);"></div>
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #ccc; border: 2px solid var(--primary-color); margin-left: -15px;"></div>
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #bbb; border: 2px solid var(--primary-color); margin-left: -15px;"></div>
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--secondary-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 700; margin-left: -15px; border: 2px solid var(--primary-color);">+2K</div>
                </div>
                <p style="font-size: 0.75rem; font-weight: 700; opacity: 0.6; text-transform: uppercase; letter-spacing: 1px;">
                    TRUSTED BY FACULTY ACROSS ALL DEPARTMENTS
                </p>
            </div>
        </div>

        <!-- Right Side -->
        <div class="auth-right" style="flex: 1.4;">
            <div class="auth-card" style="max-width: 600px;">
                <h1>Scholar Registration</h1>
                <p>Please provide your institutional credentials to begin.</p>

                <?php 
                session_start();
                if(isset($_SESSION['error'])): ?>
                    <div style="background: #fdecea; color: #d32f2f; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; font-size: 0.85rem; font-weight: 600;">
                        <i class="fa-solid fa-circle-exclamation"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="../actions/register.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php 
                        require_once('../includes/csrf.php');
                        echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
                    ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" placeholder="Sajjad Ahmed" required>
                        </div>
                        <div class="form-group">
                            <label>Institutional Email</label>
                            <input type="email" name="email" placeholder="scholar@uiu.ac.bd" required>
                            <small style="font-size: 0.65rem; opacity: 0.5;">Requires email ending with uiu.ac.bd (including subdomains)</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Secure Password</label>
                            <input type="password" name="password" placeholder="••••••••" required>
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

                    <div class="form-group">
                        <label>Research Interests</label>
                        <div class="tags-container">
                            <div class="tag">Artificial Intelligence <i class="fa-solid fa-xmark"></i></div>
                            <div class="tag">Machine Learning <i class="fa-solid fa-xmark"></i></div>
                            <div class="tag-add">+ Add Interest</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Specialized Skills</label>
                        <input type="text" name="skills" placeholder="e.g. Python, LaTeX, Data Mining, Statistical Analysis">
                    </div>

                    <button type="submit" class="btn btn-secondary" style="width: 100%; justify-content: center; padding: 1.2rem; margin-top: 1.5rem;">
                        Register as Researcher <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>

                <div class="auth-footer">
                    Already part of the UIU ScholarNet? <a href="login.php">Back to Sign In</a>
                    <div style="margin-top: 2rem; opacity: 0.4; font-size: 0.75rem; max-width: 300px; margin-left: auto; margin-right: auto;">
                        By registering, you agree to our Institutional Repository Terms and Academic Integrity Policies.
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
