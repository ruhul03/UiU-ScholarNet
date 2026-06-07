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
$files_result = db_query("SELECT * FROM resources WHERE user_id = ? ORDER BY created_at DESC", [$user_id], "i");

// Fetch notification counts
$pending_tasks = (int)(db_query("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ? AND status != 'done'", [$user_id], "i")->fetch_assoc()['total'] ?? 0);
$collab_requests = (int)(db_query("SELECT COUNT(*) as total FROM collaboration_applications ca JOIN collaboration_posts cp ON ca.post_id = cp.id WHERE cp.user_id = ? AND ca.status = 'pending'", [$user_id], "i")->fetch_assoc()['total'] ?? 0);
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
        <?php include('../includes/header.php'); ?>

        <section class="upload-section">
            <h1 class="filemanager-title">File Manager</h1>
            <p class="filemanager-desc">Manage your research assets, datasets, and collaborative publications in a secure archival environment.</p>

            <?php include('../includes/alerts.php'); ?>

            <!-- Upload Zone -->
            <form action="" method="POST" enctype="multipart/form-data" id="uploadForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="upload-zone" id="dropZone">
                    <div class="upload-icon-box">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <h3 class="upload-title">Upload research materials</h3>
                    <p class="upload-hint">Drag and drop your files here, or click to browse.<br>Supported formats: PDF, DOCX, CSV, PNG, JPG (Max 50MB).</p>
                    <input type="file" name="research_file" id="fileInput" class="hidden-input" required>
                    <input type="hidden" name="title" id="fileTitleInput">
                    <input type="hidden" name="resource_type" id="fileTypeInput" value="Other">
                    <button type="button" class="btn btn-primary upload-btn-primary">
                        <i class="fa-solid fa-plus"></i> SELECT FILES
                    </button>
                </div>
                <div id="filePreview" class="file-preview">
                    <div class="file-preview-row">
                        <div class="flex-1">
                            <div class="file-info" id="previewName"></div>
                            <div class="file-size" id="previewSize"></div>
                        </div>
                        <div class="category-select-wrapper mr-1-5">
                            <select name="category" class="filter-select filter-select-inline">
                                <option value="General">Select Category</option>
                                <option value="Research Paper">Research Paper</option>
                                <option value="Thesis">Thesis</option>
                                <option value="Dataset">Dataset</option>
                                <option value="Presentation">Presentation</option>
                                <option value="Lecture Notes">Lecture Notes</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary upload-btn-primary">
                            <i class="fa-solid fa-cloud-arrow-up"></i> UPLOAD NOW
                        </button>
                    </div>
                </div>
            </form>
        </section>

        <!-- Recent Deposits -->
        <section>
            <div class="section-header">
                <div>
                    <h2 class="section-title">Recent Deposits</h2>
                    <p class="section-meta">Total <?php echo (int)$files_result->num_rows; ?> files organized by modification date</p>
                </div>
                <div class="filter-actions">
                    <span class="filter-btn"><i class="fa-solid fa-filter"></i> Filter</span>
                    <div class="view-toggle-btn">
                        <i class="fa-solid fa-table-cells-large"></i>
                    </div>
                </div>
            </div>

            <div class="file-grid">
                <?php while($file = $files_result->fetch_assoc()): ?>
                <div class="file-card">
                    <div class="file-card-icon file-icon-<?php
                        switch($file['resource_type']) {
                            case 'PDF': echo 'pdf'; break;
                            case 'CSV': case 'Dataset': echo 'dataset'; break;
                            case 'Image': echo 'image'; break;
                            default: echo 'default';
                        }
                    ?>">
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
                    <h4 class="file-card-title"><?php echo htmlspecialchars($file['title']); ?></h4>
                    <p class="file-card-meta"><?php echo $file['file_size']; ?> • Modified <?php echo date('M d', strtotime($file['created_at'])); ?></p>
                    <div class="file-card-footer">
                        <span class="file-tag"><?php echo strtoupper($file['category']); ?></span>
                        <div class="flex-gap-0-8-center">
                            <a href="../<?php echo htmlspecialchars($file['file_path']); ?>" class="download-link-sm">
                                <i class="fa-solid fa-download"></i> Download
                            </a>
                            <form action="../actions/delete_resource.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this resource? This action cannot be undone.');" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="resource_id" value="<?php echo $file['id']; ?>">
                                <button type="submit" class="btn-delete-icon" title="Delete Resource">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>

                <!-- Add New File Card -->
                <div class="file-card file-card-dashed" onclick="document.getElementById('fileInput').click();">
                    <div class="file-card-content">
                        <i class="fa-regular fa-file file-card-icon-lg"></i>
                        <span class="file-card-text">Add new file</span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="../assets/js/file_upload.js"></script>
</body>
</html>
