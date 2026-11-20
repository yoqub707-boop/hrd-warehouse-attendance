<?php
/**
 * Telegram Bot API Xizmati
 * Kirish-chiqish hodisalarini foto va tafsilotlar bilan Telegramga jo'natadi.
 * Saytda real-time monitoring uchun telegram_logs jadvaliga log yozadi.
 */

declare(strict_types=1);

require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/Database.php';

class TelegramService
{
    private string $botToken;
    private string $chatId;
    private bool $enabled;
    private string $apiUrl = 'https://api.telegram.org/bot';
    private Database $db;

    public function __construct(?string $token = null, ?string $chatId = null)
    {
        $this->db = Database::getInstance();
        $config = require __DIR__ . '/../config/telegram.php';

        $dbToken = $this->db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'telegram_bot_token'");
        $dbChatId = $this->db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'telegram_chat_id'");
        $dbEnabled = $this->db->fetch("SELECT setting_value FROM system_settings WHERE setting_key = 'telegram_notifications_enabled'");

        $this->botToken = $token ?? ($dbToken['setting_value'] ?? $config['bot_token'] ?? '');
        $this->chatId = $chatId ?? ($dbChatId['setting_value'] ?? $config['chat_id'] ?? '');
        $this->enabled = $dbEnabled ? (bool)$dbEnabled['setting_value'] : ($config['enabled'] ?? true);
    }

    public function isConfigured(): bool
    {
        return !empty($this->botToken) && !empty($this->chatId);
    }

    public function getBotToken(): string
    {
        return $this->botToken;
    }

    public function getChatId(): string
    {
        return $this->chatId;
    }

    /**
     * Davomat hodisasi bo'yicha Telegramga xabar/foto yuborish
     */
    public function sendAttendanceNotification(array $employee, array $attendance, string $eventType): array
    {
        $fullName = Helpers::e(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''));
        $code = Helpers::e($employee['employee_code'] ?? 'W-0000');
        $dept = Helpers::e($employee['department_name'] ?? 'Ombor bo\'limi');
        $pos = Helpers::e($employee['position'] ?? 'Xodim');
        $now = date('Y-m-d H:i:s');

        $isCheckIn = ($eventType === 'check_in');
        $status = $attendance['status'] ?? 'on_time';
        $lateMinutes = (int)($attendance['late_minutes'] ?? 0);
        $workedMinutes = (int)($attendance['worked_minutes'] ?? 0);
        $undertimeMinutes = (int)($attendance['undertime_minutes'] ?? 0);

        if ($isCheckIn) {
            $actionTitle = "🟢 OMBORGA KIRDI";
            $statusText = ($status === 'late' || $lateMinutes > 0)
                ? "⚠️ <b>KECHIKISH:</b> +{$lateMinutes} daqiqa"
                : "✅ <b>O'z vaqtida</b>";
        } else {
            $actionTitle = "🔴 OMBORDAN CHIQDI";
            $workedStr = Helpers::formatMinutes($workedMinutes);
            $undertimeStr = Helpers::formatMinutes($undertimeMinutes);
            $statusText = "🏁 <b>Smena yakunlandi</b>\n⏱ <b>Jami ishlangan vaqt:</b> <b>{$workedStr}</b>\n⏳ <b>Ishlanmagan vaqt:</b> <b>{$undertimeStr}</b>";
        }

        $caption = "🏢 <b>OMBORXONA FACE ID DAVOMAT</b>\n";
        $caption .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $caption .= "📋 <b>Amal:</b> {$actionTitle}\n";
        $caption .= "👤 <b>Xodim:</b> {$fullName} (<code>{$code}</code>)\n";
        $caption .= "💼 <b>Lavozim:</b> {$pos}\n";
        $caption .= "🏢 <b>Bo'lim:</b> {$dept}\n";
        $caption .= "⏰ <b>Vaqt:</b> {$now}\n";
        $caption .= "📌 <b>Holat:</b> {$statusText}\n";
        $caption .= "📍 <b>Nuqta:</b> Asosiy Face ID Turniket #1\n";
        $caption .= "━━━━━━━━━━━━━━━━━━━━━━\n";
        $caption .= "#FaceID #Davomat #{$code}";

        // Rasm fayli
        $photoRelPath = $isCheckIn ? ($attendance['check_in_photo'] ?? null) : ($attendance['check_out_photo'] ?? null);
        $photoFullPath = $photoRelPath ? realpath(__DIR__ . '/../' . $photoRelPath) : null;

