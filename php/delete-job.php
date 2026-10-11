<?php
require_once 'auth.php';
requireRole('recruiter');
$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method.');

$input = json_decode(file_get_contents('php://input'), true);
$job_id = (int)($input['id'] ?? 0);
if ($job_id <= 0) jsonResponse(false, 'Invalid job ID.');

$stmt = $pdo->prepare("
    DELETE j FROM jobs j
    JOIN recruiters r ON j.recruiter_id = r.id
    WHERE j.id = ? AND r.user_id = ?
");
$stmt->execute([$job_id, $user_id]);

if ($stmt->rowCount() === 0) {
    jsonResponse(false, 'Vacancy not found or access denied.');
}
jsonResponse(true, 'Vacancy deleted.');