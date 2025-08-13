<?php
// Helper utilities for realtime broadcasting
require_once __DIR__ . '/../config/env.php';

if (!function_exists('ws_enqueue')) {
    // Append JSON line to the queue file consumed by realtime/server.php
    function ws_enqueue(array $payload): bool {
        $queueDir = __DIR__ . '/../storage';
        if (!is_dir($queueDir)) @mkdir($queueDir, 0777, true);
        $queueFile = $queueDir . '/ws_queue.jsonl';
        $line = json_encode($payload) . "\n";
        $fh = @fopen($queueFile, 'ab');
        if (!$fh) return false;
        if (!flock($fh, LOCK_EX)) { fclose($fh); return false; }
        $ok = fwrite($fh, $line) !== false;
        fflush($fh);
        flock($fh, LOCK_UN);
        fclose($fh);
        return $ok;
    }
}

// Backcompat alias name
if (!function_exists('ws_broadcast')) {
    function ws_broadcast(array $payload): bool { return ws_enqueue($payload); }
}

?>
