<?php
require_once 'auth.php';
requireRole('recruiter');
$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method.');

$input = json_decode(file_get_contents('php://input'), true);
$app_id = (int)($input['application_id'] ?? 0);
$status = $input['status'] ?? '';

$allowed = ['applied','review','shortlisted','interview','accepted','rejected'];
if ($app_id <= 0 || !in_array($status, $allowed)) {
    jsonResponse(false, 'Invalid application or status.');
}

/* Confirm this application belongs to one of recruiter's jobs */
$stmt = $pdo->prepare("
    UPDATE applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN recruiters r ON j.recruiter_id = r.id
    SET a.status = ?
    WHERE a.id = ? AND r.user_id = ?
");
$stmt->execute([$status, $app_id, $user_id]);

if ($stmt->rowCount() === 0) {
    jsonResponse(false, 'Application not found or access denied.');
}

/* Notify applicant */
try {
    $stmt = $pdo->prepare("
        SELECT a.applicant_id, j.title
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE a.id = ?
    ");
    $stmt->execute([$app_id]);
    $row = $stmt->fetch();
    if ($row) {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->execute([
            $row['applicant_id'],
            "Your application for \"{$row['title']}\" status: " . ucfirst($status) . "."
        ]);
    }
} catch (Exception $e) { /* notifications optional */ }

jsonResponse(true, 'Status updated.');
auditLog($pdo, 'status_change', "App ID: $app_id → $status");