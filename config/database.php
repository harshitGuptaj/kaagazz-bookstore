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

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("CREATE DATABASE IF NOT EXISTS `" . $conn->real_escape_string(DB_NAME) . "`");
$conn->select_db(DB_NAME);
$conn->set_charset("utf8");

$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result && $result->num_rows === 0) {
    $schemaFile = __DIR__ . '/schema.sql';
    if (file_exists($schemaFile)) {
        $sql = file_get_contents($schemaFile);

        $lines = explode("\n", $sql);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = ltrim($line);
            if (strpos($trimmed, '--') === 0) continue;
            $cleanLines[] = $line;
        }
        $sql = implode("\n", $cleanLines);
        $sql = preg_replace('/\n\s*\n/', "\n", $sql);
        $sql = trim($sql);

        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $stmt) {
            if (!empty($stmt)) {
                @$conn->query($stmt);
            }
        }

        $correctHash = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("UPDATE users SET password = '" . $conn->real_escape_string($correctHash) . "' WHERE email = 'admin@kaagazz.com'");
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
