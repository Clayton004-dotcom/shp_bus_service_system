<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $localConfigPath = __DIR__ . '/config.local.php';
    $localConfig = [];
    if (is_file($localConfigPath)) {
        $loadedConfig = require $localConfigPath;
        if (!is_array($loadedConfig)) {
            error_log('Local database configuration must return an array.');
            json_response(['error' => 'The service is not configured. Please contact the administrator.'], 503);
        }
        $localConfig = $loadedConfig;
    }

    $getConfig = static function (string $key, string $default = '') use ($localConfig): string {
        $environmentValue = getenv($key);
        if ($environmentValue !== false) {
            return $environmentValue;
        }
        $localValue = $localConfig[$key] ?? $default;
        return is_string($localValue) ? $localValue : $default;
    };

    $host = $getConfig('DB_HOST', '127.0.0.1');
    $port = $getConfig('DB_PORT', '3306');
    $name = $getConfig('DB_NAME', 'shp_bus_service');
    $user = $getConfig('DB_USER', 'shp_app');
    $password = $getConfig('DB_PASSWORD');
    if ($password === false || $password === '') {
        error_log('Database configuration is incomplete: set DB_PASSWORD.');
        json_response(['error' => 'The service is not configured. Please contact the administrator.'], 503);
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);
    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $error) {
        error_log('Database connection failed: ' . $error->getMessage());
        json_response(['error' => 'The service is temporarily unavailable. Please try again later.'], 503);
    }
    return $pdo;
}
