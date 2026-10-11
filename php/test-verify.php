<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Nairobi');
require_once 'config.php';
$pdo = require 'db.php';

// Show the most recent row
$row = $pdo->query("
    SELECT id, user_id, token_hash, expires_at, used, created_at
    FROM password_resets
    ORDER BY id DESC LIMIT 1
")->fetch();

if (!$row) {
    echo "❌ No rows in password_resets table.";
    exit;
}

echo "<h3>Most recent token row:</h3>";
echo "ID: {$row['id']}<br>";
echo "User ID: {$row['user_id']}<br>";
echo "Hash (first 20): " . substr($row['token_hash'], 0, 20) . "...<br>";
echo "Expires at: {$row['expires_at']}<br>";
echo "Used: {$row['used']}<br>";
echo "Created at: {$row['created_at']}<br>";
echo "<hr>";

echo "Current time (MySQL NOW()): " . $pdo->query("SELECT NOW()")->fetchColumn() . "<br>";
echo "Is expires_at > NOW()? " . (strtotime($row['expires_at']) > time() ? "✅ YES" : "❌ NO — EXPIRED") . "<br>";
echo "Is used = 0? " . ($row['used'] == 0 ? "✅ YES" : "❌ NO — ALREADY USED") . "<br>";