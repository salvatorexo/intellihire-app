<?php
/* =========================================================
   One-time: Import recruiter row into Aiven.
   ⚠️ DELETE this file after running!
   ========================================================= */

require_once 'config.php';
$pdo = require 'db.php';

header('Content-Type: text/html; charset=utf-8');

$user_id          = 4;                            // recruiter's user_id
$company_name     = 'kplc';
$company_email    = 'kplc.co.ke@gmail.com';
$company_phone    = '0740610732';
$company_location = 'kericho';
$industry         = 'Agriculture';
$website          = null;
$description      = null;
$company_logo     = null;

try {
    /* Check if row already exists */
    $stmt = $pdo->prepare("SELECT id FROM recruiters WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    if ($stmt->fetch()) {
        die("✅ Recruiter row already exists for user_id={$user_id}");
    }

    $stmt = $pdo->prepare("
        INSERT INTO recruiters
        (user_id, company_name, company_email, company_phone,
         company_location, industry, website, description, company_logo)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user_id, $company_name, $company_email, $company_phone,
        $company_location, $industry, $website, $description, $company_logo
    ]);

    echo "✅ Recruiter row inserted for user_id={$user_id}<br>";
    echo "New recruiter ID: " . $pdo->lastInsertId() . "<br><br>";
    echo "<strong>⚠️ DELETE import-recruiter.php now.</strong>";

} catch (PDOException $e) {
    echo "❌ Failed: " . htmlspecialchars($e->getMessage());
}