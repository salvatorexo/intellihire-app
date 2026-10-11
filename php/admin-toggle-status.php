<?php
require_once 'auth.php';
requireRole('admin');
$pdo = require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method.');
$input = json_decode(file_get_contents('php://input'), true);
$job_id = (int)($input['job_id'] ?? 0);
$status = $input['status'] === 'closed' ? 'closed' : 'open';

$stmt = $pdo->prepare("UPDATE jobs SET status = ? WHERE id = ?");
$stmt->execute([$status, $job_id]);
jsonResponse(true, 'Status updated.');