<?php
/* =========================================================
   IntelliHire - Database Configuration (LOCAL / XAMPP)
   ========================================================= */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'intellihire');

date_default_timezone_set('Africa/Nairobi');

/* ---- Helper: send JSON response ---- */
function jsonResponse($success, $message, $extra = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $extra));
    exit;
}