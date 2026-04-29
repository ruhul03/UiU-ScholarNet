<?php
require_once('../includes/auth_check.php');
require_once('../includes/csrf.php');

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['research_file'])) {
    csrf_validate_or_die();

    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file = $_FILES['research_file'];
    $original_name = (string)($file['name'] ?? '');
    $safe_base = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($original_name));
    $file_name = time() . '_' . $safe_base;
    $target_path = $upload_dir . $file_name;
    $db_path = 'uploads/' . $file_name;
    $file_size_bytes = $file['size'];

    // Human readable size
    if ($file_size_bytes >= 1073741824) {
        $file_size = round($file_size_bytes / 1073741824, 1) . ' GB';
    } elseif ($file_size_bytes >= 1048576) {
        $file_size = round($file_size_bytes / 1048576, 1) . ' MB';
    } else {
        $file_size = round($file_size_bytes / 1024, 1) . ' KB';
    }

    $title = trim((string)($_POST['title'] ?? $original_name));
    $resource_type = trim((string)($_POST['resource_type'] ?? 'Other'));
    $category = trim((string)($_POST['category'] ?? 'General'));

    // Allowed extensions
    $allowed = ['pdf', 'docx', 'csv', 'png', 'jpg', 'jpeg', 'zip', 'xlsx', 'txt'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $_SESSION['error'] = "Upload failed (error code " . (int)$file['error'] . ").";
    } elseif (!in_array($ext, $allowed, true)) {
        $_SESSION['error'] = "File type .$ext is not allowed.";
    } elseif ($file_size_bytes > 52428800) { // 50MB limit
        $_SESSION['error'] = "File too large. Max 50MB.";
    } elseif (move_uploaded_file($file['tmp_name'], $target_path)) {
        $stmt = $conn->prepare("INSERT INTO resources (user_id, title, resource_type, file_path, file_size, category) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $user_id, $title, $resource_type, $db_path, $file_size, $category);
        if ($stmt->execute()) {
            $_SESSION['success'] = "File uploaded successfully!";
        } else {
            $_SESSION['error'] = "Database error.";
        }
    } else {
        $_SESSION['error'] = "Failed to upload file.";
    }

    header("Location: file_upload.php");
    exit();
}

