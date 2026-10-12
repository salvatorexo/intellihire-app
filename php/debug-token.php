<?php
/* =========================================================
   IntelliHire - Token Debug
   Diagnoses why password reset links fail.
   ⚠️ DELETE after testing!
   ========================================================= */

require_once 'config.php';
$pdo = require 'db.php';

header('Content-Type: text/plain; charset=utf-8');

/* ------- STEP 1: paste the FULL token from the URL below ------- */
$urlToken = 'PASTE_THE_FULL_TOKEN_FROM_URL_HERE';
/* -------------------------------------------------------------- */

echo "============ TOKEN DEBUG ============\n\n";

/* 1. URL token info */
echo "--- URL TOKEN ---\n";
echo "Length: " . strlen($urlToken) . " (should be 64)\n";
echo "Value:  " . $urlToken . "\n\n";

/* 2. Computed hash */
$hash = hash('sha256', $urlToken);
echo "--- COMPUTED HASH (sha256) ---\n";
echo "Full:   " . $hash . "\n\n";

/* 3. Look up in DB */
echo "--- DB LOOKUP ---\n";
$stmt = $pdo->prepare("
    SELECT id, user_id, token_hash, expires_at, used, created_at
    FROM password_resets
    WHERE token_hash = ?
    LIMIT 1
");
$stmt->execute([$hash]);
$row = $stmt->fetch();

if ($row) {
    echo "✅ MATCH FOUND!\n\n";
    echo "ID:         {$row['id']}\n";
    echo "User ID:    {$row['user_id']}\n";
    echo "Expires:    {$row['expires_at']}\n";
    echo "Used:       {$row['used']}\n";
    echo "Created:    {$row['created_at']}\n";

    $nowMysql = $pdo->query("SELECT NOW()")->fetchColumn();
    echo "MySQL NOW:  $nowMysql\n\n";

    /* Compare using MySQL values only — this is the CORRECT way */
    $isExpired = strtotime($row['expires_at']) <= strtotime($nowMysql);
    $isUsed    = $row['used'] == 1;

    echo "Expired (MySQL-vs-MySQL): " . ($isExpired ? "YES ❌" : "NO ✅") . "\n";
    echo "Used:                     " . ($isUsed    ? "YES ❌" : "NO ✅") . "\n";

    if (!$isExpired && !$isUsed) {
        echo "\n🎉 This token SHOULD work for password reset!\n";
        echo "If reset-password.php still fails, its logic is using PHP time instead of MySQL time.\n";
    } else {
        echo "\n❌ This token is not usable.\n";
    }
} else {
    echo "❌ NO MATCHING TOKEN FOUND IN DATABASE\n\n";

    echo "--- RECENT TOKENS IN DB ---\n";
    $rows = $pdo->query("
        SELECT id, user_id, LEFT(token_hash, 20) AS hash_start,
               expires_at, used, created_at
        FROM password_resets
        ORDER BY id DESC
        LIMIT 5
    ")->fetchAll();

    if (!$rows) {
        echo "❌ password_resets table is EMPTY!\n";
    } else {
        foreach ($rows as $r) {
            echo "ID {$r['id']} | User {$r['user_id']} | Hash {$r['hash_start']}... ";
            echo "| Expires {$r['expires_at']} | Used {$r['used']}\n";
        }
    }

    echo "\n🔍 DIAGNOSIS:\n";
    echo "The token in the URL doesn't hash to anything in the DB.\n";
    echo "This means either:\n";
    echo "  a) The URL token is truncated (should be 64 chars).\n";
    echo "  b) send-reset.php saved a DIFFERENT token than what was sent in the email.\n";
    echo "  c) Two different requests ran, invalidating the first token.\n";
}

echo "\n============ END DEBUG ============\n";