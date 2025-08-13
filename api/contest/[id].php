<?php
session_start();
require_once __DIR__ . '/../../config/validate_env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Path-style param: api/contest/[id].php isn't auto-routed; we will be included via routes.php mapping by filename replacement.
// Extract id from request path: /api/contest/{id}
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));
$id = (int) ($parts[count($parts)-1] ?? 0);
if ($id <= 0) { jsonResponse(['success' => false, 'message' => 'Invalid id'], 400); }

$stmt = $pdo->prepare("SELECT * FROM contests WHERE id = ?");
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { jsonResponse(['success' => false, 'message' => 'Not found'], 404); }

jsonResponse(['success' => true, 'data' => $row]);
