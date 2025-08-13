<?php
session_start();
require_once __DIR__ . '/../config/validate_env.php';
require_once __DIR__ . '/../config/config.php';
// Load database only if not explicitly disabled via env DB_DISABLED=true
$DB_DISABLED = function_exists('env') ? (bool) env('DB_DISABLED', false) : false;
if (!$DB_DISABLED) {
    @require_once __DIR__ . '/../config/database.php';
}

// Lightweight health endpoint; deep checks optional via ?deep=1
$status = [
    'ok' => true,
    'app' => defined('APP_NAME') ? APP_NAME : 'app',
    'version' => defined('APP_VERSION') ? APP_VERSION : '0.0.0',
    'time' => date('c'),
];

$deep = isset($_GET['deep']) && ($_GET['deep'] === '1' || strtolower((string)$_GET['deep']) === 'true');

if ($deep) {
    $details = [];

    // Database ping (unless disabled)
    if ($DB_DISABLED) {
        $details['db'] = ['skipped' => true, 'reason' => 'DB_DISABLED=true'];
    } else {
        try {
            if (isset($pdo)) {
                $pdo->query('SELECT 1');
                $details['db'] = ['ok' => true];
            } else {
                $details['db'] = ['ok' => false, 'error' => 'PDO not initialized'];
                $status['ok'] = false;
            }
        } catch (\Throwable $e) {
            $details['db'] = ['ok' => false, 'error' => $e->getMessage()];
            $status['ok'] = false;
        }
    }

    // WebSocket port reachability (if defined)
    if (defined('WS_HOST') && defined('WS_PORT') && WS_HOST !== '' && WS_PORT !== '') {
        $errno = 0; $errstr = '';
        $fp = @fsockopen(WS_HOST, (int)WS_PORT, $errno, $errstr, 1.0);
        if ($fp) {
            fclose($fp);
            $details['ws'] = ['ok' => true, 'host' => WS_HOST, 'port' => (int)WS_PORT];
        } else {
            $details['ws'] = ['ok' => false, 'host' => WS_HOST, 'port' => (int)WS_PORT, 'error' => $errstr ?: ('errno ' . $errno)];
            $status['ok'] = false;
        }
    } else {
        $details['ws'] = ['skipped' => true, 'reason' => 'WS_HOST/WS_PORT not set'];
    }

    // WS queue directory/file checks
    $queueDir = realpath(__DIR__ . '/../storage') ?: (__DIR__ . '/../storage');
    $queueFile = $queueDir . '/ws_queue.jsonl';
    $details['ws_queue'] = [
        'dir' => $queueDir,
        'dir_exists' => is_dir($queueDir),
        'dir_writable' => is_dir($queueDir) ? is_writable($queueDir) : false,
        'file_exists' => file_exists($queueFile),
    ];
    if (!is_dir($queueDir) || (is_dir($queueDir) && !is_writable($queueDir))) {
        $status['ok'] = false;
    }

    $status['details'] = $details;
}

jsonResponse($status, $status['ok'] ? 200 : 503);
