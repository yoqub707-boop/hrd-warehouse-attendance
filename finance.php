<?php
/**
 * Moliya va Ish Haqi Hisob-kitobi (finance.php)
 */

declare(strict_types=1);

require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Database.php';

Auth::getInstance()->requireAuth();

$db = Database::getInstance();
$today = date('Y-m-d');
$month = $_GET['month'] ?? date('Y-m');

$startDate = "{$month}-01";
$endDate = date('Y-m-t', strtotime($startDate));

// Bo'limlar bo'yicha ish haqi hisobi
$sql = "SELECT 
            d.name as department_name,
            COUNT(DISTINCT e.id) as staff_count,
            SUM(a.worked_minutes) as total_worked_minutes,
            SUM(a.late_minutes) as total_late_minutes,
            AVG(e.hourly_rate) as avg_hourly_rate,
            SUM((a.worked_minutes / 60.0) * e.hourly_rate) as estimated_payout
        FROM departments d
        JOIN employees e ON d.id = e.department_id AND e.status = 'active'
        LEFT JOIN attendances a ON e.id = a.employee_id AND a.date BETWEEN ? AND ?
        GROUP BY d.id, d.name
        ORDER BY estimated_payout DESC";

$deptFinance = $db->fetchAll($sql, [$startDate, $endDate]);

$totalPayroll = 0;
$totalHours = 0;
$totalStaff = 0;
foreach ($deptFinance as $df) {
    $totalPayroll += (float)($df['estimated_payout'] ?? 0);
    $totalHours += round(((float)($df['total_worked_minutes'] ?? 0)) / 60, 1);
    $totalStaff += (int)$df['staff_count'];
}

$pageTitle = "Moliya - Ish Vaqti va Ish Haqi";
$activeNav = 'finance';

require __DIR__ . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Moliya & Ish Haqi</h1>
        <p class="text-xs text-slate-500 mt-1">Ombor ishchilarining haqiqiy ishlangan soatlari bo'yicha hisoblangan ish haqi jamg'armasi</p>
    </div>
    <form method="GET" class="flex items-center space-x-2">
        <label class="text-xs font-semibold text-slate-600">Davr:</label>
        <input type="month" name="month" value="<?= Helpers::e($month) ?>" onchange="this.form.submit()" class="text-xs bg-white border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-800">
    </form>
</div>

<!-- 3 TA MOLIYA STATISTIKA KARTOCHKASI -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Umumiy Ish Haqi Jamg'armasi</span>
        <div class="mt-2 text-2xl font-extrabold text-slate-900"><?= number_format($totalPayroll, 0, '.', ' ') ?> <span class="text-sm font-semibold text-slate-500">so'm</span></div>
        <p class="text-xs text-emerald-600 mt-1 font-medium"><i class="fa-solid fa-calculator mr-1"></i> <?= $month ?> oyi bo'yicha</p>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Jami Ishlangan Soat</span>
        <div class="mt-2 text-2xl font-extrabold text-blue-600"><?= number_format($totalHours, 1, '.', ' ') ?> <span class="text-sm font-semibold text-slate-500">soat</span></div>
        <p class="text-xs text-slate-500 mt-1">100+ ishchi bo'yicha</p>
    </div>
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">O'rtacha Soatlik Narx</span>
        <div class="mt-2 text-2xl font-extrabold text-slate-800">28 500 <span class="text-sm font-semibold text-slate-500">so'm/soat</span></div>
        <p class="text-xs text-slate-500 mt-1">Standart omborxona tariflari</p>
    </div>
</div>

<!-- BO'LIMLAR BO'YICHA TAQSIMOT -->
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs mb-6">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h3 class="text-sm font-bold text-slate-900">Bo'limlar kesimida to'lovlar hisobi</h3>
        <span class="text-xs text-slate-500">8 ta bo'lim</span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-left">
            <thead class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                <tr>
                    <th class="py-3.5 pl-6 pr-3">Bo'lim nomi</th>
                    <th class="px-3 py-3.5">Ishchilar soni</th>
                    <th class="px-3 py-3.5">Jami soat</th>
                    <th class="px-3 py-3.5">Kechikishlar (daq)</th>
                    <th class="px-3 py-3.5">O'rtacha stavka</th>
                    <th class="py-3.5 pl-3 pr-6 text-right">Hisoblangan summa</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                <?php foreach ($deptFinance as $df): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="py-3.5 pl-6 pr-3 font-bold text-slate-800"><?= Helpers::e($df['department_name']) ?></td>
                        <td class="px-3 py-3.5 text-slate-600"><?= $df['staff_count'] ?> kishi</td>
                        <td class="px-3 py-3.5 font-semibold text-slate-800"><?= round(((float)($df['total_worked_minutes'] ?? 0)) / 60, 1) ?> soat</td>
                        <td class="px-3 py-3.5 text-amber-600 font-semibold"><?= (int)($df['total_late_minutes'] ?? 0) ?> daq</td>
                        <td class="px-3 py-3.5 text-slate-600"><?= number_format((float)($df['avg_hourly_rate'] ?? 25000), 0, '.', ' ') ?> so'm</td>
                        <td class="py-3.5 pl-3 pr-6 text-right font-extrabold text-slate-900">
                            <?= number_format((float)($df['estimated_payout'] ?? 0), 0, '.', ' ') ?> so'm
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
