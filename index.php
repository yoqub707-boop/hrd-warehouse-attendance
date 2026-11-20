<?php
/**
 * HRD.UZ - Boshqaruv Paneli (Omborxona Davomat Tizimi)
 */

declare(strict_types=1);

require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Attendance.php';
require_once __DIR__ . '/src/Employee.php';

// Ruxsat tekshiruvi (faqat tizimga kirgan foydalanuvchilar kira oladi)
Auth::getInstance()->requireAuth();

$attendanceModel = new Attendance();
$employeeModel = new Employee();

// Tanlangan sana (standart bugun)
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$statusFilter = $_GET['status'] ?? 'all';
$deptFilter = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Statistika (4 ta asosiy kartochka)
$stats = $attendanceModel->getDashboardStats($selectedDate);

// Filtrlangan davomat ro'yxati
$filters = [
    'status' => $statusFilter,
    'department_id' => $deptFilter,
    'search' => $search
];

$attendances = $attendanceModel->getDailyAttendance($selectedDate, $filters, $limit, $offset);
$totalRows = $attendanceModel->getDailyAttendanceCount($selectedDate, $filters);
$totalPages = max(1, (int)ceil($totalRows / $limit));

$departments = $employeeModel->getDepartments();

// Sahifa sarlavhasi va faol menyu
$pageTitle = "Boshqaruv paneli";
$activeNav = 'dashboard';

require __DIR__ . '/includes/header.php';
?>

<!-- Sarlavha va Asosiy Boshqaruv Qatori -->
<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center space-x-3">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Boshqaruv paneli</h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                100+ Ishchi Omborxona
            </span>
        </div>
        <p class="text-xs text-slate-500 mt-1">
            Ombor xodimlarining kunlik kirish-chiqish davomati, kechikishlar va ishlangan vaqt hisoboti
        </p>
    </div>

    <!-- O'ng tomondagi asosiy tugmalar -->
    <div class="flex flex-wrap items-center gap-2">
        
        <!-- Sana Tanlagich -->
        <form method="GET" action="index.php" class="flex items-center bg-white border border-slate-200 rounded-xl p-1 shadow-2xs">
            <input type="hidden" name="status" value="<?= Helpers::e($statusFilter) ?>">
            <input type="hidden" name="search" value="<?= Helpers::e($search) ?>">
            <?php if ($deptFilter): ?>
                <input type="hidden" name="department_id" value="<?= $deptFilter ?>">
            <?php endif; ?>

            <a href="?date=<?= date('Y-m-d', strtotime($selectedDate . ' -1 day')) ?>&status=<?= Helpers::e($statusFilter) ?>&search=<?= Helpers::e($search) ?>" 
               title="Oldingi kun" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-600 transition">
                <i class="fa-solid fa-chevron-left text-xs"></i>
            </a>

            <input type="date" name="date" value="<?= Helpers::e($selectedDate) ?>" onchange="this.form.submit()" 
                   class="border-0 focus:ring-0 text-xs font-semibold text-slate-700 bg-transparent px-2 py-1 cursor-pointer">

            <a href="?date=<?= date('Y-m-d', strtotime($selectedDate . ' +1 day')) ?>&status=<?= Helpers::e($statusFilter) ?>&search=<?= Helpers::e($search) ?>" 
               title="Keyingi kun" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 text-slate-600 transition">
                <i class="fa-solid fa-chevron-right text-xs"></i>
            </a>

            <?php if ($selectedDate !== date('Y-m-d')): ?>
                <a href="?date=<?= date('Y-m-d') ?>" class="text-[11px] font-bold text-blue-600 hover:text-blue-800 px-2 border-l border-slate-200">
                    Bugun
                </a>
            <?php endif; ?>
        </form>

        <!-- Eksport Tugmasi -->
        <a href="reports.php?action=export_csv&date=<?= Helpers::e($selectedDate) ?>" class="inline-flex items-center space-x-1.5 px-3 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs font-medium shadow-2xs transition">
            <i class="fa-solid fa-file-excel text-emerald-600"></i>
            <span>Eksport CSV</span>
        </a>

        <!-- + Qo'lda davomat yaratish tugmasi -->
        <button onclick="openManualModal()" class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition transform active:scale-95">
            <i class="fa-solid fa-plus text-xs"></i>
            <span>+ Qo'lda davomat yaratish</span>
        </button>

    </div>
