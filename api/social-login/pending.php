<?php
session_start();
require_once __DIR__ . '/../../config/validate_env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session-management.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}

$status = $_GET['status'] ?? 'pending';
$filters = [];
if ($status) $filters['status'] = $status;

$result = getLoginSessions(1, $filters);
jsonResponse(['success' => true, 'data' => $result['sessions']]);
