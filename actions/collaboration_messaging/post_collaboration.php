<?php
require_once(__DIR__ . '/../../includes/session.php');
start_secure_session();
require_once(__DIR__ . '/../../includes/db_connect.php');
require_once(__DIR__ . '/../../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    csrf_validate_or_die();

    $current_user_id = (int)$_SESSION['user_id'];
    $title = trim((string)($_POST['title'] ?? ''));
    $department = trim((string)($_POST['department'] ?? ''));
    $opportunity_type = trim((string)($_POST['opportunity_type'] ?? 'Research'));
    $skills_required = trim((string)($_POST['skills'] ?? ''));
    $slots_total = (int)($_POST['slots_total'] ?? 10);
    $description = trim((string)($_POST['description'] ?? ''));
    $project_id = isset($_POST['project_id']) && $_POST['project_id'] !== '' ? (int)$_POST['project_id'] : null;
    $status = 'open';

    // Early return for missing required fields
    if ($title === '' || $department === '' || $opportunity_type === '') {
        header("Location: ../dashboard/collaboration.php?error=1");
        exit();
    }

    if ($slots_total < 1) {
        $slots_total = 1;
    } elseif ($slots_total > 100) {
        $slots_total = 100;
    }

    // 1. Ensure required columns exist using direct query to avoid prepare statement errors with SHOW COLUMNS
    global $conn;
    $columnsToEnsure = [
        'opportunity_type' => "ALTER TABLE collaboration_posts ADD COLUMN opportunity_type VARCHAR(50) NOT NULL DEFAULT 'Research'",
        'status' => "ALTER TABLE collaboration_posts ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'open'",
        'slots_total' => "ALTER TABLE collaboration_posts ADD COLUMN slots_total INT NOT NULL DEFAULT 10",
        'project_id' => "ALTER TABLE collaboration_posts ADD COLUMN project_id INT NULL"
    ];

    foreach ($columnsToEnsure as $columnName => $alterSql) {
        $checkQuery = "SHOW COLUMNS FROM collaboration_posts LIKE '" . $conn->real_escape_string($columnName) . "'";
        $checkResult = $conn->query($checkQuery);
        if ($checkResult && $checkResult->num_rows === 0) {
            $conn->query($alterSql);
        }
    }

    // 2. Insert new collaboration post
    $insertPostQuery = "
        INSERT INTO collaboration_posts 
        (user_id, title, department, description, skills_required, opportunity_type, status, slots_total, project_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $insertResult = db_query(
        $insertPostQuery,
        [$current_user_id, $title, $department, $description, $skills_required, $opportunity_type, $status, $slots_total, $project_id],
        "issssssii"
    );

    if ($insertResult) {
        // REPUTATION POINTS: Award +20 points for posting a collaboration invitation
        $awardPointsQuery = "UPDATE users SET points = points + 20 WHERE id = ?";
        db_query($awardPointsQuery, [$current_user_id], "i");
        
        $_SESSION['success'] = "Collaboration request posted successfully!";
    } else {
        $_SESSION['error'] = "Database error. Please try again.";
    }
    
    header("Location: ../dashboard/collaboration.php");
    exit();
}
?>
