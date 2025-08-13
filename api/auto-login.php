<?php
// Auto-login launcher in PHP bridging to MoreLogin. Mirrors netlify/functions/auto-login.js dryRun + start profile and return ws endpoint.
// POST JSON: { account, password, platform, chrome: { profileName }, moreLoginId, loginUrl, homeUrl, flow, dryRun }
// Returns { success, dryRun, needsOtp?, wsEndpoint?, profileId?, message }

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../includes/functions.php';

function al_cors_headers(): array {
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

function al_send($code, $arr) {
    http_response_code($code);
    foreach (al_cors_headers() as $k => $v) header($k . ': ' . $v);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    foreach (al_cors_headers() as $k => $v) header($k . ': ' . $v);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    al_send(405, [ 'success' => false, 'message' => 'Method Not Allowed' ]);
}

$raw = file_get_contents('php://input');
$body = json_decode($raw ?: 'null', true) ?: [];
$account = trim((string)($body['account'] ?? ''));
$password = (string)($body['password'] ?? '');
$platform = strtolower(trim((string)($body['platform'] ?? '')));
$chrome = $body['chrome'] ?? null;
$profileName = is_array($chrome) ? ($chrome['profileName'] ?? null) : null;
$moreLoginId = $body['moreLoginId'] ?? null;
$loginUrl = $body['loginUrl'] ?? null;
$homeUrl = $body['homeUrl'] ?? null;
$flow = $body['flow'] ?? null;
$otpCode = $body['otpCode'] ?? null;
$dryRun = !empty($body['dryRun']);

if ($account === '' || $password === '' || $platform === '') {
    al_send(400, [ 'success' => false, 'message' => 'Thiếu trường bắt buộc (account/password/platform)' ]);
}

$jobId = 'job_' . substr(bin2hex(random_bytes(6)), 0, 8);

// Dry run: estimate need for OTP for some flows
if ($dryRun) {
    $flowName = $flow ?: match ($platform) {
        'google' => 'google',
        'yahoo' => 'yahoo',
        'outlook', 'microsoft' => 'microsoft',
        'zalo' => 'zalo',
        'facebook', 'instagram' => 'single',
        default => 'single',
    };
    $needsOtp = in_array($flowName, ['google', 'yahoo', 'microsoft'], true) && empty($otpCode);
    al_send(200, [ 'success' => !$needsOtp, 'needsOtp' => $needsOtp, 'provider' => $needsOtp ? ($flowName === 'microsoft' ? 'outlook' : $flowName) : null, 'jobId' => $jobId, 'dryRun' => true, 'flow' => $flowName ]);
}

// Resolve profile id by name if needed
function ml_call(string $path, array $data) {
    // Build base URL from current request, respecting reverse proxy and subpath deployment
    $scheme = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    $reqUri = $_SERVER['REQUEST_URI'] ?? '';
    $basePrefix = '';
    if (preg_match('#^(.*?)/(?:api|admin)/#', $reqUri, $m)) { $basePrefix = $m[1]; }
    $url = $scheme . '://' . $host . $basePrefix . '/api/morelogin.php';
    $payload = json_encode([ 'path' => $path, 'method' => 'POST', 'data' => $data ]);
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 25,
        ]
    ];
    $context = stream_context_create($opts);
    $resp = file_get_contents($url, false, $context);
    if ($resp === false) return null;
    return json_decode($resp, true);
}

$profileId = $moreLoginId;
if (!$profileId && $profileName) {
    $list = ml_call('/api/v1/profile/list', [ 'pageNo' => 1, 'pageSize' => 200, 'keyword' => $profileName ]);
    $records = $list['data']['list'] ?? ($list['data']['records'] ?? ($list['list'] ?? ($list['data'] ?? [])));
    $norm = fn($s) => strtolower(trim((string)$s));
    foreach ($records as $p) {
        $n = $p['name'] ?? ($p['profileName'] ?? null);
        if ($n && $norm($n) === $norm($profileName)) {
            $profileId = $p['id'] ?? ($p['profileId'] ?? ($p['uuid'] ?? null));
            break;
        }
    }
}

if (!$profileId) {
    al_send(400, [ 'success' => false, 'message' => 'Không xác định được MoreLogin profileId', 'jobId' => $jobId ]);
}

$start = ml_call('/api/v1/profile/start', [ 'profileId' => $profileId ]);
if (!$start) {
    al_send(500, [ 'success' => false, 'message' => 'Không thể khởi động profile', 'jobId' => $jobId ]);
}
$endpoint = $start['data']['wsEndpoint'] ?? ($start['data']['webSocketDebuggerUrl'] ?? ($start['data']['debuggerAddress'] ?? ($start['data']['url'] ?? null)));
if (!$endpoint) {
    al_send(500, [ 'success' => false, 'message' => 'Không tìm thấy endpoint Debugger từ MoreLogin', 'jobId' => $jobId ]);
}

// If endpoint is host:port, fetch /json/version to get ws url
function resolve_ws_endpoint(string $endpoint): string {
    if (preg_match('#^wss?://#i', $endpoint)) return $endpoint;
    if (preg_match('#^(?:localhost|127\.0\.0\.1|\d+\.\d+\.\d+\.\d+):\d+$#i', $endpoint)) {
        $url = 'http://' . $endpoint . '/json/version';
        $ctx = stream_context_create([ 'http' => [ 'timeout' => 10 ] ]);
        $txt = @file_get_contents($url, false, $ctx);
        if ($txt) {
            $j = json_decode($txt, true);
            if (!empty($j['webSocketDebuggerUrl'])) return $j['webSocketDebuggerUrl'];
        }
    }
    return $endpoint;
}

$wsEndpoint = resolve_ws_endpoint((string)$endpoint);

al_send(200, [ 'success' => true, 'message' => 'Đã khởi chạy profile. Hãy kết nối bằng Puppeteer nếu cần.', 'profileId' => $profileId, 'wsEndpoint' => $wsEndpoint, 'jobId' => $jobId ]);
