<?php
/* =========================================================
   IntelliHire - Registration Handler
   Handles both Applicant and Recruiter registration
   Passwords are hashed using password_hash() (bcrypt)
   ========================================================= */

require_once 'config.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(false, 'Invalid or missing data.');
}

// ---- Collect & sanitize common fields ----
$account_type     = trim($input['account_type'] ?? '');
$full_name        = trim($input['full_name'] ?? '');
$email            = trim($input['email'] ?? '');
$phone            = trim($input['phone'] ?? '');
$password         = $input['password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

// ---- Basic validation ----
if (!in_array($account_type, ['applicant', 'recruiter'])) {
    jsonResponse(false, 'Invalid account type.');
}
if ($full_name === '' || $email === '' || $phone === '' || $password === '') {
    jsonResponse(false, 'Please fill in all required fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(false, 'Invalid email address.');
}
if (strlen($password) < 8) {
    jsonResponse(false, 'Password must be at least 8 characters.');
}
if ($password !== $confirm_password) {
    jsonResponse(false, 'Passwords do not match.');
}

// ---- Check if email already exists ----
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonResponse(false, 'An account with this email already exists.');
}

// ---- Hash the password (bcrypt) ----
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// ---- Begin transaction ----
try {
    $pdo->beginTransaction();

    // ---- Insert into users table ----
    $stmt = $pdo->prepare("
        INSERT INTO users (full_name, email, phone, password_hash, account_type, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$full_name, $email, $phone, $password_hash, $account_type]);
    $user_id = $pdo->lastInsertId();

    // ---- Insert role-specific data ----
    if ($account_type === 'applicant') {
        $university      = trim($input['university'] ?? '');
        $course          = trim($input['course'] ?? '');
        $graduation_year = trim($input['graduation_year'] ?? '');
        $location        = trim($input['location'] ?? '');

        if ($university === '' || $course === '' || $graduation_year === '' || $location === '') {
            $pdo->rollBack();
            jsonResponse(false, 'Please complete all applicant fields.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO applicants (user_id, university, course, graduation_year, location)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $university, $course, $graduation_year, $location]);

    } else { // recruiter
        $company_name     = trim($input['company_name'] ?? '');
        $company_email    = trim($input['company_email'] ?? '');
        $company_phone    = trim($input['company_phone'] ?? '');
        $company_location = trim($input['company_location'] ?? '');
        $industry         = trim($input['industry'] ?? '');

        if ($company_name === '' || $company_email === '' || $company_phone === '' ||
            $company_location === '' || $industry === '') {
            $pdo->rollBack();
            jsonResponse(false, 'Please complete all company fields.');
        }
        if (!filter_var($company_email, FILTER_VALIDATE_EMAIL)) {
            $pdo->rollBack();
            jsonResponse(false, 'Invalid company email address.');
        }

        $stmt = $pdo->prepare("
            INSERT INTO recruiters
                (user_id, company_name, company_email, company_phone, company_location, industry)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id, $company_name, $company_email,
            $company_phone, $company_location, $industry
        ]);
    }

    $pdo->commit();

    jsonResponse(true, 'Account created successfully! Redirecting to login...', [
        'user_id' => $user_id,
        'role'    => $account_type
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(false, 'Registration failed: ' . $e->getMessage());
}