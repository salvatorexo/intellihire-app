<?php
/* =========================================================
   IntelliHire - Send Password Reset Link (Resend API)
   ========================================================= */

require_once 'security.php';
require_once 'config.php';

$pdo = require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid method.');
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Please enter a valid email address.');
}

$stmt = $pdo->prepare("SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

$genericMessage = 'If that email is registered, a reset link has been sent. Check your inbox (and spam folder).';

if (!$user) {
    auditLog($pdo, 'password_reset_unknown_email', "Email: $email");
    jsonResponse(true, $genericMessage);
}

$token = bin2hex(random_bytes(32));
$hash  = hash('sha256', $token);

$stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
$stmt->execute([$user['id']]);

$stmt = $pdo->prepare("
    INSERT INTO password_resets (user_id, token_hash, expires_at)
    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
");
$stmt->execute([$user['id'], $hash]);

$link = APP_URL . '/reset-password.html?token=' . urlencode($token);

/* ---------- Build email HTML ---------- */
$htmlBody = "
<div style='font-family:Arial,sans-serif;max-width:560px;margin:auto;padding:24px;background:#f8fafc;border-radius:12px;'>
    <h2 style='color:#0f172a;'>Reset your IntelliHire password</h2>
    <p style='color:#475569;'>Hi " . htmlspecialchars($user['full_name']) . ",</p>
    <p style='color:#475569;'>Click the button below to reset your password. This link expires in 1 hour.</p>
    <p style='text-align:center;margin:30px 0;'>
        <a href='" . htmlspecialchars($link) . "' style='background:#2563eb;color:#fff;padding:12px 24px;text-decoration:none;border-radius:8px;display:inline-block;'>Reset Password</a>
    </p>
    <p style='color:#64748b;font-size:0.85rem;'>Or copy this link: <br>" . htmlspecialchars($link) . "</p>
</div>
";

/* ---------- Send via Resend API (uses HTTPS, allowed on Render) ---------- */
$apiKey = getenv('RESEND_API_KEY');

if (!$apiKey) {
    jsonResponse(false, 'Email service is not configured. Please contact support.');
}

$payload = json_encode([
    'from'    => 'onboarding@resend.dev',  // Use this for testing until you verify your own domain
    'to'      => [$email],
    'subject' => 'Reset your IntelliHire password',
    'html'    => $htmlBody,
]);

$ch = curl_init('https://api.resend.com/emails');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    auditLog($pdo, 'password_reset_email_failed', "cURL: $curlErr");
    jsonResponse(false, 'Could not send reset email. Please try again later.');
}

if ($httpCode !== 200) {
    auditLog($pdo, 'password_reset_email_failed', "HTTP $httpCode: $response");
    jsonResponse(false, 'Email service error. Please try again later.');
}

auditLog($pdo, 'password_reset_sent', "User ID: {$user['id']}");

/* ---------- Response ---------- */
jsonResponse(true, $genericMessage);