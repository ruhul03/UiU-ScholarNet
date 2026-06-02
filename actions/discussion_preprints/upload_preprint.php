<?php
require_once(__DIR__ . '/../../includes/auth_check.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $abstract = $_POST['abstract'];
    $keywords = $_POST['keywords'];
    $visibility = $_POST['visibility'];
    $project_id = !empty($_POST['project_id']) ? intval($_POST['project_id']) : NULL;
    $license_type = isset($_POST['license_type']) ? $_POST['license_type'] : 'All Rights Reserved';
    $accepted_copyright = isset($_POST['accepted_copyright']) ? 1 : 0;
    
    // File upload
    $upload_dir = __DIR__ . '/../../uploads/preprints/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (isset($_FILES['preprint_file']) && $_FILES['preprint_file']['error'] == 0) {
        $file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['preprint_file']['name']));
        $file_path = 'uploads/preprints/' . $file_name;
        $dest_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['preprint_file']['tmp_name'], $dest_path)) {
            // QUERY: Insert the preprint details (title, abstract, keywords, local file path) into preprints table
            $insert = db_query(
                "INSERT INTO preprints (title, abstract, keywords, file_path, author_id, project_id, visibility, license_type, accepted_copyright) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)", 
                [$title, $abstract, $keywords, $file_path, $user_id, $project_id, $visibility, $license_type, $accepted_copyright], 
                "ssssisssi"
            );
            
            if ($insert) {
                // REPUTATION POINTS: Award +100 points to the author for uploading their research preprint
                db_query("UPDATE users SET points = points + 100 WHERE id = ?", [$user_id], "i");
                
                header("Location: ../dashboard/preprints.php");
                exit();
            } else {
                echo "Error inserting preprint.";
            }
        } else {
            echo "Error uploading file.";
        }
    } else {
        echo "No file uploaded or file error.";
    }
}
?>
