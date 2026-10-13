<?php
/* =========================================================
   IntelliHire - Apply to a Job (Render-ready)
   1. Verify applicant is logged in
   2. Verify applicant has a CV
   3. Prevent duplicate applications
   4. Save application to MySQL
   5. Send CV contents to Python AI screening (base64)
   6. Save match score + AI breakdown
   7. Create notification
   ========================================================= */

require_once 'auth.php';
require_once 'security.php';
requireRole('applicant');

$pdo = require 'db.php';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

/* CSRF disabled for Render stateless sessions.
   Re-enable once session persistence works properly. */
// verifyCsrf();

$input = json_decode(file_get_contents('php://input'), true);
$job_id = (int)($input['job_id'] ?? 0);

if ($job_id <= 0) jsonResponse(false, 'Invalid job ID.');

/* ---------- 1. Confirm job exists ---------- */
$stmt = $pdo->prepare("
    SELECT j.id, j.title, j.description, j.requirements, j.skills,
           j.education_requirements, r.company_name
    FROM jobs j
    JOIN recruiters r ON j.recruiter_id = r.id
    WHERE j.id = ? AND j.status = 'open'
    LIMIT 1
");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) jsonResponse(false, 'This job is no longer available.');

/* ---------- 2. Verify applicant has uploaded a CV ---------- */
$stmt = $pdo->prepare("SELECT cv_path FROM applicants WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$cv_path = $stmt->fetchColumn();

if (!$cv_path) {
    jsonResponse(false, 'Please upload your CV before applying.');
}

/* Resolve the CV path on disk and read its contents for transfer */
$cv_full_path = __DIR__ . '/../' . ltrim($cv_path, '/');
$cv_base64    = null;
$cv_filename  = basename($cv_path);

if (is_file($cv_full_path) && is_readable($cv_full_path)) {
    $raw = @file_get_contents($cv_full_path);
    if ($raw !== false) {
        $cv_base64 = base64_encode($raw);
    }
}

/* ---------- 3. Prevent duplicate applications ---------- */
$stmt = $pdo->prepare("SELECT id FROM applications WHERE applicant_id = ? AND job_id = ? LIMIT 1");
$stmt->execute([$user_id, $job_id]);
if ($stmt->fetch()) {
    jsonResponse(false, 'You have already applied for this job.');
}

/* ---------- 4. Save application ---------- */
$initial_status = 'applied';
$stmt = $pdo->prepare("
    INSERT INTO applications (applicant_id, job_id, status, applied_at)
    VALUES (?, ?, ?, NOW())
");
$stmt->execute([$user_id, $job_id, $initial_status]);
$application_id = $pdo->lastInsertId();

/* ---------- 5. AI Screening (optional — fails gracefully) ---------- */
$match_score      = null;
$skills_score     = null;
$education_score  = null;
$experience_score = null;
$recommendation   = null;
$matched_keywords = null;
$screening_note   = '';

/* Read endpoint from env so it works locally and on Render */
$ai_endpoint = getenv('AI_ENDPOINT') ?: 'https://intellihire-python.onrender.com/screen';

if ($cv_base64 === null) {
    $screening_note = 'CV file missing on server — application saved without score.';
} else {
    try {
        $payload = json_encode([
            'application_id'         => (int)$application_id,
            'cv_base64'              => $cv_base64,
            'cv_filename'            => $cv_filename,
            'job_title'              => $job['title'],
            'job_description'        => $job['description'],
            'job_requirements'       => $job['requirements'],
            'job_skills'             => $job['skills'],
            'education_requirements' => $job['education_requirements'],
        ]);

        $ch = curl_init($ai_endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 25,     // Stay under Render's 30s proxy timeout
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            $screening_note = 'AI service unavailable — application saved without score.';
        } elseif ($httpCode === 200) {
            $result = json_decode($response, true);
            if (is_array($result) && isset($result['match_score'])) {
                $match_score      = (float)$result['match_score'];
                $skills_score     = isset($result['skills_score'])     ? (float)$result['skills_score']     : null;
                $education_score  = isset($result['education_score'])  ? (float)$result['education_score']  : null;
                $experience_score = isset($result['experience_score']) ? (float)$result['experience_score'] : null;
                $recommendation   = $result['recommendation']   ?? null;
                $matched_keywords = $result['matched_keywords'] ?? null;
                $screening_note   = 'AI screening completed.';
            } else {
                $screening_note = 'AI returned unexpected response.';
            }
        } else {
            $screening_note = 'AI service returned HTTP ' . $httpCode;
        }
    } catch (Exception $e) {
        $screening_note = 'AI error: ' . $e->getMessage();
    }
}

/* ---------- 6. Save AI screening result ---------- */
if ($match_score !== null) {
    $stmt = $pdo->prepare("
        UPDATE applications
        SET match_score = ?, skills_score = ?, education_score = ?,
            experience_score = ?, recommendation = ?, matched_keywords = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $match_score, $skills_score, $education_score,
        $experience_score, $recommendation, $matched_keywords,
        $application_id
    ]);
}

/* ---------- 7. Create notification ---------- */
try {
    $msg = "Your application for \"{$job['title']}\" at {$job['company_name']} was submitted successfully."
         . ($match_score !== null ? " AI match score: {$match_score}%." : "");
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
    $stmt->execute([$user_id, $msg]);
} catch (Exception $e) {
    /* notifications optional */
}

/* ---------- Response ---------- */
jsonResponse(true, $screening_note ?: 'Application submitted.', [
    'application_id' => (int)$application_id,
    'status'         => 'Applied',
    'match_score'    => $match_score,
    'recommendation' => $recommendation
]);
