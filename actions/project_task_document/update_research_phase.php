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
    $new_phase = (string)($_POST['research_phase'] ?? '');
    
    $valid_phases = ['literature_review', 'gap_analysis', 'methodology', 'implementation', 'experimentation', 'drafting', 'publishing'];
    
    if ($project_id <= 0 || !in_array($new_phase, $valid_phases, true)) {
        $_SESSION['error'] = "Invalid project or phase.";
        header("Location: ../../dashboard/edit_project.php?id=" . $project_id);
        exit();
    }
    
    // Check if user is the team leader (creator)
    $creatorCheck = db_query("SELECT id, title FROM projects WHERE id = ? AND creator_id = ?", [$project_id, $user_id], "ii");
    
    if ($creatorCheck->num_rows === 0) {
        $_SESSION['error'] = "You do not have permission to update the research pipeline.";
        header("Location: ../../dashboard/edit_project.php?id=" . $project_id);
        exit();
    }
    
    $projectData = $creatorCheck->fetch_assoc();
    $projectTitle = $projectData['title'];
    
    db_query("UPDATE projects SET research_phase = ? WHERE id = ?", [$new_phase, $project_id], "si");
    
    // Send notifications to all other active members
    $membersRes = db_query("SELECT user_id FROM project_members WHERE project_id = ? AND status = 'active' AND user_id != ?", [$project_id, $user_id], "ii");
    
    $phaseLabels = [
        'literature_review' => 'Literature Review',
        'gap_analysis' => 'Gap Analysis',
        'methodology' => 'Methodology',
        'implementation' => 'Implementation',
        'experimentation' => 'Experimentation',
        'drafting' => 'Drafting',
        'publishing' => 'Publishing'
    ];
    $nice_phase_name = $phaseLabels[$new_phase];
    
    $msg = "Project '$projectTitle' has moved to the $nice_phase_name phase.";
    
    while ($m = $membersRes->fetch_assoc()) {
        send_notification($m['user_id'], "Pipeline Update", $msg, "../dashboard/projects.php", "system");
    }
    
    // Notify supervisor if exists
    $supCheck = db_query("SELECT supervisor_id FROM projects WHERE id = ? AND supervisor_id IS NOT NULL", [$project_id], "i");
    if ($supCheck->num_rows > 0) {
        $sup = $supCheck->fetch_assoc()['supervisor_id'];
        if ($sup != $user_id) {
            send_notification($sup, "Pipeline Update", $msg, "../dashboard/projects.php", "system");
        }
    }
    
    $_SESSION['success'] = "Research pipeline updated to $nice_phase_name.";
    header("Location: ../../dashboard/edit_project.php?id=" . $project_id);
    exit();
}
