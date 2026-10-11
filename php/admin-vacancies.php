<?php
require_once 'auth.php';
requireRole('admin');
$pdo = require 'db.php';

$stmt = $pdo->query("
    SELECT j.id, j.title, j.location, j.job_type, j.status, j.created_at,
           r.company_name,
           (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS application_count
    FROM jobs j
    JOIN recruiters r ON j.recruiter_id = r.id
    ORDER BY j.created_at DESC
");
jsonResponse(true, 'OK', ['vacancies' => $stmt->fetchAll()]);