<?php
/* =========================================================
   IntelliHire - Get Applicant CV Info
   ========================================================= */

require_once 'auth.php';
requireRole('applicant');

$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT cv_path, updated_at
    FROM applicants
    WHERE user_id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$row = $stmt->fetch();

if (!$row || !$row['cv_path']) {
    jsonResponse(true, 'No CV uploaded.', ['cv' => null]);
}

jsonResponse(true, 'CV found.', [
    'cv' => [
        'path'        => $row['cv_path'],
        'file_name'   => basename($row['cv_path']),
        'uploaded_at' => $row['updated_at'] ?? 'recently'
    ]
]);