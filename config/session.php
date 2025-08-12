<?php
// Session configuration - must be set before session_start()
require_once __DIR__ . '/env.php';

// Session security settings (MUST be set before session_start())
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', env('SESSION_SECURE_COOKIE', 0) ? 1 : 0); // Set to 1 for HTTPS
ini_set('session.use_strict_mode', 1);
?>