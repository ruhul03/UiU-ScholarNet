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

    // 5. Handle specific logic for 'student' roles
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'student') {
        if (!$supervisor_id) {
            $_SESSION['error'] = "Students must select a faculty supervisor.";
            header("Location: ../dashboard/projects.php");
            exit();
        }
        
        // Verify supervisor is a verified faculty member
        $supervisorCheckQuery = "SELECT id FROM users WHERE id = ? AND role = 'faculty' AND is_verified = 1";
        $supervisorCheckResult = db_query($supervisorCheckQuery, [$supervisor_id], "i");
        
        if (!$supervisorCheckResult || $supervisorCheckResult->num_rows === 0) {
            $_SESSION['error'] = "Invalid faculty supervisor selected.";
            header("Location: ../dashboard/projects.php");
            exit();
        }
    }
    
    // 6. Insert new project into the database
    $insertProjectQuery = "
        INSERT INTO projects (title, description, department, visibility, status, progress, creator_id, supervisor_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $projectInsertResult = db_query(
        $insertProjectQuery, 
        [$title, $description, $department, $visibility, $status, $progress, $user_id, $supervisor_id], 
        "sssssiii"
    );

    // Early return on database failure
    if (!$projectInsertResult) {
        $_SESSION['error'] = "Failed to create project.";
        header("Location: ../dashboard/projects.php");
        exit();
    }

    $project_id = $conn->insert_id;
    
    // 7. Notify Supervisor
    if ($supervisor_id) {
        $userDataResult = db_query("SELECT full_name FROM users WHERE id = ?", [$user_id], "i");
        $user_data = $userDataResult->fetch_assoc();
        
        $notificationTitle = "Supervision Request";
        $notificationMsg = $user_data['full_name'] . " has requested your supervision for the project: " . $title;
        send_notification($supervisor_id, $notificationTitle, $notificationMsg, "../dashboard/projects.php", "system");
    }
    
    // 8. Add creator as the initial 'owner' of the project
    $addOwnerQuery = "INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'owner')";
    db_query($addOwnerQuery, [$project_id, $user_id], "ii");
    
    // 9. Handle invited researchers
    if (isset($_POST['invited_users']) && is_array($_POST['invited_users'])) {
        foreach ($_POST['invited_users'] as $invited_user_id) {
            $invited_user_id = (int)$invited_user_id;
            
            // Do not invite the creator themselves
            if ($invited_user_id > 0 && $invited_user_id !== $user_id) {
                // Add member with 'pending' status
                $inviteMemberQuery = "
                    INSERT IGNORE INTO project_members (project_id, user_id, role, status) 
                    VALUES (?, ?, 'editor', 'pending')
                ";
                db_query($inviteMemberQuery, [$project_id, $invited_user_id], "ii");
                
                // Send Notification to the invited user
                $inviteMsg = "You have been invited to join the project: " . $title;
                send_notification($invited_user_id, "Project Invitation", $inviteMsg, "../dashboard/projects.php", "system");
            }
        }
    }

    // 10. Success Return
    $_SESSION['success'] = "Project created successfully!";
    header("Location: ../dashboard/projects.php");
    exit();
}
?>
