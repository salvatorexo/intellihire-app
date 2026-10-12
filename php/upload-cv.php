<?php
/* =========================================================
   IntelliHire - CV Upload Handler (Render-ready)
   Validates file type & size, stores securely, saves path.
   ========================================================= */

require_once 'auth.php';
requireRole('applicant');

$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

if (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, 'No file uploaded or upload error.');
}

$file = $_FILES['cv'];

/* ---------- Config ---------- */
$allowed_exts  = ['pdf', 'docx'];
$allowed_mimes = [
    'application/pdf',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];
$max_size   = 5 * 1024 * 1024;                  // 5 MB
$upload_dir = __DIR__ . '/../uploads/cvs/';

/* ---------- Validate size ---------- */
if ($file['size'] > $max_size) {
    jsonResponse(false, 'File too large. Maximum size is 5 MB.');
}

/* ---------- Validate extension ---------- */
$original_name = $file['name'];
$ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
if (!in_array($ext, $allowed_exts)) {
    jsonResponse(false, 'Only PDF and DOCX files are allowed.');
}

/* ---------- Validate MIME ---------- */
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed_mimes)) {
    jsonResponse(false, 'Invalid file type detected.');
}

/* ---------- Ensure upload directory exists ---------- */
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        jsonResponse(false, 'Server storage error.');
    }
}

/* ---------- Verify directory is writable ---------- */
if (!is_writable($upload_dir)) {
    jsonResponse(false, 'Server storage is not writable.');
}

/* ---------- Generate safe unique filename ---------- */
$safe_name   = 'cv_' . $user_id . '_' . time() . '.' . $ext;
$destination = $upload_dir . $safe_name;

/* ---------- Move file ---------- */
if (!move_uploaded_file($file['tmp_name'], $destination)) {
    jsonResponse(false, 'Failed to store file.');
}

/* ---------- Delete old CV (optional) ---------- */
try {
    $stmt = $pdo->prepare("SELECT cv_path FROM applicants WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $old = $stmt->fetchColumn();
    if ($old) {
        $old_path = __DIR__ . '/../' . ltrim($old, '/');
        if (is_file($old_path) && $old_path !== $destination) {
            @unlink($old_path);
        }
    }
} catch (Exception $e) {
    /* non-fatal — continue */
}

/* ---------- Save path to DB ---------- */
$relative_path = 'uploads/cvs/' . $safe_name;

$stmt = $pdo->prepare("UPDATE applicants SET cv_path = ? WHERE user_id = ?");
$stmt->execute([$relative_path, $user_id]);

/* ---------- Respond ---------- */
jsonResponse(true, 'CV uploaded successfully.', [
    'file_name'   => $original_name,
    'stored_name' => $safe_name,
    'path'        => $relative_path,
    'size'        => $file['size'],
    'mime'        => $mime
]);
