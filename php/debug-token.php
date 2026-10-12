<?php
require_once 'config.php';
$pdo = require 'db.php';

header('Content-Type: text/plain');

echo "=== All tokens in password_resets ===\n\n";

$rows = $pdo->query("SELECT id, user_id, LEFT(token_hash,20) AS hash_start, expires_at, used, created_at FROM password_resets ORDER BY id DESC LIMIT 10")->fetchAll();

if (!$rows) {
    echo "❌ No rows in password_resets table.\n";
} else {
    foreach ($rows as $r) {
        echo "ID: {$r['id']}\n";
        echo "User ID: {$r['user_id']}\n";
        echo "Hash start: {$r['hash_start']}\n";
        echo "Expires: {$r['expires_at']}\n";
        echo "Used: {$r['used']}\n";
        echo "Created: {$r['created_at']}\n";
        echo "NOW (MySQL): " . $pdo->query("SELECT NOW()")->fetchColumn() . "\n";
        echo "Valid: " . (strtotime($r['expires_at']) > time() && $r['used'] == 0 ? "YES ✅" : "NO ❌") . "\n";
        echo "---\n";
    }
}