<?php
/**
 * test_runner.php - Web & CLI QA Test Dashboard
 */

declare(strict_types=1);

require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/tests/run_tests.php';

// Agar CLI orqali chaqirilsa
if (php_sapi_name() === 'cli') {
    $runner = new TestRunner();
    exit($runner->runAll() ? 0 : 1);
}

// Web orqali ko'rish
ob_start();
$runner = new TestRunner();
$success = $runner->runAll();
$rawOutput = ob_get_clean();

$pageTitle = "QA Avtomatlashtirilgan Testlar";
$activeNav = 'dashboard';

require __DIR__ . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center space-x-3">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">QA Avtomatlashtirilgan Testlar</h1>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?= $success ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                <i class="fa-solid <?= $success ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-rose-600' ?> mr-1.5"></i>
                <?= $success ? 'Barcha testlar muvaffaqiyatli o\'tdi (100% PASS)' : 'Testlarda xatolik bor' ?>
            </span>
        </div>
        <p class="text-xs text-slate-500 mt-1">
            TimeCalculator formulalari, 110 ta xodim, Face ID API endpointlar, rasm yuklash va xavfsizlik tekshiruvi
        </p>
    </div>

    <a href="test_runner.php" class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
        <i class="fa-solid fa-rotate-right"></i>
        <span>Testlarni qayta ishga tushirish</span>
    </a>
</div>

<!-- TERMINAL LOG DISPLAY -->
<div class="bg-slate-950 text-slate-200 rounded-2xl border border-slate-800 p-6 font-mono text-xs shadow-2xl overflow-x-auto leading-relaxed">
    <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4 text-slate-400">
        <div class="flex items-center space-x-2">
            <span class="w-3 h-3 rounded-full bg-rose-500 inline-block"></span>
            <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span>
            <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
            <span class="pl-2 font-bold text-slate-300">qa_test_runner.log</span>
        </div>
        <span>PHP <?= PHP_VERSION ?> &bull; UTF-8</span>
    </div>
    <pre class="whitespace-pre-wrap"><?= htmlspecialchars($rawOutput, ENT_QUOTES, 'UTF-8') ?></pre>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
