<?php
/* =========================================================
   IntelliHire - Fetch Applicant Profile
   ========================================================= */

require_once 'auth.php';
requireRole('applicant');

$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT u.full_name, u.email, u.phone,
           a.university, a.course, a.graduation_year, a.location,
           a.skills, a.certifications, a.work_experience, a.education
    FROM users u
    LEFT JOIN applicants a ON a.user_id = u.id
    WHERE u.id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$profile = $stmt->fetch();

if (!$profile) {
    jsonResponse(false, 'Profile not found.');
}

// Convert JSON columns back to arrays if you store them as JSON
// (optional — depends on how you save them)

jsonResponse(true, 'OK', ['profile' => $profile]);