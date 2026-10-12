<?php
require_once 'config.php';
$pdo = require 'db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== Last 5 audit log entries ===\n\n";

$rows = $pdo->query("
    SELECT action, details, created_at
    FROM audit_log
    WHERE action LIKE 'password_reset%'
    ORDER BY id DESC
    LIMIT 5
")->fetchAll();

foreach ($rows as $r) {
    echo "Time: {$r['created_at']}\n";
    echo "Action: {$r['action']}\n";
    echo "Details: {$r['details']}\n";
    echo "---\n\n";
}

echo "\n=== Check RESEND_API_KEY ===\n";
$key = getenv('RESEND_API_KEY');
if ($key) {
    echo "Key present. Length: " . strlen($key) . "\n";
    echo "First 8 chars: " . substr($key, 0, 8) . "...\n";
} else {
    echo "❌ RESEND_API_KEY is NOT set!\n";
}