<?php
namespace Src\Database;
use PDO;
use PDOException;

class Connection {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dsn = "mysql:host=" . env('DB_HOST') . ";dbname=" . env('DB_NAME') . ";charset=utf8mb4";

        try {
            $this->pdo = new PDO($dsn, env('DB_USER'), env('DB_PASS'), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT         => false,
            ]);
        } catch (PDOException $e) {
            die("DB Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPDO(): PDO {
        return $this->pdo;
    }
}
