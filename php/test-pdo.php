<?php
require_once 'config.php';
$pdo = require 'db.php';

echo "Type of \$pdo: " . gettype($pdo) . "<br>";
echo "Class: " . (is_object($pdo) ? get_class($pdo) : 'NOT AN OBJECT') . "<br>";