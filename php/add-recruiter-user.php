<?php
/* =========================================================
   One-time: Insert recruiter user (id=4) into Aiven.
   ⚠️ DELETE after running!
   ========================================================= */

require_once 'config.php';
$pdo = require 'db.php';

header('Content-Type: text/html; charset=utf-8');

$id            = 4;
$full_name     = 'Kiboen Kemboi';
$email         = 'kiboenkemboi005@gmail.com';
$phone         = '+254740610732';
$password_hash = '$2y$10$s8H8Mv0HH8Q6fnttGi5G0uBh38FMA8epyG1B8EMx/u3wSz1OrwUmq';
$account_type  = 'recruiter';

try {
    /* Check if user id=4 already exists */
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetch()) {
        die("✅ User with id={$id} already exists.");
    }

    /* Check if that email already exists under a different id */
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();
    if ($existing) {
        die("⚠️ Email already exists with id={$existing['id']}. Delete that row first, or change the recruiter's user_id.");
    }

    /* Insert the user */
    $stmt = $pdo->prepare("
        INSERT INTO users (id, full_name, email, phone, password_hash, account_type, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$id, $full_name, $email, $phone, $password_hash, $account_type]);

    echo "✅ Recruiter user (id={$id}) inserted into Aiven<br>";
    echo "Email: <code>{$email}</code><br>";
    echo "Role: <code>{$account_type}</code><br><br>";
    echo "<strong style='color:red;'>⚠️ DELETE add-recruiter-user.php now.</strong>";

} catch (PDOException $e) {
    echo "❌ Failed: " . htmlspecialchars($e->getMessage());
}