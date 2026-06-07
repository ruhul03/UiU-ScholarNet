<?php
require_once(__DIR__ . '/../includes/db_connect.php');

$result = db_query("SELECT id, full_name, role, department, points FROM users ORDER BY points DESC LIMIT 1", [], "");
if ($result) {
    echo "Query 1 works.\n";
}

$result2 = db_query("SELECT title, description, points, icon FROM reputation_rules ORDER BY points DESC LIMIT 1", [], "");
if ($result2) {
    echo "Query 2 works.\n";
}
