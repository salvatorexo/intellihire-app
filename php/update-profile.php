<?php
/* =========================================================
   IntelliHire - Update Applicant Profile
   ========================================================= */

require_once 'auth.php';
requireRole('applicant');

$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) jsonResponse(false, 'Invalid or missing data.');

$full_name       = trim($input['full_name'] ?? '');
$phone           = trim($input['phone'] ?? '');
$location        = trim($input['location'] ?? '');
$university      = trim($input['university'] ?? '');
$course          = trim($input['course'] ?? '');
$graduation_year = trim($input['graduation_year'] ?? '');
$skills          = trim($input['skills'] ?? '');
$certifications  = trim($input['certifications'] ?? '');
$work_experience = trim($input['work_experience'] ?? '');
$education       = trim($input['education'] ?? '');

if ($full_name === '' || $phone === '') {
    jsonResponse(false, 'Full name and phone are required.');
}

try {
    $pdo->beginTransaction();

    // Update common fields
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
    $stmt->execute([$full_name, $phone, $user_id]);

    // Update applicant-specific fields
    $stmt = $pdo->prepare("
        UPDATE applicants
        SET university = ?, course = ?, graduation_year = ?, location = ?,
            skills = ?, certifications = ?, work_experience = ?, education = ?
        WHERE user_id = ?
    ");
    $stmt->execute([
        $university, $course, $graduation_year, $location,
        $skills, $certifications, $work_experience, $education,
        $user_id
    ]);

    $pdo->commit();

    // Refresh session name
    $_SESSION['full_name'] = $full_name;

    jsonResponse(true, 'Profile updated successfully.');
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, 'Update failed: ' . $e->getMessage());
}