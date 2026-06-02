<?php
function update_project_progress($conn, $project_id) {
    $project_id = (int)$project_id;
    if ($project_id <= 0) return;

    // 1. Get project status (up to 20%)
    $pStmt = $conn->prepare("SELECT status FROM projects WHERE id = ?");
    $pStmt->bind_param("i", $project_id);
    $pStmt->execute();
    $pRes = $pStmt->get_result();
    if ($pRes->num_rows === 0) return;
    $status = $pRes->fetch_assoc()['status'];
    
    $status_score = 0;
    if ($status === 'planning') $status_score = 5;
    elseif ($status === 'active') $status_score = 10;
    elseif ($status === 'review') $status_score = 15;
    elseif ($status === 'completed') $status_score = 20;

    // 2. Get tasks progress (up to 40%)
    $tStmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done FROM tasks WHERE project_id = ?");
    $tStmt->bind_param("i", $project_id);
    $tStmt->execute();
    $tRow = $tStmt->get_result()->fetch_assoc();
    
    $tasks_score = 0;
    $total_tasks = (int)$tRow['total'];
    $done_tasks = (int)$tRow['done'];
    if ($total_tasks > 0) {
        $tasks_score = ($done_tasks / $total_tasks) * 40;
    }

    // 3. Get document progress (up to 40%)
    $dStmt = $conn->prepare("SELECT SUM(LENGTH(content)) as doc_len FROM documents WHERE project_id = ?");
    $dStmt->bind_param("i", $project_id);
    $dStmt->execute();
    $doc_len = (int)$dStmt->get_result()->fetch_assoc()['doc_len'];
    
    // Assume 10,000 characters is 100% of the document score (40% overall)
    $doc_score = min(40, ($doc_len / 10000) * 40);

    // Calculate total progress
    $total_progress = round($status_score + $tasks_score + $doc_score);
    if ($total_progress > 100) $total_progress = 100;
    
    // Update project
    $upd = $conn->prepare("UPDATE projects SET progress = ? WHERE id = ?");
    $upd->bind_param("ii", $total_progress, $project_id);
    $upd->execute();
}
?>
