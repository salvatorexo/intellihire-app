<?php
/* =========================================================
   IntelliHire - Admin: Delete a Company
   Deletes from recruiters AND users (cascades applications/jobs)
   ========================================================= */

require_once 'auth.php';
require_once 'security.php';
requireRole('admin');

$pdo = require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid method.');
}

// verifyCsrf(); // Disabled for Render

$input = json_decode(file_get_contents('php://input'), true);
$user_id = (int)($input['id'] ?? 0);

if ($user_id <= 0) jsonResponse(false, 'Invalid user ID.');

try {
    $pdo->beginTransaction();

    /* 1. Find the recruiter row */
    $stmt = $pdo->prepare("SELECT id, company_name FROM recruiters WHERE user_id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $rec = $stmt->fetch();

    if (!$rec) {
        $pdo->rollBack();
        jsonResponse(false, 'Company not found for this user.');
    }

    /* 2. Delete the recruiter row (and its jobs via cascade if FK exists) */
    $stmt = $pdo->prepare("DELETE FROM recruiters WHERE id = ?");
    $stmt->execute([$rec['id']]);

    /* 3. Delete the user row */
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);

    $pdo->commit();
    auditLog($pdo, 'admin_delete_company', "Company: {$rec['company_name']} (user_id={$user_id})");

    jsonResponse(true, 'Company and its recruiter account deleted.');

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, 'Delete failed: ' . $e->getMessage());
}