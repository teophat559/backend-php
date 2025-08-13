<?php
session_start();
require_once __DIR__ . '/../../config/validate_env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Expect query ?contest_id= or infer from path /api/contest/{id}/contestants
$contestId = (int)($_GET['contest_id'] ?? 0);
if ($contestId <= 0) {
  $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
  $parts = explode('/', trim($path, '/'));
  // looking for ... /contest/{id}/contestants
  for ($i = 0; $i < count($parts) - 1; $i++) {
    if ($parts[$i] === 'contest' && isset($parts[$i+1]) && ctype_digit($parts[$i+1])) {
      $contestId = (int)$parts[$i+1];
      break;
    }
  }
}
if ($contestId <= 0) { jsonResponse(['success' => false, 'message' => 'Missing contest_id'], 400); }

$stmt = $pdo->prepare("SELECT id, name, total_votes as votes, photo_url FROM contestants WHERE contest_id = ? ORDER BY total_votes DESC");
$stmt->execute([$contestId]);
$rows = $stmt->fetchAll();

jsonResponse(['success' => true, 'data' => $rows]);
