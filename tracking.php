<?php
/**
 * Kuzatuv va Jonli Monitoring (tracking.php)
 */

declare(strict_types=1);

require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Database.php';

Auth::getInstance()->requireAuth();

$db = Database::getInstance();

// Oxirgi 15 ta faoliyat logi
$sql = "SELECT 
            al.*,
            e.employee_code,
            e.first_name,
            e.last_name,
            e.position,
            e.avatar,
            d.name as department_name
        FROM attendance_logs al
        JOIN employees e ON al.employee_id = e.id
        LEFT JOIN departments d ON e.department_id = d.id
        ORDER BY al.id DESC
        LIMIT 15";

$logs = $db->fetchAll($sql);

$pageTitle = "Kuzatuv va Jonli Faoliyat";
$activeNav = 'tracking';

require __DIR__ . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center space-x-2">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Kuzatuv & Jonli Faoliyat</h1>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span> LIVE
            </span>
        </div>
        <p class="text-xs text-slate-500 mt-1">Omborxona kirish nuqtalari, turniket va terminallardan kelayotgan jonli oqim</p>
    </div>
    
    <a href="terminal.php" target="_blank" class="inline-flex items-center space-x-2 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
        <i class="fa-solid fa-camera"></i>
        <span>Terminal Kioskni Ochish</span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
    
    <!-- CHAP: OMBORXONA XARITASI VA ZONALARI -->
    <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-bold text-slate-900">
                <i class="fa-solid fa-warehouse mr-2 text-blue-600"></i> Sherobod Markaziy Omborxona Zonasi
            </h3>
            <span class="text-xs text-slate-400 font-mono">12,500 m²</span>
        </div>

        <!-- Vizual Zonalar Xaritasi -->
        <div class="aspect-16/10 bg-slate-950 rounded-xl p-4 relative overflow-hidden text-white flex flex-col justify-between border border-slate-800">
            <div class="flex justify-between items-start">
                <div class="p-3 bg-blue-900/60 border border-blue-500/40 rounded-xl text-center">
                    <span class="text-[10px] text-blue-300 font-bold block">1-DARVOZA</span>
                    <span class="text-xs font-extrabold text-white">ASOSIY TERMINAL</span>
                    <span class="text-[9px] text-emerald-400 block mt-0.5"><i class="fa-solid fa-circle text-[6px]"></i> Faol</span>
                </div>
                <div class="p-3 bg-purple-900/50 border border-purple-500/40 rounded-xl text-center">
                    <span class="text-[10px] text-purple-300 font-bold block">A-ZONA</span>
                    <span class="text-xs font-extrabold">QABUL & SARALASH</span>
                </div>
                <div class="p-3 bg-emerald-900/50 border border-emerald-500/40 rounded-xl text-center">
                    <span class="text-[10px] text-emerald-300 font-bold block">2-DARVOZA</span>
                    <span class="text-xs font-extrabold">YUK TUSHIRISH DOKI</span>
                </div>
            </div>

            <div class="my-auto py-6 border-y border-dashed border-white/20 grid grid-cols-4 gap-2 text-center text-xs">
                <div class="p-2 bg-slate-800/80 rounded-lg border border-slate-700">1-Javon Qatori</div>
                <div class="p-2 bg-slate-800/80 rounded-lg border border-slate-700">2-Javon Qatori</div>
                <div class="p-2 bg-slate-800/80 rounded-lg border border-slate-700">3-Javon Qatori</div>
                <div class="p-2 bg-slate-800/80 rounded-lg border border-slate-700">Forklift Yo'lagi</div>
            </div>

            <div class="flex justify-between items-end">
                <div class="p-3 bg-amber-900/50 border border-amber-500/40 rounded-xl text-center">
                    <span class="text-[10px] text-amber-300 font-bold block">QADOQLASH</span>
                    <span class="text-xs font-extrabold">Komplektatsiya</span>
                </div>
                <div class="p-3 bg-slate-800/80 border border-slate-600 rounded-xl text-center">
                    <span class="text-[10px] text-slate-400 font-bold block">XAVFSIZLIK</span>
                    <span class="text-xs font-extrabold">Kuzatuv Xonasi</span>
                </div>
                <div class="p-3 bg-rose-900/50 border border-rose-500/40 rounded-xl text-center">
                    <span class="text-[10px] text-rose-300 font-bold block">3-DARVOZA</span>
                    <span class="text-xs font-extrabold">JO'NATISH RAMPASI</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-3 mt-4 text-center text-xs">
            <div class="p-2 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-slate-400 block text-[10px]">Bugungi kirishlar</span>
                <span class="text-base font-extrabold text-emerald-600">95+</span>
            </div>
            <div class="p-2 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-slate-400 block text-[10px]">Terminal faolligi</span>
                <span class="text-base font-extrabold text-blue-600">99.9%</span>
            </div>
            <div class="p-2 bg-slate-50 rounded-xl border border-slate-200">
                <span class="text-slate-400 block text-[10px]">O'rtacha o'tish vaqti</span>
                <span class="text-base font-extrabold text-slate-800">1.8 soniya</span>
            </div>
        </div>
    </div>

    <!-- O'NG: JONLI VOQEALAR JURNALI (ACTIVITY LOG) -->
    <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200 p-6 shadow-2xs">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="text-sm font-bold text-slate-900">
                <i class="fa-solid fa-clock-rotate-left mr-2 text-emerald-600"></i> Jonli O'tish Jurnali
            </h3>
            <span class="text-[11px] text-slate-500 font-mono">So'nggi hodisalar</span>
        </div>

        <div class="space-y-3 overflow-y-auto max-h-[460px] pr-1">
            <?php if (empty($logs)): ?>
                <div class="text-center py-8 text-slate-400 text-xs">
                    Hozircha terminal orqali o'tishlar qayd etilmadi.
                </div>
            <?php else: ?>
                <?php foreach ($logs as $log): 
                    $isCheckIn = ($log['event_type'] === 'check_in');
                ?>
                    <div class="flex items-center justify-between p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs <?= $isCheckIn ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' ?>">
                                <i class="fa-solid <?= $isCheckIn ? 'fa-right-to-bracket' : 'fa-right-from-bracket' ?>"></i>
                            </div>
                            <div>
                                <div class="font-bold text-xs text-slate-900">
                                    <?= Helpers::e($log['first_name'] . ' ' . $log['last_name']) ?>
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    <?= Helpers::e($log['employee_code']) ?> &bull; <?= Helpers::e($log['position']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-mono font-bold text-slate-700 block">
                                <?= date('H:i:s', strtotime($log['event_time'])) ?>
                            </span>
                            <span class="text-[9px] font-semibold uppercase px-1.5 py-0.5 rounded <?= $isCheckIn ? 'bg-emerald-50 text-emerald-600' : 'bg-blue-50 text-blue-600' ?>">
                                <?= $isCheckIn ? 'Kirish' : 'Chiqish' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