// Fetch uploaded files
$files_stmt = $conn->prepare("SELECT * FROM resources WHERE user_id = ? ORDER BY created_at DESC");
$files_stmt->bind_param("i", $user_id);
$files_stmt->execute();
$files_result = $files_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager | UIU ScholarNet</title>
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
        <header class="dash-header" style="margin-bottom: 2rem;">
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass" style="opacity: 0.3;"></i>
                <input type="text" placeholder="Search archive...">
            </div>
            <div style="display: flex; gap: 1.5rem; align-items: center;">
                <i class="fa-regular fa-bell" style="font-size: 1.2rem; opacity: 0.5;"></i>
                <span style="font-weight: 700; font-size: 0.85rem;">Workspace <i class="fa-solid fa-chevron-down" style="font-size: 0.6rem;"></i></span>
            </div>
        </header>

        <section style="margin-bottom: 4rem;">
            <h1 style="font-size: 3.5rem; margin-bottom: 0.5rem;">File Manager</h1>
            <p style="opacity: 0.5; max-width: 700px; font-size: 1.1rem; margin-bottom: 4rem;">Manage your research assets, datasets, and collaborative publications in a secure archival environment.</p>

            <?php if(isset($_SESSION['success'])): ?>
                <div style="background: #e8f5e9; color: #2e7d32; padding: 1rem 1.5rem; border-radius: 6px; margin-bottom: 2rem; font-size: 0.85rem; font-weight: 600;">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['error'])): ?>
                <div style="background: #fdecea; color: #d32f2f; padding: 1rem 1.5rem; border-radius: 6px; margin-bottom: 2rem; font-size: 0.85rem; font-weight: 600;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Upload Zone -->
            <form action="" method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="upload-zone" id="dropZone" onclick="document.getElementById('fileInput').click();">
                    <div style="width: 70px; height: 70px; background: var(--secondary-color); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem; color: white; font-size: 1.5rem;">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;">Upload research materials</h3>
                    <p style="opacity: 0.5; margin-bottom: 2rem;">Drag and drop your files here, or click to browse.<br>Supported formats: PDF, DOCX, CSV, PNG, JPG (Max 50MB).</p>
                    <input type="file" name="research_file" id="fileInput" style="display: none;" required onchange="showFilePreview(this)">
                    <input type="hidden" name="title" id="fileTitleInput">
                    <input type="hidden" name="resource_type" id="fileTypeInput" value="Other">
                    <button type="button" class="btn btn-primary" style="background-color: var(--secondary-color); color: var(--primary-color);" onclick="event.stopPropagation(); document.getElementById('fileInput').click();">
                        <i class="fa-solid fa-plus"></i> SELECT FILES
                    </button>
                </div>
                <div id="filePreview" style="display: none; background: #fdfcf8; border: 1px solid #eee; padding: 1.5rem 2rem; border-radius: 8px; margin-top: -2rem; margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 700;" id="previewName"></div>
                            <div style="font-size: 0.75rem; opacity: 0.5;" id="previewSize"></div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="background-color: var(--secondary-color); color: var(--primary-color);">
                            <i class="fa-solid fa-cloud-arrow-up"></i> UPLOAD NOW
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <!-- Recent Deposits -->
        <section>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div>
                    <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">Recent Deposits</h2>
                    <p style="opacity: 0.5; font-size: 0.85rem;">Total <?php echo (int)$files_result->num_rows; ?> files organized by modification date</p>
                </div>
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <span style="font-size: 0.85rem; font-weight: 700; opacity: 0.5;"><i class="fa-solid fa-filter"></i> Filter</span>
                    <div style="width: 35px; height: 35px; background: #f5f5f5; border-radius: 6px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                        <i class="fa-solid fa-table-cells-large"></i>
                    </div>
                </div>
            </div>

            <div class="file-grid">
                <?php while($file = $files_result->fetch_assoc()): ?>
                <div class="file-card">
                    <div class="file-card-icon" style="background: <?php
                        switch($file['resource_type']) {
                            case 'PDF': echo '#fdecea'; break;
                            case 'CSV': case 'Dataset': echo '#e8f5e9'; break;
                            case 'Image': echo '#e3f2fd'; break;
                            default: echo '#f5f5f5';
                        }
                    ?>; color: <?php
                        switch($file['resource_type']) {
                            case 'PDF': echo '#c62828'; break;
                            case 'CSV': case 'Dataset': echo '#2e7d32'; break;
                            case 'Image': echo '#1565c0'; break;
                            default: echo '#555';
                        }
                    ?>;">
                        <?php
                        $icon = 'fa-file';
                        switch($file['resource_type']) {
                            case 'PDF': $icon = 'fa-file-pdf'; break;
                            case 'CSV': case 'Dataset': $icon = 'fa-table'; break;
                            case 'Image': $icon = 'fa-image'; break;
                            case 'Archive': $icon = 'fa-file-zipper'; break;
                            default: $icon = 'fa-file-lines';
                        }
                        ?>
                        <i class="fa-solid <?php echo $icon; ?>"></i>
                    </div>
                    <h4 style="font-size: 0.85rem; margin-bottom: 0.3rem;"><?php echo htmlspecialchars($file['title']); ?></h4>
                    <p style="font-size: 0.7rem; opacity: 0.4; margin-bottom: 1.5rem;"><?php echo $file['file_size']; ?> • Modified <?php echo date('M d', strtotime($file['created_at'])); ?></p>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="file-tag"><?php echo strtoupper($file['category']); ?></span>
                        <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" style="font-size: 0.75rem; font-weight: 700; color: var(--secondary-color);">
                            <i class="fa-solid fa-download"></i> Download
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>

                <!-- Add New File Card -->
                <div class="file-card" style="border-style: dashed; display: flex; align-items: center; justify-content: center; cursor: pointer; opacity: 0.5;" onclick="document.getElementById('fileInput').click();">
                    <div style="text-align: center;">
                        <i class="fa-regular fa-file" style="font-size: 2rem; margin-bottom: 0.5rem; display: block;"></i>
                        <span style="font-size: 0.75rem; font-weight: 700;">Add new file</span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        function showFilePreview(input) {
            if (input.files.length > 0) {
                const file = input.files[0];
                document.getElementById('previewName').textContent = file.name;
                document.getElementById('fileTitleInput').value = file.name;
                
                // Determine type from extension
                const ext = file.name.split('.').pop().toLowerCase();
                const typeMap = { pdf: 'PDF', csv: 'CSV', xlsx: 'Dataset', docx: 'Report', png: 'Image', jpg: 'Image', jpeg: 'Image', zip: 'Archive' };
                document.getElementById('fileTypeInput').value = typeMap[ext] || 'Other';
                
                // Format size
                const bytes = file.size;
                let size = '';
                if (bytes >= 1073741824) size = (bytes / 1073741824).toFixed(1) + ' GB';
                else if (bytes >= 1048576) size = (bytes / 1048576).toFixed(1) + ' MB';
                else size = (bytes / 1024).toFixed(1) + ' KB';
                document.getElementById('previewSize').textContent = size;
                
                document.getElementById('filePreview').style.display = 'block';
            }
        }

        // Drag and drop
        const dropZone = document.getElementById('dropZone');
        ['dragenter', 'dragover'].forEach(e => {
            dropZone.addEventListener(e, (ev) => {
                ev.preventDefault();
                dropZone.style.borderColor = '#c5a022';
                dropZone.style.background = '#fffdf5';
            });
        });
        ['dragleave', 'drop'].forEach(e => {
            dropZone.addEventListener(e, (ev) => {
                ev.preventDefault();
                dropZone.style.borderColor = '#ddd';
                dropZone.style.background = '#fdfcf8';
            });
        });
        dropZone.addEventListener('drop', (ev) => {
            ev.preventDefault();
            const fileInput = document.getElementById('fileInput');
            fileInput.files = ev.dataTransfer.files;
            showFilePreview(fileInput);
        });
    </script>

</body>
</html>
