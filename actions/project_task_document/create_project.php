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

    $user_id = (int)$_SESSION['user_id'];
    $title = trim((string)($_POST['title'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $department = trim((string)($_POST['department'] ?? ''));
    $visibility = (string)($_POST['visibility'] ?? 'public');
    $status = (string)($_POST['status'] ?? 'planning');
    $progress = isset($_POST['progress']) ? (int)$_POST['progress'] : 0;
    if ($progress < 0) $progress = 0;
    if ($progress > 100) $progress = 100;

    if ($title === '' || $department === '') {
        $_SESSION['error'] = "Project title and department are required.";
        header("Location: ../dashboard/projects.php");
        exit();
    }

    $allowed_visibility = ['public', 'institution', 'private'];
    if (!in_array($visibility, $allowed_visibility, true)) {
        $visibility = 'public';
    }
    $allowed_status = ['planning', 'active', 'review', 'completed'];
    if (!in_array($status, $allowed_status, true)) {
        $status = 'planning';
    }

    $supervisor_id = isset($_POST['supervisor_id']) && (int)$_POST['supervisor_id'] > 0 ? (int)$_POST['supervisor_id'] : null;

    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student') {
        if (!$supervisor_id) {
            $_SESSION['error'] = "Students must select a faculty supervisor.";
            header("Location: ../dashboard/projects.php");
            exit();
        }
        
        // Verify supervisor is a verified faculty member
        $sup_check = db_query("SELECT id FROM users WHERE id = ? AND role = 'faculty' AND is_verified = 1", [$supervisor_id], "i");
        if (!$sup_check || $sup_check->num_rows === 0) {
            $_SESSION['error'] = "Invalid faculty supervisor selected.";
            header("Location: ../dashboard/projects.php");
            exit();
        }
    }
    // Insert new project into the database
    $stmt = db_query("INSERT INTO projects (title, description, department, visibility, status, progress, creator_id, supervisor_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", [$title, $description, $department, $visibility, $status, $progress, $user_id, $supervisor_id], "sssssiii");

    if ($stmt) {
        $project_id = $conn->insert_id;
        
        // Notify Supervisor
        if ($supervisor_id) {
            $user_data = db_query("SELECT full_name FROM users WHERE id = ?", [$user_id], "i")->fetch_assoc();
            
            $sup_title = "Supervision Request";
            $sup_msg = $user_data['full_name'] . " has requested your supervision for the project: " . $title;
            db_query("INSERT INTO notifications (user_id, title, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())", [$supervisor_id, $sup_title, $sup_msg], "iss");
        }
        
        // Add creator as owner
        db_query("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'owner')", [$project_id, $user_id], "ii");
        
        // Handle invited researchers
        if (isset($_POST['invited_users']) && is_array($_POST['invited_users'])) {
            foreach ($_POST['invited_users'] as $inv_uid) {
                $inv_uid = (int)$inv_uid;
                if ($inv_uid > 0 && $inv_uid !== $user_id) {
                    // Add pending member
                    db_query("INSERT IGNORE INTO project_members (project_id, user_id, role, status) VALUES (?, ?, 'editor', 'pending')", [$project_id, $inv_uid], "ii");
                    
                    // Send Notification
                    $msg = "You have been invited to join the project: " . $title;
                    db_query("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'system', 'Project Invitation', ?, '../dashboard/projects.php')", [$inv_uid, $msg], "is");
                }
            }
        }

        $_SESSION['success'] = "Project created successfully!";
        header("Location: ../dashboard/projects.php");
    } else {
        $_SESSION['error'] = "Failed to create project.";
        header("Location: ../dashboard/projects.php");
    }
    exit();
}
?>
