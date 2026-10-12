<?php
/* =========================================================
   IntelliHire - Shared PDO connection
   Uses createPdo() from config.php
   ========================================================= */

require_once __DIR__ . '/config.php';

try {
    $pdo = createPdo();
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

return $pdo;