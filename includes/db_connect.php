<?php

$envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));
            $val = trim($val, "\"'");
            
            if ($key !== '' && getenv($key) === false) {
                putenv($key . '=' . $val);
                $_ENV[$key] = $val;
            }
        }
    }
}

$host = getenv('DB_HOST') ?: "127.0.0.1";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASS');
if ($pass === false) {
    $pass = "";
}
$dbname = getenv('DB_NAME') ?: "uiu_scholarnet";
$port = getenv('DB_PORT') ?: 3306;

$conn = mysqli_connect($host, $user, $pass, $dbname, $port);

if (!$conn) {
    http_response_code(500);
    die("Database connection failed.");
}

mysqli_set_charset($conn, "utf8mb4");

/**
 * Helper function for executing prepared statements.
 * This is useful for beginners to run SQL queries securely and prevent SQL injection.
 * 
 * @param string $sql The SQL query with ? placeholders
 * @param array $params The values to bind to the placeholders
 * @param string $types The parameter types (e.g., 'i' for integer, 's' for string)
 * @return mysqli_result|bool The result set for SELECT queries, true for write queries
 */
function db_query($sql, $params = [], $types = "") {
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Query preparation failed: " . mysqli_error($conn));
    }
    
    if ($params) {
        if (empty($types)) {
            $types = "";
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= "i";
                } elseif (is_double($param)) {
                    $types .= "d";
                } else {
                    $types .= "s";
                }
            }
        }
        $stmt->bind_param($types, ...$params);
    }
    
    if (!$stmt->execute()) {
        die("Query execution failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($result === false && $stmt->field_count === 0) {
        return true;
    }

    return $result;
}

/**
 * Sends a notification to a user.
 * 
 * @param int $user_id The ID of the user to receive the notification
 * @param string $title The notification title
 * @param string $message The notification message body
 * @param string|null $link Optional link to redirect the user when clicked
 * @param string $type The notification type (e.g., 'system', 'message', 'project')
 * @return bool True if successful, False otherwise
 */
function send_notification($user_id, $title, $message, $link = null, $type = 'general') {
    $query = "INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)";
    return db_query($query, [$user_id, $type, $title, $message, $link], "issss");
}
?>
