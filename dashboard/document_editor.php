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
        SELECT d.*, p.status as project_status, p.creator_id
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

$is_locked = false;
$locked_by_name = null;
$is_archived = false;

if ($doc['id'] > 0) {
    if (isset($doc['project_status']) && $doc['project_status'] === 'completed' && $doc['creator_id'] != $user_id) {
        $is_locked = true;
        $is_archived = true;
        $locked_by_name = "Archive System";
    } else {
        $locked_by = $doc['locked_by'] ?? null;
        $locked_at = $doc['locked_at'] ?? null;
        if ($locked_by && $locked_by != $user_id) {
            if (time() - strtotime($locked_at) < 300) {
                $is_locked = true;
                $uRes = db_query("SELECT full_name FROM users WHERE id = ?", [$locked_by], "i");
                if ($uRes && $u = $uRes->fetch_assoc()) {
                    $locked_by_name = $u['full_name'];
                }
            }
        }
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
    <link rel="stylesheet" href="../assets/css/editor.css">
    <link rel="stylesheet" href="../assets/css/document_editor.css">
    <!-- Quill CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .ql-container { font-family: 'Inter', sans-serif; font-size: 16px; border: none !important; }
        .ql-toolbar { border: none !important; border-bottom: 1px solid rgba(0,0,0,0.1) !important; background: #fff; }
        .ql-snow .ql-stroke { stroke: #444; }
        .ql-snow .ql-fill, .ql-snow .ql-stroke.ql-fill { fill: #444; }
        .ql-snow .ql-picker { color: #444; }
        
        .main-content {
            max-width: 100% !important;
            padding-right: 3rem;
        }
        
        .editor-layout {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
        }
        
        .form-row-meta .meta-actions {
            flex-wrap: wrap;
            justify-content: flex-end;
        }
    </style>
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <?php include('../includes/header.php'); ?>
        <?php include('../includes/alerts.php'); ?>

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

                <?php if($is_locked && !$is_archived): ?>
                    <div class="alert-error-editor">
                        <i class="fa-solid fa-lock"></i> <strong>Read-Only Mode:</strong> Document is currently locked by <?php echo htmlspecialchars($locked_by_name); ?>.
                    </div>
                <?php endif; ?>

                <?php if($is_archived): ?>
                    <div class="alert-error-editor">
                        <i class="fa-solid fa-box-archive"></i> <strong>Archived Project:</strong> This project is completed. Documents are strictly read-only for all members except the team leader.
                    </div>
                <?php endif; ?>

                <form action="../actions/save_document.php" method="POST" id="doc-form">
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
                            <select name="visibility" class="visibility-select" <?php echo $is_locked ? 'disabled' : ''; ?>>
                                <option value="private" <?php echo ($doc['visibility'] === 'private') ? 'selected' : ''; ?>>Private</option>
                                <option value="institution" <?php echo ($doc['visibility'] === 'institution') ? 'selected' : ''; ?>>Institution</option>
                                <option value="public" <?php echo ($doc['visibility'] === 'public') ? 'selected' : ''; ?>>Public</option>
                            </select>
                            <input type="text" name="commit_message" placeholder="Save message (optional)" class="input-editor" style="flex: 1; min-width: 250px; padding: 0.6rem 0.8rem; font-size: 0.9rem; font-weight: 500;" <?php echo $is_locked ? 'disabled' : ''; ?>>
                            <button type="submit" class="btn btn-primary save-btn" <?php echo $is_locked ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                                <i class="fa-solid fa-floppy-disk"></i> Save
                            </button>
                        </div>
                    </div>

                <!-- Editor Body -->
                <div class="editor-main editor-main-transparent">
                    <div class="editor-wrapper" style="padding: 0;">
                        <!-- Quill Editor -->
                        <div id="quill-editor" style="min-height: 500px; padding: 2rem;">
                            <?php echo $doc['content'] !== '' ? $doc['content'] : "<h1>Introduction</h1><p>Write your research notes, methodology, and draft sections here.</p>"; ?>
                        </div>
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
                            <div class="version-info" style="width: 100%;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <h5><?php echo htmlspecialchars($v['version_name']); ?></h5>
                                    <?php if (!$first && !$is_locked && !$is_archived): ?>
                                        <form action="../actions/restore_version.php" method="POST" style="margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="version_id" value="<?php echo $v['id']; ?>">
                                            <button type="submit" class="btn btn-outline" style="padding: 0.2rem 0.5rem; font-size: 0.75rem;"><i class="fa-solid fa-clock-rotate-left"></i> Restore</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                                <p><?php echo date('M d, g:i A', strtotime($v['created_at'])); ?> by <?php echo htmlspecialchars($v['full_name'] ?? 'Unknown'); ?></p>
                                <?php if (!empty($v['commit_message'])): ?>
                                    <p style="font-style: italic; opacity: 0.8; font-size: 0.8rem; margin-top: 0.3rem;">"<?php echo htmlspecialchars($v['commit_message']); ?>"</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php $first = false; endforeach; ?>
                    <?php endif; ?>
                    
                    <button class="btn btn-outline view-all-btn">View All Versions</button>
                </section>
            </aside>
        </div>
    </main>

    <!-- Quill JS -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        var isLocked = <?php echo $is_locked ? 'true' : 'false'; ?>;
        var docId = <?php echo (int)$doc['id']; ?>;
        var csrfToken = "<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>";

        var quill = new Quill('#quill-editor', {
            theme: 'snow',
            readOnly: isLocked,
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'align': [] }],
                    ['clean']
                ]
            }
        });

        var isDirty = false;
        quill.on('text-change', function() {
            if (!isLocked) isDirty = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (isDirty && !isLocked) {
                var msg = 'You have unsaved changes. Are you sure you want to leave?';
                e.returnValue = msg;
                return msg;
            }
        });

        // Sync HTML content to hidden input before form submit
        var form = document.getElementById('doc-form');
        var hiddenContent = document.querySelector('#hidden-content');
        
        if (form && !isLocked) {
            form.addEventListener('submit', function() {
                isDirty = false;
                hiddenContent.value = quill.root.innerHTML;
                return true;
            });
        }

        // Lock heartbeat and Auto-save
        if (docId > 0 && !isLocked) {
            // Lock heartbeat every 2 minutes
            setInterval(function() {
                var fd = new FormData();
                fd.append('action', 'renew');
                fd.append('document_id', docId);
                fetch('../actions/lock_document.php', { method: 'POST', body: fd });
            }, 120000);

            // Auto-save every 30 seconds if dirty
            setInterval(function() {
                if (isDirty) {
                    var fd = new FormData();
                    fd.append('document_id', docId);
                    fd.append('content', quill.root.innerHTML);
                    fetch('../actions/autosave_document.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
                        if (data.success) {
                            isDirty = false; // reset dirty since autosave worked
                            console.log('Autosaved');
                        }
                    });
                }
            }, 30000);

            // Release lock when closing page
            window.addEventListener('unload', function() {
                var fd = new FormData();
                fd.append('action', 'release');
                fd.append('document_id', docId);
                navigator.sendBeacon('../actions/lock_document.php', fd);
            });
        }
    </script>
</body>
</html>
