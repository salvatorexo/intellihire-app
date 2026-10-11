<?php
require_once 'config.php';
$pdo = require 'db.php';

$email = 'admin@intellihire.com';
$newPassword = 'admin123';
$newHash = password_hash($newPassword, PASSWORD_BCRYPT);

// Update the admin's password
$stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
$stmt->execute([$newHash, $email]);

if ($stmt->rowCount() === 0) {
    echo "❌ No admin row found for $email — check the email spelling.";
    exit;
}

// Confirm by reading back
$stmt = $pdo->prepare("SELECT id, email, account_type, password_hash FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

echo "<h2>✅ Password reset for admin</h2>";
echo "Email: " . htmlspecialchars($user['email']) . "<br>";
echo "Role: " . htmlspecialchars($user['account_type']) . "<br>";
echo "Hash in DB: <code>" . htmlspecialchars($user['password_hash']) . "</code><br><br>";

$check = password_verify($newPassword, $user['password_hash']);
echo "Verify 'admin123' against DB hash: " . ($check ? "✅ MATCH" : "❌ NO MATCH") . "<br><br>";
echo "<strong>You can now log in with:</strong><br>";
echo "Email: <code>admin@intellihire.com</code><br>";
echo "Password: <code>admin123</code>";