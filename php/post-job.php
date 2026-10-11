<?php
require_once 'auth.php';
requireRole('recruiter');
$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method.');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) jsonResponse(false, 'Invalid or missing data.');

/* Get recruiter_id */
$stmt = $pdo->prepare("SELECT id FROM recruiters WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$recruiter_id = $stmt->fetchColumn();
if (!$recruiter_id) jsonResponse(false, 'Recruiter profile not found.');

$title       = trim($input['title'] ?? '');
$description = trim($input['description'] ?? '');
$location    = trim($input['location'] ?? '');
$job_type    = trim($input['job_type'] ?? 'Full-time');
$salary      = trim($input['salary_range'] ?? '');
$deadline    = trim($input['deadline'] ?? '');
$edu         = trim($input['education_requirements'] ?? '');
$requirements= trim($input['requirements'] ?? '');
$skills      = trim($input['skills'] ?? '');
$pref        = trim($input['preferred_skills'] ?? '');

if ($title === '' || $description === '' || $location === '' || $skills === '' || $deadline === '') {
    jsonResponse(false, 'Please fill in all required fields.');
}

$stmt = $pdo->prepare("
    INSERT INTO jobs
    (recruiter_id, title, description, requirements, education_requirements,
     skills, preferred_skills, location, job_type, salary_range, deadline, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open')
");
$stmt->execute([
    $recruiter_id, $title, $description, $requirements, $edu,
    $skills, $pref, $location, $job_type, $salary, $deadline
]);

jsonResponse(true, 'Vacancy posted successfully.', ['job_id' => $pdo->lastInsertId()]);