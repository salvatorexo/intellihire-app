<?php
require_once 'auth.php';
requireRole('applicant');
$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

/* ---------- Mark all as read ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    jsonResponse(true, 'All marked as read.');
}

/* ---------- Fetch notifications ---------- */
$stmt = $pdo->prepare("
    SELECT id, message, is_read, created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 50
");
$stmt->execute([$user_id]);
jsonResponse(true, 'OK', ['notifications' => $stmt->fetchAll()]);