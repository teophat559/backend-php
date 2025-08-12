<?php
// Validate required environment variables for production deployment
require_once __DIR__ . '/env.php';

if (!function_exists('require_env_keys')) {
    function require_env_keys(array $keys): void {
        $missing = [];
        foreach ($keys as $k) {
            $v = env($k, null);
            if ($v === null || $v === '') {
                $missing[] = $k;
            }
        }
        if (!empty($missing)) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
            echo "Configuration error: missing required environment variables: " . implode(', ', $missing) . "\n";
            echo "Please create and populate php-version/.env (based on .env.example) before deploying.";
            exit;
        }
    }
}

// Production-required keys
$requiredKeys = [
    'APP_URL',
    'TIMEZONE',
    'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
    'ADMIN_SECURITY_KEY',
];

require_env_keys($requiredKeys);

// Soft warnings (not fatal) for recommended keys
$recommended = [
    'DATA_ENCRYPTION_KEY',
    'ERROR_LOG_PATH',
];
foreach ($recommended as $rk) {
    if (!env($rk, '')) {
        error_log("[WARN] Missing recommended env key: {$rk}");
    }
}
?>
