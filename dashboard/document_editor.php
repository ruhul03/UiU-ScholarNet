<?php
require_once('../includes/auth_check.php');
require_once('../includes/csrf.php');

$document_id = isset($_GET['document_id']) ? (int)$_GET['document_id'] : 0;
$initial_project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

// Projects for dropdown (user's own projects or where they are an editor/owner)
$projects_result = db_query("
    SELECT p.id, p.title 
    FROM projects p
    LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
    WHERE p.creator_id = ? OR pm.role IN ('owner', 'editor')
    ORDER BY p.created_at DESC
", [$user_id, $user_id], "ii");

$doc = [
    'id' => 0,
    'project_id' => $initial_project_id,
    'title' => 'Untitled Document',
    'content' => '',
    'visibility' => 'private',
    'updated_at' => null,
];

if ($document_id > 0) {
    $dres = db_query("
        SELECT d.*
        FROM documents d
        JOIN projects p ON p.id = d.project_id
        LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.user_id = ?
        WHERE d.id = ? AND (p.creator_id = ? OR pm.role IN ('owner', 'editor', 'viewer'))
        LIMIT 1
    ", [$user_id, $document_id, $user_id], "iii");
    $found = $dres ? $dres->fetch_assoc() : null;
    if ($found) {
        $doc = array_merge($doc, $found);
        $doc['id'] = (int)$doc['id'];
        $doc['project_id'] = (int)$doc['project_id'];
    }
}

$team_members = [];
$versions = [];

if ($doc['project_id'] > 0) {
    // Fetch project creator
    $cRes = db_query("SELECT u.id, u.full_name, 'owner' as role FROM users u JOIN projects p ON p.creator_id = u.id WHERE p.id = ?", [$doc['project_id']], "i");
    if ($cRes && $row = $cRes->fetch_assoc()) {
        $team_members[] = $row;
    }
    
    // Fetch members
    $mRes = db_query("SELECT u.id, u.full_name, pm.role FROM project_members pm JOIN users u ON pm.user_id = u.id WHERE pm.project_id = ? ORDER BY pm.added_at ASC", [$doc['project_id']], "i");
    while ($mRes && $row = $mRes->fetch_assoc()) {
        $is_dup = false;
        foreach($team_members as $tm) { if($tm['id'] == $row['id']) $is_dup = true; }
        if(!$is_dup) $team_members[] = $row;
    }
}

if ($doc['id'] > 0) {
    // Fetch versions
    $vRes = db_query("SELECT v.*, u.full_name FROM document_versions v LEFT JOIN users u ON v.created_by = u.id WHERE v.document_id = ? ORDER BY v.created_at DESC", [$doc['id']], "i");
    while ($vRes && $row = $vRes->fetch_assoc()) {
        $versions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Editor | UIU ScholarNet</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,700;1,700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/document_editor.css">
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php include('../includes/header.php'); ?>

        <div class="editor-layout">
            <!-- Left: Main Editor Area -->
            <div class="editor-container">
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert-success-editor">
                        <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert-error-editor">
                        <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <form action="../actions/save_document.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="document_id" value="<?php echo (int)$doc['id']; ?>">

                    <div class="form-row-editor">
                        <div class="form-group-title">
                            <label class="label-small">DOCUMENT TITLE</label>
                            <input name="title" value="<?php echo htmlspecialchars((string)$doc['title']); ?>" class="input-editor">
                        </div>
                        <div class="form-group-project">
                            <label class="label-small">PROJECT</label>
                            <select name="project_id" required class="select-editor">
                                <option value="">Select Project</option>
                                <?php while($p = $projects_result->fetch_assoc()): ?>
                                    <option value="<?php echo (int)$p['id']; ?>" <?php echo ((int)$doc['project_id'] === (int)$p['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row-meta">
                        <div class="last-saved">
                            <i class="fa-regular fa-clock"></i>
                            <span>Last saved: <?php echo $doc['updated_at'] ? htmlspecialchars(date('M d, g:i A', strtotime($doc['updated_at']))) : '—'; ?></span>
                        </div>
                        <div class="meta-actions">
                            <select name="visibility" class="visibility-select">
                                <option value="private" <?php echo ($doc['visibility'] === 'private') ? 'selected' : ''; ?>>Private</option>
                                <option value="institution" <?php echo ($doc['visibility'] === 'institution') ? 'selected' : ''; ?>>Institution</option>
                                <option value="public" <?php echo ($doc['visibility'] === 'public') ? 'selected' : ''; ?>>Public</option>
                            </select>
                            <button type="submit" class="btn btn-primary save-btn">
                                <i class="fa-solid fa-floppy-disk"></i> Save
                            </button>
                        </div>
                    </div>

                <!-- Editor Body -->
                <div class="editor-main editor-main-transparent">
                    <div class="editor-wrapper">
                        <!-- Custom Handmade Toolbar -->
                        <div class="handmade-toolbar">
                            <button type="button" class="toolbar-btn" onclick="formatDoc('bold')" title="Bold"><i class="fa-solid fa-bold"></i></button>
                            <button type="button" class="toolbar-btn" onclick="formatDoc('italic')" title="Italic"><i class="fa-solid fa-italic"></i></button>
                            <button type="button" class="toolbar-btn" onclick="formatDoc('underline')" title="Underline"><i class="fa-solid fa-underline"></i></button>
                            <span class="toolbar-divider"></span>
                            <button type="button" class="toolbar-btn" onclick="formatDoc('formatBlock', 'H1')" title="Heading 1"><i class="fa-solid fa-heading"></i>1</button>
                            <button type="button" class="toolbar-btn" onclick="formatDoc('formatBlock', 'H2')" title="Heading 2"><i class="fa-solid fa-heading"></i>2</button>
                            <button type="button" class="toolbar-btn" onclick="formatDoc('formatBlock', 'P')" title="Paragraph"><i class="fa-solid fa-paragraph"></i></button>
                            <span class="toolbar-divider"></span>
                            <button type="button" class="toolbar-btn" onclick="formatDoc('insertUnorderedList')" title="Bullet List"><i class="fa-solid fa-list-ul"></i></button>
                            <button type="button" class="toolbar-btn" onclick="formatDoc('insertOrderedList')" title="Numbered List"><i class="fa-solid fa-list-ol"></i></button>
                            <span class="toolbar-divider"></span>
                            <button type="button" class="toolbar-btn" onclick="formatDoc('justifyLeft')" title="Align Left"><i class="fa-solid fa-align-left"></i></button>
                            <button type="button" class="toolbar-btn" onclick="formatDoc('justifyCenter')" title="Align Center"><i class="fa-solid fa-align-center"></i></button>
                        </div>
                        
                        <!-- Contenteditable Canvas -->
                        <div id="handmade-editor" class="handmade-editor" contenteditable="true"><?php
                            echo $doc['content'] !== '' ? $doc['content'] : "<h1>Introduction</h1><p>Write your research notes, methodology, and draft sections here.</p>";
                        ?></div>
                    </div>
                    <input type="hidden" name="content" id="hidden-content">
                </div>
                </form>
            </div>

            <!-- Right: Sidebar -->
            <aside class="editor-sidebar">
                <section>
                    <h4>DOCUMENT INFO</h4>
                    <?php if ($doc['id'] > 0 && $doc['project_id'] > 0): ?>
                        <div class="mb-1-5-center">
                            <form action="../actions/publish_preprint.php" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="document_id" value="<?php echo $doc['id']; ?>">
                                <button type="submit" class="btn btn-primary btn-black-full"><i class="fa-solid fa-rocket"></i> Publish as Preprint</button>
                            </form>
                        </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="info-label">Project ID</span>
                        <span class="info-value"><?php echo $doc['project_id'] ? (int)$doc['project_id'] : '—'; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Visibility</span>
                        <span class="info-value info-value-badge"><?php echo strtoupper(htmlspecialchars((string)$doc['visibility'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Last Saved</span>
                        <span class="info-value"><?php echo $doc['updated_at'] ? htmlspecialchars(date('M d, g:i A', strtotime($doc['updated_at']))) : '—'; ?></span>
                    </div>
                </section>

                <section class="version-section">
                    <h4>TEAM MEMBERS <a href="#" class="invite-link">Invite</a></h4>
                    <div class="team-list">
                        <?php if (empty($team_members)): ?>
                            <p class="empty-text-sm">No project selected.</p>
                        <?php else: ?>
                            <?php foreach($team_members as $tm): ?>
                            <div class="team-member">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($tm['full_name']); ?>&background=random&color=fff" class="team-member-img">
                                <div>
                                    <div class="team-member-name"><?php echo htmlspecialchars($tm['full_name']); ?></div>
                                    <div class="team-member-role"><?php echo ucfirst(htmlspecialchars($tm['role'])); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="version-history">
                    <h4>VERSION HISTORY</h4>
                    <?php if (empty($versions)): ?>
                        <p class="empty-text-sm">No version history yet.</p>
                    <?php else: ?>
                        <?php $first = true; foreach($versions as $v): ?>
                        <div class="version-item <?php echo $first ? 'active' : ''; ?>">
                            <div class="version-info">
                                <h5><?php echo htmlspecialchars($v['version_name']); ?></h5>
                                <p><?php echo date('M d, g:i A', strtotime($v['created_at'])); ?> by <?php echo htmlspecialchars($v['full_name'] ?? 'Unknown'); ?></p>
                            </div>
                        </div>
                        <?php $first = false; endforeach; ?>
                    <?php endif; ?>
                    
                    <button class="btn btn-outline view-all-btn">View All Versions</button>
                </section>
            </aside>
        </div>
    </main>

    <!-- Custom Handmade Editor Logic -->
    <script>
        function formatDoc(cmd, value=null) {
            document.execCommand(cmd, false, value);
            document.getElementById('handmade-editor').focus();
        }

        var isDirty = false;
        var editor = document.getElementById('handmade-editor');
        
        editor.addEventListener('input', function() {
            isDirty = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (isDirty) {
                var msg = 'You have unsaved changes. Are you sure you want to leave?';
                e.returnValue = msg;
                return msg;
            }
        });

        // Sync HTML content to hidden input before form submit
        var form = document.querySelector('form[action="../actions/save_document.php"]');
        var hiddenContent = document.querySelector('#hidden-content');
        
        if (form) {
            form.addEventListener('submit', function() {
                isDirty = false;
                hiddenContent.value = editor.innerHTML;
                return true;
            });
        }
    </script>
</body>
</html>
