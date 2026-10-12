<?php
/* =========================================================
   IntelliHire - Database Configuration
   Reads from environment variables (Render) or falls back
   to localhost values (local XAMPP).
   Supports SSL (required for Aiven MySQL).
   ========================================================= */

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3307');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'intellihire');
define('DB_SSL',  getenv('DB_SSL')  ?: 'false');

date_default_timezone_set('Africa/Nairobi');

function jsonResponse($success, $message, $extra = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}

function createPdo() {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (DB_SSL === 'true') {
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    return new PDO($dsn, DB_USER, DB_PASS, $options);
}