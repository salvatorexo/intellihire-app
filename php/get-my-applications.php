<?php
require_once 'auth.php';
requireRole('applicant');
$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        a.id, a.status, a.match_score, a.recommendation, a.applied_at,
        j.title   AS job_title,
        j.location,
        j.job_type,
        r.company_name
    FROM applications a
    JOIN jobs j       ON a.job_id = j.id
    JOIN recruiters r ON j.recruiter_id = r.id
    WHERE a.applicant_id = ?
    ORDER BY a.applied_at DESC
");
$stmt->execute([$user_id]);
jsonResponse(true, 'OK', ['applications' => $stmt->fetchAll()]);