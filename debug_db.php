<?php
require_once 'config/database.php';

header('Content-Type: text/plain');

echo "=== KAAGAZZ DB Reset & Import ===\n\n";

// Drop all tables and re-create
$tables = ['reviews', 'wishlist', 'cart', 'order_items', 'orders', 'books', 'categories', 'users'];
foreach ($tables as $table) {
    $conn->query("DROP TABLE IF EXISTS `$table`");
    echo "Dropped: $table\n";
}

echo "\n=== Re-importing schema ===\n";

$schemaFile = __DIR__ . '/config/schema.sql';
$sql = file_get_contents($schemaFile);
$sql = preg_replace('/^\s*--.*$/m', '', $sql);
$sql = preg_replace('/CREATE DATABASE IF NOT EXISTS [^;]+;?\s*/i', '', $sql);
$sql = preg_replace('/USE [^;]+;?\s*/i', '', $sql);

$statements = array_filter(array_map('trim', explode(';', $sql)));
$ok = 0;
$fail = 0;
foreach ($statements as $stmt) {
    if (!empty($stmt)) {
        $result = @$conn->query($stmt);
        if ($result === TRUE) {
            $ok++;
        } else {
            $fail++;
            echo "FAIL: " . substr($stmt, 0, 80) . "...\n";
            echo "ERROR: " . $conn->error . "\n\n";
        }
    }
}
echo "Statements: $ok OK, $fail FAIL\n";

// Fix admin password
$correctHash = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("UPDATE users SET password = '" . $conn->real_escape_string($correctHash) . "' WHERE email = 'admin@kaagazz.com'");
echo "\nAdmin password updated.\n";

// Verify
echo "\n=== Verification ===\n";
foreach (['categories', 'users', 'books'] as $table) {
    $r = $conn->query("SELECT COUNT(*) as c FROM `$table`");
    $count = $r->fetch_assoc()['c'];
    echo "$table: $count rows\n";
}

// Show first 5 books
echo "\n=== First 5 books ===\n";
$result = $conn->query("SELECT id, title, category_id, image FROM books LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "  {$row['id']}: {$row['title']} (cat={$row['category_id']}, img={$row['image']})\n";
}
