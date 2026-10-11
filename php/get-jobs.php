<?php
/* =========================================================
   IntelliHire - List Jobs (with applied flags)
   ========================================================= */

require_once 'auth.php';
requireRole('applicant');

$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

// Fetch all open jobs
$stmt = $pdo->query("
    SELECT j.id, j.title, j.description, j.requirements,
           j.education_requirements, j.skills,
           j.location, j.job_type, j.deadline, j.created_at,
           r.company_name, r.industry
    FROM jobs j
    JOIN recruiters r ON j.recruiter_id = r.id
    WHERE j.status = 'open'
    ORDER BY j.created_at DESC
");
$jobs = $stmt->fetchAll();

// Fetch IDs the applicant already applied to
$stmt = $pdo->prepare("SELECT job_id FROM applications WHERE applicant_id = ?");
$stmt->execute([$user_id]);
$applied = array_column($stmt->fetchAll(), 'job_id');

jsonResponse(true, 'OK', [
    'jobs' => $jobs,
    'applied_job_ids' => array_map('intval', $applied)
]);