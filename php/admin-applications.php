<?php
require_once 'auth.php';
requireRole('admin');
$pdo = require 'db.php';

$stmt = $pdo->query("
    SELECT
        a.id, a.status, a.match_score, a.applied_at,
        u.full_name AS candidate_name,
        j.title     AS job_title,
        r.company_name
    FROM applications a
    JOIN jobs j       ON a.job_id = j.id
    JOIN users u      ON a.applicant_id = u.id
    JOIN recruiters r ON j.recruiter_id = r.id
    ORDER BY a.applied_at DESC
");
jsonResponse(true, 'OK', ['applications' => $stmt->fetchAll()]);