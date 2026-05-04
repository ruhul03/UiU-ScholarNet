<?php
require_once('../includes/session.php');
start_secure_session();
require_once('../includes/db_connect.php');
require_once('../includes/csrf.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../auth/login.php");
        exit();
    }

    csrf_validate_or_die();

    $user_id = (int)$_SESSION['user_id'];
    $title = trim((string)($_POST['title'] ?? ''));
    $department = trim((string)($_POST['department'] ?? ''));
    $opportunity_type = trim((string)($_POST['opportunity_type'] ?? 'Research'));
    $skills = trim((string)($_POST['skills'] ?? ''));
    $slots_total = (int)($_POST['slots_total'] ?? 10);
    $description = trim((string)($_POST['description'] ?? ''));
    $project_id = isset($_POST['project_id']) && $_POST['project_id'] !== '' ? (int)$_POST['project_id'] : null;

    $columnsToEnsure = [
        'opportunity_type' => "ALTER TABLE collaboration_posts ADD COLUMN opportunity_type VARCHAR(50) NOT NULL DEFAULT 'Research'",
        'status' => "ALTER TABLE collaboration_posts ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'open'",
        'slots_total' => "ALTER TABLE collaboration_posts ADD COLUMN slots_total INT NOT NULL DEFAULT 10",
        'project_id' => "ALTER TABLE collaboration_posts ADD COLUMN project_id INT NULL"
    ];

    foreach ($columnsToEnsure as $columnName => $alterSql) {
        $check = $conn->query("SHOW COLUMNS FROM collaboration_posts LIKE '" . $conn->real_escape_string($columnName) . "'");
        if ($check && $check->num_rows === 0) {
            $conn->query($alterSql);
        }
    }

    if ($title === '' || $department === '' || $opportunity_type === '') {
        header("Location: ../dashboard/collaboration.php?error=1");
        exit();
    }

    if ($slots_total < 1) {
        $slots_total = 1;
    } elseif ($slots_total > 100) {
        $slots_total = 100;
    }

    $stmt = $conn->prepare("INSERT INTO collaboration_posts (user_id, title, department, description, skills_required, opportunity_type, status, slots_total, project_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssii", $user_id, $title, $department, $description, $skills, $opportunity_type, $status, $slots_total, $project_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Collaboration request posted successfully!";
    } else {
        $_SESSION['error'] = "Database error. Please try again.";
    }
    header("Location: ../dashboard/collaboration.php");
    exit();
}
?>
