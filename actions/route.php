<?php
// actions/route.php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

$action = trim((string)($_POST['action'] ?? ''));
if ($action === '') {
    redirect_with_error("../dashboard/index.php", "Invalid action.");
}

// Global CSRF validation for all POST actions
csrf_validate_or_die();

switch ($action) {
    case 'login':
        include 'login.php';
        break;
    case 'register':
        include 'register.php';
        break;
    case 'add_task':
        include 'add_task.php';
        break;
    case 'clear_completed_tasks':
        include 'clear_completed_tasks.php';
        break;
    case 'create_project':
        include 'create_project.php';
        break;
    case 'update_project':
        include 'update_project.php';
        break;
    case 'delete_project':
        include 'delete_project.php';
        break;
    case 'post_collaboration':
        include 'post_collaboration.php';
        break;
    case 'apply_collaboration':
        include 'apply_collaboration.php';
        break;
    case 'delete_collaboration_post':
        include 'delete_collaboration_post.php';
        break;
    case 'post_message':
        include 'post_message.php';
        break;
    case 'save_document':
        include 'save_document.php';
        break;
    case 'update_profile':
        include 'update_profile.php';
        break;
    case 'update_task_status':
        include 'update_task_status.php';
        break;
    case 'upload_preprint':
        include 'upload_preprint.php';
        break;
    case 'add_preprint_comment':
        include 'add_preprint_comment.php';
        break;
    case 'report_content':
        include 'report_content.php';
        break;
    default:
        redirect_with_error("../dashboard/index.php", "Action '$action' not found.");
}
?>
