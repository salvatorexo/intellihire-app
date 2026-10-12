<?php
/* =========================================================
   IntelliHire - Import users.sql into Aiven (one-time use)
   Reads users.sql from the same folder as this script.
   ⚠️ DELETE this file from your project after running!
   ========================================================= */

require_once 'config.php';
$pdo = require 'db.php';

$sqlFile = __DIR__ . '/users.sql';

if (!is_file($sqlFile)) {
    die("❌ users.sql not found at: " . $sqlFile);
}

$sql = file_get_contents($sqlFile);
if ($sql === false || trim($sql) === '') {
    die("❌ users.sql is empty or unreadable.");
}

header('Content-Type: text/html; charset=utf-8');
echo "<h2>Importing users.sql into Aiven...</h2>";

try {
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->exec($sql);
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<p style='color:green;font-size:1.2em;'>✅ Import completed successfully!</p>";
    echo "<p>Total users now in Aiven: <strong>{$count}</strong></p>";

    echo "<h3>Users:</h3><ul>";
    $rows = $pdo->query("SELECT id, full_name, email, account_type FROM users ORDER BY id")->fetchAll();
    foreach ($rows as $r) {
        echo "<li>#{$r['id']} — " . htmlspecialchars($r['full_name']) .
             " — " . htmlspecialchars($r['email']) .
             " ({$r['account_type']})</li>";
    }
    echo "</ul>";

    echo "<p style='color:red;'><strong>⚠️ DELETE import-users.php and users.sql from your project and GitHub now.</strong></p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ Import failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}