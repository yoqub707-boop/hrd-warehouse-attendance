<?php
/**
 * Ma'lumotlar Bazasi Bilan Ishlash Klasi (PDO Wrapper)
 * MySQL 8.0+ va SQLite bilan 100% mos ishlaydi.
 */

declare(strict_types=1);

require_once __DIR__ . '/Helpers.php';

class Database
{
    private static ?Database $instance = null;
    private ?PDO $pdo = null;
    private string $driver = 'sqlite';

    private function __construct()
    {
        $config = require __DIR__ . '/../config/database.php';
        $default = $config['default'] ?? 'sqlite';
        $this->driver = $default;

        $connConfig = $config['connections'][$default] ?? $config['connections']['sqlite'];

        try {
            if ($default === 'mysql') {
                $dsn = sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                    $connConfig['host'],
                    $connConfig['port'],
                    $connConfig['database'],
                    $connConfig['charset']
                );
                $this->pdo = new PDO($dsn, $connConfig['username'], $connConfig['password'], $connConfig['options']);
            } else {
                // SQLite
                $dbFile = $connConfig['database'];
                if (!empty($_ENV['VERCEL']) || getenv('VERCEL')) {
                    $tmpDb = '/tmp/warehouse.sqlite';
                    if (!file_exists($tmpDb) && file_exists($dbFile)) {
                        copy($dbFile, $tmpDb);
                    }
                    $dbFile = file_exists($tmpDb) ? $tmpDb : $dbFile;
                }
                $dir = dirname($dbFile);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                $dsn = 'sqlite:' . $dbFile;
                $this->pdo = new PDO($dsn, null, null, $connConfig['options']);
                $this->pdo->exec('PRAGMA foreign_keys = ON;');
                $this->pdo->exec('PRAGMA journal_mode = WAL;');
            }
        } catch (PDOException $e) {
            // Agar MySQL ulanmasa, SQLite ga zaxira (fallback) ulanish imkoni
            if ($default === 'mysql') {
                error_log("MySQL ga ulanishda xato: " . $e->getMessage() . ". SQLite zaxira bazasiga ulanmoqda...");
                $sqlitePath = __DIR__ . '/../database/warehouse.sqlite';
                $dir = dirname($sqlitePath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0777, true);
                }
                $this->driver = 'sqlite';
                $this->pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $this->pdo->exec('PRAGMA foreign_keys = ON;');
            } else {
                throw $e;
            }
        }

        // Jadvallarni tekshirib, yo'q bo'lsa avtomatik yaratish
        $this->ensureTables();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Baza jadvallari mavjudligini tekshirish va yaratish
     */
    public function ensureTables(): void
    {
        $checkTable = $this->driver === 'sqlite' 
            ? "SELECT name FROM sqlite_master WHERE type='table' AND name='employees'"
            : "SHOW TABLES LIKE 'employees'";
        
        $stmt = $this->pdo->query($checkTable);
        if (!$stmt->fetch()) {
            $this->migrate();
        } else {
            // Ustunlar yangiligini tekshirish
            $this->checkMissingColumns();
        }
    }

    private function checkMissingColumns(): void
    {
        try {
            if ($this->driver === 'sqlite') {
                $cols = $this->fetchAll("PRAGMA table_info(employees)");
                $colNames = array_column($cols, 'name');
                if (!in_array('face_descriptor', $colNames)) {
                    $this->pdo->exec("ALTER TABLE employees ADD COLUMN face_descriptor TEXT NULL");
                }
                if (!in_array('face_photo', $colNames)) {
                    $this->pdo->exec("ALTER TABLE employees ADD COLUMN face_photo VARCHAR(255) NULL");
                }

                $logCols = $this->fetchAll("PRAGMA table_info(attendance_logs)");
                $logColNames = array_column($logCols, 'name');
                if (!in_array('confidence_score', $logColNames)) {
                    $this->pdo->exec("ALTER TABLE attendance_logs ADD COLUMN confidence_score DECIMAL(5, 2) DEFAULT 98.50");
                }
            } else {
                $cols = $this->fetchAll("SHOW COLUMNS FROM employees LIKE 'face_descriptor'");
                if (empty($cols)) {
                    $this->pdo->exec("ALTER TABLE employees ADD COLUMN face_descriptor TEXT NULL AFTER avatar");
                }
                $cols2 = $this->fetchAll("SHOW COLUMNS FROM employees LIKE 'face_photo'");
                if (empty($cols2)) {
                    $this->pdo->exec("ALTER TABLE employees ADD COLUMN face_photo VARCHAR(255) NULL AFTER avatar");
                }
                $logCols = $this->fetchAll("SHOW COLUMNS FROM attendance_logs LIKE 'confidence_score'");
                if (empty($logCols)) {
                    $this->pdo->exec("ALTER TABLE attendance_logs ADD COLUMN confidence_score DECIMAL(5, 2) DEFAULT 98.50 AFTER device");
                }
            }
        } catch (Throwable $e) {
            // Ignore if already added
        }
    }

    /**
     * Migratsiyani bajarish
     */
    public function migrate(): void
    {
        $schemaFile = __DIR__ . '/../database/schema.sql';
        if (!file_exists($schemaFile)) {
            return;
        }

        $sql = file_get_contents($schemaFile);

        if ($this->driver === 'sqlite') {
            // SQLite uchun sintaksisni moslashtirish
            $sql = preg_replace('/INTEGER\s+PRIMARY\s+KEY\s+AUTO_INCREMENT/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
            $sql = preg_replace('/INT\s+AUTO_INCREMENT\s+PRIMARY\s+KEY/i', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
            $sql = preg_replace('/AUTO_INCREMENT/i', 'AUTOINCREMENT', $sql);
            $sql = preg_replace('/TINYINT\(\d+\)/i', 'INTEGER', $sql);
            $sql = str_replace('DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'DATETIME DEFAULT CURRENT_TIMESTAMP', $sql);

            // Ko'p qatorli so'rovlarni bo'lib bajarish
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $stmt) {
                if (!empty($stmt)) {
                    $this->pdo->exec($stmt);
                }
            }
        } else {
            // MySQL
            $this->pdo->exec($sql);
        }
    }

    /**
     * SQL so'rovni xavfsiz tayyorlab bajarish
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function lastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }
}
