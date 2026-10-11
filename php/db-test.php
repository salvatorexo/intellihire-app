<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Testing DB connection...</h3>";

$host = '127.0.0.1';
$port = '3307';       // try 3307 first, then 3306 if it fails
$user = 'root';
$pass = '';
$db   = 'intellihire';

echo "Host: $host<br>";
echo "Port: $port<br>";
echo "User: $user<br>";
echo "DB: $db<br><hr>";

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<h2 style='color:green'>✅ CONNECTED successfully!</h2>";
} catch (PDOException $e) {
    echo "<h2 style='color:red'>❌ FAILED</h2>";
    echo "<p>Error: <strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
}