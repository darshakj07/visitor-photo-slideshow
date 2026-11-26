<?php
/**
 * Lightweight Environment (.env) Loader
 * VISITOR PHOTO SLIDESHOW - Core PHP Architecture
 */

declare(strict_types=1);

if (!function_exists('loadEnv')) {
    function loadEnv(string $path): array
    {
        $variables = [];
        if (!file_exists($path) || !is_readable($path)) {
            return $variables;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return $variables;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comments
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val);

                // Strip surrounding single/double quotes
                if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                    (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                    $val = substr($val, 1, -1);
                }

                $variables[$key] = $val;

                // Also populate $_ENV and putenv if not already set
                if (!array_key_exists($key, $_ENV)) {
                    $_ENV[$key] = $val;
                }
                putenv("{$key}={$val}");
            }
        }

        return $variables;
    }
}

// Automatically load .env if present, otherwise .env.example
$baseDir = dirname(__DIR__);
$envFile = $baseDir . DIRECTORY_SEPARATOR . '.env';
if (!file_exists($envFile)) {
    $envFile = $baseDir . DIRECTORY_SEPARATOR . '.env.example';
}

loadEnv($envFile);

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $default;
        }

        if ($val === 'true' || $val === '(true)') {
            return true;
        }
        if ($val === 'false' || $val === '(false)') {
            return false;
        }
        if ($val === 'null' || $val === '(null)') {
            return null;
        }

        return $val;
    }
}
