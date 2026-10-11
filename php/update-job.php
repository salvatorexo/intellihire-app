<?php
require_once 'auth.php';
requireRole('recruiter');
$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method.');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) jsonResponse(false, 'Invalid or missing data.');

$stmt = $pdo->prepare("SELECT id FROM recruiters WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$recruiter_id = $stmt->fetchColumn();
if (!$recruiter_id) jsonResponse(false, 'Recruiter not found.');

$job_id = (int)($input['id'] ?? 0);
if ($job_id <= 0) jsonResponse(false, 'Invalid job ID.');

/* Verify ownership */
$stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND recruiter_id = ? LIMIT 1");
$stmt->execute([$job_id, $recruiter_id]);
if (!$stmt->fetch()) jsonResponse(false, 'Vacancy not found or access denied.');

/* Status-only update */
if (isset($input['status']) && count($input) === 2) {
    $newStatus = $input['status'] === 'closed' ? 'closed' : 'open';
    $stmt = $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $job_id]);
    jsonResponse(true, "Vacancy {$newStatus}.");
}

/* Full update */
$fields = ['title','description','location','job_type','salary_range','deadline',
           'education_requirements','requirements','skills','preferred_skills'];
$updates = [];
$params  = [];
foreach ($fields as $f) {
    if (array_key_exists($f, $input)) {
        $updates[] = "$f = ?";
        $params[]  = trim($input[$f] ?? '');
    }
}
if (!$updates) jsonResponse(false, 'Nothing to update.');

$params[] = $job_id;
$sql = "UPDATE jobs SET " . implode(', ', $updates) . " WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

jsonResponse(true, 'Vacancy updated.');