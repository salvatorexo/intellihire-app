<?php
/* =========================================================
   IntelliHire - Recruiter Reports & Analytics
   ========================================================= */

require_once 'auth.php';
requireRole('recruiter');
$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

/* Get recruiter_id */
$stmt = $pdo->prepare("SELECT id FROM recruiters WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$recruiter_id = $stmt->fetchColumn();
if (!$recruiter_id) jsonResponse(false, 'Recruiter not found.');

/* ---------- KPIs ---------- */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE recruiter_id = ?");
$stmt->execute([$recruiter_id]);
$total_vacancies = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE recruiter_id = ? AND status = 'open'");
$stmt->execute([$recruiter_id]);
$active_vacancies = (int)$stmt->fetchColumn();

/* Application statuses */
$stmt = $pdo->prepare("
    SELECT
        COUNT(*) AS total,
        SUM(a.status = 'applied')     AS applied,
        SUM(a.status = 'review')      AS review,
        SUM(a.status = 'shortlisted') AS shortlisted,
        SUM(a.status = 'interview')   AS interview,
        SUM(a.status = 'rejected')    AS rejected,
        ROUND(AVG(a.match_score), 1)  AS avg_score
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE j.recruiter_id = ?
");
$stmt->execute([$recruiter_id]);
$stats = $stmt->fetch();

$total = (int)$stats['total'];
$shortlist_rate = $total > 0 ? round(($stats['shortlisted'] / $total) * 100, 1) : 0;
$interview_rate = $total > 0 ? round(($stats['interview']   / $total) * 100, 1) : 0;

/* ---------- Monthly (last 6 months) ---------- */
$monthly = ['labels' => [], 'values' => []];
for ($i = 5; $i >= 0; $i--) {
    $label = date('M Y', strtotime("-$i months"));
    $month = date('Y-m', strtotime("-$i months"));
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE j.recruiter_id = ? AND DATE_FORMAT(a.applied_at, '%Y-%m') = ?
    ");
    $stmt->execute([$recruiter_id, $month]);
    $monthly['labels'][] = $label;
    $monthly['values'][] = (int)$stmt->fetchColumn();
}

/* ---------- Score Distribution ---------- */
$buckets = [0, 0, 0, 0, 0];
$stmt = $pdo->prepare("
    SELECT a.match_score
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE j.recruiter_id = ? AND a.match_score IS NOT NULL
");
$stmt->execute([$recruiter_id]);
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $s) {
    if     ($s <= 20) $buckets[0]++;
    elseif ($s <= 40) $buckets[1]++;
    elseif ($s <= 60) $buckets[2]++;
    elseif ($s <= 80) $buckets[3]++;
    else              $buckets[4]++;
}

/* ---------- Top 5 Vacancies ---------- */
$stmt = $pdo->prepare("
    SELECT j.title, COUNT(a.id) AS cnt
    FROM jobs j
    LEFT JOIN applications a ON a.job_id = j.id
    WHERE j.recruiter_id = ?
    GROUP BY j.id
    ORDER BY cnt DESC
    LIMIT 5
");
$stmt->execute([$recruiter_id]);
$rows = $stmt->fetchAll();
$top_vacancies = [
    'labels' => array_column($rows, 'title'),
    'values' => array_map('intval', array_column($rows, 'cnt'))
];

/* ---------- Best Performing Vacancy ---------- */
$stmt = $pdo->prepare("
    SELECT j.title, j.location, j.job_type, COUNT(a.id) AS applications
    FROM jobs j
    LEFT JOIN applications a ON a.job_id = j.id
    WHERE j.recruiter_id = ?
    GROUP BY j.id
    ORDER BY applications DESC
    LIMIT 1
");
$stmt->execute([$recruiter_id]);
$best_vacancy = $stmt->fetch() ?: null;

jsonResponse(true, 'OK', [
    'reports' => [
        'total_vacancies'     => $total_vacancies,
        'active_vacancies'    => $active_vacancies,
        'total_applications'  => $total,
        'shortlist_rate'      => $shortlist_rate,
        'interview_rate'      => $interview_rate,
        'avg_score'           => $stats['avg_score'] ?: 0,
        'status_breakdown'    => [
            'applied'     => (int)$stats['applied'],
            'review'      => (int)$stats['review'],
            'shortlisted' => (int)$stats['shortlisted'],
            'interview'   => (int)$stats['interview'],
            'rejected'    => (int)$stats['rejected'],
        ],
        'monthly'             => $monthly,
        'score_distribution'  => $buckets,
        'top_vacancies'       => $top_vacancies,
        'best_vacancy'        => $best_vacancy,
    ]
]);