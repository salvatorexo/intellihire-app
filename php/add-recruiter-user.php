<?php
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
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetch()) {
        die("✅ User id={$id} already exists.");
    }

    $stmt = $pdo->prepare("
        INSERT INTO users (id, full_name, email, phone, password_hash, account_type, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$id, $full_name, $email, $phone, $password_hash, $account_type]);

    echo "✅ Recruiter user re-inserted (id={$id})<br>";
    echo "<strong style='color:red;'>⚠️ DELETE add-recruiter-user.php now.</strong>";

} catch (PDOException $e) {
    echo "❌ Failed: " . htmlspecialchars($e->getMessage());
}