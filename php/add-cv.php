<?php
/* =========================================================
   One-time: Insert applicant row + dummy CV path for user_id=3.
   ⚠️ DELETE after running!
   ========================================================= */

require_once 'config.php';
$pdo = require 'db.php';

header('Content-Type: text/html; charset=utf-8');

/* 👇 Applicant's user_id (kiboenkemboi840@gmail.com) */
$user_id = 3;

$cv_path = 'uploads/cvs/dummy.pdf';

try {
    /* 1. Ensure uploads directory exists */
    $dir = __DIR__ . '/../uploads/cvs/';
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            die("❌ Could not create directory: " . $dir);
        }
    }

    /* 2. Create a dummy PDF file so the path exists on disk */
    if (!file_put_contents($dir . 'dummy.pdf', '%PDF-1.4 dummy CV for testing')) {
        die("❌ Could not write dummy.pdf");
    }

    /* 3. Upsert into applicants table */
    $stmt = $pdo->prepare("SELECT id FROM applicants WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);

    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE applicants SET cv_path = ? WHERE user_id = ?");
        $stmt->execute([$cv_path, $user_id]);
        echo "✅ CV path updated for user_id={$user_id}<br>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO applicants (user_id, cv_path) VALUES (?, ?)");
        $stmt->execute([$user_id, $cv_path]);
        echo "✅ Applicant row + CV inserted for user_id={$user_id}<br>";
    }

    echo "Stored path: <code>{$cv_path}</code><br><br>";
    echo "📄 File exists on disk: " . (is_file($dir . 'dummy.pdf') ? "YES ✅" : "NO ❌") . "<br><br>";
    echo "<strong style='color:red;'>⚠️ DELETE add-cv.php from your PC and GitHub now.</strong>";

} catch (PDOException $e) {
    echo "❌ Failed: " . htmlspecialchars($e->getMessage());
}