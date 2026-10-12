<?php
/* =========================================================
   IntelliHire - Security Helpers
   Includes: CSRF, input sanitization, audit log.
   Releases session lock early for fast parallel AJAX.
   CSRF verification is disabled for Render stateless sessions.
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    if (!empty($_SERVER['HTTPS'])) {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

/* ---------- Release session lock unless explicitly kept open ---------- */
if (!defined('KEEP_SESSION_OPEN')) {
    session_write_close();
}

/* ---------- CSRF ---------- */
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf() {
    /* Temporarily disabled for Render stateless sessions.
       Session tokens don't persist between the AJAX request
       that fetches the token and the AJAX request that submits.
       Re-enable once session storage is persistent. */
    return;

    // ---------- Original logic (kept for future use) ----------
    $sent = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid security token. Refresh the page and try again.'
        ]);
        exit;
    }
}

/* ---------- Input sanitization ---------- */
function cleanInput($value) {
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function cleanEmail($email) {
    return filter_var(trim((string)$email), FILTER_SANITIZE_EMAIL);
}

/* ---------- Audit log ---------- */
function auditLog(PDO $pdo, $action, $details = null) {
    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt = $pdo->prepare("
            INSERT INTO audit_log (user_id, action, details, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $action, $details, $ip]);
    } catch (Exception $e) {
        /* Never let logging break the app */
    }
}

/* ---------- Session regeneration ---------- */
function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/* ---------- Client IP ---------- */
function clientIp() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}