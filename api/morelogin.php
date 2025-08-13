<?php
// Proxy to MoreLogin local API (PHP version of netlify/functions/morelogin.js)
// Env:
//   MORELOGIN_BASE_URL (default: http://127.0.0.1:40000)
//   MORELOGIN_API_ID
//   MORELOGIN_API_KEY

require_once __DIR__ . '/../config/env.php';

function ml_cors_headers(): array {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $appUrl = rtrim(env('APP_URL', ''), '/');
    $allowList = array_filter(array_map('trim', explode(',', (string)env('CORS_ALLOW_ORIGINS', 'https://missudsinhvien2025.online'))));
    $allowed = $allowList[0] ?? '';
    // Allow same-origin
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

function ml_send($statusCode, $bodyArr) {
    http_response_code($statusCode);
    foreach (ml_cors_headers() as $k => $v) header($k . ': ' . $v);
    echo json_encode($bodyArr, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    foreach (ml_cors_headers() as $k => $v) header($k . ': ' . $v);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ml_send(405, [ 'success' => false, 'message' => 'Method Not Allowed' ]);
}

$BASE = env('MORELOGIN_BASE_URL', 'http://127.0.0.1:40000');
$API_ID = env('MORELOGIN_API_ID', '');
$API_KEY = env('MORELOGIN_API_KEY', '');

$raw = file_get_contents('php://input');
$payload = json_decode($raw ?: 'null', true) ?: [];
$path = isset($payload['path']) ? (string)$payload['path'] : '';
$method = isset($payload['method']) ? strtoupper((string)$payload['method']) : 'POST';
$data = $payload['data'] ?? null;
$extraHeaders = $payload['headers'] ?? [];

if ($path === '') {
    ml_send(400, [ 'success' => false, 'message' => 'Missing path' ]);
}

// Build URL
$url = $path;
if (!preg_match('#^https?://#i', $path)) {
    $base = rtrim($BASE, '/');
    $p = '/' . ltrim($path, '/');
    $url = $base . $p;
}

// Headers
$headers = [ 'Content-Type: application/json' ];
// Pass through and add API creds if missing
$lower = [];
foreach ($extraHeaders as $k => $v) { $lower[strtolower($k)] = $v; }
if ($API_ID && !isset($lower['x-api-id'])) $extraHeaders['x-api-id'] = $API_ID;
if ($API_KEY && !isset($lower['x-api-key'])) $extraHeaders['x-api-key'] = $API_KEY;
foreach ($extraHeaders as $k => $v) { $headers[] = $k . ': ' . $v; }

// Attach apiId/apiKey into body if applicable
if (is_array($data)) {
    if ($API_ID && !array_key_exists('apiId', $data)) $data['apiId'] = $API_ID;
    if ($API_KEY && !array_key_exists('apiKey', $data)) $data['apiKey'] = $API_KEY;
}

// Execute request
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => $headers,
]);
if ($method !== 'GET') {
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data ?? []));
}
$resp = curl_exec($ch);
$errno = curl_errno($ch);
$status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

if ($errno) {
    ml_send(500, [ 'success' => false, 'message' => 'Proxy error', 'error' => 'cURL errno ' . $errno ]);
}

$decoded = json_decode((string)$resp, true);
if ($decoded === null) {
    $decoded = [ 'raw' => $resp ];
}

http_response_code($status > 0 ? $status : 200);
foreach (ml_cors_headers() as $k => $v) header($k . ': ' . $v);
echo json_encode($decoded, JSON_UNESCAPED_UNICODE);
exit;
