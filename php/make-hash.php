<?php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Password: " . $password . "<br>";
echo "Hash: <br><textarea rows='3' style='width:100%;font-family:monospace;'>" . $hash . "</textarea>";