<?php
/* =========================================================
   IntelliHire - Reset Password (using token)
   ========================================================= */

require_once 'security.php';
require_once 'config.php';
$pdo = require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method.');

verifyCsrf();

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$password = $input['password'] ?? '';

if (strlen($token) !== 64) {
    jsonResponse(false, 'Invalid reset token.');
}
if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    jsonResponse(false, 'Password must be at least 8 characters and contain letters and numbers.');
}

$hash = hash('sha256', $token);

$stmt = $pdo->prepare("
    SELECT id, user_id FROM password_resets
    WHERE token_hash = ? AND expires_at > NOW() AND used = 0
    LIMIT 1
");
$stmt->execute([$hash]);
$row = $stmt->fetch();

if (!$row) {
    jsonResponse(false, 'This reset link is invalid or has expired. Please request a new one.');
}

$newHash = password_hash($password, PASSWORD_BCRYPT);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$newHash, $row['user_id']]);

    $stmt = $pdo->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
    $stmt->execute([$row['id']]);

    $pdo->commit();
    auditLog($pdo, 'password_reset_success', "User ID: {$row['user_id']}");

    jsonResponse(true, '✅ Password reset successfully. Redirecting to login...');

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, 'Reset failed. Please try again.');
}