<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit;
}

require_once __DIR__ . '/config/database.php';

// Database name from env
$dbName = getenv('DB_NAME') ?: 'kaagazz_db';

echo "Creating database `$dbName`...\n";
$conn->query("CREATE DATABASE IF NOT EXISTS `$dbName`");
$conn->select_db($dbName);

// Read schema file
$sql = file_get_contents(__DIR__ . '/config/schema.sql');

// Remove CREATE DATABASE and USE statements (we already created/selected)
$sql = preg_replace('/CREATE DATABASE IF NOT EXISTS [^;]+;?\s*/i', '', $sql);
$sql = preg_replace('/USE [^;]+;?\s*/i', '', $sql);

// Split by semicolons but be careful with INSERT data (semicolons inside quotes)
$statements = [];
$current = '';
$inString = false;
$char = '';
for ($i = 0; $i < strlen($sql); $i++) {
    $c = $sql[$i];
    if ($c === "'" && ($i === 0 || $sql[$i-1] !== '\\')) {
        $inString = !$inString;
    }
    if ($c === ';' && !$inString) {
        $trimmed = trim($current);
        if (!empty($trimmed) && $trimmed !== ';') {
            $statements[] = $trimmed;
        }
        $current = '';
    } else {
        $current .= $c;
    }
}

$success = 0;
$errors = 0;
foreach ($statements as $i => $stmt) {
    $result = $conn->query($stmt);
    if ($result === TRUE) {
        $success++;
        echo "OK [$success]: " . substr($stmt, 0, 60) . "...\n";
    } else {
        $errors++;
        $err = $conn->error;
        if (strpos($err, 'Duplicate') !== false || strpos($err, 'already exists') !== false) {
            echo "SKIP (exists): " . substr($stmt, 0, 60) . "...\n";
        } else {
            echo "ERR [$errors]: " . substr($stmt, 0, 60) . "... => $err\n";
        }
    }
}

echo "\nDone! Success: $success, Errors/Skipped: $errors\n";

// Verify tables
$result = $conn->query("SHOW TABLES");
echo "\nTables in `$dbName`:\n";
while ($row = $result->fetch_row()) {
    echo "  - $row[0]\n";
}

// Count books
$result = $conn->query("SELECT COUNT(*) as cnt FROM books");
$row = $result->fetch_assoc();
echo "\nTotal books: " . $row['cnt'] . "\n";

// Fix admin password hash
$correctHash = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("UPDATE users SET password = '" . $conn->real_escape_string($correctHash) . "' WHERE email = 'admin@kaagazz.com'");
echo "Admin password updated.\n";

$conn->close();
