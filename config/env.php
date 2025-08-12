<?php
// Lightweight dotenv loader and env() helper

if (!function_exists('loadDotEnv')) {
    function loadDotEnv(string $path): void {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $splitPos = strpos($line, '=');
            if ($splitPos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $splitPos));
            $value = trim(substr($line, $splitPos + 1));
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            if (!array_key_exists($key, $_ENV)) {
                $_ENV[$key] = $value;
            }
            if (!array_key_exists($key, $_SERVER)) {
                $_SERVER[$key] = $value;
            }
            putenv($key . '=' . $value);
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }
        if ($val === null) {
            return $default;
        }
        // Normalize boolean and numeric strings
        $lower = strtolower($val);
        if ($lower === 'true') return true;
        if ($lower === 'false') return false;
        if (is_numeric($val)) return $val + 0;
        return $val;
    }
}

// Attempt to load .env placed in project root or current directory
$possiblePaths = [
    dirname(__DIR__) . '/.env', // project root
    __DIR__ . '/.env',          // config directory
];
foreach ($possiblePaths as $envPath) {
    loadDotEnv($envPath);
}
?>
