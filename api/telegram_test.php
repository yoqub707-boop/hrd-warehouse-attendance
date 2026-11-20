<?php
/**
 * Telegram Test API - Bot va kanal ulanishini tekshirish
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/TelegramService.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$token = trim($input['bot_token'] ?? '');
$chatId = trim($input['chat_id'] ?? '');

$tg = new TelegramService($token, $chatId);
$res = $tg->testConnection($token, $chatId);

Helpers::jsonResponse($res);
