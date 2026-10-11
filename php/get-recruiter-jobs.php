<?php
require_once 'auth.php';
requireRole('recruiter');
$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id FROM recruiters WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$recruiter_id = $stmt->fetchColumn();
if (!$recruiter_id) jsonResponse(false, 'Recruiter profile not found.');

$stmt = $pdo->prepare("
    SELECT
        j.*,
        (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS application_count,
        (SELECT ROUND(AVG(a.match_score),1) FROM applications a
            WHERE a.job_id = j.id AND a.match_score IS NOT NULL) AS avg_score
    FROM jobs j
    WHERE j.recruiter_id = ?
    ORDER BY j.created_at DESC
");
$stmt->execute([$recruiter_id]);
jsonResponse(true, 'OK', ['jobs' => $stmt->fetchAll()]);