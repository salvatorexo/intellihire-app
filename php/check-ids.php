<?php
require_once 'config.php';
$pdo = require 'db.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Users in Aiven</h2>";
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>id</th><th>email</th><th>account_type</th></tr>";
$users = $pdo->query("SELECT id, email, account_type FROM users ORDER BY id")->fetchAll();
foreach ($users as $u) {
    echo "<tr><td>{$u['id']}</td><td>" . htmlspecialchars($u['email']) . "</td><td>{$u['account_type']}</td></tr>";
}
echo "</table>";

echo "<h2>Recruiters in Aiven</h2>";
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>id</th><th>user_id</th><th>company_name</th></tr>";
$reps = $pdo->query("SELECT id, user_id, company_name FROM recruiters ORDER BY id")->fetchAll();
foreach ($reps as $r) {
    echo "<tr><td>{$r['id']}</td><td>{$r['user_id']}</td><td>" . htmlspecialchars($r['company_name']) . "</td></tr>";
}
echo "</table>";