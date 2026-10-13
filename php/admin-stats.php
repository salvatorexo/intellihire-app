<?php
require_once 'auth.php';
requireRole('admin');
$pdo = require 'db.php';
/* ---------- Reports mode ---------- */
if (isset($_GET['reports'])) {
    /* Conversion & shortlist rates */
    $total_apps = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
    $interviews = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'interview'")->fetchColumn();
    $shortlisted= (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'shortlisted'")->fetchColumn();
    $avg_score  = $pdo->query("SELECT ROUND(AVG(match_score),1) FROM applications WHERE match_score IS NOT NULL")->fetchColumn();

    $conversion_rate = $total_apps > 0 ? round(($interviews / $total_apps) * 100, 1) : 0;
    $shortlist_rate  = $total_apps > 0 ? round(($shortlisted / $total_apps) * 100, 1) : 0;

    /* Top industry */
    $top_industry = $pdo->query("
        SELECT r.industry, COUNT(a.id) AS cnt
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN recruiters r ON j.recruiter_id = r.id
        WHERE r.industry IS NOT NULL AND r.industry <> ''
        GROUP BY r.industry
        ORDER BY cnt DESC LIMIT 1
    ")->fetchColumn();

    /* Applications per month (last 6) */
    $monthly = ['labels' => [], 'values' => []];
    for ($i = 5; $i >= 0; $i--) {
        $label = date('M Y', strtotime("-$i months"));
        $month = date('Y-m', strtotime("-$i months"));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE DATE_FORMAT(applied_at, '%Y-%m') = ?");
        $stmt->execute([$month]);
        $monthly['labels'][] = $label;
        $monthly['values'][] = (int)$stmt->fetchColumn();
    }

    /* Top companies by vacancies */
    $rows = $pdo->query("
        SELECT r.company_name, COUNT(j.id) AS cnt
        FROM recruiters r
        LEFT JOIN jobs j ON j.recruiter_id = r.id
        GROUP BY r.id
        ORDER BY cnt DESC LIMIT 5
    ")->fetchAll();
    $top_companies = ['labels' => array_column($rows, 'company_name'), 'values' => array_map('intval', array_column($rows, 'cnt'))];

    /* Score distribution buckets */
    $buckets = [0, 0, 0, 0, 0];
    $scores = $pdo->query("SELECT match_score FROM applications WHERE match_score IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($scores as $s) {
        if ($s <= 20) $buckets[0]++;
        elseif ($s <= 40) $buckets[1]++;
        elseif ($s <= 60) $buckets[2]++;
        elseif ($s <= 80) $buckets[3]++;
        else              $buckets[4]++;
    }

    jsonResponse(true, 'OK', [
        'reports' => [
            'conversion_rate'    => $conversion_rate,
            'shortlist_rate'     => $shortlist_rate,
            'avg_score'          => $avg_score ?: 0,
            'top_industry'       => $top_industry ?: '—',
            'monthly'            => $monthly,
            'top_companies'      => $top_companies,
            'score_distribution' => $buckets,
        ]
    ]);
}

$stats = [
    'applicants'       => 0,
    'recruiters'       => 0,
    'companies'        => 0,
    'vacancies'        => 0,
    'applications'     => 0,
    'shortlisted'      => 0,
    'interviews'       => 0,
    'active_vacancies' => 0,
    'applied_only'     => 0,
    'rejected'         => 0,
];

$stats['applicants']       = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE account_type = 'applicant'")->fetchColumn();
$stats['recruiters']       = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE account_type = 'recruiter'")->fetchColumn();
$stats['companies']        = (int)$pdo->query("SELECT COUNT(*) FROM recruiters")->fetchColumn();

/* ⬇️ CHANGED: count only open jobs so it matches what recruiters see */
$stats['vacancies']        = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'open'")->fetchColumn();

$stats['applications']     = (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$stats['shortlisted']      = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'shortlisted'")->fetchColumn();
$stats['interviews']       = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'interview'")->fetchColumn();
$stats['active_vacancies'] = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'open'")->fetchColumn();
$stats['applied_only']     = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'applied'")->fetchColumn();
$stats['rejected']         = (int)$pdo->query("SELECT COUNT(*) FROM applications WHERE status = 'rejected'")->fetchColumn();

/* Trend: last 7 days */
$trend = [];
for ($i = 6; $i >= 0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE DATE(applied_at) = ?");
    $stmt->execute([$day]);
    $trend[] = ['day' => date('D', strtotime($day)), 'count' => (int)$stmt->fetchColumn()];
}

/* Recent signups */
$recent_users = $pdo->query("
    SELECT full_name, email, account_type, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 6
")->fetchAll();

/* Recent applications */
$recent_apps = $pdo->query("
    SELECT u.full_name AS candidate_name, j.title AS job_title, a.match_score, a.applied_at
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.applicant_id = u.id
    ORDER BY a.applied_at DESC
    LIMIT 6
")->fetchAll();

jsonResponse(true, 'OK', [
    'user' => [
        'full_name' => $_SESSION['full_name'],
        'email'     => $_SESSION['email'],
    ],
    'stats'                => $stats,
    'trend'                => $trend,
    'recent_users'         => $recent_users,
    'recent_applications'  => $recent_apps,
]);
