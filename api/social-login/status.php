<?php
session_start();
require_once __DIR__ . '/../../config/validate_env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session-management.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$id = $_GET['id'] ?? $_GET['session_id'] ?? '';
if (!$id) {
    jsonResponse(['success' => false, 'message' => 'Missing id'], 400);
}

$details = getSessionDetails($id);
if (!$details) {
    jsonResponse(['success' => false, 'message' => 'Session not found'], 404);
}

$session = $details['session'];
jsonResponse([
    'success' => true,
    'data' => [
        'id' => $session['session_id'],
        'status' => $session['status'],
        'details' => $session['details'],
        'username' => $session['username'],
        'platform' => $session['platform'],
    ]
]);
