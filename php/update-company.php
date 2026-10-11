<?php
require_once 'auth.php';
require_once 'security.php';
requireRole('recruiter');
$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method.');

verifyCsrf();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) jsonResponse(false, 'Invalid data.');

$name        = trim($input['company_name'] ?? '');
$industry    = trim($input['industry'] ?? '');
$email       = trim($input['company_email'] ?? '');
$phone       = trim($input['company_phone'] ?? '');
$location    = trim($input['company_location'] ?? '');
$website     = trim($input['website'] ?? '');
$description = trim($input['description'] ?? '');

if ($name === '' || $industry === '' || $email === '' || $phone === '' || $location === '') {
    jsonResponse(false, 'Please fill in all required fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid company email format.');
}

$stmt = $pdo->prepare("
    UPDATE recruiters
    SET company_name = ?, industry = ?, company_email = ?,
        company_phone = ?, company_location = ?,
        website = ?, description = ?
    WHERE user_id = ?
");
$stmt->execute([$name, $industry, $email, $phone, $location, $website, $description, $user_id]);

auditLog($pdo, 'company_update', "Company: $name");
jsonResponse(true, 'Company profile updated.');