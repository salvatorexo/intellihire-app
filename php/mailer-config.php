<?php
/* =========================================================
   IntelliHire - Email (SMTP) Configuration
   Reads APP_URL from environment (Render) or falls back to
   localhost (local XAMPP).
   ========================================================= */

define('MAIL_HOST',       'smtp.gmail.com');
define('MAIL_PORT',       587);
define('MAIL_USERNAME',   'kiboenkemboi005@gmail.com');
define('MAIL_PASSWORD',   'kmjd ojgr gefw jwpc');    // Gmail app password
define('MAIL_FROM',       'kiboenkemboi005@gmail.com');
define('MAIL_FROM_NAME',  'IntelliHire');
define('MAIL_ENCRYPTION', 'tls');

/* Base URL used in emails — reads from env on Render, falls back to localhost.
   On Render, set APP_URL=https://intellihire-app-c4bc.onrender.com */
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8080/IntelliHire');