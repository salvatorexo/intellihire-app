<?php
require_once 'config.php';
$pdo = require 'db.php';

header('Content-Type: text/html; charset=utf-8');

$user_id          = 4;
$company_name     = 'kplc';
$company_email    = 'kplc.co.ke@gmail.com';
$company_phone    = '0740610732';
$company_location = 'kericho';
$industry         = 'Agriculture';

try {
    $stmt = $pdo->prepare("SELECT id FROM recruiters WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    if ($stmt->fetch()) {
        die("✅ Recruiter company row already exists for user_id={$user_id}");
    }

    $stmt = $pdo->prepare("
        INSERT INTO recruiters
        (user_id, company_name, company_email, company_phone, company_location, industry)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $company_name, $company_email, $company_phone, $company_location, $industry]);

    echo "✅ Recruiter company row re-inserted<br>";
    echo "New recruiter id: " . $pdo->lastInsertId() . "<br>";
    echo "<strong style='color:red;'>⚠️ DELETE add-recruiter-company.php now.</strong>";

} catch (PDOException $e) {
    echo "❌ Failed: " . htmlspecialchars($e->getMessage());
}