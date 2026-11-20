<?php
/**
 * src/Auth.php - Autentifikatsiya, Avtorizatsiya va Xavfsizlik Xizmati
 * Ruxsatsiz kirishlarni bloklaydi, IP va sessiyalarni monitoring qiladi.
 */

declare(strict_types=1);

require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/Database.php';

class Auth
{
    private static ?Auth $instance = null;
    private Database $db;

    public const MAX_FAILED_ATTEMPTS = 5;       // Maksimal xato urinishlar soni
    public const LOCKOUT_MINUTES = 15;          // Bloklanish vaqti (daqiqa)

    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->ensureAuthTables();
        $this->ensureDefaultAdmin();

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            // Xavfsiz sessiya sozlamalari
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Lax');
            @session_start();
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Tizimga kirish (Login)
     */
    public function login(string $username, string $password): array
    {
        $ip = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // 1. IP bo'yicha bloklanganlikni tekshirish (Brute-force himoyasi)
        if ($this->isIpLocked($ip)) {
            $lockRemaining = $this->getIpLockRemainingMinutes($ip);
            $this->logLoginAttempt(null, $username, $ip, $userAgent, 'ip_locked', "IP vaqtincha bloklangan ({$lockRemaining} daqiqa qoldi)");
            return [
                'success' => false,
                'message' => "Ko'p marotaba xato urinish! IP manzilingiz {$lockRemaining} daqiqaga bloklangan."
            ];
        }

        // 2. Foydalanuvchini qidirish
        $user = $this->db->fetch("SELECT * FROM users WHERE username = ? OR email = ?", [trim($username), trim($username)]);

        if (!$user) {
            $this->incrementFailedAttempts($ip);
            $this->logLoginAttempt(null, $username, $ip, $userAgent, 'user_not_found', "Bunday foydalanuvchi mavjud emas");
            return [
                'success' => false,
                'message' => "Login yoki parol noto'g'ri!"
            ];
        }

        // 3. Foydalanuvchi bloklanganligini tekshirish
        if ($user['status'] === 'blocked') {
            $this->logLoginAttempt((int)$user['id'], $username, $ip, $userAgent, 'blocked_attempt', "Bloklangan hisob bilan kirishga urinish");
            return [
                'success' => false,
                'message' => "Ushbu hisob ma'muryat tomonidan bloklangan! Tizimga kirish taqiqlanadi."
            ];
        }

        // 4. Parolni tekshirish
        if (!password_verify($password, $user['password_hash'])) {
            $this->incrementFailedAttempts($ip);
            $this->logLoginAttempt((int)$user['id'], $username, $ip, $userAgent, 'failed_password', "Noto'g'ri parol kiritildi");
            $remaining = max(1, self::MAX_FAILED_ATTEMPTS - $this->getFailedAttempts($ip));
            return [
                'success' => false,
                'message' => "Login yoki parol noto'g'ri! (Qolgan urinishlar: {$remaining})"
            ];
        }

        // 5. Muvaffaqiyatli kirish
        $this->resetFailedAttempts($ip);
        $now = date('Y-m-d H:i:s');

        // Baza yozuvini yangilash
        $this->db->execute(
            "UPDATE users SET last_login_at = ?, last_login_ip = ? WHERE id = ?",
            [$now, $ip, $user['id']]
        );

        // Sessiyani xavfsiz qayta generatsiya qilish
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_ip'] = $ip;
        $_SESSION['logged_at'] = time();

        $this->logLoginAttempt((int)$user['id'], $username, $ip, $userAgent, 'success', "Tizimga muvaffaqiyatli kirdi");

        return [
            'success' => true,
            'message' => "Xush kelibsiz, {$user['name']}!",
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'name' => $user['name'],
                'role' => $user['role']
            ]
        ];
    }

    /**
     * Tizimdan chiqish (Logout)
     */
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION = [];
        if (!headers_sent() && ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            @setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        @session_destroy();
    }

    /**
     * Foydalanuvchi tizimga kirganligini tekshirish
     */
    public function check(): bool
    {
        if (empty($_SESSION['user_id'])) {
            return false;
        }

        // Baza orqali hisob hali ham faol ekanligini tasdiqlash
        $user = $this->db->fetch("SELECT id, status FROM users WHERE id = ?", [(int)$_SESSION['user_id']]);
        if (!$user || $user['status'] !== 'active') {
            $this->logout();
            return false;
        }

        return true;
    }

    /**
     * Hozirgi kirgan foydalanuvchi ma'lumotlari
     */
    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        return $this->db->fetch(
            "SELECT id, username, name, email, role, status, last_login_at, last_login_ip FROM users WHERE id = ?",
            [(int)$_SESSION['user_id']]
        );
    }

    /**
     * Ochiq yo'naltirish (Open Redirect) va XSS ga qarshi qat'iy sanitizatsiya
     * Faqat ichki xavfsiz sahifalarga ruxsat beradi, tashqi yoki zararli havolalarni bloklaydi.
     */
    public function sanitizeRedirectUrl(?string $url): string
    {
        if (empty($url)) {
            return '';
        }

        $url = trim($url);

        // 1. Tashqi URL, protokol va protokol-nisbiy havolalarni (//evil.com, https://, javascript:) rad etish
        if (preg_match('#^(https?:)?//#i', $url) || preg_match('#^(javascript|data|vbscript):#i', $url)) {
            return '';
        }

        // 2. URL qismlarini tahlil qilish
        $parsed = parse_url($url);
        if ($parsed === false || !empty($parsed['host'])) {
            return '';
        }

        $path = $parsed['path'] ?? '';
        $query = !empty($parsed['query']) ? '?' . $parsed['query'] : '';

        $cleanPath = ltrim($path, '/');

        // Agar bo'sh, /, /index.php, /login.php, /logout.php bo'lsa, redirect parametri umuman kerak emas
        if (empty($cleanPath) || $cleanPath === 'index.php' || $cleanPath === 'index' || str_contains($cleanPath, 'login') || str_contains($cleanPath, 'logout')) {
            return '';
        }

        // Loyiha ichidagi ruxsat berilgan xavfsiz sahifalar ro'yxati (Whitelist)
        $allowedPages = [
            'employees.php', 'finance.php', 'reports.php',
            'schedules.php', 'tracking.php', 'settings.php',
            'users.php', 'terminal.php', 'test_runner.php'
        ];

        $checkPath = str_ends_with($cleanPath, '.php') ? $cleanPath : ($cleanPath . '.php');

        if (in_array($checkPath, $allowedPages, true)) {
            return $cleanPath . $query;
        }

        return '';
    }

    /**
     * Ruxsat tekshiruvi (Middleware Guard)
     * Agar ruxsatsiz bo'lsa login sahifasiga yo'naltiradi (Toza URL va Open Redirect himoyasi bilan).
     */
    public function requireAuth(string $redirectBack = ''): void
    {
        if (!$this->check()) {
            $currentUrl = $redirectBack ?: ($_SERVER['REQUEST_URI'] ?? '');
            $safeRedirect = $this->sanitizeRedirectUrl($currentUrl);

            // Agar shunchaki bosh sahifa bo'lsa, URL ga ?redirect=%2F qo'shilmaydi (Professional toza URL)
            if (!empty($safeRedirect)) {
                header('Location: login.php?redirect=' . urlencode($safeRedirect));
            } else {
                header('Location: login.php');
            }
            exit;
        }
    }

    /**
     * Faqat bosh admin uchun tekshiruv
     */
    public function requireAdmin(): void
    {
        $this->requireAuth();
        $u = $this->user();
        if (($u['role'] ?? '') !== 'admin') {
            http_response_code(403);
            die("Ruxsat cheklangan! Ushbu sahifaga faqat Bosh Admin kira oladi.");
        }
    }

    /**
     * Mijozning haqiqiy IP manzilini aniqlash
     */
    public function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_REAL_IP',        // Nginx proxy
            'HTTP_X_FORWARDED_FOR',  // Standard proxy
            'REMOTE_ADDR'            // Direct IP
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    // --- BRUTE-FORCE VA IP MONITORING LOGIKASI ---

    private function isIpLocked(string $ip): bool
    {
        $record = $this->db->fetch("SELECT * FROM ip_rate_limits WHERE ip_address = ?", [$ip]);
        if (!$record || empty($record['locked_until'])) {
            return false;
        }

        return strtotime($record['locked_until']) > time();
    }

    private function getIpLockRemainingMinutes(string $ip): int
    {
        $record = $this->db->fetch("SELECT locked_until FROM ip_rate_limits WHERE ip_address = ?", [$ip]);
        if (!$record || empty($record['locked_until'])) {
            return 0;
        }
        $diff = strtotime($record['locked_until']) - time();
        return max(1, (int)ceil($diff / 60));
    }

    private function incrementFailedAttempts(string $ip): void
    {
        $record = $this->db->fetch("SELECT * FROM ip_rate_limits WHERE ip_address = ?", [$ip]);
        $now = date('Y-m-d H:i:s');

        if ($record) {
            $newCount = (int)$record['failed_attempts'] + 1;
            $lockedUntil = null;
            if ($newCount >= self::MAX_FAILED_ATTEMPTS) {
                $lockedUntil = date('Y-m-d H:i:s', time() + (self::LOCKOUT_MINUTES * 60));
            }
            $this->db->execute(
                "UPDATE ip_rate_limits SET failed_attempts = ?, locked_until = ?, last_attempt_at = ? WHERE ip_address = ?",
                [$newCount, $lockedUntil, $now, $ip]
            );
        } else {
            $this->db->execute(
                "INSERT INTO ip_rate_limits (ip_address, failed_attempts, last_attempt_at) VALUES (?, 1, ?)",
                [$ip, $now]
            );
        }
    }

    private function getFailedAttempts(string $ip): int
    {
        $record = $this->db->fetch("SELECT failed_attempts FROM ip_rate_limits WHERE ip_address = ?", [$ip]);
        return (int)($record['failed_attempts'] ?? 0);
    }

    private function resetFailedAttempts(string $ip): void
    {
        $this->db->execute("DELETE FROM ip_rate_limits WHERE ip_address = ?", [$ip]);
    }

    private function logLoginAttempt(?int $userId, string $username, string $ip, string $userAgent, string $status, ?string $details = null): void
    {
        try {
            $this->db->execute(
                "INSERT INTO login_logs (user_id, username, ip_address, user_agent, status, details)
                 VALUES (?, ?, ?, ?, ?, ?)",
                [$userId, $username, $ip, substr($userAgent, 0, 255), $status, $details]
            );
        } catch (Throwable $e) {
            error_log("Login log yozishda xato: " . $e->getMessage());
        }
    }

    // --- BAZA JADVALLARI VA DEFAULT ADMIN ---

    private function ensureAuthTables(): void
    {
        // 1. users jadvali
        $this->db->execute("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'admin',
            status VARCHAR(20) DEFAULT 'active',
            last_login_at DATETIME NULL,
            last_login_ip VARCHAR(45) NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // 2. login_logs jadvali
        $this->db->execute("CREATE TABLE IF NOT EXISTS login_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INT NULL,
            username VARCHAR(50) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT NULL,
            status VARCHAR(30) NOT NULL,
            details TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // 3. ip_rate_limits jadvali
        $this->db->execute("CREATE TABLE IF NOT EXISTS ip_rate_limits (
            ip_address VARCHAR(45) PRIMARY KEY,
            failed_attempts INT DEFAULT 0,
            locked_until DATETIME NULL,
            last_attempt_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    private function ensureDefaultAdmin(): void
    {
        $count = $this->db->fetch("SELECT COUNT(*) as cnt FROM users");
        if ((int)($count['cnt'] ?? 0) === 0) {
            // Standart super-admin yaratish: admin / Admin123!_#
            $hash = password_hash('Admin123!_#', PASSWORD_DEFAULT);
            $this->db->execute(
                "INSERT INTO users (username, name, email, password_hash, role, status)
                 VALUES ('admin', 'Toshkent Markaziy Ombor (Bosh Admin)', 'admin@hrd.uz', ?, 'admin', 'active')",
                [$hash]
            );
        }
    }
}
