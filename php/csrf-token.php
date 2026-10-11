<?php
/* =========================================================
   IntelliHire - CSRF Token Endpoint
   Returns a fresh session-bound CSRF token as JSON.
   ========================================================= */

require_once 'security.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');

echo json_encode([
    'success' => true,
    'token'   => csrfToken()
]);