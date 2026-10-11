<?php
require_once 'auth.php';
requireRole('recruiter');
$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

$app_id = (int)($_GET['id'] ?? 0);
if ($app_id <= 0) jsonResponse(false, 'Invalid application ID.');

$stmt = $pdo->prepare("
    SELECT
        a.id, a.status, a.match_score, a.recommendation,
        a.skills_score, a.education_score, a.experience_score,
        a.matched_keywords, a.applied_at,
        u.full_name AS candidate_name, u.email, u.phone,
        ap.cv_path,
        j.title AS job_title
    FROM applications a
    JOIN jobs j         ON a.job_id = j.id
    JOIN recruiters r   ON j.recruiter_id = r.id
    JOIN users u        ON a.applicant_id = u.id
    LEFT JOIN applicants ap ON ap.user_id = a.applicant_id
    WHERE a.id = ? AND r.user_id = ?
    LIMIT 1
");
$stmt->execute([$app_id, $user_id]);
$candidate = $stmt->fetch();

if (!$candidate) jsonResponse(false, 'Candidate not found or access denied.');

jsonResponse(true, 'OK', ['candidate' => $candidate]);