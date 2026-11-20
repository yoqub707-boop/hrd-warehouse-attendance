<?php
/**
 * Yuqori Navigatsiya Paneli (app.hrd.uz uslubida)
 */

declare(strict_types=1);

$activeNav = $activeNav ?? 'dashboard';
?>
<header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            
            <!-- Logo va Tizim Nomi -->
            <div class="flex items-center space-x-6">
                <a href="index.php" class="flex items-center space-x-3 group">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold text-xl shadow-md group-hover:bg-blue-700 transition">
                        <i class="fa-solid fa-boxes-stacked text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-1.5">
                            <span class="font-extrabold text-xl tracking-tight text-slate-900">HRD</span>
                            <span class="text-xs font-semibold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">OMBOR</span>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium">Davomat va Ish Vaqti</p>
                    </div>
                </a>

                <!-- Asosiy Navigatsiya Menyusi -->
                <nav class="hidden lg:flex items-center space-x-1">
                    <a href="index.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $activeNav === 'dashboard' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-gauge-high mr-1.5 text-xs"></i> Boshqaruv paneli
                    </a>
                    <a href="employees.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $activeNav === 'employees' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-sitemap mr-1.5 text-xs"></i> Tashkilot tuzilmasi
                    </a>
                    <a href="finance.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $activeNav === 'finance' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-wallet mr-1.5 text-xs"></i> Moliya
                    </a>
                    <a href="reports.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $activeNav === 'reports' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-chart-line mr-1.5 text-xs"></i> Monitoring
                    </a>
                    <a href="schedules.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $activeNav === 'schedules' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-regular fa-calendar-check mr-1.5 text-xs"></i> Ish jadvallari
                    </a>
                    <a href="tracking.php" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $activeNav === 'tracking' ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-map-location-dot mr-1.5 text-xs"></i> Kuzatuv
                    </a>
                </nav>
            </div>

            <!-- O'ng Tomondagi Boshqaruvlar -->
            <div class="flex items-center space-x-3">
                
                <!-- Terminal Kiosk Tezkor O'tish Tugmasi -->
                <a href="terminal.php" target="_blank" class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition transform hover:scale-[1.02]">
                    <span class="w-2 h-2 rounded-full bg-emerald-200 animate-pulse"></span>
                    <i class="fa-solid fa-camera"></i>
                    <span>Terminal Kiosk</span>
                </a>

                <!-- Test Runner (QA vositasi) -->
                <a href="test_runner.php" title="Avtomatlashtirilgan QA testlarini ko'rish" class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs font-medium transition">
                    <i class="fa-solid fa-vial-circle-check text-blue-600 mr-1"></i> QA Testlar
                </a>

                <!-- Telegram Bot Tezkor Status -->
                <?php 
                require_once __DIR__ . '/../src/TelegramService.php';
                $navTg = new TelegramService();
                $navBotConfigured = $navTg->isConfigured();
                ?>
                <a href="settings.php" title="Telegram Bot holati: <?= $navBotConfigured ? 'Sozlangan' : 'Offline / Sozlanmagan' ?>" class="hidden md:inline-flex items-center space-x-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold <?= $navBotConfigured ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-600' ?>">
                    <i class="fa-brands fa-telegram text-blue-500"></i>
                    <span>Bot: <?= $navBotConfigured ? 'Ulangan' : 'Offline' ?></span>
                </a>

                <!-- Sozlamalar -->
                <a href="settings.php" title="Telegram va tizim sozlamalari" class="p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition <?= $activeNav === 'settings' ? 'text-blue-600 bg-blue-50' : '' ?>">
                    <i class="fa-solid fa-gear"></i>
                </a>

                <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

                <!-- Til Almashtirgich -->
                <div class="hidden sm:flex items-center space-x-1 text-xs font-medium text-slate-600 bg-slate-100 py-1 px-2 rounded-lg">
                    <span class="text-blue-600 font-bold">O'ZB</span>
                    <span class="text-slate-400">|</span>
                    <span class="text-slate-400 hover:text-slate-700 cursor-pointer">РУС</span>
                </div>

                <!-- Tashkilot va Profil -->
                <div class="flex items-center space-x-3 pl-2">
                    <div class="hidden md:block text-right">
                        <div class="text-xs font-bold text-slate-800">Toshkent Markaziy Ombor</div>
                        <div class="text-[10px] text-slate-500">Omborxona #1 (Admin)</div>
                    </div>
                    <div class="w-9 h-9 rounded-full bg-slate-800 text-white font-bold text-xs flex items-center justify-center ring-2 ring-blue-500 ring-offset-2">
                        TO
                    </div>
                </div>

            </div>
        </div>
    </div>
</header>
