<?php
/* =========================================================
   IntelliHire - Aiven Table Setup
   Run this ONCE to create all tables in Aiven.
   ⚠️ DELETE this file after running!
   ========================================================= */

require_once 'config.php';
$pdo = require 'db.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS users (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        full_name     VARCHAR(120)  NOT NULL,
        email         VARCHAR(150)  NOT NULL UNIQUE,
        phone         VARCHAR(30)   NOT NULL,
        password_hash VARCHAR(255)  NOT NULL,
        account_type  ENUM('applicant','recruiter','admin') NOT NULL,
        created_at    DATETIME      DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS applicants (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        user_id          INT NOT NULL,
        university       VARCHAR(150),
        course           VARCHAR(150),
        graduation_year  YEAR,
        location         VARCHAR(150),
        skills           TEXT NULL,
        certifications   TEXT NULL,
        work_experience  TEXT NULL,
        education        TEXT NULL,
        cv_path          VARCHAR(255) DEFAULT NULL,
        updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS recruiters (
        id                INT AUTO_INCREMENT PRIMARY KEY,
        user_id           INT NOT NULL,
        company_name      VARCHAR(150),
        company_email     VARCHAR(150),
        company_phone     VARCHAR(30),
        company_location  VARCHAR(150),
        industry          VARCHAR(100),
        company_logo      VARCHAR(255) NULL,
        website           VARCHAR(255) NULL,
        description       TEXT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS jobs (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        recruiter_id  INT NOT NULL,
        title         VARCHAR(150) NOT NULL,
        description   TEXT,
        requirements  TEXT,
        education_requirements VARCHAR(255),
        skills        TEXT,
        preferred_skills TEXT,
        location      VARCHAR(150),
        job_type      VARCHAR(50) DEFAULT 'Full-time',
        salary_range  VARCHAR(100),
        deadline      DATE,
        status        ENUM('open','closed') DEFAULT 'open',
        created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (recruiter_id) REFERENCES recruiters(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS applications (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        applicant_id  INT NOT NULL,
        job_id        INT NOT NULL,
        status        ENUM('applied','review','shortlisted','interview','accepted','rejected','hired') DEFAULT 'applied',
        match_score   DECIMAL(5,2) DEFAULT NULL,
        skills_score  DECIMAL(5,2) DEFAULT NULL,
        education_score DECIMAL(5,2) DEFAULT NULL,
        experience_score DECIMAL(5,2) DEFAULT NULL,
        recommendation VARCHAR(50) DEFAULT NULL,
        matched_keywords TEXT DEFAULT NULL,
        applied_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_app (applicant_id, job_id),
        FOREIGN KEY (applicant_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS notifications (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NOT NULL,
        message     TEXT NOT NULL,
        is_read     TINYINT(1) DEFAULT 0,
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS login_attempts (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        email       VARCHAR(150) NOT NULL,
        ip_address  VARCHAR(45)  NOT NULL,
        attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email_time (email, attempted_at),
        INDEX idx_ip_time (ip_address, attempted_at)
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS audit_log (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NULL,
        action      VARCHAR(80) NOT NULL,
        details     TEXT NULL,
        ip_address  VARCHAR(45),
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS password_resets (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        user_id     INT NOT NULL,
        token_hash  VARCHAR(255) NOT NULL,
        expires_at  DATETIME NOT NULL,
        used        TINYINT(1) DEFAULT 0,
        created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_expires (expires_at),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB"
];

$results = [];
foreach ($queries as $i => $sql) {
    try {
        $pdo->exec($sql);
        $results[] = "✅ Table " . ($i + 1) . " created";
    } catch (PDOException $e) {
        $results[] = "❌ Table " . ($i + 1) . " failed: " . $e->getMessage();
    }
}

header('Content-Type: text/html');
echo "<h2>Aiven Setup Complete</h2>";
echo "<pre>" . implode("\n", $results) . "</pre>";
echo "<p><strong>⚠️ DELETE this file now from your PC and from GitHub.</strong></p>";