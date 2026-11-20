<?php
/**
 * Ish Jadvallari va Smenalar (schedules.php)
 */

declare(strict_types=1);

require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Schedule.php';

$scheduleModel = new Schedule();
$schedules = $scheduleModel->getAll();

$pageTitle = "Ish Jadvallari va Smenalar";
$activeNav = 'schedules';

require __DIR__ . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Ish Jadvallari & Smenalar</h1>
        <p class="text-xs text-slate-500 mt-1">Omborxona smenalari, ish vaqti oralig'i, kechikish chegarasi (grace period) va dam olish vaqtlari</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php foreach ($schedules as $sch): ?>
        <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-2xs hover:shadow-md transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-lg">
                    Smena #<?= $sch['id'] ?>
                </span>
                <span class="text-xs text-emerald-600 font-semibold flex items-center">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span> Faol
                </span>
            </div>

            <h3 class="font-bold text-slate-900 text-sm mb-2"><?= Helpers::e($sch['name']) ?></h3>

            <div class="space-y-2 text-xs text-slate-600 mt-4 border-t border-slate-100 pt-3">
                <div class="flex justify-between">
                    <span class="text-slate-400">Ish vaqti:</span>
                    <span class="font-bold text-slate-800"><?= substr($sch['start_time'], 0, 5) ?> &mdash; <?= substr($sch['end_time'], 0, 5) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Kechikish chegarasi:</span>
                    <span class="font-semibold text-amber-600"><?= $sch['grace_period_minutes'] ?> daqiqa ruxsat</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Tushlik tanaffusi:</span>
                    <span class="font-semibold text-slate-700"><?= $sch['break_minutes'] ?> daqiqa</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Ish kunlari:</span>
                    <span class="font-semibold text-slate-700">Dush - Shan (6 kun)</span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="bg-blue-50/50 border border-blue-200 rounded-2xl p-4 flex items-start space-x-3 text-xs text-blue-800">
    <i class="fa-solid fa-circle-info text-blue-600 text-base mt-0.5"></i>
    <div>
        <h4 class="font-bold mb-0.5">Deterministik Kechikish Qoidasi:</h4>
        <p class="text-blue-700">Agar xodim belgilangan smena boshlanish vaqtidan (masalan 09:00) so'ng berilgan 15 daqiqalik chegaradan keyin (09:16 dan boshlab) kirsa, tizim 100% deterministik formula bo'yicha kechikish daqiqalarini aniq hisoblab, Telegram orqali ogohlantirish yuboradi.</p>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
