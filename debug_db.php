<?php
require_once 'config/database.php';

header('Content-Type: text/plain');

echo "=== KAAGAZZ DB Reset & Import ===\n\n";

$tables = ['reviews', 'wishlist', 'cart', 'order_items', 'orders', 'books', 'categories', 'users'];
foreach ($tables as $table) {
    $conn->query("DROP TABLE IF EXISTS `$table`");
}
echo "All tables dropped.\n";

$schemaFile = __DIR__ . '/config/schema.sql';
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

// Split into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

$ok = 0;
$fail = 0;
foreach ($statements as $stmt) {
    if (empty($stmt)) continue;
    $result = @$conn->query($stmt);
    if ($result === TRUE) {
        $ok++;
    } else {
        $fail++;
        echo "FAIL [" . $fail . "]: " . substr($conn->real_escape_string($stmt), 0, 100) . "...\n";
        echo "  ERROR: " . $conn->error . "\n";
        if ($fail > 5) { echo "Too many errors, stopping.\n"; break; }
    }
}
echo "Statements: $ok OK, $fail FAIL\n";

$correctHash = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("UPDATE users SET password = '" . $conn->real_escape_string($correctHash) . "' WHERE email = 'admin@kaagazz.com'");

echo "\n=== Verification ===\n";
foreach (['categories', 'users', 'books'] as $table) {
    $r = $conn->query("SELECT COUNT(*) as c FROM `$table`");
    $count = $r->fetch_assoc()['c'];
    echo "$table: $count rows\n";
}
