<?php
// Enqueue auto-login job: PHP API writes a JSON file into storage/auto_jobs/pending
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../config/config.php';

function aj_cors_headers(): array {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $appUrl = rtrim(env('APP_URL', ''), '/');
    $allowList = array_filter(array_map('trim', explode(',', (string)env('CORS_ALLOW_ORIGINS', 'https://missudsinhvien2025.online'))));
    $allowed = $allowList[0] ?? '';
    if ($origin && ($origin === $appUrl || in_array($origin, $allowList, true) || env('APP_ENV', 'prod') !== 'prod')) {
        $allowed = $origin;
    }
    return [
        'Access-Control-Allow-Origin' => $allowed ?: ($appUrl ?: '*'),
        'Access-Control-Allow-Headers' => 'Content-Type, Authorization',
        'Access-Control-Allow-Methods' => 'POST, OPTIONS',
        'Access-Control-Allow-Credentials' => 'true',
        'Content-Type' => 'application/json; charset=utf-8',
    ];
}

function aj_send($code, $arr) {
    http_response_code($code);
    foreach (aj_cors_headers() as $k => $v) header($k . ': ' . $v);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); foreach (aj_cors_headers() as $k => $v) header($k . ': ' . $v); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') aj_send(405, ['success' => false, 'message' => 'Method Not Allowed']);

$raw = file_get_contents('php://input');
$body = json_decode($raw ?: 'null', true) ?: [];

$account = trim((string)($body['account'] ?? ''));
$password = (string)($body['password'] ?? '');
$platform = strtolower(trim((string)($body['platform'] ?? '')));
$chrome = $body['chrome'] ?? null;
$moreLoginId = $body['moreLoginId'] ?? null;
$otpCode = $body['otpCode'] ?? null;

if ($account === '' || $password === '' || $platform === '') {
    aj_send(400, ['success' => false, 'message' => 'Thiếu trường bắt buộc (account/password/platform)']);
}

$jobId = 'job_' . substr(bin2hex(random_bytes(6)), 0, 8);
$job = [
    'id' => $jobId,
    'ts' => time(),
    'type' => 'auto-login',
    'payload' => [
        'account' => $account,
        'password' => $password,
        'platform' => $platform,
        'chrome' => $chrome,
        'moreLoginId' => $moreLoginId,
        'otpCode' => $otpCode,
    ],
];

$dir = dirname(__DIR__, 2) . '/storage/auto_jobs/pending';
if (!is_dir($dir)) @mkdir($dir, 0777, true);
$path = $dir . '/' . $jobId . '.json';
$ok = (bool)file_put_contents($path, json_encode($job, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
if (!$ok) aj_send(500, ['success' => false, 'message' => 'Không thể ghi hàng đợi']);

aj_send(200, ['success' => true, 'jobId' => $jobId]);
?>