        if (!$this->enabled || !$this->isConfigured()) {
            // Offline/Mock holati: tizim to'xtab qolmasligi uchun logga yozib muvaffaqiyatli deb qaytaramiz
            $this->logTelegramEvent($eventType, $this->chatId ?: 'mock_chat', $photoRelPath, $caption, 'offline', 200, 'Telegram token belgilanmagan (Mock test rejim)');
            return [
                'success' => true,
                'message' => 'Telegram offline rejimda qayd etildi',
                'mock' => true
            ];
        }

        if ($photoFullPath && file_exists($photoFullPath)) {
            $res = $this->sendPhoto($photoFullPath, $caption);
        } else {
            $res = $this->sendMessage($caption);
        }

        $this->logTelegramEvent(
            $eventType,
            $this->chatId,
            $photoRelPath,
            $caption,
            $res['success'] ? 'success' : 'error',
            $res['http_code'] ?? 0,
            $res['error'] ?? null
        );

        return $res;
    }

    /**
     * Rasm yuborish (multipart/form-data sendPhoto CURLFile)
     */
    public function sendPhoto(string $photoPath, string $caption): array
    {
        $url = $this->apiUrl . $this->botToken . '/sendPhoto';

        if (!file_exists($photoPath)) {
            return $this->sendMessage($caption);
        }

        $postFields = [
            'chat_id' => $this->chatId,
            'caption' => $caption,
            'parse_mode' => 'HTML',
            'photo' => new CURLFile($photoPath)
        ];

        return $this->executeCurl($url, $postFields);
    }

    /**
     * Oddiy xabar yuborish (sendMessage)
     */
    public function sendMessage(string $text): array
    {
        $url = $this->apiUrl . $this->botToken . '/sendMessage';
        $postFields = [
            'chat_id' => $this->chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ];

        return $this->executeCurl($url, $postFields);
    }

    /**
     * Bot holati va statistikasini olish (Saytda avtomatik yangilanish uchun)
     */
    public function getBotStatus(): array
    {
        $isConfigured = $this->isConfigured();
        $botInfo = null;
        $isConnected = false;

        if ($isConfigured) {
            $me = $this->getMe();
            if ($me['ok']) {
                $isConnected = true;
                $botInfo = $me['result'];
            }
        }

        // Statistika loglar jadvalidan
        $stats = $this->db->fetch("SELECT 
            COUNT(CASE WHEN status = 'success' THEN 1 END) as success_count,
            COUNT(CASE WHEN status = 'error' THEN 1 END) as error_count,
            MAX(created_at) as last_sent_at
        FROM telegram_logs");

        // Oxirgi 6 ta log
        $recentLogs = $this->db->fetchAll("SELECT * FROM telegram_logs ORDER BY id DESC LIMIT 6");

        return [
            'is_configured' => $isConfigured,
            'is_connected' => $isConnected,
            'bot_username' => $botInfo['username'] ?? null,
            'bot_name' => $botInfo['first_name'] ?? null,
            'chat_id' => $this->chatId,
            'bot_token_masked' => !empty($this->botToken) ? substr($this->botToken, 0, 6) . '...' . substr($this->botToken, -4) : '',
            'success_count' => (int)($stats['success_count'] ?? 0),
            'error_count' => (int)($stats['error_count'] ?? 0),
            'last_sent_at' => $stats['last_sent_at'] ?? null,
            'recent_logs' => $recentLogs
        ];
    }

    /**
     * Bot haqida ma'lumot olish (getMe)
     */
    public function getMe(): array
    {
        if (empty($this->botToken)) {
            return ['ok' => false, 'error' => 'Bot token bo\'sh'];
        }

        $url = $this->apiUrl . $this->botToken . '/getMe';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['ok' => false, 'error' => $err];
        }

        $data = json_decode((string)$resp, true);
        return [
            'ok' => ($data['ok'] ?? false) === true,
            'result' => $data['result'] ?? null,
            'description' => $data['description'] ?? null
        ];
    }

    /**
     * Log yozish
     */
    private function logTelegramEvent(string $eventType, string $chatId, ?string $photoPath, string $caption, string $status, int $httpCode, ?string $error): void
    {
        try {
            $this->db->execute(
                "INSERT INTO telegram_logs (event_type, chat_id, photo_path, caption, status, http_code, error_message)
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$eventType, $chatId, $photoPath, substr($caption, 0, 500), $status, $httpCode, $error]
            );
        } catch (Throwable $e) {
            error_log("Telegram log yozishda xato: " . $e->getMessage());
        }
    }

    private function executeCurl(string $url, array $postFields): array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $httpCode
            ];
        }

        $data = json_decode((string)$response, true);
        return [
            'success' => ($data['ok'] ?? false) === true,
            'response' => $data,
            'http_code' => $httpCode
        ];
    }
}