</div>

<!-- 4 TA ASOSIY STATISTIKA KARTOCHKASI -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    
    <!-- 1. Barchasi -->
    <a href="?date=<?= $selectedDate ?>&status=all" class="bg-white rounded-2xl p-4 border transition-all duration-200 shadow-2xs hover:shadow-md <?= $statusFilter === 'all' ? 'ring-2 ring-blue-500 border-transparent bg-blue-50/20' : 'border-slate-200' ?>">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 tracking-wider uppercase">Barchasi</span>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                <i class="fa-solid fa-users text-sm"></i>
            </div>
        </div>
        <div class="mt-3 flex items-baseline justify-between">
            <div class="text-3xl font-extrabold text-slate-900"><?= $stats['all'] ?></div>
            <span class="text-xs font-medium text-slate-400">100% ombor xodimlari</span>
        </div>
    </a>

    <!-- 2. Ishda -->
    <a href="?date=<?= $selectedDate ?>&status=present" class="bg-white rounded-2xl p-4 border transition-all duration-200 shadow-2xs hover:shadow-md <?= $statusFilter === 'present' ? 'ring-2 ring-emerald-500 border-transparent bg-emerald-50/20' : 'border-slate-200' ?>">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-emerald-600 tracking-wider uppercase">Ishda</span>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <i class="fa-solid fa-user-check text-sm"></i>
            </div>
        </div>
        <div class="mt-3 flex items-baseline justify-between">
            <div class="text-3xl font-extrabold text-emerald-700"><?= $stats['present'] ?></div>
            <span class="inline-flex items-center text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1 animate-pulse"></span>
                Faol ishlamoqda
            </span>
        </div>
    </a>

    <!-- 3. Kech -->
    <a href="?date=<?= $selectedDate ?>&status=late" class="bg-white rounded-2xl p-4 border transition-all duration-200 shadow-2xs hover:shadow-md <?= $statusFilter === 'late' ? 'ring-2 ring-amber-500 border-transparent bg-amber-50/20' : 'border-slate-200' ?>">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-amber-600 tracking-wider uppercase">Kech</span>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <i class="fa-solid fa-clock-rotate-left text-sm"></i>
            </div>
        </div>
        <div class="mt-3 flex items-baseline justify-between">
            <div class="text-3xl font-extrabold text-amber-600"><?= $stats['late'] ?></div>
            <span class="inline-flex items-center text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full">
                Kechikishlar qaydi
            </span>
        </div>
    </a>

    <!-- 4. Ishda emas -->
    <a href="?date=<?= $selectedDate ?>&status=absent" class="bg-white rounded-2xl p-4 border transition-all duration-200 shadow-2xs hover:shadow-md <?= $statusFilter === 'absent' ? 'ring-2 ring-rose-500 border-transparent bg-rose-50/20' : 'border-slate-200' ?>">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-rose-600 tracking-wider uppercase">Ishda emas</span>
            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                <i class="fa-solid fa-user-xmark text-sm"></i>
            </div>
        </div>
        <div class="mt-3 flex items-baseline justify-between">
            <div class="text-3xl font-extrabold text-rose-600"><?= $stats['absent'] ?></div>
            <span class="inline-flex items-center text-xs font-semibold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full">
                Kelmaganlar
            </span>
        </div>
    </a>

</div>

