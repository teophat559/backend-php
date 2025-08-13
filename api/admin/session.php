<?php
session_start();
require_once __DIR__ . '/../../config/validate_env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/admin-security.php';
require_once __DIR__ . '/../../includes/session-management.php';

requireAdminKeyHeaderOrSession();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$id = sanitizeInput($_GET['id'] ?? ($_GET['session_id'] ?? ''));
if (!$id) {
    jsonResponse(['success' => false, 'message' => 'Missing id'], 400);
}

$details = getSessionDetails($id);
if (!$details || empty($details['session'])) {
    jsonResponse(['success' => false, 'message' => 'Not found'], 404);
}

// Return only the session core fields needed by the UI (keep extensible)
jsonResponse(['success' => true, 'data' => $details['session']]);
