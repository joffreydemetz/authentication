<?php
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use JDZ\Utils\Data;

/**
 * Load configuration from config.php (or config.php.dist as fallback)
 *
 * @return Data Configuration object with dot notation access
 */
function loadConfig(): Data
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $configFile = __DIR__ . '/config.php';
    $configDistFile = __DIR__ . '/config.php.dist';

    if (file_exists($configFile)) {
        $data = require $configFile;
    } elseif (file_exists($configDistFile)) {
        $data = require $configDistFile;
    } else {
        $data = [];
    }

    $config = new Data();
    $config->sets($data);

    return $config;
}

/**
 * Get configuration value using dot notation
 *
 * @param string $path Dot notation path (e.g., 'database.mysql.host')
 * @param mixed $default Default value if path not found
 * @return mixed
 */
function config(string $path, mixed $default = null): mixed
{
    return loadConfig()->get($path, $default);
}

/**
 * Create a PDO connection from configuration
 *
 * @param string|null $driver Driver name (defaults to configured driver)
 * @return \PDO
 */
function createPdo(?string $driver = null): \PDO
{
    $driver = $driver ?? config('database.driver', 'sqlite');

    $dsn = match ($driver) {
        'sqlite' => 'sqlite:' . config('database.sqlite.path', ':memory:'),
        'mysql' => sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            config('database.mysql.host', 'localhost'),
            config('database.mysql.port', 3306),
            config('database.mysql.database', 'test_db')
        ),
        'pgsql' => sprintf(
            'pgsql:host=%s;port=%d;dbname=%s',
            config('database.pgsql.host', 'localhost'),
            config('database.pgsql.port', 5432),
            config('database.pgsql.database', 'test_db')
        ),
        default => throw new \InvalidArgumentException("Unsupported database driver: {$driver}"),
    };

    $user = match ($driver) {
        'sqlite' => null,
        'mysql' => config('database.mysql.user', 'root'),
        'pgsql' => config('database.pgsql.user', 'root'),
        default => null,
    };

    $pass = match ($driver) {
        'sqlite' => null,
        'mysql' => config('database.mysql.password', ''),
        'pgsql' => config('database.pgsql.password', ''),
        default => null,
    };

    return new \PDO($dsn, $user, $pass, [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);
}
