<?php
/* =========================================================
   IntelliHire - Single Job Details
   ========================================================= */

require_once 'auth.php';
requireRole('applicant');

$pdo = require 'db.php';

$job_id = (int)($_GET['id'] ?? 0);
if ($job_id <= 0) jsonResponse(false, 'Invalid job ID.');

$stmt = $pdo->prepare("
    SELECT j.id, j.title, j.description, j.requirements,
           j.education_requirements, j.skills,
           j.location, j.job_type, j.deadline, j.created_at,
           r.company_name, r.industry, r.company_location
    FROM jobs j
    JOIN recruiters r ON j.recruiter_id = r.id
    WHERE j.id = ? AND j.status = 'open'
    LIMIT 1
");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) jsonResponse(false, 'Job not found or no longer available.');

jsonResponse(true, 'OK', ['job' => $job]);