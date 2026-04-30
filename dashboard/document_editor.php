<?php
require_once('../includes/auth_check.php');
require_once('../includes/csrf.php');

$document_id = isset($_GET['document_id']) ? (int)$_GET['document_id'] : 0;

// Projects for dropdown (only user's own projects for now)
$pstmt = $conn->prepare("SELECT id, title FROM projects WHERE creator_id = ? ORDER BY created_at DESC");
$pstmt->bind_param("i", $user_id);
$pstmt->execute();
$projects_result = $pstmt->get_result();

$doc = [
    'id' => 0,
    'project_id' => 0,
    'title' => 'Untitled Document',
    'content' => '',
    'visibility' => 'private',
    'updated_at' => null,
];

if ($document_id > 0) {
    $dstmt = $conn->prepare("
        SELECT d.*
        FROM documents d
        JOIN projects p ON p.id = d.project_id
        WHERE d.id = ? AND p.creator_id = ?
        LIMIT 1
    ");
    $dstmt->bind_param("ii", $document_id, $user_id);
    $dstmt->execute();
    $dres = $dstmt->get_result();
    $found = $dres ? $dres->fetch_assoc() : null;
    if ($found) {
        $doc = array_merge($doc, $found);
        $doc['id'] = (int)$doc['id'];
        $doc['project_id'] = (int)$doc['project_id'];
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
</head>
<body class="dashboard-page">

    <?php include('../includes/sidebar.php'); ?>

    <!-- Main Content -->
    <main class="main-content">
        <header class="dash-header dash-header-editor">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" placeholder="Search research papers, collaborators...">
            </div>
            <div class="header-actions">
                <i class="fa-regular fa-bell header-icon"></i>
                <div class="user-badge">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name']); ?>&background=0a1128&color=fff" class="user-badge-img">
                    <span class="user-badge-name"><?php echo $user_data['full_name']; ?></span>
                </div>
            </div>
        </header>

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

                <!-- Toolbar -->
                <div class="editor-toolbar">
                    <div class="toolbar-btn"><strong>B</strong></div>
                    <div class="toolbar-btn"><em>I</em></div>
                    <div class="toolbar-btn"><u>U</u></div>
                    <div style="width: 1px; height: 20px; background: #eee; margin: 0 0.5rem; align-self: center;"></div>
                    <div class="toolbar-btn"><i class="fa-solid fa-align-left"></i></div>
                    <div class="toolbar-btn"><i class="fa-solid fa-align-center"></i></div>
                    <div class="toolbar-btn"><i class="fa-solid fa-list-ul"></i></div>
                    <div style="width: 1px; height: 20px; background: #eee; margin: 0 0.5rem; align-self: center;"></div>
                    <div class="toolbar-btn"><i class="fa-solid fa-link"></i></div>
                    <div class="toolbar-btn"><i class="fa-regular fa-image"></i></div>
                    <div class="toolbar-btn"><i class="fa-solid fa-ellipsis-vertical"></i></div>
                </div>

                <!-- Editor Body -->
                <div class="editor-main">
                    <label class="editor-label">CONTENT</label>
                    <textarea name="content" rows="18" class="textarea-editor"><?php
                        echo htmlspecialchars($doc['content'] !== '' ? (string)$doc['content'] : "## Introduction\n\nWrite your research notes, methodology, and draft sections here.\n\n## References\n\n- Add citations and links...");
                    ?></textarea>
                </div>
                </form>
            </div>

            <!-- Right: Sidebar -->
            <aside class="editor-sidebar">
                <section>
                    <h4>DOCUMENT INFO</h4>
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
                        <div class="team-member">
                            <img src="https://ui-avatars.com/api/?name=Sabbir+Ahmed&background=0a1128&color=fff" class="team-member-img">
                            <div>
                                <div class="team-member-name">Sabbir Ahmed</div>
                                <div class="team-member-role">Owner</div>
                            </div>
                        </div>
                        <div class="team-member">
                            <img src="https://ui-avatars.com/api/?name=Dr+Mithila&background=000&color=fff" class="team-member-img">
                            <div>
                                <div class="team-member-name">Dr. Mithila</div>
                                <div class="team-member-role">Editor</div>
                            </div>
                        </div>
                        <div class="team-member">
                            <img src="https://ui-avatars.com/api/?name=K+H+Khan&background=ccc&color=fff" class="team-member-img">
                            <div>
                                <div class="team-member-name">K. H. Khan</div>
                                <div class="team-member-role">Viewer</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="version-history">
                    <h4>VERSION HISTORY</h4>
                    <div class="version-item active">
                        <div class="version-info">
                            <h5>v2.4 Final Submission Draft</h5>
                            <p>Today, 12:10 PM by Aryan</p>
                        </div>
                    </div>
                    <div class="version-item">
                        <div class="version-info">
                            <h5>v2.3 Added Bibliography</h5>
                            <p>Yesterday, 4:45 PM by Elena</p>
                        </div>
                    </div>
                    <div class="version-item">
                        <div class="version-info">
                            <h5>v2.1 Structural Changes</h5>
                            <p>Oct 12, 9:00 AM by Julian</p>
                        </div>
                    </div>
                    
                    <button class="btn btn-outline view-all-btn">View All Versions</button>
                </section>
            </aside>
        </div>
    </main>

</body>
</html>
