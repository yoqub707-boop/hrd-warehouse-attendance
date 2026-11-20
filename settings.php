<?php
/**
 * Sozlamalar va Telegram Bot Monitoring (settings.php)
 */

declare(strict_types=1);

require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/TelegramService.php';

$telegramService = new TelegramService();
$db = Database::getInstance();

$botStatus = $telegramService->getBotStatus();

$pageTitle = "Tizim va Telegram Bot Sozlamalari";
$activeNav = 'settings';

require __DIR__ . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center space-x-3">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Tizim & Telegram Bot Sozlamalari</h1>
            <span id="botLiveBadge" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $botStatus['is_connected'] ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?= $botStatus['is_connected'] ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500' ?>"></span>
                <?= $botStatus['is_connected'] ? 'Bot Faol (@' . $botStatus['bot_username'] . ')' : 'Ulanmagan (Offline)' ?>
            </span>
        </div>
        <p class="text-xs text-slate-500 mt-1">
            Telegram Bot API, kanal sozlamalari, real-time yuborish loglari va Face ID parametrlari
        </p>
    </div>

    <!-- Yangilash tugmasi -->
    <button onclick="refreshBotStatus()" class="inline-flex items-center space-x-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-semibold shadow-2xs transition">
        <i id="refreshIcon" class="fa-solid fa-arrows-rotate text-blue-600"></i>
        <span>Holatni yangilash</span>
    </button>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
    
    <!-- CHAP: TELEGRAM SOZLAMALARI FORMASI -->
    <div class="lg:col-span-6 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
        <div class="flex items-center space-x-3 pb-4 border-b border-slate-100 mb-5">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-brands fa-telegram"></i>
            </div>
            <div>
                <h3 class="text-sm font-bold text-slate-900">Telegram Bot Integratsiyasi</h3>
                <p class="text-xs text-slate-500">Kirish va chiqish fotolari ushbu kanalga yuboriladi</p>
            </div>
        </div>

        <form id="botSettingsForm" onsubmit="saveBotSettings(event)" class="space-y-4 text-xs">
            <input type="hidden" name="action" value="update_settings">
            <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">

            <!-- Bot Token -->
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Telegram Bot Token *</label>
                <div class="relative">
                    <input type="password" name="bot_token" id="input_bot_token" value="<?= Helpers::e($telegramService->getBotToken()) ?>" placeholder="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ" class="w-full font-mono border border-slate-200 rounded-xl pl-3 pr-10 py-2.5 text-xs text-slate-800 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <button type="button" onclick="toggleTokenVisibility()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                        <i id="tokenEyeIcon" class="fa-solid fa-eye text-xs"></i>
                    </button>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">@BotFather orqali olingan token</p>
            </div>

            <!-- Chat ID -->
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Kanal yoki Guruh Chat ID *</label>
                <input type="text" name="chat_id" id="input_chat_id" value="<?= Helpers::e($telegramService->getChatId()) ?>" placeholder="-1001234567890 yoki @kanal_username" class="w-full font-mono border border-slate-200 rounded-xl px-3 py-2.5 text-xs text-slate-800 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                <p class="text-[11px] text-slate-400 mt-1">Bot ushbu guruh/kanalga admin qilib qo'shilgan bo'lishi kerak</p>
            </div>

            <!-- Bildirishnoma holati -->
            <div class="flex items-center space-x-2 pt-2">
                <input type="checkbox" name="enabled" id="input_enabled" value="1" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <label for="input_enabled" class="text-xs font-semibold text-slate-700">Telegram orqali avtomatik foto-xabar yuborish faol</label>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <button type="button" onclick="sendTestMessage()" class="px-4 py-2 border border-blue-200 text-blue-700 hover:bg-blue-50 rounded-xl text-xs font-semibold transition">
                    <i class="fa-solid fa-paper-plane mr-1"></i> Test Xabar Yuborish
                </button>
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-xl text-xs font-semibold hover:bg-blue-700 shadow-sm transition">
                    Saqlash
                </button>
            </div>
        </form>
    </div>

    <!-- O'NG: REAL-TIME BOT STATUSI VA METRIKALAR -->
    <div class="lg:col-span-6 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
            <h3 class="text-sm font-bold text-slate-900">
                <i class="fa-solid fa-satellite-dish mr-2 text-emerald-600"></i> Jonli Bot Holati (Real-time Polling)
            </h3>
            <span class="text-[10px] text-slate-400" id="pollTimestamp">Avto-yangilanish: 15s</span>
        </div>

        <!-- 3 ta qisqa metrika kartasi -->
        <div class="grid grid-cols-3 gap-3 mb-5">
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-center">
                <span class="text-[10px] text-slate-400 uppercase font-bold block">Ulanish</span>
                <span id="statConnected" class="text-sm font-extrabold <?= $botStatus['is_connected'] ? 'text-emerald-600' : 'text-slate-500' ?>">
                    <?= $botStatus['is_connected'] ? 'ONLINE' : 'OFFLINE' ?>
                </span>
            </div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-center">
                <span class="text-[10px] text-slate-400 uppercase font-bold block">Muvaffaqiyatli</span>
                <span id="statSuccess" class="text-sm font-extrabold text-blue-600">
                    <?= $botStatus['success_count'] ?> ta
                </span>
            </div>
            <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl text-center">
                <span class="text-[10px] text-slate-400 uppercase font-bold block">Xatoliklar</span>
                <span id="statError" class="text-sm font-extrabold text-rose-600">
                    <?= $botStatus['error_count'] ?> ta
                </span>
            </div>
        </div>

        <!-- Oxirgi loglar -->
        <div>
            <h4 class="text-xs font-bold text-slate-700 mb-2">So'nggi yuborish loglari:</h4>
            <div id="logsContainer" class="space-y-2 max-h-56 overflow-y-auto text-xs">
                <?php if (empty($botStatus['recent_logs'])): ?>
                    <p class="text-slate-400 text-center py-4 text-xs">Hozircha yuborishlar mavjud emas</p>
                <?php else: ?>
                    <?php foreach ($botStatus['recent_logs'] as $log): ?>
                        <div class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/60 flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="w-2 h-2 rounded-full <?= $log['status'] === 'success' ? 'bg-emerald-500' : ($log['status'] === 'offline' ? 'bg-amber-500' : 'bg-rose-500') ?>"></span>
                                <span class="font-bold text-slate-800 uppercase text-[10px]"><?= Helpers::e($log['event_type']) ?></span>
                                <span class="text-slate-400 text-[10px]">&bull; <?= $log['created_at'] ?></span>
                            </div>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded <?= $log['status'] === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700' ?>">
                                <?= Helpers::e($log['status']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script>
    function toggleTokenVisibility() {
        const input = document.getElementById('input_bot_token');
        const icon = document.getElementById('tokenEyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'fa-solid fa-eye-slash text-xs';
        } else {
            input.type = 'password';
            icon.className = 'fa-solid fa-eye text-xs';
        }
    }

    async function refreshBotStatus() {
        const icon = document.getElementById('refreshIcon');
        icon.classList.add('fa-spin');

        try {
            const res = await fetch('api/bot_status.php');
            const data = await res.json();
            if (data.success && data.bot_status) {
                updateBotUi(data.bot_status);
                showToast('Bot holati yangilandi', 'success');
            }
        } catch (e) {
            console.warn('Bot status yangilashda xato:', e);
        } finally {
            setTimeout(() => icon.classList.remove('fa-spin'), 500);
        }
    }

    function updateBotUi(status) {
        document.getElementById('statConnected').innerText = status.is_connected ? 'ONLINE' : 'OFFLINE';
        document.getElementById('statConnected').className = `text-sm font-extrabold ${status.is_connected ? 'text-emerald-600' : 'text-slate-500'}`;
        document.getElementById('statSuccess').innerText = status.success_count + ' ta';
        document.getElementById('statError').innerText = status.error_count + ' ta';

        const badge = document.getElementById('botLiveBadge');
        if (status.is_connected) {
            badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800';
            badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-emerald-500 animate-pulse"></span> Bot Faol (@${status.bot_username || ''})`;
        } else {
            badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800';
            badge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-amber-500"></span> Ulanmagan (Offline)`;
        }

        // Loglarni qayta chizish
        if (status.recent_logs && status.recent_logs.length > 0) {
            const container = document.getElementById('logsContainer');
            container.innerHTML = status.recent_logs.map(log => `
                <div class="p-2.5 rounded-xl border border-slate-100 bg-slate-50/60 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full ${log.status === 'success' ? 'bg-emerald-500' : (log.status === 'offline' ? 'bg-amber-500' : 'bg-rose-500')}"></span>
                        <span class="font-bold text-slate-800 uppercase text-[10px]">${log.event_type}</span>
                        <span class="text-slate-400 text-[10px]">&bull; ${log.created_at}</span>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded ${log.status === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-700'}">
                        ${log.status}
                    </span>
                </div>
            `).join('');
        }
    }

    // Har 15 soniyada avtomatik polling
    setInterval(async () => {
        try {
            const res = await fetch('api/bot_status.php');
            const data = await res.json();
            if (data.success && data.bot_status) {
                updateBotUi(data.bot_status);
            }
        } catch (e) {}
    }, 15000);

    async function saveBotSettings(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const res = await fetch('api/bot_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                showToast(result.message, 'success');
                if (result.bot_status) updateBotUi(result.bot_status);
            } else {
                showToast(result.message || 'Xatolik', 'error');
            }
        } catch (err) {
            showToast('Aloqa xatosi: ' + err.message, 'error');
        }
    }

    async function sendTestMessage() {
        const token = document.getElementById('input_bot_token').value.trim();
        const chatId = document.getElementById('input_chat_id').value.trim();

        if (!token || !chatId) {
            alert('Iltimos, avval Bot Token va Chat ID ni kiriting!');
            return;
        }

        try {
            const res = await fetch('api/bot_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'test_send',
                    bot_token: token,
                    chat_id: chatId
                })
            });
            const result = await res.json();
            if (result.success) {
                showToast(result.message, 'success');
                if (result.bot_status) updateBotUi(result.bot_status);
            } else {
                showToast(result.message, 'error');
            }
        } catch (err) {
            showToast('Xatolik: ' + err.message, 'error');
        }
    }
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
