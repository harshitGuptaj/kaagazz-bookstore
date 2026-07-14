<?php
require_once 'config/database.php';

header('Content-Type: text/plain');

echo "=== KAAGAZZ DB Debug ===\n\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_HOST: " . DB_HOST . "\n\n";

// Check tables
$result = $conn->query("SHOW TABLES");
echo "=== Tables ===\n";
if ($result) {
    while ($row = $result->fetch_row()) {
        $table = $row[0];
        $count = $conn->query("SELECT COUNT(*) as c FROM `$table`")->fetch_assoc()['c'];
        echo "  $table: $count rows\n";
    }
}

// Check categories
echo "\n=== Categories ===\n";
$result = $conn->query("SELECT id, name FROM categories");
while ($row = $result->fetch_assoc()) {
    echo "  {$row['id']}: {$row['name']}\n";
}

// Check books
echo "\n=== Books (first 5) ===\n";
$result = $conn->query("SELECT id, title, category_id, seller_id, image FROM books LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['id']}: {$row['title']} (cat={$row['category_id']}, seller={$row['seller_id']}, img={$row['image']})\n";
    }
} else {
    echo "  NO BOOKS FOUND!\n";
}

// Check users
echo "\n=== Users ===\n";
$result = $conn->query("SELECT id, name, email, role FROM users");
while ($row = $result->fetch_assoc()) {
    echo "  {$row['id']}: {$row['name']} ({$row['email']}) [{$row['role']}]\n";
}

// Try manual import
echo "\n=== Manual Import Test ===\n";
$schemaFile = __DIR__ . '/config/schema.sql';
if (file_exists($schemaFile)) {
    echo "Schema file exists, size: " . filesize($schemaFile) . " bytes\n";
    
    // Check if books table has data
    $r = $conn->query("SELECT COUNT(*) as c FROM books");
    $bookCount = $r->fetch_assoc()['c'];
    echo "Books before import: $bookCount\n";
    
    if ($bookCount == 0) {
        echo "Attempting manual import...\n";
        $sql = file_get_contents($schemaFile);
        $sql = preg_replace('/CREATE DATABASE IF NOT EXISTS [^;]+;?\s*/i', '', $sql);
        $sql = preg_replace('/USE [^;]+;?\s*/i', '', $sql);
        
        $current = '';
        $inString = false;
        $ok = 0;
        $fail = 0;
        for ($i = 0; $i < strlen($sql); $i++) {
            $c = $sql[$i];
            if ($c === "'" && ($i === 0 || $sql[$i-1] !== '\\')) {
                $inString = !$inString;
            }
            if ($c === ';' && !$inString) {
                $trimmed = trim($current);
                if (!empty($trimmed) && $trimmed !== ';') {
                    $result = @$conn->query($trimmed);
                    if ($result === TRUE) {
                        $ok++;
                    } else {
                        $fail++;
                        echo "  FAIL: " . substr($trimmed, 0, 100) . "...\n";
                        echo "  ERROR: " . $conn->error . "\n\n";
                    }
                }
                $current = '';
            } else {
                $current .= $c;
            }
        }
        echo "Import done: $ok ok, $fail failed\n";
        
        $r = $conn->query("SELECT COUNT(*) as c FROM books");
        $bookCount = $r->fetch_assoc()['c'];
        echo "Books after import: $bookCount\n";
    }
} else {
    echo "Schema file NOT found!\n";
}
