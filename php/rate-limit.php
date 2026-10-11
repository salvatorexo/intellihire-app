<?php
/* =========================================================
   IntelliHire - Login Rate Limiting
   - Max 5 failed attempts per email per 15 minutes
   - Max 20 failed attempts per IP per hour
   ========================================================= */

/** Returns [bool allowed, string message] */
function checkRateLimit(PDO $pdo, $email) {
    $ip = clientIp();

    /* 5 fails per email per 15 min */
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM login_attempts
        WHERE email = ? AND attempted_at > (NOW() - INTERVAL 15 MINUTE)
    ");
    $stmt->execute([$email]);
    if ((int)$stmt->fetchColumn() >= 5) {
        return [false, 'Too many failed attempts for this email. Try again in 15 minutes.'];
    }

    /* 20 fails per IP per hour */
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM login_attempts
        WHERE ip_address = ? AND attempted_at > (NOW() - INTERVAL 1 HOUR)
    ");
    $stmt->execute([$ip]);
    if ((int)$stmt->fetchColumn() >= 20) {
        return [false, 'Too many failed attempts from your network. Try again later.'];
    }

    return [true, ''];
}

function recordFailedAttempt(PDO $pdo, $email) {
    $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
    $stmt->execute([$email, clientIp()]);
}

function clearAttempts(PDO $pdo, $email) {
    $stmt = $pdo->prepare("DELETE FROM login_attempts WHERE email = ?");
    $stmt->execute([$email]);
}

/** Cleanup old entries (call occasionally) */
function pruneOldAttempts(PDO $pdo) {
    $pdo->exec("DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 7 DAY)");
}