<?php
/**
 * Monitoring va Hisobotlar (reports.php)
 */

declare(strict_types=1);

require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/ReportService.php';

Auth::getInstance()->requireAuth();

$reportService = new ReportService();

// CSV eksport so'rovi
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $exportDate = $_GET['date'] ?? date('Y-m-d');
    $reportService->exportCsv($exportDate);
    exit;
}

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$summary = $reportService->getSummary($startDate, $endDate);
$deptStats = $reportService->getDepartmentStats($startDate, $endDate);

$pageTitle = "Monitoring va Hisobotlar";
$activeNav = 'reports';

require __DIR__ . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Monitoring & Davomat Tahlili</h1>
        <p class="text-xs text-slate-500 mt-1">Omborxona ishchi kuchining davomat intizomi, kechikishlar statistikasi va umumiy samaradorlik</p>
    </div>

    <!-- Oraliq Tanlagich va Eksport -->
    <form method="GET" class="flex flex-wrap items-center gap-2">
        <div class="flex items-center space-x-1 bg-white border border-slate-200 rounded-xl p-1 text-xs">
            <input type="date" name="start_date" value="<?= Helpers::e($startDate) ?>" class="border-0 px-2 py-1 text-slate-700 font-semibold">
            <span class="text-slate-400">&mdash;</span>
            <input type="date" name="end_date" value="<?= Helpers::e($endDate) ?>" class="border-0 px-2 py-1 text-slate-700 font-semibold">
            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">Ko'rish</button>
        </div>
        <a href="?action=export_csv&date=<?= $endDate ?>" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
            <i class="fa-solid fa-file-csv mr-1"></i> Excel/CSV Yuklash
        </a>
    </form>
</div>

<!-- 4 TA MONITORING METRIKASI -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Davomat Intizomi</span>
        <div class="mt-2 text-3xl font-extrabold text-emerald-600"><?= $summary['punctuality_rate'] ?>%</div>
        <p class="text-xs text-slate-500 mt-1">O'z vaqtida kelganlar ulushi</p>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kechikishlar Soni</span>
        <div class="mt-2 text-3xl font-extrabold text-amber-500"><?= $summary['total_lates'] ?> ta</div>
        <p class="text-xs text-slate-500 mt-1">Tanlangan davr bo'yicha</p>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jami Ishlangan Soatlar</span>
        <div class="mt-2 text-3xl font-extrabold text-blue-600"><?= $summary['total_worked_hours'] ?> soat</div>
        <p class="text-xs text-slate-500 mt-1">Jami: <?= $summary['total_worked_formatted'] ?></p>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Kelmaganlar (Absence)</span>
        <div class="mt-2 text-3xl font-extrabold text-rose-600"><?= $summary['total_absences'] ?> ta</div>
        <p class="text-xs text-slate-500 mt-1">Sababsiz kelmagan kunlar</p>
    </div>
</div>

<!-- BO'LIMLAR INTIOMI TAHLILI -->
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs mb-6">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-900">Bo'limlar kesimida davomat ko'rsatkichlari</h3>
        <span class="text-xs text-slate-500"><?= $startDate ?> dan <?= $endDate ?> gacha</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-left">
            <thead class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="py-3.5 pl-6 pr-3">Bo'lim</th>
                    <th class="px-3 py-3.5">Ishchilar soni</th>
                    <th class="px-3 py-3.5">Kirishlar soni</th>
                    <th class="px-3 py-3.5">Kechikishlar</th>
                    <th class="px-3 py-3.5">Ishlangan soat</th>
                    <th class="py-3.5 pl-3 pr-6">Intizom ko'rsatkichi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                <?php foreach ($deptStats as $dept): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="py-3.5 pl-6 pr-3 font-bold text-slate-800"><?= Helpers::e($dept['department_name']) ?></td>
                        <td class="px-3 py-3.5 text-slate-600"><?= $dept['employee_count'] ?> kishi</td>
                        <td class="px-3 py-3.5 font-semibold text-slate-800"><?= $dept['check_in_count'] ?></td>
                        <td class="px-3 py-3.5 text-amber-600 font-bold"><?= $dept['late_count'] ?></td>
                        <td class="px-3 py-3.5 text-slate-700"><?= $dept['worked_hours'] ?> soat</td>
                        <td class="py-3.5 pl-3 pr-6">
                            <div class="flex items-center space-x-3">
                                <div class="w-24 bg-slate-100 rounded-full h-2 overflow-hidden">
                                    <div class="bg-emerald-500 h-2 rounded-full" style="width: <?= min(100, $dept['punctuality']) ?>%"></div>
                                </div>
                                <span class="font-bold <?= $dept['punctuality'] >= 85 ? 'text-emerald-600' : 'text-amber-600' ?>">
                                    <?= $dept['punctuality'] ?>%
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
