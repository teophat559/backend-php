<?php
session_start();
require_once __DIR__ . '/../../config/validate_env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/admin-security.php';

// Allow public read for listing contests used on public pages
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    requireAdminKeyHeaderOrSession();
}

$rows = getContests('active');
jsonResponse(['success' => true, 'data' => $rows]);
