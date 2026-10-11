<?php
/* =========================================================
   IntelliHire - Email (SMTP) Configuration
   Uses Gmail SMTP via PHPMailer.
   ========================================================= */

define('MAIL_HOST',       'smtp.gmail.com');
define('MAIL_PORT',       587);
define('MAIL_USERNAME',   'kiboenkemboi840@gmail.com');   // 👈 your Gmail
define('MAIL_PASSWORD',   'kmjd ojgr gefw jwpc');         // 👈 Gmail app password
define('MAIL_FROM',       'kiboenkemboi840@gmail.com');   // 👈 same as username
define('MAIL_FROM_NAME',  'IntelliHire');
define('MAIL_ENCRYPTION', 'tls');

// Base URL used in emails (must match your local server)
define('APP_URL', 'http://localhost:8080/IntelliHire');