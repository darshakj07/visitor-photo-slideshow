<?php
/**
 * Database Connection Provider (PDO)
 * VISITOR PHOTO SLIDESHOW - Core PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

class Database
{
    private static ?PDO $instance = null;

    /**
     * Get or initialize the singleton PDO connection.
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host = (string) env('DB_HOST', '127.0.0.1');
            $port = (string) env('DB_PORT', '3307');
            $dbName = (string) env('DB_NAME', 'visitor_slideshow_db');
            $user = (string) env('DB_USER', 'root');
            $pass = (string) env('DB_PASS', '');
            $charset = (string) env('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci",
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // If connecting on custom port failed, try default 3306 as automatic fallback
                if ($port !== '3306') {
                    try {
                        $fallbackDsn = "mysql:host={$host};port=3306;dbname={$dbName};charset={$charset}";
                        self::$instance = new PDO($fallbackDsn, $user, $pass, $options);
                        return self::$instance;
                    } catch (PDOException) {
                        // ignore and report original exception
                    }
                }

                error_log("Database connection failed: " . $e->getMessage());

                if (APP_DEBUG) {
                    die("Database Connection Error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
                } else {
                    http_response_code(500);
                    die("A database error occurred. Please contact the administrator.");
                }
            }
        }

        return self::$instance;
    }

    /**
     * Prevent direct cloning or instantiation.
     */
    private function __construct() {}
    private function __clone() {}
}

/**
 * Global helper function to obtain PDO connection
 */
function getDB(): PDO
{
    return Database::getConnection();
}
