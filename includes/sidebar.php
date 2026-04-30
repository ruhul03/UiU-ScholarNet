<!-- Sidebar Component -->
<aside class="sidebar">
    <div class="logo">UIU ScholarNet</div>
    <div class="sidebar-subtitle">RESEARCH & COLLABORATION</div>

    <nav class="sidebar-menu">
        <a href="index.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-house"></i> Dashboard
        </a>
        <a href="collaboration.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'collaboration.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-magnifying-glass"></i> Collaboration Finder
        </a>
        <a href="projects.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'projects.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-folder"></i> Projects
        </a>
        <a href="tasks.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'tasks.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-square-check"></i> Tasks
        </a>
        <a href="document_editor.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'document_editor.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-file-lines"></i> Document Editor
        </a>
        <a href="messages.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'messages.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-message"></i> Messages
        </a>
        <a href="file_upload.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'file_upload.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-paperclip"></i> File Upload
        </a>
        <a href="resources.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'resources.php') ? 'active' : ''; ?>">
            <i class="fa-solid fa-book"></i> Resource Hub
        </a>
        <a href="#" class="menu-item"><i class="fa-solid fa-award"></i> Reputation</a>
        <a href="#" class="menu-item"><i class="fa-solid fa-comments"></i> Research Discussion</a>
        <a href="#" class="menu-item"><i class="fa-solid fa-user"></i> My Profile</a>
    </nav>

    <div class="sidebar-footer">
        <a href="#" class="menu-item"><i class="fa-solid fa-gear"></i> Settings</a>
        <a href="../auth/logout.php" class="menu-item logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        
        <div class="user-profile-small">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_data['full_name']); ?>&background=0a1128&color=fff" alt="User">
            <div>
                <div class="user-name"><?php echo $user_data['full_name']; ?></div>
                <div class="user-role"><?php echo ucfirst($user_data['role']); ?></div>
            </div>
        </div>
    </div>
</aside>
