<?php
session_start();
require_once __DIR__ . '/../../config/validate_env.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Rankings across all contests by total_votes
$stmt = $pdo->query("SELECT ct.*, c.name as contest_name FROM contestants ct JOIN contests c ON ct.contest_id=c.id WHERE ct.status='active' ORDER BY ct.total_votes DESC LIMIT 100");
$rows = $stmt->fetchAll();

echo json_encode(['success' => true, 'data' => $rows]);
