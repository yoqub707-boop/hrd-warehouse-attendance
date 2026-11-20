<?php
/**
 * api/bot_status.php - Telegram Bot Holati va Sozlamalari API
 * Real-time monitoring va polling uchun xizmat qiladi.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/TelegramService.php';

header('Content-Type: application/json; charset=utf-8');

$telegramService = new TelegramService();
$db = Database::getInstance();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

try {
    if ($method === 'GET' || $action === 'status') {
        // Botning real-time holatini qaytarish
        $status = $telegramService->getBotStatus();
        Helpers::jsonResponse([
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'bot_status' => $status
        ]);
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $act = $input['action'] ?? $action;

        if ($act === 'update_settings') {
            // CSRF tekshirish
            if (!Helpers::verifyCsrfToken($input['csrf_token'] ?? '')) {
                Helpers::jsonResponse(['success' => false, 'message' => 'CSRF xavfsizlik tekshiruvi xatosi'], 403);
            }

            $token = trim($input['bot_token'] ?? '');
            $chatId = trim($input['chat_id'] ?? '');
            $enabled = !empty($input['enabled']) ? '1' : '0';

            // Sozlamalarni yangilash
            $db->execute("INSERT INTO system_settings (setting_key, setting_value, description) 
                          VALUES ('telegram_bot_token', ?, 'Telegram Bot Token')
                          ON CONFLICT(setting_key) DO UPDATE SET setting_value = ?", [$token, $token]);

            $db->execute("INSERT INTO system_settings (setting_key, setting_value, description) 
                          VALUES ('telegram_chat_id', ?, 'Telegram Chat ID')
                          ON CONFLICT(setting_key) DO UPDATE SET setting_value = ?", [$chatId, $chatId]);

            $db->execute("INSERT INTO system_settings (setting_key, setting_value, description) 
                          VALUES ('telegram_notifications_enabled', ?, 'Telegram faollik holati')
                          ON CONFLICT(setting_key) DO UPDATE SET setting_value = ?", [$enabled, $enabled]);

            $newTg = new TelegramService($token, $chatId);
            $newStatus = $newTg->getBotStatus();

            Helpers::jsonResponse([
                'success' => true,
                'message' => 'Telegram sozlamalari muvaffaqiyatli saqlandi!',
                'bot_status' => $newStatus
            ]);
        }

        if ($act === 'test_send') {
            $token = trim($input['bot_token'] ?? $telegramService->getBotToken());
            $chatId = trim($input['chat_id'] ?? $telegramService->getChatId());

            if (empty($token) || empty($chatId)) {
                Helpers::jsonResponse([
                    'success' => false,
                    'message' => 'Bot token yoki Chat ID kiritilmagan!'
                ], 400);
            }

            $testTg = new TelegramService($token, $chatId);
            $me = $testTg->getMe();

            if (!$me['ok']) {
                Helpers::jsonResponse([
                    'success' => false,
                    'message' => 'Bot token xato: ' . ($me['description'] ?? 'Ulanib bo\'lmadi')
                ], 400);
            }

            $botUser = $me['result']['username'] ?? 'HRD_Bot';
            $msg = "🤖 <b>HRD Omborxona Face ID Tizimi</b>\n\n";
            $msg .= "✅ Telegram Bot muvaffaqiyatli sinovdan o'tkazildi!\n";
            $msg .= "👤 <b>Bot:</b> @{$botUser}\n";
            $msg .= "💬 <b>Chat ID:</b> <code>{$chatId}</code>\n";
            $msg .= "⏰ <b>Vaqt:</b> " . date('Y-m-d H:i:s') . "\n";
            $msg .= "🏢 Barcha Face ID kirish-chiqish suratlari ushbu manzilga yuboriladi.";

            $res = $testTg->sendMessage($msg);

            Helpers::jsonResponse([
                'success' => $res['success'],
                'message' => $res['success'] ? "Test xabar muvaffaqiyatli jo'natildi!" : ("Xabar yuborishda xato: " . ($res['error'] ?? 'Chat ID tekshiring')),
                'bot_status' => $testTg->getBotStatus()
            ]);
        }
    }

    Helpers::jsonResponse(['success' => false, 'message' => 'Noma\'lum so\'rov'], 400);

} catch (Throwable $e) {
    Helpers::jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
