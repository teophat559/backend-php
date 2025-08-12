<?php
// Deprecated: Do not use this file in production.
// Use includes/admin-security.php which reads ADMIN_SECURITY_KEY from .env
http_response_code(500);
header('Content-Type: text/plain; charset=utf-8');
echo 'Deprecated admin security file. Please include includes/admin-security.php instead.';
exit;