<!-- QIDIRUV VA FILTRLAR PANELI -->
<div class="bg-white rounded-2xl border border-slate-200 p-4 mb-6 shadow-2xs">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Status Filtri (Pills) -->
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="?date=<?= $selectedDate ?>&status=all&search=<?= Helpers::e($search) ?>" 
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= $statusFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Barchasi (<?= $stats['all'] ?>)
            </a>
            <a href="?date=<?= $selectedDate ?>&status=present&search=<?= Helpers::e($search) ?>" 
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= $statusFilter === 'present' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' ?>">
                Ishda (<?= $stats['present'] ?>)
            </a>
            <a href="?date=<?= $selectedDate ?>&status=late&search=<?= Helpers::e($search) ?>" 
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= $statusFilter === 'late' ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' ?>">
                Kech (<?= $stats['late'] ?>)
            </a>
            <a href="?date=<?= $selectedDate ?>&status=completed&search=<?= Helpers::e($search) ?>" 
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= $statusFilter === 'completed' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-700 hover:bg-blue-100' ?>">
                Ish tugagan (<?= $stats['completed'] ?>)
            </a>
            <a href="?date=<?= $selectedDate ?>&status=absent&search=<?= Helpers::e($search) ?>" 
               class="px-3 py-1.5 rounded-lg text-xs font-semibold transition <?= $statusFilter === 'absent' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' ?>">
                Ishda emas (<?= $stats['absent'] ?>)
            </a>
        </div>

        <!-- Qidiruv va Bo'lim Tanlash -->
        <form method="GET" action="index.php" class="flex flex-wrap sm:flex-nowrap items-center gap-2">
            <input type="hidden" name="date" value="<?= Helpers::e($selectedDate) ?>">
            <input type="hidden" name="status" value="<?= Helpers::e($statusFilter) ?>">

            <!-- Bo'lim bo'yicha filter -->
            <select name="department_id" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-700 font-medium focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                <option value="">Barcha bo'limlar</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= $deptFilter === (int)$dept['id'] ? 'selected' : '' ?>>
                        <?= Helpers::e($dept['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Qidiruv Maydoni -->
            <div class="relative w-full sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
                <input type="text" name="search" value="<?= Helpers::e($search) ?>" placeholder="Xodim, tabel yoki lavozim..."
                       class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <?php if (!empty($search) || !empty($deptFilter)): ?>
                <a href="?date=<?= $selectedDate ?>&status=<?= $statusFilter ?>" title="Filtrlarni tozalash" class="p-2 text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </form>

    </div>
</div>

<!-- DAVOMAT JADVALI (HRD JADVAL TUZILMASI) -->
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs mb-6">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-left">
            <thead class="bg-slate-50/80">
                <tr class="text-[11px] font-bold text-slate-500 tracking-wider uppercase">
                    <th scope="col" class="py-3.5 pl-4 pr-3 sm:pl-6">Xodim</th>
                    <th scope="col" class="px-3 py-3.5">Kirish</th>
                    <th scope="col" class="px-3 py-3.5">Chiqish</th>
                    <th scope="col" class="px-3 py-3.5">Jami</th>
                    <th scope="col" class="px-3 py-3.5">Ishlanmagan vaqt</th>
                    <th scope="col" class="px-3 py-3.5">Holat</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right">Amallar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white text-xs">
                <?php if (empty($attendances)): ?>
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <i class="fa-regular fa-folder-open text-3xl mb-2 block"></i>
                            Kiritilgan parametrlar bo'yicha ma'lumot topilmadi.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($attendances as $row): ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            
                            <!-- Xodim (rasm, ism-familiya, lavozim) -->
                            <td class="py-3 pl-4 pr-3 sm:pl-6">
                                <div class="flex items-center space-x-3">
                                    <div class="relative shrink-0">
                                        <img class="h-10 w-10 rounded-xl object-cover ring-1 ring-slate-200" 
                                             src="<?= Helpers::e($row['avatar'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($row['full_name']) . '&background=2563EB&color=fff') ?>" 
                                             alt="<?= Helpers::e($row['full_name']) ?>">
                                        <span class="absolute -bottom-1 -right-1 text-[9px] font-extrabold px-1 rounded bg-slate-800 text-white">
                                            <?= Helpers::e($row['employee_code']) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 hover:text-blue-600 cursor-pointer">
                                            <?= Helpers::e($row['full_name']) ?>
                                        </div>
                                        <div class="text-[11px] text-slate-500 flex items-center space-x-1.5 mt-0.5">
                                            <span><?= Helpers::e($row['position']) ?></span>
                                            <span>&bull;</span>
                                            <span class="text-slate-400"><?= Helpers::e($row['department_name'] ?? 'Ombor') ?></span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Kirish -->
                            <td class="px-3 py-3 font-semibold text-slate-800 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <span><?= $row['check_in_time'] ?></span>
                                    <?php if (!empty($row['check_in_photo'])): ?>
                                        <button onclick="viewSnapshot('<?= Helpers::e($row['check_in_photo']) ?>', '<?= Helpers::e($row['full_name']) ?>', 'Kirish rasmi', '<?= $row['check_in_time'] ?>')" 
                                                title="Kamera rasmini ko'rish" 
                                                class="text-blue-600 hover:text-blue-800 p-1 bg-blue-50 rounded-md">
                                            <i class="fa-solid fa-camera text-xs"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <?php if ($row['late_minutes'] > 0): ?>
                                    <span class="text-[10px] text-amber-600 block font-normal">
                                        +<?= $row['late_minutes'] ?> daqiqa kech
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Chiqish -->
                            <td class="px-3 py-3 font-semibold text-slate-800 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <span><?= $row['check_out_time'] ?></span>
                                    <?php if (!empty($row['check_out_photo'])): ?>
                                        <button onclick="viewSnapshot('<?= Helpers::e($row['check_out_photo']) ?>', '<?= Helpers::e($row['full_name']) ?>', 'Chiqish rasmi', '<?= $row['check_out_time'] ?>')" 
                                                title="Kamera rasmini ko'rish" 
                                                class="text-blue-600 hover:text-blue-800 p-1 bg-blue-50 rounded-md">
                                            <i class="fa-solid fa-camera text-xs"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- Jami (ishlangan vaqt) -->
                            <td class="px-3 py-3 font-bold text-slate-900 whitespace-nowrap">
                                <?= $row['worked_time_formatted'] ?>
                            </td>

                            <!-- Ishlanmagan vaqt -->
                            <td class="px-3 py-3 text-slate-500 whitespace-nowrap">
                                <?= $row['undertime_formatted'] ?>
                            </td>

                            <!-- Holat (Ishda, Kech, Ish tugagan, Ishda emas) -->
                            <td class="px-3 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset <?= $row['badge_class'] ?>">
                                    <?php if ($row['status_code'] === 'present'): ?>
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                    <?php elseif ($row['status_code'] === 'late'): ?>
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                    <?php elseif ($row['status_code'] === 'completed'): ?>
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-1.5"></span>
                                    <?php else: ?>
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                                    <?php endif; ?>
                                    <?= $row['display_status'] ?>
                                </span>
                            </td>

                            <!-- Amallar -->
                            <td class="py-3 pl-3 pr-4 sm:pr-6 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-1">
                                    <button onclick="openDetailModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)" 
                                            title="Tafsilotlar" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-slate-100 rounded-lg transition">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </button>
                                    <button onclick="editRow(<?= htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') ?>)" 
                                            title="Tahrirlash" class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-slate-100 rounded-lg transition">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                    <?php if (!empty($row['attendance_id'])): ?>
                                        <button onclick="deleteRow(<?= $row['attendance_id'] ?>)" 
                                                title="O'chirish" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-slate-100 rounded-lg transition">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>

                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- SAHIFALASH (PAGINATION) -->
    <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between sm:px-6">
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-xs text-slate-600">
                    Ko'rsatilmoqda: <span class="font-bold text-slate-800"><?= min($totalRows, $offset + 1) ?></span> dan 
                    <span class="font-bold text-slate-800"><?= min($totalRows, $offset + $limit) ?></span> gacha, 
                    jami <span class="font-bold text-slate-800"><?= $totalRows ?></span> ta xodim
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-xl shadow-2xs -space-x-px" aria-label="Pagination">
                    <!-- Oldingisi -->
                    <?php if ($page > 1): ?>
                        <a href="?date=<?= $selectedDate ?>&status=<?= $statusFilter ?>&search=<?= Helpers::e($search) ?>&page=<?= $page - 1 ?>" 
                           class="relative inline-flex items-center px-3 py-1.5 rounded-l-xl border border-slate-300 bg-white text-xs font-medium text-slate-600 hover:bg-slate-50">
                            Oldingisi
                        </a>
                    <?php else: ?>
                        <span class="relative inline-flex items-center px-3 py-1.5 rounded-l-xl border border-slate-200 bg-slate-100 text-xs font-medium text-slate-400 cursor-not-allowed">
                            Oldingisi
                        </span>
                    <?php endif; ?>

                    <!-- Sahifa raqamlari -->
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    for ($p = $startPage; $p <= $endPage; $p++):
                    ?>
                        <a href="?date=<?= $selectedDate ?>&status=<?= $statusFilter ?>&search=<?= Helpers::e($search) ?>&page=<?= $p ?>" 
                           class="relative inline-flex items-center px-3 py-1.5 border <?= $p === $page ? 'z-10 bg-blue-600 border-blue-600 text-white font-bold' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50' ?> text-xs">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <!-- Keyingisi -->
                    <?php if ($page < $totalPages): ?>
                        <a href="?date=<?= $selectedDate ?>&status=<?= $statusFilter ?>&search=<?= Helpers::e($search) ?>&page=<?= $page + 1 ?>" 
                           class="relative inline-flex items-center px-3 py-1.5 rounded-r-xl border border-slate-300 bg-white text-xs font-medium text-slate-600 hover:bg-slate-50">
                            Keyingisi
                        </a>
                    <?php else: ?>
                        <span class="relative inline-flex items-center px-3 py-1.5 rounded-r-xl border border-slate-200 bg-slate-100 text-xs font-medium text-slate-400 cursor-not-allowed">
                            Keyingisi
                        </span>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: QO'LDA DAVOMAT YARATISH -->
<div id="manualModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 animate-in fade-in zoom-in duration-150">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">
                <i class="fa-solid fa-clock mr-2 text-blue-600"></i> Qo'lda davomat yaratish
            </h3>
            <button onclick="closeManualModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="manualAttendanceForm" onsubmit="submitManualAttendance(event)" class="space-y-4 mt-4">
            <input type="hidden" name="action" value="manual_create">
            <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">

            <!-- Xodimni tanlash -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Xodimni tanlang *</label>
                <select name="employee_id" id="manual_employee_id" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                    <option value="">-- Xodimni tanlang --</option>
                    <?php
                    $allEmps = $employeeModel->getAll([], 150, 0);
                    foreach ($allEmps as $emp):
                    ?>
                        <option value="<?= $emp['id'] ?>">
                            <?= Helpers::e($emp['employee_code']) ?> - <?= Helpers::e($emp['first_name'] . ' ' . $emp['last_name']) ?> (<?= Helpers::e($emp['position']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Sana -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Sana *</label>
                <input type="date" name="date" id="manual_date" value="<?= $selectedDate ?>" required class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
            </div>

            <!-- Kirish va Chiqish vaqti -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kirish vaqti</label>
                    <input type="time" name="check_in_time" id="manual_check_in_time" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Chiqish vaqti</label>
                    <input type="time" name="check_out_time" id="manual_check_out_time" class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                </div>
            </div>

            <!-- Izoh -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Izoh / Sabab</label>
                <textarea name="note" id="manual_note" rows="2" placeholder="Masalan: Komandirovka yoki tizim nosozligi tufayli..." class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-hidden"></textarea>
            </div>

            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                <button type="button" onclick="closeManualModal()" class="px-4 py-2 border border-slate-200 text-slate-600 rounded-xl text-xs font-medium hover:bg-slate-50 transition">
                    Bekor qilish
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-semibold hover:bg-blue-700 transition">
                    Saqlash
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: KAMERA RASMI KO'RISH -->
<div id="snapshotModal" class="fixed inset-0 z-50 hidden bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-5 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div>
                <h4 id="snapTitle" class="text-sm font-bold text-slate-900">Kamera Rasmi</h4>
                <p id="snapSubtitle" class="text-xs text-slate-500"></p>
            </div>
            <button onclick="closeSnapshotModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="mt-4 bg-slate-950 rounded-xl overflow-hidden flex items-center justify-center border border-slate-800">
            <img id="snapImage" src="" alt="Snapshot" class="max-h-80 w-auto object-contain">
        </div>

        <div class="mt-4 flex items-center justify-between text-xs text-slate-500 pt-3 border-t border-slate-100">
            <span class="inline-flex items-center text-emerald-600 font-semibold">
                <i class="fa-brands fa-telegram mr-1.5 text-blue-500 text-sm"></i> Telegram kanalga yuborilgan
            </span>
            <button onclick="closeSnapshotModal()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 rounded-lg text-slate-700 font-medium">
                Yopish
            </button>
        </div>
    </div>
</div>

<!-- MODAL: BATAFSIL MA'LUMOT -->
<div id="detailModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">
                <i class="fa-solid fa-id-card text-blue-600 mr-2"></i> Davomat Tafsilotlari
            </h3>
            <button onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div id="detailContent" class="space-y-4 mt-4 text-xs">
            <!-- Dinamik to'ldiriladi -->
        </div>

        <div class="pt-4 mt-4 border-t border-slate-100 flex justify-end">
            <button onclick="closeDetailModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 rounded-xl text-slate-700 font-semibold">
                Yopish
            </button>
        </div>
    </div>
</div>

<script>
    // Modal funksiyalari
    function openManualModal() {
        document.getElementById('manualModal').classList.remove('hidden');
    }
    function closeManualModal() {
        document.getElementById('manualModal').classList.add('hidden');
    }

    function viewSnapshot(url, name, type, time) {
        document.getElementById('snapImage').src = url;
        document.getElementById('snapTitle').innerText = `${name} - ${type}`;
        document.getElementById('snapSubtitle').innerText = `Vaqt: ${time}`;
        document.getElementById('snapshotModal').classList.remove('hidden');
    }
    function closeSnapshotModal() {
        document.getElementById('snapshotModal').classList.add('hidden');
    }

    function openDetailModal(data) {
        const content = document.getElementById('detailContent');
        content.innerHTML = `
            <div class="flex items-center space-x-3 p-3 bg-slate-50 rounded-xl">
                <img src="${data.avatar || ''}" class="w-12 h-12 rounded-xl object-cover">
                <div>
                    <div class="font-bold text-slate-900 text-sm">${data.full_name}</div>
                    <div class="text-slate-500">${data.position} &bull; ${data.department_name || ''}</div>
                    <div class="text-[10px] font-mono font-bold text-blue-600">Tabel: ${data.employee_code}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3 pt-2">
                <div class="p-3 border border-slate-200 rounded-xl">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Kirish vaqti</span>
                    <span class="font-bold text-sm text-slate-800">${data.check_in_time || '-'}</span>
                    ${data.late_minutes > 0 ? `<span class="text-amber-600 block text-[10px]">+${data.late_minutes} daqiqa kech</span>` : ''}
                </div>
                <div class="p-3 border border-slate-200 rounded-xl">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Chiqish vaqti</span>
                    <span class="font-bold text-sm text-slate-800">${data.check_out_time || '-'}</span>
                </div>
                <div class="p-3 border border-slate-200 rounded-xl">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Jami ishlangan</span>
                    <span class="font-bold text-sm text-emerald-600">${data.worked_time_formatted || '-'}</span>
                </div>
                <div class="p-3 border border-slate-200 rounded-xl">
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Ishlanmagan vaqt</span>
                    <span class="font-bold text-sm text-rose-600">${data.undertime_formatted || '-'}</span>
                </div>
            </div>

            <div class="p-3 bg-blue-50/50 rounded-xl border border-blue-100">
                <span class="text-blue-700 font-bold block mb-1">Qayd usuli va izoh:</span>
                <p class="text-slate-600">${data.note || 'Terminal orqali avtomatik qayd etilgan'}</p>
            </div>
        `;
        document.getElementById('detailModal').classList.remove('hidden');
    }
    function closeDetailModal() {
        document.getElementById('detailModal').classList.add('hidden');
    }

    function editRow(data) {
        openManualModal();
        document.getElementById('manual_employee_id').value = data.employee_id;
        document.getElementById('manual_date').value = data.date || '<?= $selectedDate ?>';
        document.getElementById('manual_check_in_time').value = data.check_in_time !== '-' ? data.check_in_time : '';
        document.getElementById('manual_check_out_time').value = data.check_out_time !== '-' ? data.check_out_time : '';
        document.getElementById('manual_note').value = data.note || '';
    }

    async function submitManualAttendance(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const res = await fetch('api/attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                showToast(result.message, 'success');
                closeManualModal();
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(result.message || 'Xatolik yuz berdi', 'error');
            }
        } catch (err) {
            showToast('Aloqa xatosi: ' + err.message, 'error');
        }
    }

    async function deleteRow(id) {
        if (!confirm('Haqiqatan ham bu davomat yozuvini o\'chirmoqchimisiz?')) return;

        try {
            const res = await fetch('api/attendance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete',
                    attendance_id: id,
                    csrf_token: '<?= Helpers::csrfToken() ?>'
                })
            });
            const result = await res.json();
            if (result.success) {
                showToast('Muvaffaqiyatli o\'chirildi', 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(result.message, 'error');
            }
        } catch (err) {
            showToast('Xatolik: ' + err.message, 'error');
        }
    }
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
