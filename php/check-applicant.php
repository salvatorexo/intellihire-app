<?php
/* =========================================================
   IntelliHire - Applicant Gate Check
   ========================================================= */

require_once 'auth.php';
requireRole('applicant');

$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT cv_path FROM applicants WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$cv = $stmt->fetchColumn();

jsonResponse(true, 'OK', [
    'has_cv'  => !empty($cv),
    'cv_path' => $cv
]);