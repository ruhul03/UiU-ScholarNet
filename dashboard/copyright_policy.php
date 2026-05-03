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
    <style>
        .policy-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 3rem 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-top: 2rem;
            margin-bottom: 2rem;
        }
        .policy-header {
            text-align: center;
            margin-bottom: 3rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .policy-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: #0a1128;
            margin-bottom: 1rem;
        }
        .policy-header p {
            color: #64748b;
            font-size: 1.1rem;
        }
        .policy-section {
            margin-bottom: 2.5rem;
        }
        .policy-section h2 {
            font-size: 1.4rem;
            color: #1e293b;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }
        .policy-section h2 i {
            color: #3b82f6;
        }
        .policy-section p, .policy-section li {
            color: #475569;
            line-height: 1.7;
            font-size: 1.05rem;
        }
        .policy-section ul {
            padding-left: 1.5rem;
            margin-top: 0.5rem;
        }
        .policy-section li {
            margin-bottom: 0.5rem;
        }
        .highlight-box {
            background: #f8fafc;
            border-left: 4px solid #10b981;
            padding: 1.5rem;
            border-radius: 0 8px 8px 0;
            margin: 1.5rem 0;
        }
        .highlight-box p {
            margin: 0;
            font-weight: 500;
            color: #0f172a;
        }
    </style>
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <main class="main-content" style="padding: 2rem;">
        
        <div class="policy-container">
            <div class="policy-header">
                <h1>Terms & Copyright Policy</h1>
                <p>Understanding content ownership, licensing, and sharing on UIU ScholarNet.</p>
            </div>
            
            <div class="highlight-box">
                <p><i class="fa-solid fa-scale-balanced" style="color: #10b981; margin-right: 0.5rem;"></i> UIU ScholarNet is a hosting and sharing platform. We do not claim ownership of any content uploaded by our users.</p>
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

            <div style="text-align: center; margin-top: 3rem;">
                <a href="preprints.php" class="btn btn-primary">Back to Preprints</a>
            </div>
        </div>
        
    </main>

</body>
</html>
