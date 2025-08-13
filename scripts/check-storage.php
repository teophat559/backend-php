<?php
// CLI tool to check storage/logs permissions and attempt a minimal fix
// Usage: php scripts/check-storage.php [--fix]

if (PHP_SAPI !== 'cli') {
    echo "Run from CLI: php scripts/check-storage.php [--fix]\n";
    exit(1);
}

$fix = in_array('--fix', $argv, true);
$root = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
$paths = [
    'storage',
    'logs',
];

$results = [];
foreach ($paths as $rel) {
    $path = $root . DIRECTORY_SEPARATOR . $rel;
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
    $writable = is_writable($path);
    $testFile = $path . DIRECTORY_SEPARATOR . '.perm_test_' . uniqid() . '.txt';
    $canWrite = false;
    $err = '';
    if ($writable) {
        $canWrite = (@file_put_contents($testFile, 'ok') !== false);
        if ($canWrite) {
            @unlink($testFile);
        } else {
            $err = 'write failed';
        }
    } else {
        $err = 'not writable';
    }

    if (!$canWrite && $fix) {
        // Try chmod; chown requires privileges so we skip it here
        @chmod($path, 0775);
        $writable = is_writable($path);
        if ($writable) {
            $canWrite = (@file_put_contents($testFile, 'ok') !== false);
            if ($canWrite) @unlink($testFile);
        }
    }

    $results[$rel] = [
        'path' => $path,
        'exists' => is_dir($path),
        'writable' => $writable,
        'can_write_file' => $canWrite,
        'error' => $canWrite ? null : $err,
    ];
}

echo json_encode(['fix' => $fix, 'results' => $results], JSON_PRETTY_PRINT) . "\n";
