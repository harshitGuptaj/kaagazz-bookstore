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

// Set charset
$conn->set_charset("utf8");

// Auto-initialize schema if tables don't exist
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result && $result->num_rows === 0) {
    $schemaFile = __DIR__ . '/schema.sql';
    if (file_exists($schemaFile)) {
        $sql = file_get_contents($schemaFile);
        $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS [^;]+;?\s*/i', '', $sql);
        $sql = preg_replace('/USE [^;]+;?\s*/i', '', $sql);

        $current = '';
        $inString = false;
        for ($i = 0; $i < strlen($sql); $i++) {
            $c = $sql[$i];
            if ($c === "'" && ($i === 0 || $sql[$i-1] !== '\\')) {
                $inString = !$inString;
            }
            if ($c === ';' && !$inString) {
                $trimmed = trim($current);
                if (!empty($trimmed) && $trimmed !== ';') {
                    $conn->query($trimmed);
                }
                $current = '';
            } else {
                $current .= $c;
            }
        }

        $correctHash = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '" . $conn->real_escape_string($correctHash) . "' WHERE email = 'admin@kaagazz.com'");
    }
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
