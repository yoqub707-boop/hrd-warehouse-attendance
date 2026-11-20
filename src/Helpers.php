<?php
/**
 * Yordamchi Funksiyalar (Helpers)
 */

declare(strict_types=1);

class Helpers
{
    /**
     * .env faylini o'qib $_ENV va $_SERVER ga yuklaydi
     */
    public static function loadEnv(string $path = __DIR__ . '/../.env'): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Qo'shtirnoqlarni olib tashlash
                if (
                    (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))
                ) {
                    $value = substr($value, 1, -1);
                }

                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }

    /**
     * Konfiguratsiya qiymatini olish
     */
    public static function env(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
    }

    /**
     * JSON javob qaytarish
     */
    public static function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Xavfsiz matn (XSS oldini olish)
     */
    public static function e(?string $string): string
    {
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Daqiqalarni soat va daqiqaga o'girish (masalan: 465 min -> 7 soat 45 daq)
     */
    public static function formatMinutes(int $minutes, bool $short = false): string
    {
        if ($minutes <= 0) {
            return $short ? '00:00' : '0 daq';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($short) {
            return sprintf('%02d:%02d', $hours, $remainingMinutes);
        }

        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hours} soat {$remainingMinutes} daq";
        } elseif ($hours > 0) {
            return "{$hours} soat";
        } else {
            return "{$remainingMinutes} daq";
        }
    }

    /**
     * Vaqt formatini chiroyli chiqarish (14:30:00 -> 14:30)
     */
    public static function formatTime(?string $datetime): string
    {
        if (!$datetime) {
            return '-';
        }
        $timestamp = strtotime($datetime);
        return $timestamp ? date('H:i', $timestamp) : '-';
    }

    /**
     * O'zbekcha sana formatlash (2026-11-20 -> 20-Noyabr, 2026)
     */
    public static function formatDateUz(string $date): string
    {
        $months = [
            1 => 'Yanvar', 2 => 'Fevral', 3 => 'Mart', 4 => 'Aprel',
            5 => 'May', 6 => 'Iyun', 7 => 'Iyul', 8 => 'Avgust',
            9 => 'Sentabr', 10 => 'Oktabr', 11 => 'Noyabr', 12 => 'Dekabr'
        ];

        $time = strtotime($date);
        if (!$time) {
            return $date;
        }

        $day = date('j', $time);
        $month = $months[(int)date('n', $time)] ?? '';
        $year = date('Y', $time);

        return "{$day}-{$month}, {$year}";
    }

    /**
     * CSRF token generatsiya qilish
     */
    public static function csrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * CSRF tokenni tekshirish
     */
    public static function verifyCsrfToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Base64 rasmni fayl qilib saqlash
     */
    public static function saveBase64Image(string $base64Data, string $subDir = ''): ?string
    {
        if (empty($base64Data)) {
            return null;
        }

        // data:image/jpeg;base64,... prefiksini olib tashlash
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
            $extension = strtolower($type[1]);
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }
        } else {
            $extension = 'jpg';
        }

        $imageData = base64_decode($base64Data);
        if ($imageData === false) {
            return null;
        }

        // Bugungi sana bo'yicha papka: uploads/attendance/YYYY-MM-DD/
        $dateFolder = date('Y-m-d');
        $isVercel = !empty($_ENV['VERCEL']) || getenv('VERCEL');
        $baseDir = $isVercel ? '/tmp/uploads/attendance/' : (__DIR__ . '/../uploads/attendance/');
        $targetDir = $baseDir . $dateFolder;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }

        $filename = uniqid('att_', true) . '.' . $extension;
        $filePath = $targetDir . '/' . $filename;

        if (@file_put_contents($filePath, $imageData) !== false) {
            return $isVercel ? ('data:image/jpeg;base64,' . base64_encode($imageData)) : ('uploads/attendance/' . $dateFolder . '/' . $filename);
        }

        return null;
    }
}

// Global yuklash
Helpers::loadEnv();
date_default_timezone_set(Helpers::env('APP_TIMEZONE', 'Asia/Tashkent'));
