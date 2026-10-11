<?php
require_once 'auth.php';
requireRole('recruiter');
$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT id, company_name, company_email, company_phone, company_location,
           industry, company_logo, website, description
    FROM recruiters
    WHERE user_id = ?
    LIMIT 1
");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) jsonResponse(false, 'Company profile not found.');

$recruiter_id = $company['id'];

/* Mini stats */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE recruiter_id = ?");
$stmt->execute([$recruiter_id]);
$total_vacancies = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM jobs WHERE recruiter_id = ? AND status = 'open'");
$stmt->execute([$recruiter_id]);
$active_vacancies = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE j.recruiter_id = ?
");
$stmt->execute([$recruiter_id]);
$total_applications = (int)$stmt->fetchColumn();

jsonResponse(true, 'OK', [
    'company' => $company,
    'stats'   => [
        'total_vacancies'    => $total_vacancies,
        'active_vacancies'   => $active_vacancies,
        'total_applications' => $total_applications,
    ]
]);