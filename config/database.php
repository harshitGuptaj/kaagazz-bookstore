<?php
// Database configuration - supports Railway (cloud) and local XAMPP
if (getenv('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST'));
    define('DB_USER', getenv('DB_USER'));
    define('DB_PASS', getenv('DB_PASS'));
    define('DB_NAME', getenv('DB_NAME'));
} else {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'kaagazz_db');
}

// Create connection (without database first to allow auto-create)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Auto-create database if it doesn't exist
$conn->query("CREATE DATABASE IF NOT EXISTS `" . $conn->real_escape_string(DB_NAME) . "`");
$conn->select_db(DB_NAME);
$conn->set_charset("utf8");

// Auto-initialize schema if tables don't exist
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result && $result->num_rows === 0) {
    $schemaFile = __DIR__ . '/schema.sql';
    if (file_exists($schemaFile)) {
        $sql = file_get_contents($schemaFile);

        // Remove single-line comments
        $lines = explode("\n", $sql);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = ltrim($line);
            if (strpos($trimmed, '--') === 0) {
                continue;
            }
            $cleanLines[] = $line;
        }
        $sql = implode("\n", $cleanLines);
        $sql = preg_replace('/\n\s*\n/', "\n", $sql);
        $sql = trim($sql);

        // Execute via multi_query
        if ($conn->multi_query($sql)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_query());
        }

        // Fix admin password
        $correctHash = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '" . $conn->real_escape_string($correctHash) . "' WHERE email = 'admin@kaagazz.com'");
    }
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
