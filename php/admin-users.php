<?php
require_once 'auth.php';
requireRole('admin');
$pdo = require 'db.php';

$stmt = $pdo->query("
    SELECT id, full_name, email, phone, account_type, created_at
    FROM users
    ORDER BY created_at DESC
");
jsonResponse(true, 'OK', ['users' => $stmt->fetchAll()]);