<?php
define('KEEP_SESSION_OPEN', true);
require_once 'security.php';

session_unset();
session_destroy();
header('Location: ../login.html');
exit;