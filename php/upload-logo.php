<?php
require_once 'auth.php';
require_once 'security.php';
requireRole('recruiter');
$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method.');
verifyCsrf();

if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, 'No logo uploaded.');
}

$file = $_FILES['logo'];

/* Validate */
$allowed_exts = ['png', 'jpg', 'jpeg', 'webp'];
$allowed_mimes = ['image/png', 'image/jpeg', 'image/webp'];
$max_size = 2 * 1024 * 1024; // 2 MB

if ($file['size'] > $max_size) jsonResponse(false, 'Logo must be under 2 MB.');

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed_exts)) jsonResponse(false, 'Only PNG, JPG, or WEBP allowed.');

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!in_array($mime, $allowed_mimes)) jsonResponse(false, 'Invalid image file.');

/* Store */
$upload_dir = __DIR__ . '/../uploads/logos/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

$safe = 'logo_' . $user_id . '_' . time() . '.' . $ext;
$dest = $upload_dir . $safe;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    jsonResponse(false, 'Failed to store logo.');
}

/* Delete old logo */
$stmt = $pdo->prepare("SELECT company_logo FROM recruiters WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$old = $stmt->fetchColumn();
if ($old && is_file(__DIR__ . '/../' . $old)) {
    @unlink(__DIR__ . '/../' . $old);
}

/* Save path */
$relative = 'uploads/logos/' . $safe;
$stmt = $pdo->prepare("UPDATE recruiters SET company_logo = ? WHERE user_id = ?");
$stmt->execute([$relative, $user_id]);

auditLog($pdo, 'logo_upload', "File: $safe");
jsonResponse(true, 'Logo uploaded.', ['path' => $relative]);