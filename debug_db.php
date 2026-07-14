<?php
require_once 'config/database.php';

header('Content-Type: text/plain');

echo "=== KAAGAZZ DB Reset & Import ===\n\n";

// Drop all tables
$tables = ['reviews', 'wishlist', 'cart', 'order_items', 'orders', 'books', 'categories', 'users'];
foreach ($tables as $table) {
    $conn->query("DROP TABLE IF EXISTS `$table`");
}
echo "All tables dropped.\n";

// Read and clean schema
$schemaFile = __DIR__ . '/config/schema.sql';
$sql = file_get_contents($schemaFile);

// Remove single-line comments (-- style)
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

// Remove empty lines and normalize whitespace
$sql = preg_replace('/\n\s*\n/', "\n", $sql);
$sql = trim($sql);

// Fix admin password
$correctHash = password_hash('admin123', PASSWORD_DEFAULT);

echo "Importing schema...\n";

// Execute via multi_query
if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_query());
    
    if ($conn->errno) {
        echo "MySQL Error: " . $conn->error . "\n";
    } else {
        echo "Schema imported successfully!\n";
    }
} else {
    echo "multi_query failed: " . $conn->error . "\n";
}

// Fix admin password
$conn->query("UPDATE users SET password = '" . $conn->real_escape_string($correctHash) . "' WHERE email = 'admin@kaagazz.com'");
echo "Admin password updated.\n";

// Verify
echo "\n=== Verification ===\n";
foreach (['categories', 'users', 'books'] as $table) {
    $r = $conn->query("SELECT COUNT(*) as c FROM `$table`");
    $count = $r->fetch_assoc()['c'];
    echo "$table: $count rows\n";
}

echo "\n=== First 5 books ===\n";
$result = $conn->query("SELECT id, title, category_id, image FROM books LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  {$row['id']}: {$row['title']} (cat={$row['category_id']})\n";
    }
}
