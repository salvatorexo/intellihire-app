<?php
require_once 'auth.php';
requireRole('admin');
$pdo = require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method.');
verifyCsrf();

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Invalid user ID.');

/* Prevent self-delete */
if ($id === (int)$_SESSION['user_id']) {
    jsonResponse(false, 'You cannot delete your own account.');
}

/* Get CV path to delete file */
$stmt = $pdo->prepare("SELECT cv_path FROM applicants WHERE user_id = ?");
$stmt->execute([$id]);
$cv = $stmt->fetchColumn();

if ($cv) {
    $path = __DIR__ . '/../' . $cv;
    if (is_file($path)) @unlink($path);
}

/* Delete user (cascades to applicants, recruiters, applications, notifications) */
$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$id]);

if ($stmt->rowCount() === 0) jsonResponse(false, 'User not found.');
auditLog($pdo, 'admin_delete_user', "Deleted user ID: $id");
jsonResponse(true, 'User deleted.');