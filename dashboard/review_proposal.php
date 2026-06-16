<?php
require_once('../includes/auth_check.php');
require_once('../includes/csrf.php');

$version_id = isset($_GET['version_id']) ? (int)$_GET['version_id'] : 0;
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if ($version_id <= 0 || $project_id <= 0) {
    header("Location: projects.php");
    exit();
}

// Ensure the user is the project creator or supervisor
$projectPermissionQuery = "
    SELECT id, title, creator_id, supervisor_id 
    FROM projects 
    WHERE id = ? AND (creator_id = ? OR supervisor_id = ?)
    LIMIT 1
";
$projectPermissionResult = db_query($projectPermissionQuery, [$project_id, $user_id, $user_id], "iii");

if (!$projectPermissionResult || $projectPermissionResult->num_rows !== 1) {
    $_SESSION['error'] = "You do not have permission to review proposals for this project.";
    header("Location: projects.php");
    exit();
}
$projectData = $projectPermissionResult->fetch_assoc();

// Fetch the proposed version
$versionQuery = "
    SELECT v.*, u.full_name as submitter_name, d.title as document_title 
    FROM document_versions v 
    JOIN documents d ON v.document_id = d.id
    LEFT JOIN users u ON v.created_by = u.id 
    WHERE v.id = ? AND v.status = 'pending' AND d.project_id = ?
    LIMIT 1
";
$versionResult = db_query($versionQuery, [$version_id, $project_id], "ii");

if (!$versionResult || $versionResult->num_rows !== 1) {
    $_SESSION['error'] = "Proposal not found or already processed.";
    header("Location: document_editor.php?project_id=" . $project_id);
    exit();
}

$proposal = $versionResult->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Proposal | UIU ScholarNet</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/editor.css">
    <!-- Quill CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .ql-container { font-family: 'Inter', sans-serif; font-size: 16px; border: 1px solid #ddd !important; border-top: none !important; border-radius: 0 0 8px 8px; }
        .ql-toolbar { border: 1px solid #ddd !important; border-radius: 8px 8px 0 0; background: #fafafa; }
        .preview-container {
            max-width: 900px;
            margin: 2rem auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            padding: 2rem;
        }
        .proposal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eaeaea;
        }
        .proposal-meta h2 { margin-bottom: 0.5rem; font-family: 'Playfair Display', serif; }
        .proposal-meta p { color: #666; font-size: 0.9rem; }
        .proposal-actions {
            display: flex;
            gap: 1rem;
        }
    </style>
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php include('../includes/header.php'); ?>
        <?php include('../includes/alerts.php'); ?>

        <div class="preview-container">
            <div class="proposal-header">
                <div class="proposal-meta">
                    <h2>Review: <?php echo htmlspecialchars($proposal['document_title']); ?></h2>
                    <p>
                        Proposed by <strong><?php echo htmlspecialchars($proposal['submitter_name'] ?? 'Unknown User'); ?></strong> 
                        on <?php echo date('M d, Y g:i A', strtotime($proposal['created_at'])); ?>
                    </p>
                    <?php if(!empty($proposal['commit_message'])): ?>
                        <div style="margin-top:1rem; padding:1rem; background:#f5f5f5; border-radius:6px; font-style:italic;">
                            "<?php echo htmlspecialchars($proposal['commit_message']); ?>"
                        </div>
                    <?php endif; ?>
                </div>
                <div class="proposal-actions">
                    <form action="../actions/handle_proposal.php" method="POST" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="version_id" value="<?php echo $proposal['id']; ?>">
                        <input type="hidden" name="action_type" value="reject">
                        <button type="submit" class="btn btn-outline" style="color: #e74c3c; border-color: #e74c3c;" onclick="return confirm('Are you sure you want to reject this proposal?');">
                            <i class="fa-solid fa-xmark"></i> Reject
                        </button>
                    </form>
                    <form action="../actions/handle_proposal.php" method="POST" style="margin:0;">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="version_id" value="<?php echo $proposal['id']; ?>">
                        <input type="hidden" name="action_type" value="accept">
                        <button type="submit" class="btn btn-primary" onclick="return confirm('Accepting will overwrite the current live document with this draft. Proceed?');">
                            <i class="fa-solid fa-check"></i> Accept Proposal
                        </button>
                    </form>
                    <a href="document_editor.php?document_id=<?php echo $proposal['document_id']; ?>" class="btn btn-outline">Back</a>
                </div>
            </div>

            <div class="editor-wrapper" style="padding: 0;">
                <div id="quill-editor" style="min-height: 400px; padding: 1.5rem;">
                    <?php echo $proposal['content']; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Quill JS -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        // Initialize read-only editor
        var quill = new Quill('#quill-editor', {
            theme: 'snow',
            readOnly: true,
            modules: { toolbar: false }
        });
    </script>
</body>
</html>
