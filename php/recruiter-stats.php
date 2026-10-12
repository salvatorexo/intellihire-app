<?php
/* =========================================================
   IntelliHire - Recruiter Dashboard Data (Optimized)
   Returns everything in ONE response.
   ========================================================= */

require_once 'auth.php';
requireRole('recruiter');

$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

/* ---------- Get recruiter + company ---------- */
$stmt = $pdo->prepare("
    SELECT r.id AS recruiter_id, r.company_name, r.industry
    FROM recruiters r
    WHERE r.user_id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$recruiter = $stmt->fetch();

if (!$recruiter) jsonResponse(false, 'Recruiter profile not found.');
$recruiter_id = $recruiter['recruiter_id'];

/* ---------- Single-shot stats query ---------- */
$stmt = $pdo->prepare("
    SELECT
        (SELECT COUNT(*) FROM jobs WHERE recruiter_id = ?)                                    AS vacancies,
        (SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id=j.id
            WHERE j.recruiter_id = ?)                                                          AS total_apps,
        (SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id=j.id
            WHERE j.recruiter_id = ? AND a.status='shortlisted')                              AS shortlisted,
        (SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id=j.id
            WHERE j.recruiter_id = ? AND a.status='interview')                                AS interviews,
        (SELECT COUNT(*) FROM applications a JOIN jobs j ON a.job_id=j.id
            WHERE j.recruiter_id = ? AND a.status='rejected')                                 AS rejected
");
$stmt->execute([$recruiter_id, $recruiter_id, $recruiter_id, $recruiter_id, $recruiter_id]);
$s = $stmt->fetch();

/* ---------- Recent applications ---------- */
$stmt = $pdo->prepare("
    SELECT a.id, a.status, a.match_score, a.applied_at,
           u.full_name AS candidate_name,
           j.title     AS job_title
    FROM applications a
    JOIN jobs j    ON a.job_id = j.id
    JOIN users u   ON a.applicant_id = u.id
    WHERE j.recruiter_id = ?
    ORDER BY a.applied_at DESC
    LIMIT 6
");
$stmt->execute([$recruiter_id]);
$recent = $stmt->fetchAll();

/* ---------- Top candidates ---------- */
$stmt = $pdo->prepare("
    SELECT u.full_name AS candidate_name,
           j.title     AS job_title,
           a.match_score
    FROM applications a
    JOIN jobs j  ON a.job_id = j.id
    JOIN users u ON a.applicant_id = u.id
    WHERE j.recruiter_id = ?
      AND a.match_score IS NOT NULL
    ORDER BY a.match_score DESC
    LIMIT 5
");
$stmt->execute([$recruiter_id]);
$top = $stmt->fetchAll();

/* ---------- Response ---------- */
jsonResponse(true, 'OK', [
    'user' => [
        'full_name' => $_SESSION['full_name'],
        'email'     => $_SESSION['email'],
    ],
    'company' => $recruiter,
    'stats' => [
        'vacancies'    => (int)$s['vacancies'],
        'applications' => (int)$s['total_apps'],
        'shortlisted'  => (int)$s['shortlisted'],
        'interviews'   => (int)$s['interviews'],
        'rejected'     => (int)$s['rejected'],
    ],
    'recent' => $recent,
    'top'    => $top,
]);