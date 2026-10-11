<?php
require_once 'auth.php';
requireRole('admin');
$pdo = require 'db.php';

$stmt = $pdo->query("
    SELECT r.id, r.user_id, r.company_name, r.company_email, r.company_phone,
           r.company_location, r.industry,
           (SELECT COUNT(*) FROM jobs j WHERE j.recruiter_id = r.id) AS vacancy_count
    FROM recruiters r
    ORDER BY r.company_name ASC
");
jsonResponse(true, 'OK', ['companies' => $stmt->fetchAll()]);