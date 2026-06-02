<?php
require_once('../../includes/db_connect.php');

function column_exists(mysqli $conn, string $table, string $column): bool
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS count FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (int)($row['count'] ?? 0) > 0;
}

$columnQueries = [
    ['users', 'is_verified', "ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 1 AFTER role"],
    ['users', 'account_status', "ALTER TABLE users ADD COLUMN account_status ENUM('active','banned') DEFAULT 'active' AFTER is_verified"],
    ['users', 'reputation', "ALTER TABLE users ADD COLUMN reputation INT DEFAULT 0 AFTER points"],
    ['messages', 'file_path', "ALTER TABLE messages ADD COLUMN file_path VARCHAR(255) NULL AFTER message"],
    ['messages', 'file_name', "ALTER TABLE messages ADD COLUMN file_name VARCHAR(255) NULL AFTER file_path"],
];

foreach ($columnQueries as [$table, $column, $query]) {
    if (column_exists($conn, $table, $column)) {
        echo "Column already exists: {$table}.{$column}<br>\n";
        continue;
    }

    try {
        $conn->query($query);
        echo "Column added: {$table}.{$column}<br>\n";
    } catch (Exception $e) {
        echo "Column failed: {$table}.{$column} - " . $e->getMessage() . "<br>\n";
    }
}

$queries = [
    "CREATE TABLE IF NOT EXISTS departments (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE)",
    "CREATE TABLE IF NOT EXISTS skills (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE)",
    "CREATE TABLE IF NOT EXISTS opportunity_types (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE)",
    "CREATE TABLE IF NOT EXISTS reputation_rules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action_key VARCHAR(100) NOT NULL UNIQUE,
        title VARCHAR(150) NOT NULL,
        description TEXT,
        points INT NOT NULL DEFAULT 0,
        icon VARCHAR(100) DEFAULT 'fa-solid fa-star'
    )",
    "INSERT IGNORE INTO departments (name) VALUES ('CSE'),('EEE'),('BBA'),('Economics'),('English')",
    "INSERT IGNORE INTO skills (name) VALUES ('Python'),('Machine Learning'),('Data Mining'),('LaTeX'),('SPSS'),('NLP'),('Statistics')",
    "INSERT IGNORE INTO opportunity_types (name) VALUES ('Research'),('Project'),('Thesis'),('Publication'),('Dataset')",
    "INSERT IGNORE INTO reputation_rules (action_key, title, description, points, icon) VALUES
        ('task_completed','Complete a Task','Earn points when an assigned project task is completed.',50,'fa-solid fa-list-check'),
        ('preprint_published','Publish a Preprint','Earn points for sharing academic work.',100,'fa-solid fa-file-lines'),
        ('collaboration_posted','Post Collaboration','Earn points for opening a collaboration opportunity.',20,'fa-solid fa-users'),
        ('discussion_started','Start Discussion','Earn points for starting a research discussion.',2,'fa-solid fa-comments')",
    "UPDATE users SET is_verified = 1 WHERE is_verified IS NULL",
    "UPDATE users SET account_status = 'active' WHERE account_status IS NULL",
];

foreach ($queries as $query) {
    try {
        $conn->query($query);
        echo "Query success: {$query}<br>\n";
    } catch (Exception $e) {
        echo "Query failed/ignored: " . $e->getMessage() . "<br>\n";
    }
}

echo "<h3>06_sync_user_admin_schema complete!</h3>";
?>
