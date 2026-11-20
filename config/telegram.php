<?php
/**
 * Telegram Bot API konfiguratsiyasi
 */

declare(strict_types=1);

return [
    'enabled' => filter_var($_ENV['TELEGRAM_BOT_ENABLE'] ?? true, FILTER_VALIDATE_BOOLEAN),
    'bot_token' => $_ENV['TELEGRAM_BOT_TOKEN'] ?? '',
    'chat_id' => $_ENV['TELEGRAM_CHAT_ID'] ?? '',
    'api_url' => 'https://api.telegram.org/bot',
    'timeout' => 10,
    'send_on_check_in' => true,
    'send_on_check_out' => true,
    'send_on_late' => true,
];
