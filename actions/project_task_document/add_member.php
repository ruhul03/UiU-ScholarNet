<?php
require_once('../../includes/session.php');
start_secure_session();
require_once('../../includes/db_connect.php');
require_once('../../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../../auth/login.php");
        exit();
    }
    
    csrf_validate_or_die();
    
    $user_id = (int)$_SESSION['user_id'];
    $project_id = (int)($_POST['project_id'] ?? 0);
    
    if ($project_id <= 0) {
        $_SESSION['error'] = "Invalid project.";
        header("Location: ../../dashboard/projects.php");
        exit();
    }
    
    // Check if the current user is the owner/creator of the project
    $creatorCheck = db_query("SELECT id, title FROM projects WHERE id = ? AND creator_id = ?", [$project_id, $user_id], "ii");
    
    if ($creatorCheck->num_rows === 0) {
        $_SESSION['error'] = "You do not have permission to add members to this project.";
        header("Location: ../../dashboard/edit_project.php?id=" . $project_id);
        exit();
    }
    
    $projectData = $creatorCheck->fetch_assoc();
    $projectTitle = $projectData['title'];
    
    // Handle invited researchers
    if (isset($_POST['invited_users']) && is_array($_POST['invited_users'])) {
        $added_count = 0;
        foreach ($_POST['invited_users'] as $invited_user_id) {
            $invited_user_id = (int)$invited_user_id;
            
            if ($invited_user_id > 0 && $invited_user_id !== $user_id) {
                // Check if user is already a member
                $memberCheck = db_query("SELECT id FROM project_members WHERE project_id = ? AND user_id = ?", [$project_id, $invited_user_id], "ii");
                
                if ($memberCheck->num_rows === 0) {
                    $inviteMemberQuery = "
                        INSERT INTO project_members (project_id, user_id, role, status) 
                        VALUES (?, ?, 'editor', 'pending')
                    ";
                    db_query($inviteMemberQuery, [$project_id, $invited_user_id], "ii");
                    
                    $inviteMsg = "You have been invited to join the project: " . $projectTitle;
                    send_notification($invited_user_id, "Project Invitation", $inviteMsg, "../dashboard/projects.php", "system");
                    $added_count++;
                }
            }
        }
        
        if ($added_count > 0) {
            $_SESSION['success'] = "Member(s) invited successfully.";
        } else {
            $_SESSION['error'] = "No new members were added.";
        }
    } else {
        $_SESSION['error'] = "No members selected.";
    }
    
    header("Location: ../../dashboard/edit_project.php?id=" . $project_id);
    exit();
}
