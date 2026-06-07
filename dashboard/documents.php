<?php
require_once('../includes/auth_check.php');
require_once('../includes/csrf.php');

$user_id = (int)$_SESSION['user_id'];

// Fetch all documents accessible to the user
$docs_result = db_query("
    SELECT d.id, d.title, d.updated_at, d.visibility, p.title as project_title, p.id as project_id, p.creator_id, pm.role
    FROM documents d
    JOIN projects p ON p.id = d.project_id
    LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
    WHERE p.creator_id = ? OR pm.role IN ('owner', 'editor', 'viewer')
    ORDER BY d.updated_at DESC
", [$user_id, $user_id], "ii");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents | UIU ScholarNet</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/documents.css">
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php include('../includes/header.php'); ?>
        <?php include('../includes/alerts.php'); ?>

        <div class="docs-container">
            <div class="page-header">
                <div>
                    <h1 class="page-title-lg">Document Hub</h1>
                    <p class="text-light">Access and manage all your research drafts.</p>
                </div>
                <a href="document_editor.php" class="btn btn-primary"><i class="fa-solid fa-plus"></i> New Document</a>
            </div>

            <div class="docs-grid">
                <?php if ($docs_result->num_rows === 0): ?>
                    <div class="empty-state">
                        <i class="fa-regular fa-file-lines"></i>
                        <h3>No Documents Yet</h3>
                        <p>Create your first research document to start collaborating!</p>
                        <br>
                        <a href="document_editor.php" class="btn btn-primary">Create Document</a>
                    </div>
                <?php else: ?>
                    <?php while($doc = $docs_result->fetch_assoc()): ?>
                        <div class="doc-card">
                            <a href="document_editor.php?document_id=<?php echo $doc['id']; ?>" class="doc-link-card">
                                <div class="doc-icon"><i class="fa-solid fa-file-lines"></i></div>
                                <span class="doc-visibility"><?php echo htmlspecialchars($doc['visibility']); ?></span>
                                <div class="doc-title"><?php echo htmlspecialchars($doc['title'] ?: 'Untitled Document'); ?></div>
                                <div class="doc-meta">
                                    <span><i class="fa-solid fa-folder"></i> <?php echo htmlspecialchars($doc['project_title'] ?? 'No Project'); ?></span>
                                    <span><i class="fa-regular fa-clock"></i> <?php echo $doc['updated_at'] ? date('M d, Y', strtotime($doc['updated_at'])) : 'Never'; ?></span>
                                </div>
                            </a>
                            <?php 
                            $is_leader = ($doc['creator_id'] == $user_id || $doc['role'] === 'owner');
                            if ($is_leader): 
                            ?>
                            <form action="../actions/project_task_document/delete_document.php" method="POST" class="absolute-top-right" onsubmit="return confirm('Are you sure you want to delete this document?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="document_id" value="<?php echo $doc['id']; ?>">
                                <button type="submit" class="btn-delete" title="Delete Document"><i class="fa-solid fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
