<?php
session_start();
require_once __DIR__ . '/../../config/validate_env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/admin-security.php';
require_once __DIR__ . '/../../includes/session-management.php';
require_once __DIR__ . '/../../includes/realtime.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

// Admin key guard (header X-Admin-Key)
requireAdminKeyHeaderOrSession();

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$sessionId = $input['id'] ?? $input['session_id'] ?? '';
if (!$sessionId) {
    jsonResponse(['success' => false, 'message' => 'Missing id'], 400);
}

$details = getSessionDetails($sessionId);
if (!$details) {
    jsonResponse(['success' => false, 'message' => 'Session not found'], 404);
}

// Mark as approved and ready for processing by automation/bot
updateSessionStatus($sessionId, 'approved', 'Approved by admin');
logSessionAction($sessionId, 'approved', 'Approved via API', 'success');

// Broadcast approval event for clients waiting realtime
ws_enqueue([
    'type' => 'auth:approved',
    'request_id' => $sessionId,
    'ts' => time(),
]);

jsonResponse(['success' => true, 'message' => 'Approved', 'id' => $sessionId]);
