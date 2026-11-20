<?php
/**
 * Yuqori Navigatsiya Paneli (app.hrd.uz uslubida)
 * Bir qatorli qat'iy toza joylashuv, matn buzilishi va ko'chishi yo'q.
 * Xavfsiz foydalanuvchi profili, IP monitoringi va boshqaruv menyusi.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/TelegramService.php';

$auth = Auth::getInstance();
$currentUser = $auth->user();

$tg = new TelegramService();
$botConfigured = $tg->isConfigured();

$activeNav = $activeNav ?? 'dashboard';
?>
<header class="bg-white border-b border-slate-200 sticky top-0 z-40 shadow-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16 gap-2">
            
            <!-- CHAP: LOGO VA ASOSIY MENYU -->
            <div class="flex items-center space-x-3 xl:space-x-6 min-w-0">
                <!-- LOGO -->
                <a href="index.php" class="flex items-center space-x-2.5 shrink-0 group">
                    <div class="w-9 h-9 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold text-lg shadow-md group-hover:bg-blue-700 transition">
                        <i class="fa-solid fa-boxes-stacked text-base"></i>
                    </div>
                    <div class="hidden sm:block">
                        <div class="flex items-center space-x-1.5">
                            <span class="font-extrabold text-base tracking-tight text-slate-900 leading-none">HRD</span>
                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 leading-none">OMBOR</span>
                        </div>
                        <p class="text-[10px] text-slate-500 font-medium leading-none mt-1 whitespace-nowrap">Davomat va Ish Vaqti</p>
                    </div>
                </a>

                <!-- ASOSIY NAVIGATSIYA MENYUSI (Bir qatorda, qat'iy whitespace-nowrap) -->
                <nav class="hidden lg:flex items-center space-x-1 text-xs shrink-0">
                    <a href="index.php" class="px-2.5 py-2 rounded-xl whitespace-nowrap font-semibold transition <?= $activeNav === 'dashboard' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-gauge-high mr-1.5 text-xs"></i>Boshqaruv paneli
                    </a>
                    <a href="employees.php" class="px-2.5 py-2 rounded-xl whitespace-nowrap font-semibold transition <?= $activeNav === 'employees' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-sitemap mr-1.5 text-xs"></i>Tashkilot tuzilmasi
                    </a>
                    <a href="finance.php" class="px-2.5 py-2 rounded-xl whitespace-nowrap font-semibold transition <?= $activeNav === 'finance' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-wallet mr-1.5 text-xs"></i>Moliya
                    </a>
                    <a href="reports.php" class="px-2.5 py-2 rounded-xl whitespace-nowrap font-semibold transition <?= $activeNav === 'reports' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-chart-line mr-1.5 text-xs"></i>Monitoring
                    </a>
                    <a href="schedules.php" class="px-2.5 py-2 rounded-xl whitespace-nowrap font-semibold transition <?= $activeNav === 'schedules' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-regular fa-calendar-check mr-1.5 text-xs"></i>Ish jadvallari
                    </a>
                    <a href="tracking.php" class="px-2.5 py-2 rounded-xl whitespace-nowrap font-semibold transition <?= $activeNav === 'tracking' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                        <i class="fa-solid fa-map-location-dot mr-1.5 text-xs"></i>Kuzatuv
                    </a>
                </nav>
            </div>

            <!-- O'NG: BOSHQARUV TUGMALARI VA PROFIL -->
            <div class="flex items-center space-x-2 shrink-0">
                
                <!-- Terminal Kiosk (Yashil ixcham tugma) -->
                <a href="terminal.php" target="_blank" class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-200 animate-pulse"></span>
                    <i class="fa-solid fa-camera text-xs"></i>
                    <span>Terminal Kiosk</span>
                </a>

                <!-- Telegram Bot holati (ixcham) -->
                <a href="settings.php" title="Telegram Bot holati: <?= $botConfigured ? 'Ulangan' : 'Offline' ?>" class="hidden md:inline-flex items-center space-x-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold border <?= $botConfigured ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-slate-50 text-slate-600' ?> whitespace-nowrap">
                    <i class="fa-brands fa-telegram text-blue-500"></i>
                    <span class="text-[11px]"><?= $botConfigured ? 'Bot: Faol' : 'Bot: Offline' ?></span>
                </a>

                <!-- Foydalanuvchilar va Xavfsizlik qisqa tugmasi -->
                <a href="users.php" title="Foydalanuvchilar va Xavfsizlik monitoringi" class="hidden sm:inline-flex items-center p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs transition <?= $activeNav === 'users' ? 'bg-blue-50 text-blue-600 border-blue-200' : '' ?>">
                    <i class="fa-solid fa-user-shield text-xs"></i>
                </a>

                <!-- Sozlamalar -->
                <a href="settings.php" title="Tizim sozlamalari" class="hidden sm:inline-flex items-center p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs transition <?= $activeNav === 'settings' ? 'bg-blue-50 text-blue-600 border-blue-200' : '' ?>">
                    <i class="fa-solid fa-gear text-xs"></i>
                </a>

                <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

                <!-- Til Almashtirgich -->
                <div class="hidden sm:flex items-center space-x-1 text-xs font-medium text-slate-600 bg-slate-100 py-1 px-2 rounded-lg whitespace-nowrap">
                    <span class="text-blue-600 font-bold">O'ZB</span>
                    <span class="text-slate-400">|</span>
                    <span class="text-slate-400 hover:text-slate-700 cursor-pointer">РУС</span>
                </div>

                <!-- FOYDALANUVCHI PROFILI VA DROPDOWN -->
                <div class="relative" id="userProfileDropdown">
                    <button onclick="toggleProfileMenu()" type="button" class="flex items-center space-x-2 pl-1.5 focus:outline-hidden group cursor-pointer">
                        <div class="hidden md:block text-right whitespace-nowrap">
                            <div class="text-xs font-bold text-slate-800 group-hover:text-blue-600 transition truncate max-w-[150px]">
                                <?= Helpers::e($currentUser['name'] ?? 'Toshkent Markaziy Ombor') ?>
                            </div>
                            <div class="text-[10px] text-slate-400 flex items-center justify-end space-x-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span class="capitalize">Omborxona #1 (<?= Helpers::e($currentUser['role'] ?? 'admin') ?>)</span>
                            </div>
                        </div>
                        <div class="w-9 h-9 rounded-xl bg-slate-800 text-white font-bold text-xs flex items-center justify-center ring-2 ring-blue-500/30 group-hover:ring-blue-500 transition shrink-0">
                            <?= strtoupper(mb_substr($currentUser['name'] ?? 'TO', 0, 2)) ?>
                        </div>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
                    </button>

                    <!-- Profile Dropdown Menyu -->
                    <div id="profileMenu" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-2xl shadow-xl border border-slate-200 py-2 z-50 animate-in fade-in zoom-in-95 duration-100 text-xs">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <div class="font-bold text-slate-900"><?= Helpers::e($currentUser['name'] ?? 'Admin') ?></div>
                            <div class="text-[11px] text-slate-500">@<?= Helpers::e($currentUser['username'] ?? 'admin') ?> &bull; <span class="capitalize text-blue-600 font-semibold"><?= Helpers::e($currentUser['role'] ?? 'admin') ?></span></div>
                            <div class="text-[10px] text-slate-400 mt-1.5 font-mono bg-slate-50 p-1.5 rounded-lg border border-slate-100 flex items-center justify-between">
                                <span><i class="fa-solid fa-network-wired mr-1 text-slate-400"></i> IP Manzilingiz:</span>
                                <span class="font-bold text-blue-600"><?= Helpers::e($auth->getClientIp()) ?></span>
                            </div>
                        </div>

                        <div class="py-1">
                            <a href="users.php?tab=users" class="flex items-center px-4 py-2 text-slate-700 hover:bg-slate-50 font-medium">
                                <i class="fa-solid fa-users-gear mr-2.5 text-blue-600 text-sm"></i>
                                <div>
                                    <div class="font-semibold text-slate-800">Foydalanuvchilar boshqaruvi</div>
                                    <div class="text-[10px] text-slate-400">Yangi qo'shish va tahrirlash</div>
                                </div>
                            </a>
                            <a href="users.php?tab=logs" class="flex items-center px-4 py-2 text-slate-700 hover:bg-slate-50 font-medium">
                                <i class="fa-solid fa-shield-halved mr-2.5 text-emerald-600 text-sm"></i>
                                <div>
                                    <div class="font-semibold text-slate-800">Xavfsizlik & IP Auditi</div>
                                    <div class="text-[10px] text-slate-400">Kirish vaqti, IP va bloklanganlar</div>
                                </div>
                            </a>
                            <a href="settings.php" class="flex items-center px-4 py-2 text-slate-700 hover:bg-slate-50 font-medium">
                                <i class="fa-brands fa-telegram mr-2.5 text-blue-500 text-sm"></i>
                                <div>
                                    <div class="font-semibold text-slate-800">Telegram Bot sozlamalari</div>
                                    <div class="text-[10px] text-slate-400">Token, guruh ID va test xabar</div>
                                </div>
                            </a>
                            <a href="test_runner.php" class="flex items-center px-4 py-2 text-slate-700 hover:bg-slate-50 font-medium">
                                <i class="fa-solid fa-vial-circle-check mr-2.5 text-purple-600 text-sm"></i>
                                <div>
                                    <div class="font-semibold text-slate-800">QA Avtomatik Testlar</div>
                                    <div class="text-[10px] text-slate-400">Barcha funksiyalar diagnostikasi</div>
                                </div>
                            </a>
                        </div>

                        <div class="border-t border-slate-100 pt-1 mt-1">
                            <a href="logout.php" class="flex items-center px-4 py-2 text-rose-600 hover:bg-rose-50 font-bold transition">
                                <i class="fa-solid fa-right-from-bracket mr-2.5"></i> Xavfsiz tizimdan chiqish
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Mobil Menyuni Ochish Tugmasi -->
                <button type="button" onclick="toggleMobileNav()" class="lg:hidden p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 text-xs">
                    <i class="fa-solid fa-bars text-base"></i>
                </button>

            </div>
        </div>
    </div>

    <!-- MOBIL MENYU (Kichik ekranlar uchun) -->
    <div id="mobileNavMenu" class="hidden lg:hidden border-t border-slate-200 bg-white px-4 py-3 space-y-1 text-xs">
        <a href="index.php" class="block px-3 py-2 rounded-lg font-semibold <?= $activeNav === 'dashboard' ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' ?>">
            <i class="fa-solid fa-gauge-high mr-2 text-blue-600"></i>Boshqaruv paneli
        </a>
        <a href="employees.php" class="block px-3 py-2 rounded-lg font-semibold <?= $activeNav === 'employees' ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' ?>">
            <i class="fa-solid fa-sitemap mr-2 text-blue-600"></i>Tashkilot tuzilmasi
        </a>
        <a href="finance.php" class="block px-3 py-2 rounded-lg font-semibold <?= $activeNav === 'finance' ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' ?>">
            <i class="fa-solid fa-wallet mr-2 text-blue-600"></i>Moliya
        </a>
        <a href="reports.php" class="block px-3 py-2 rounded-lg font-semibold <?= $activeNav === 'reports' ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' ?>">
            <i class="fa-solid fa-chart-line mr-2 text-blue-600"></i>Monitoring
        </a>
        <a href="schedules.php" class="block px-3 py-2 rounded-lg font-semibold <?= $activeNav === 'schedules' ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' ?>">
            <i class="fa-regular fa-calendar-check mr-2 text-blue-600"></i>Ish jadvallari
        </a>
        <a href="tracking.php" class="block px-3 py-2 rounded-lg font-semibold <?= $activeNav === 'tracking' ? 'bg-blue-50 text-blue-700' : 'text-slate-700 hover:bg-slate-50' ?>">
            <i class="fa-solid fa-map-location-dot mr-2 text-blue-600"></i>Kuzatuv
        </a>
        <div class="border-t border-slate-100 my-2 pt-2">
            <a href="users.php" class="block px-3 py-2 rounded-lg font-semibold text-slate-700 hover:bg-slate-50">
                <i class="fa-solid fa-user-shield mr-2 text-blue-600"></i>Foydalanuvchilar & Xavfsizlik
            </a>
            <a href="settings.php" class="block px-3 py-2 rounded-lg font-semibold text-slate-700 hover:bg-slate-50">
                <i class="fa-solid fa-gear mr-2 text-slate-600"></i>Sozlamalar
            </a>
        </div>
    </div>
</header>

<script>
    function toggleProfileMenu() {
        const menu = document.getElementById('profileMenu');
        if (menu) menu.classList.toggle('hidden');
    }

    function toggleMobileNav() {
        const nav = document.getElementById('mobileNavMenu');
        if (nav) nav.classList.toggle('hidden');
    }

    // Tashqariga bosilganda dropdownni yopish
    document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('userProfileDropdown');
        const menu = document.getElementById('profileMenu');
        if (dropdown && menu && !dropdown.contains(event.target)) {
            menu.classList.add('hidden');
        }
    });
</script>
