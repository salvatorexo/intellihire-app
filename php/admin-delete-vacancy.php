<?php
require_once 'auth.php';
requireRole('admin');
$pdo = require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Invalid method.');
$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);

$stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
$stmt->execute([$id]);
if ($stmt->rowCount() === 0) jsonResponse(false, 'Not found.');
jsonResponse(true, 'Deleted.');