<?php
$ctx = stream_context_create(['http' => ['timeout' => 15, 'ignore_errors' => true, 'user_agent' => 'Mozilla/5.0']]);

// Test with a known book
$query = urlencode('intitle:Harry Potter');
$url = "https://www.googleapis.com/books/v1/volumes?q={$query}&maxResults=1";
$data = json_decode(@file_get_contents($url, false, $ctx), true);

echo "=== Full API Response ===\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n";

if (!empty($data['items'][0]['volumeInfo'])) {
    $vi = $data['items'][0]['volumeInfo'];
    echo "\n=== VolumeInfo keys ===\n";
    echo implode(", ", array_keys($vi)) . "\n";
    echo "\n=== imageLinks ===\n";
    echo json_encode($vi['imageLinks'] ?? 'NOT FOUND', JSON_PRETTY_PRINT) . "\n";
}
