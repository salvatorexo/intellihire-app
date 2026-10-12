<?php
/* =========================================================
   IntelliHire - Applicant Dashboard Data (Optimized)
   Combines stats into a single SQL round-trip.
   ========================================================= */

require_once 'auth.php';
requireRole('applicant');

$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

/* ---------- Recent Applications Mode ---------- */
if (isset($_GET['recent'])) {
    $stmt = $pdo->prepare("
        SELECT a.id, a.status, a.match_score, a.applied_at,
               j.title AS job_title, r.company_name
        FROM applications a
        JOIN jobs j       ON a.job_id = j.id
        JOIN recruiters r ON j.recruiter_id = r.id
        WHERE a.applicant_id = ?
        ORDER BY a.applied_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    jsonResponse(true, 'OK', ['recent' => $stmt->fetchAll()]);
}

/* ---------- Jobs Mode ---------- */
if (isset($_GET['jobs'])) {
    $stmt = $pdo->query("
        SELECT j.id, j.title, j.location, j.job_type, r.company_name
        FROM jobs j
        JOIN recruiters r ON j.recruiter_id = r.id
        WHERE j.status = 'open'
        ORDER BY j.created_at DESC
        LIMIT 8
    ");
    jsonResponse(true, 'OK', ['jobs' => $stmt->fetchAll()]);
}

/* ---------- Default: combined stats in ONE query ---------- */
$stmt = $pdo->prepare("
    SELECT
        (SELECT COUNT(*) FROM applications WHERE applicant_id = ?)                             AS total_apps,
        (SELECT COUNT(*) FROM applications WHERE applicant_id = ? AND status = 'applied')      AS pending,
        (SELECT COUNT(*) FROM applications WHERE applicant_id = ? AND status = 'shortlisted')  AS shortlisted,
        (SELECT COUNT(*) FROM jobs WHERE status = 'open')                                      AS available_jobs,
        (SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0)                 AS unread
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$row = $stmt->fetch();

$stats = [
    'total_applications'   => (int)$row['total_apps'],
    'pending'              => (int)$row['pending'],
    'shortlisted'          => (int)$row['shortlisted'],
    'available_jobs'       => (int)$row['available_jobs'],
    'unread_notifications' => (int)$row['unread']
];

jsonResponse(true, 'OK', [
    'user' => [
        'full_name' => $_SESSION['full_name'],
        'email'     => $_SESSION['email']
    ],
    'stats' => $stats
]);