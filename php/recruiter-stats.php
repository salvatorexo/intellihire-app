<?php
/* =========================================================
   IntelliHire - Recruiter Dashboard Stats
   ?stats=1   -> stats
   ?recent=1  -> recent applications
   ?top=1     -> top ranked candidates
   (default)  -> user + company
   ========================================================= */

require_once 'auth.php';
requireRole('recruiter');

$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

/* ---------- Get recruiter's ID ---------- */
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

/* ---------- Stats ---------- */
if (isset($_GET['stats'])) {
    $stats = [
        'vacancies'    => 0,
        'applications' => 0,
        'shortlisted'  => 0,
        'interviews'   => 0,
        'rejected'     => 0,
    ];

    // Vacancies
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE recruiter_id = ?");
    $stmt->execute([$recruiter_id]);
    $stats['vacancies'] = (int)$stmt->fetchColumn();

    // Applications by status
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(a.status = 'shortlisted') AS shortlisted,
            SUM(a.status = 'interview')   AS interviews,
            SUM(a.status = 'rejected')    AS rejected
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE j.recruiter_id = ?
    ");
    $stmt->execute([$recruiter_id]);
    $row = $stmt->fetch();
    $stats['applications'] = (int)($row['total'] ?? 0);
    $stats['shortlisted']  = (int)($row['shortlisted'] ?? 0);
    $stats['interviews']   = (int)($row['interviews'] ?? 0);
    $stats['rejected']     = (int)($row['rejected'] ?? 0);

    jsonResponse(true, 'OK', ['stats' => $stats]);
}

/* ---------- Recent Applications ---------- */
if (isset($_GET['recent'])) {
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
    jsonResponse(true, 'OK', ['recent' => $stmt->fetchAll()]);
}

/* ---------- Top Candidates ---------- */
if (isset($_GET['top'])) {
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
    jsonResponse(true, 'OK', ['top' => $stmt->fetchAll()]);
}

/* ---------- Default: user + company ---------- */
jsonResponse(true, 'OK', [
    'user'    => [
        'full_name' => $_SESSION['full_name'],
        'email'     => $_SESSION['email'],
    ],
    'company' => $recruiter
]);