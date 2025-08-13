<?php
session_start();
require_once __DIR__ . '/../config/validate_env.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Return simple rankings: top contestants by votes with contest name
$sql = "SELECT ct.id,
                           ct.name as contestant_name,
                           ct.total_votes as votes,
                           c.name as contest_name,
                           ct.contest_id,
                           COALESCE(ct.image_url, ct.photo_url) as image_url
                FROM contestants ct
                JOIN contests c ON ct.contest_id = c.id
                WHERE c.status = 'active'
                ORDER BY ct.total_votes DESC LIMIT 100";
$stmt = $pdo->query($sql);
$rows = [];
while ($row = $stmt->fetch()) {
        $row['image_url'] = imageUrl($row['image_url'] ?? null, 'contestant');
        $rows[] = $row;
}

jsonResponse(['success' => true, 'data' => $rows]);
