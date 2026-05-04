<?php
require_once('../includes/auth_check.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Copyright Policy | UIU ScholarNet</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/preprints.css">
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content policy-main-content">

        <div class="policy-container">
            <div class="policy-header">
                <h1>Terms & Copyright Policy</h1>
                <p>Understanding content ownership, licensing, and sharing on UIU ScholarNet.</p>
            </div>

            <div class="highlight-box">
                <p><i class="fa-solid fa-scale-balanced policy-icon-green"></i> UIU ScholarNet is a hosting and sharing platform. We do not claim ownership of any content uploaded by our users.</p>
            </div>

            <div class="policy-section">
                <h2><i class="fa-regular fa-copyright"></i> 1. Ownership</h2>
                <p><strong>Users retain full ownership of the content they upload.</strong> When you upload a preprint, document, or resource, you continue to own all intellectual property rights associated with your work.</p>
            </div>

            <div class="policy-section">
                <h2><i class="fa-regular fa-handshake"></i> 2. Permission to Display</h2>
                <p><strong>By uploading, users grant the platform permission to display and share the content within UIU.</strong> This allows your peers and faculty members to view, download, and provide feedback on your academic work through the platform.</p>
            </div>

            <div class="policy-section">
                <h2><i class="fa-solid fa-shield-halved"></i> 3. User Responsibility</h2>
                <p><strong>Users must only upload content they own or have explicit permission to share.</strong></p>
                <ul>
                    <li>Do not upload copyrighted journal papers without the publisher's permission.</li>
                    <li>Ensure preprints are not under restricted publication copyright elsewhere.</li>
                    <li>Always properly attribute collaborative work to all contributing authors.</li>
                </ul>
            </div>

            <div class="policy-section">
                <h2><i class="fa-solid fa-gavel"></i> 4. Violation Clause</h2>
                <p><strong>Content violating copyright may be removed.</strong> If we receive a report that content infringes on intellectual property rights or violates our terms, administrators reserve the right to review and remove the material without prior notice.</p>
            </div>

            <div class="policy-section">
                <h2><i class="fa-solid fa-file-contract"></i> 5. Licensing Options</h2>
                <p>When uploading, users can select how they want their work to be licensed to the community:</p>
                <ul>
                    <li><strong>All Rights Reserved:</strong> Default protection. Others cannot freely copy, distribute, or modify the work without asking first.</li>
                    <li><strong>Creative Commons (CC BY):</strong> Others can distribute, remix, and build upon the work as long as they credit the original author.</li>
                    <li><strong>Creative Commons (CC BY-NC):</strong> Others can distribute and build upon the work non-commercially, as long as they credit the original author.</li>
                </ul>
            </div>

            <div class="policy-footer-center">
                <a href="preprints.php" class="btn btn-primary">Back to Preprints</a>
            </div>
        </div>

    </main>

</body>
</html>
