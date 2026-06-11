<?php
require_once(__DIR__ . '/../../includes/auth_check.php');
require_once(__DIR__ . '/../../includes/csrf.php');
require_once(__DIR__ . '/../../includes/preprint_moderation.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    csrf_validate_or_die();
    ensure_preprint_moderation_schema();
    $title = $_POST['title'];
    $abstract = $_POST['abstract'];
    $keywords = $_POST['keywords'];
    $visibility = $_POST['visibility'];
    $project_id = !empty($_POST['project_id']) ? intval($_POST['project_id']) : NULL;
    $license_type = isset($_POST['license_type']) ? $_POST['license_type'] : 'All Rights Reserved';
    $accepted_copyright = isset($_POST['accepted_copyright']) ? 1 : 0;
    $user_id = (int)$_SESSION['user_id'];
    
    // 1. Setup the upload directory
    $upload_dir = __DIR__ . '/../../uploads/preprints/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // 2. Validate file upload
    if (!isset($_FILES['preprint_file']) || $_FILES['preprint_file']['error'] !== 0) {
        $_SESSION['error'] = "No file uploaded or file error occurred.";
        header("Location: ../dashboard/preprints.php");
        exit();
    }
    
    // 3. Process the file
    $file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['preprint_file']['name']));
    $relative_file_path = 'uploads/preprints/' . $file_name;
    $destination_path = $upload_dir . $file_name;
    
    if (!move_uploaded_file($_FILES['preprint_file']['tmp_name'], $destination_path)) {
        $_SESSION['error'] = "Error moving uploaded file to destination.";
        header("Location: ../dashboard/preprints.php");
        exit();
    }
    
    // 4. Insert the preprint details into the database
    $insertPreprintQuery = "
        INSERT INTO preprints 
        (title, abstract, keywords, file_path, author_id, project_id, visibility, license_type, accepted_copyright, moderation_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ";
    
    $insertResult = db_query(
        $insertPreprintQuery, 
        [$title, $abstract, $keywords, $relative_file_path, $user_id, $project_id, $visibility, $license_type, $accepted_copyright], 
        "ssssisssi"
    );
    
    if ($insertResult) {
        // 5. REPUTATION POINTS: Award +100 points to the author for uploading their research preprint
        $updatePointsQuery = "UPDATE users SET points = points + 100 WHERE id = ?";
        db_query($updatePointsQuery, [$user_id], "i");
        
        $_SESSION['success'] = "Preprint uploaded successfully and is now pending admin review.";
        header("Location: ../dashboard/preprints.php");
        exit();
    } else {
        $_SESSION['error'] = "Database error inserting preprint.";
        header("Location: ../dashboard/preprints.php");
        exit();
    }
}
?>
