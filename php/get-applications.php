<?php
require_once 'auth.php';
requireRole('recruiter');
$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id FROM recruiters WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$recruiter_id = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT
        a.id, a.job_id, a.status, a.match_score, a.recommendation,
        a.applied_at, a.skills_score, a.education_score, a.experience_score,
        u.full_name AS candidate_name,
        j.title     AS job_title
    FROM applications a
    JOIN jobs j  ON a.job_id = j.id
    JOIN users u ON a.applicant_id = u.id
    WHERE j.recruiter_id = ?
    ORDER BY a.match_score DESC, a.applied_at DESC
");
$stmt->execute([$recruiter_id]);
jsonResponse(true, 'OK', ['applications' => $stmt->fetchAll()]);