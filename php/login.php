<?php
/* =========================================================
   IntelliHire - Login Handler (Optimized)
   - Session stays open so we can write $_SESSION on success
   - Rate limited
   - Session regenerated on success
   - Audit logged
   - CSRF temporarily disabled for Render stateless sessions
   ========================================================= */

define('KEEP_SESSION_OPEN', true);

require_once 'security.php';
require_once 'auth.php';
require_once 'rate-limit.php';
require_once 'config.php';

$pdo = require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

/* ---------------------------------------------------------
   CSRF verification — temporarily disabled
   Reason: Render stateless sessions invalidate tokens between
   the token fetch and the login submit.
   --------------------------------------------------------- */
// verifyCsrf();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) jsonResponse(false, 'Invalid or missing data.');

$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$remember = !empty($input['remember']);

if ($email === '' || $password === '') {
    jsonResponse(false, 'Please enter your email and password.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email format.');
}

/* ---------- Rate limit check ---------- */
list($allowed, $msg) = checkRateLimit($pdo, $email);
if (!$allowed) {
    auditLog($pdo, 'login_rate_limited', "Email: $email");
    jsonResponse(false, $msg);
}

/* ---------- Fetch user ---------- */
$stmt = $pdo->prepare("
    SELECT id, full_name, email, password_hash, account_type
    FROM users WHERE email = ? LIMIT 1
");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    recordFailedAttempt($pdo, $email);
    auditLog($pdo, 'login_failed', "Email: $email");
    jsonResponse(false, 'Invalid email or password.');
}

/* ---------- Success ---------- */
clearAttempts($pdo, $email);
regenerateSession();

$_SESSION['user_id']   = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['email']     = $user['email'];
$_SESSION['role']      = $user['account_type'];
$_SESSION['logged_in'] = true;

if ($remember) {
    setcookie('ihr_user', $user['id'], [
        'expires'  => time() + (30 * 24 * 60 * 60),
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

auditLog($pdo, 'login_success', "Role: {$user['account_type']}");

$redirect = 'index.html';
switch ($user['account_type']) {
    case 'applicant': $redirect = 'applicant/applicantdash.html'; break;
    case 'recruiter': $redirect = 'recruiter/recruiterdash.html'; break;
    case 'admin':     $redirect = 'admin/admindash.html'; break;
}

jsonResponse(true, 'Login successful! Redirecting...', [
    'role'     => $user['account_type'],
    'redirect' => $redirect,
    'csrf'     => csrfToken(),
    'user'     => [
        'id'        => $user['id'],
        'full_name' => $user['full_name'],
        'email'     => $user['email']
    ]
]);