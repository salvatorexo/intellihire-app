<?php
/* =========================================================
   IntelliHire - Applicant Dashboard Data
   Returns user info, stats, recent applications, and jobs.
   Query params:
     ?recent=1  -> returns only recent applications
     ?jobs=1    -> returns only available jobs
   ========================================================= */

require_once 'auth.php';
requireRole('applicant');

$user_id = $_SESSION['user_id'];

$pdo = require 'db.php';  // your PDO connection (see config.php)

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

/* ---------- Default: stats + user ---------- */
$stats = [
    'total_applications' => 0,
    'pending'            => 0,
    'shortlisted'        => 0,
    'available_jobs'     => 0,
    'unread_notifications' => 0
];

// Applications breakdown
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total,
        SUM(status = 'pending')     AS pending,
        SUM(status = 'shortlisted') AS shortlisted
    FROM applications
    WHERE applicant_id = ?
");
$stmt->execute([$user_id]);
$row = $stmt->fetch();
$stats['total_applications'] = (int)($row['total'] ?? 0);
$stats['pending']            = (int)($row['pending'] ?? 0);
$stats['shortlisted']        = (int)($row['shortlisted'] ?? 0);

// Available jobs
$stats['available_jobs'] = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'open'")->fetchColumn();

// Notifications (optional table)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $stats['unread_notifications'] = (int)$stmt->fetchColumn();
} catch (Exception $e) {
    // notifications table may not exist yet
    $stats['unread_notifications'] = 0;
}

jsonResponse(true, 'OK', [
    'user'  => [
        'full_name' => $_SESSION['full_name'],
        'email'     => $_SESSION['email']
    ],
    'stats' => $stats
]);