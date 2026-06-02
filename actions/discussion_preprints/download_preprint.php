<?php
require_once(__DIR__ . '/../../includes/auth_check.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Fetch file path
    $res = db_query("SELECT file_path FROM preprints WHERE id = ?", [$id], "i");
    
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $file_path = __DIR__ . '/../../' . $row['file_path'];
        
        if (file_exists($file_path)) {
            // Increment downloads using db_query helper
            db_query("UPDATE preprints SET downloads_count = downloads_count + 1 WHERE id = ?", [$id], "i");
            
            // Force download
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="'.basename($file_path).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        }
    }
}
echo "File not found.";
?>
