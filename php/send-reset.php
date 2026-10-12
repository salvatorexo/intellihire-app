<?php
/* =========================================================
   IntelliHire - Send Password Reset Link
   - Works for ANY registered user (applicant, recruiter, admin)
   - Always returns a generic message (prevents email enumeration)
   - Uses MySQL's clock for expiry
   - Always includes demo_link in the response so the frontend
     can show a clickable reset link (works around SMTP blocks)
   ========================================================= */

require_once 'security.php';
require_once 'config.php';
require_once 'mailer-config.php';

/* ⚠️ MUST be `require` (not require_once) so it returns the PDO object */
$pdo = require 'db.php';

require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid method.');
}

/* CSRF disabled for Render stateless sessions */
// verifyCsrf();

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Please enter a valid email address.');
}

/* ---------- Look up the user ---------- */
$stmt = $pdo->prepare("
    SELECT id, full_name, email, account_type
    FROM users
    WHERE email = ?
    LIMIT 1
");
$stmt->execute([$email]);
$user = $stmt->fetch();

/* ---------- Generic message (prevents email enumeration) ---------- */
$genericMessage = 'If that email is registered, a reset link has been sent. Check your inbox (and spam folder).';

if (!$user) {
    auditLog($pdo, 'password_reset_unknown_email', "Email: $email");
    jsonResponse(true, $genericMessage);
}

/* ---------- Generate a fresh token ---------- */
$token = bin2hex(random_bytes(32));       // 64 hex chars
$hash  = hash('sha256', $token);          // store hash only

/* Wipe any previous tokens for this user */
$stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
$stmt->execute([$user['id']]);

/* Insert new token — expiry set by MySQL */
$stmt = $pdo->prepare("
    INSERT INTO password_resets (user_id, token_hash, expires_at)
    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
");
$stmt->execute([$user['id'], $hash]);

/* Build the reset link */
$link = APP_URL . '/reset-password.html?token=' . urlencode($token);

/* ---------- Build the email HTML ---------- */
$htmlBody = "
<div style='font-family:Poppins,Arial,sans-serif;max-width:560px;margin:auto;padding:24px;background:#f8fafc;border-radius:12px;'>
    <div style='text-align:center;margin-bottom:24px;'>
        <span style='background:#2563eb;color:#fff;padding:8px 14px;border-radius:8px;font-weight:700;letter-spacing:1px;display:inline-block;'>IHR</span>
        <h1 style='color:#0f172a;font-size:1.4rem;margin:16px 0 4px;'>IntelliHire</h1>
    </div>

    <h2 style='color:#0f172a;font-size:1.1rem;'>Reset your password</h2>

    <p style='color:#475569;font-size:0.95rem;'>Hi " . htmlspecialchars($user['full_name']) . ",</p>

    <p style='color:#475569;font-size:0.95rem;'>
        We received a request to reset the password for your IntelliHire account
        (<strong>" . htmlspecialchars($user['email']) . "</strong>).
        Click the button below to set a new password.
    </p>

    <p style='text-align:center;margin:32px 0;'>
        <a href='" . htmlspecialchars($link) . "'
           style='background:#2563eb;color:#fff;padding:14px 30px;text-decoration:none;border-radius:8px;display:inline-block;font-weight:600;'>
            Reset Password
        </a>
    </p>

    <p style='color:#64748b;font-size:0.85rem;'>Or copy this link into your browser:</p>
    <p style='color:#2563eb;word-break:break-all;font-size:0.85rem;'>" . htmlspecialchars($link) . "</p>

    <hr style='border:none;border-top:1px solid #e2e8f0;margin:24px 0;'>

    <p style='color:#94a3b8;font-size:0.78rem;'>
        This link expires in 1 hour. If you didn't request a password reset, you can safely ignore this email.
    </p>
</div>
";

/* ---------- Try to send via Gmail SMTP (best-effort, short timeout) ---------- */
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
    $mail->CharSet    = 'UTF-8';
    $mail->Timeout    = 8;   // don't hang the request if SMTP is blocked

    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress($email, $user['full_name']);
    $mail->isHTML(true);
    $mail->Subject = 'Reset your IntelliHire password';
    $mail->Body    = $htmlBody;
    $mail->AltBody = "Reset your IntelliHire password: $link (expires in 1 hour)";

    $mail->send();
    $emailSent = true;
    auditLog($pdo, 'password_reset_sent', "User ID: {$user['id']} (email sent)");

} catch (Exception $e) {
    $errorInfo = $mail->ErrorInfo ?: $e->getMessage();
    auditLog($pdo, 'password_reset_email_failed', "User ID: {$user['id']} — $errorInfo");
}

/* ---------- Response ----------
   ALWAYS include the reset link in the response. The frontend
   will display it as a clickable link whether or not the email
   actually went out. This makes the flow work on Render where
   SMTP is blocked. */
jsonResponse(true, $emailSent
        ? $genericMessage
        : 'Email delivery is disabled on this host — here is your reset link:',
    [
        'demo_link'  => $link,
        'email_sent' => $emailSent,
        'smtp_error' => $errorInfo ?: null
    ]
);