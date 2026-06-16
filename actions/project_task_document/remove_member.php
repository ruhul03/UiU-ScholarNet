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
    $member_id = (int)($_POST['member_id'] ?? 0);
    
    if ($project_id <= 0 || $member_id <= 0) {
        $_SESSION['error'] = "Invalid data.";
        header("Location: ../../dashboard/projects.php");
        exit();
    }
    
    // Check if the current user is the owner of the project
    $creatorCheck = db_query("SELECT id FROM projects WHERE id = ? AND creator_id = ?", [$project_id, $user_id], "ii");
    
    if ($creatorCheck->num_rows === 0) {
        $_SESSION['error'] = "You do not have permission to remove members from this project.";
        header("Location: ../../dashboard/edit_project.php?id=" . $project_id);
        exit();
    }
    
    // Check if the member to remove is the owner/creator
    $memberToRemoveRole = db_query("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?", [$project_id, $member_id], "ii")->fetch_assoc();
    
    if ($memberToRemoveRole && $memberToRemoveRole['role'] === 'owner') {
        $_SESSION['error'] = "Cannot remove the project owner.";
        header("Location: ../../dashboard/edit_project.php?id=" . $project_id);
        exit();
    }
    
    // Delete the member
    db_query("DELETE FROM project_members WHERE project_id = ? AND user_id = ?", [$project_id, $member_id], "ii");
    
    // Also remove any tasks assigned to this user in this project?
    // Leaving it as-is for now, tasks may become unassigned if we want, but let's just delete the member.
    db_query("UPDATE tasks SET assigned_to = NULL WHERE project_id = ? AND assigned_to = ?", [$project_id, $member_id], "ii");
    
    $_SESSION['success'] = "Member removed successfully.";
    header("Location: ../../dashboard/edit_project.php?id=" . $project_id);
    exit();
}
