<?php
// actions/route.php
// This is the main routing hub for handling POST requests in the application.

require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

// Retrieve and sanitize the requested action
$action = trim((string)($_POST['action'] ?? ''));
if ($action === '') {
    redirect_with_error("../dashboard/index.php", "Invalid action.");
}

// Global CSRF validation for security on all POST actions
csrf_validate_or_die();

switch ($action) {
    case 'login':
        include 'auth_user/login.php';
        break;
    case 'register':
        include 'auth_user/register.php';
        break;
    case 'add_task':
        include 'project_task_document/add_task.php';
        break;
    case 'clear_completed_tasks':
        include 'project_task_document/clear_completed_tasks.php';
        break;
    case 'create_project':
        include 'project_task_document/create_project.php';
        break;
    case 'update_project':
        include 'project_task_document/update_project.php';
        break;
    case 'delete_project':
        include 'project_task_document/delete_project.php';
        break;
    case 'post_collaboration':
        include 'collaboration_messaging/post_collaboration.php';
        break;
    case 'apply_collaboration':
        include 'collaboration_messaging/apply_collaboration.php';
        break;
    case 'delete_collaboration_post':
        include 'collaboration_messaging/delete_collaboration_post.php';
        break;
    case 'post_message':
        include 'collaboration_messaging/post_message.php';
        break;
    case 'save_document':
        include 'project_task_document/save_document.php';
        break;
    case 'update_profile':
        include 'auth_user/update_profile.php';
        break;
    case 'update_task_status':
        include 'project_task_document/update_task_status.php';
        break;
    case 'upload_preprint':
        include 'discussion_preprints/upload_preprint.php';
        break;
    case 'add_preprint_comment':
        include 'discussion_preprints/add_preprint_comment.php';
        break;
    case 'report_content':
        include 'admin_misc/report_content.php';
        break;
    default:
        redirect_with_error("../dashboard/index.php", "Action '$action' not found.");
}
?>
