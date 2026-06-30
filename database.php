<?php
// config/database.php
class Database {
    private static $connection = null;
    private static $config = [];

    public static function init($config) {
        self::$config = $config;
    }

    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $host = self::$config['host'];
                $dbname = self::$config['dbname'];
                $charset = self::$config['charset'];
                $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                self::$connection = new PDO($dsn, self::$config['user'], self::$config['pass'], $options);
            } catch (PDOException $e) {
                error_log("DB Connection failed: " . $e->getMessage());
                throw new RuntimeException("Sistem sedang sibuk. Silakan coba lagi nanti.");
            }
        }
        return self::$connection;
    }
}

// Load .env (sederhana)
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) == 2) {
            putenv(trim($parts[0]) . '=' . trim($parts[1]));
            $_ENV[trim($parts[0])] = trim($parts[1]);
        }
    }
}
loadEnv(__DIR__ . '/../.env');

$dbConfig = array(
    'host' => 'localhost',
    'dbname' => 'pt_bukitasam',
    'user' => 'root',
    'pass' => '',
    'charset' => 'utf8mb4'
);
Database::init($dbConfig);  