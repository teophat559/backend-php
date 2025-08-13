<?php
session_start();
require_once __DIR__ . '/../../config/validate_env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/admin-security.php';
require_once __DIR__ . '/../../includes/session-management.php';
require_once __DIR__ . '/../../includes/realtime.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Admin auth (via header or session) and CSRF
requireAdminKeyHeaderOrSession();
if (!checkCSRFToken()) {
    jsonResponse(['success' => false, 'message' => 'Invalid CSRF token'], 403);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(['success' => false, 'message' => 'Invalid input'], 400);
}

$sessionId = sanitizeInput($input['session_id'] ?? '');
$action = strtolower(sanitizeInput($input['action'] ?? ''));
$reason = trim((string)($input['reason'] ?? ''));

if (!$sessionId || !$action) {
    jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
}

// Fetch current session
$details = getSessionDetails($sessionId);
if (!$details) {
    jsonResponse(['success' => false, 'message' => 'Session not found'], 404);
}
$session = $details['session'];
$status = strtolower($session['status'] ?? '');

// Helper: rate limit for certain actions based on recent logs
function tooManyRecent($sessionId, $actionLike, $limit = 3, $windowSeconds = 60) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM session_logs WHERE session_id=? AND action LIKE ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->execute([$sessionId, $actionLike.'%', $windowSeconds]);
    $count = (int)$stmt->fetchColumn();
    return $count >= $limit;
}

// Idempotency: no-op if already in target state
$targetStatus = null;
$event = null;
$logAction = null;
$logStatus = 'info';

switch ($action) {
    case 'approve':
        // Allowed from pending/device_approval_required
        if (!in_array($status, ['pending', 'device_approval_required'], true)) {
            // If already approved states, treat as idempotent
            if (in_array($status, ['approved', 'otp_approved', 'success'], true)) {
                jsonResponse(['success' => true, 'message' => 'Already approved']);
            }
            jsonResponse(['success' => false, 'message' => 'Invalid state for approve'], 409);
        }
        $targetStatus = 'approved';
        $event = ['type' => 'auth:approved', 'request_id' => $sessionId, 'ts' => time()];
        $logAction = 'APPROVE';
        $logStatus = 'success';
        break;

    case 'otp_ok':
        // Admin confirms OTP is valid
        if (!in_array($status, ['otp_required', 'otp_verifying'], true)) {
            if (in_array($status, ['otp_approved'], true)) {
                jsonResponse(['success' => true, 'message' => 'Already OTP approved']);
            }
            jsonResponse(['success' => false, 'message' => 'Invalid state for OTP approval'], 409);
        }
        if (tooManyRecent($sessionId, 'OTP_OK', 6, 60)) {
            jsonResponse(['success' => false, 'message' => 'Rate limited'], 429);
        }
        $targetStatus = 'otp_approved';
        $event = ['type' => 'auth:otp_ok', 'request_id' => $sessionId, 'ts' => time()];
        $logAction = 'OTP_OK';
        $logStatus = 'success';
        break;

    case 'otp_fail':
        // Mark OTP invalid, increment attempts via logs
        if (!in_array($status, ['otp_required', 'otp_verifying'], true)) {
            jsonResponse(['success' => false, 'message' => 'Invalid state for OTP fail'], 409);
        }
        if ($reason === '' || strlen($reason) < 3) {
            jsonResponse(['success' => false, 'message' => 'Reason required'], 400);
        }
        if (tooManyRecent($sessionId, 'OTP_FAIL', 6, 60)) {
            jsonResponse(['success' => false, 'message' => 'Rate limited'], 429);
        }
        // Keep status at otp_required, just log failure and emit event
        $targetStatus = 'otp_required';
        // Count failures
        global $pdo;
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM session_logs WHERE session_id=? AND action='OTP_FAIL'");
        $stmt->execute([$sessionId]);
        $failCount = (int)$stmt->fetchColumn() + 1;
        $event = ['type' => 'auth:otp_invalid', 'request_id' => $sessionId, 'attempts' => $failCount, 'reason' => $reason, 'ts' => time()];
        $logAction = 'OTP_FAIL';
        $logStatus = 'warning';
        break;

    case 'reset':
        if ($reason === '' || strlen($reason) < 3) {
            jsonResponse(['success' => false, 'message' => 'Reason required'], 400);
        }
        if (tooManyRecent($sessionId, 'RESET', 3, 60)) {
            jsonResponse(['success' => false, 'message' => 'Rate limited'], 429);
        }
        // Reset session to a terminal reset state; clients should restart login
        $targetStatus = 'reset';
        $event = ['type' => 'auth:reset', 'session_id' => $sessionId, 'reason' => $reason, 'ts' => time()];
        $logAction = 'RESET';
        $logStatus = 'info';
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Unknown action'], 400);
}

// Idempotent update: if already at targetStatus (when targetStatus != null)
if ($targetStatus && strtolower($session['status']) === strtolower($targetStatus)) {
    // Still log for audit purposes
    logSessionAction($sessionId, $logAction, json_encode(['reason' => $reason, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '']), $logStatus);
    if ($event) { ws_enqueue($event); }
    jsonResponse(['success' => true, 'message' => 'No change']);
}

// Perform update
$ok = updateSessionStatus($sessionId, $targetStatus ?: $status, $reason);
if (!$ok) {
    jsonResponse(['success' => false, 'message' => 'Update failed'], 500);
}

// Audit
logSessionAction($sessionId, $logAction, json_encode(['reason' => $reason, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '']), $logStatus);

// Realtime notify
if ($event) { ws_enqueue($event); }

jsonResponse(['success' => true, 'status' => $targetStatus]);
?>
