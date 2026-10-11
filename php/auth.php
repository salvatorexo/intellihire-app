<?php
/* =========================================================
   IntelliHire - Session & Role Guard
   ========================================================= */

require_once __DIR__ . '/security.php';

function requireRole($role) {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header('Location: ../login.html');
        exit;
    }
    if ($_SESSION['role'] !== $role) {
        switch ($_SESSION['role']) {
            case 'applicant': header('Location: ../applicant/applicantdash.html'); break;
            case 'recruiter': header('Location: ../recruiter/recruiterdash.html'); break;
            case 'admin':     header('Location: ../admin/admindash.html'); break;
            default:          header('Location: ../login.html');
        }
        exit;
    }
}

function currentUser() {
    return [
        'id'        => $_SESSION['user_id'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? '',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? ''
    ];
}