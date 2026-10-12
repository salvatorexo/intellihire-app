<?php
header('Content-Type: text/plain; charset=utf-8');

$file = __DIR__ . '/send-reset.php';

if (!is_file($file)) {
    die("❌ send-reset.php NOT FOUND at: $file");
}

echo "=== FILE INFO ===\n";
echo "Path: $file\n";
echo "Size: " . filesize($file) . " bytes\n";
echo "Modified: " . date('Y-m-d H:i:s', filemtime($file)) . "\n\n";

echo "=== LAST 25 LINES OF send-reset.php ===\n\n";
$lines = file($file);
$tail = array_slice($lines, -25);
foreach ($tail as $i => $line) {
    echo ($i + 1) . ": " . $line;
}

echo "\n\n=== SEARCH FOR 'demo_link' ===\n";
$content = file_get_contents($file);
if (strpos($content, 'demo_link') !== false) {
    echo "✅ 'demo_link' IS present in the file\n";
} else {
    echo "❌ 'demo_link' NOT FOUND — this is the OLD version\n";
}

echo "\n=== SEARCH FOR 'Email delivery is disabled' ===\n";
if (strpos($content, 'Email delivery is disabled') !== false) {
    echo "✅ New message string IS present\n";
} else {
    echo "❌ New message string NOT FOUND\n";
}