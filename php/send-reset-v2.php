<?php
/* =========================================================
   IntelliHire - Send Password Reset Link (v2)
   Filename changed to bypass PHP OPcache.
   ========================================================= */

require_once 'security.php';
require_once 'config.php';
require_once 'mailer-config.php';

$pdo = require 'db.php';

require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

$htmlBody = "<p>Hi " . htmlspecialchars($user['full_name']) . ",</p>"
          . "<p>Reset your password: <a href='" . htmlspecialchars($link) . "'>" . htmlspecialchars($link) . "</a></p>"
          . "<p>This link expires in 1 hour.</p>";

$emailSent = false;
$errorInfo = '';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_ENCRYPTION;
    $mail->Port       = MAIL_PORT;
    $mail->Timeout    = 8;

    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress($email, $user['full_name']);
    $mail->isHTML(true);
    $mail->Subject = 'Reset your IntelliHire password';
    $mail->Body    = $htmlBody;
    $mail->AltBody = "Reset: $link";

    $mail->send();
    $emailSent = true;
    auditLog($pdo, 'password_reset_sent', "User ID: {$user['id']}");
} catch (Exception $e) {
    $errorInfo = $mail->ErrorInfo ?: $e->getMessage();
    auditLog($pdo, 'password_reset_email_failed', "User ID: {$user['id']} — $errorInfo");
}

jsonResponse(true, 'Reset link ready:', [
    'demo_link'  => $link,
    'email_sent' => $emailSent,
    'smtp_error' => $errorInfo ?: null
]);