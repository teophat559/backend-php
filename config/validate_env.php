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
            echo "Please create and populate .env before deploying.";
            exit;
        }
    }
}

// Production-required keys
// Base required keys
$requiredKeys = [
    'APP_URL',
    'TIMEZONE',
    'ADMIN_SECURITY_KEY',
];

// Require DB keys unless explicitly disabled
$dbDisabled = env('DB_DISABLED', false);
if (!$dbDisabled) {
    $requiredKeys = array_merge($requiredKeys, [
        'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
    ]);
}

// Conditionally require auto-login keys
$enableAuto = env('ENABLE_AUTO_LOGIN', false);
if ($enableAuto) {
    $requiredKeys = array_merge($requiredKeys, [
        'MORELOGIN_BASE_URL',
        'MORELOGIN_API_ID',
        'MORELOGIN_API_KEY',
    ]);
}

require_env_keys($requiredKeys);

// Soft warnings (not fatal) for recommended keys
$recommended = [
    'APP_ENV',
    'DATA_ENCRYPTION_KEY',
    'ERROR_LOG_PATH',
    'CORS_ALLOW_ORIGINS',
    'WS_HOST', 'WS_PORT',
];
foreach ($recommended as $rk) {
    if (!env($rk, '')) {
        error_log("[WARN] Missing recommended env key: {$rk}");
    }
}
?>
