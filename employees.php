<?php
/**
 * Tashkilot tuzilmasi - Xodimlar ro'yxati (employees.php)
 */

declare(strict_types=1);

require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Employee.php';
require_once __DIR__ . '/src/Schedule.php';

$employeeModel = new Employee();
$scheduleModel = new Schedule();

$deptFilter = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$filters = [
    'department_id' => $deptFilter,
    'search' => $search
];

$employees = $employeeModel->getAll($filters, $limit, $offset);
$totalRows = $employeeModel->count($filters);
$totalPages = max(1, (int)ceil($totalRows / $limit));

$departments = $employeeModel->getDepartments();
$schedules = $scheduleModel->getAll();

$pageTitle = "Tashkilot tuzilmasi - Xodimlar";
$activeNav = 'employees';

require __DIR__ . '/includes/header.php';
?>

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center space-x-3">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Tashkilot tuzilmasi</h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                <?= $totalRows ?> ta faol ishchi
            </span>
        </div>
        <p class="text-xs text-slate-500 mt-1">
            Omborxona bo'limlari, xodimlar shtati, tabel raqamlari va PIN-kodlar boshqaruvi
        </p>
    </div>

    <!-- O'ng tomondagi tugmalar -->
    <div class="flex items-center space-x-2">
        <button onclick="openEmployeeModal()" class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
            <i class="fa-solid fa-user-plus text-xs"></i>
            <span>+ Yangi xodim qo'shish</span>
        </button>
    </div>
</div>

<!-- FILTRLAR VA QIDIRUV -->
<div class="bg-white rounded-2xl border border-slate-200 p-4 mb-6 shadow-2xs">
    <form method="GET" action="employees.php" class="flex flex-wrap sm:flex-nowrap items-center justify-between gap-3">
        
        <!-- Bo'limlar bo'yicha filter -->
        <div class="flex items-center space-x-2 w-full sm:w-auto">
            <label class="text-xs font-semibold text-slate-500 whitespace-nowrap">Bo'lim:</label>
            <select name="department_id" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-700 font-medium focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
                <option value="">Barcha bo'limlar</option>
                <?php foreach ($departments as $dept): ?>
                    <option value="<?= $dept['id'] ?>" <?= $deptFilter === (int)$dept['id'] ? 'selected' : '' ?>>
                        <?= Helpers::e($dept['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Qidiruv Maydoni -->
        <div class="relative w-full sm:w-80">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </div>
            <input type="text" name="search" value="<?= Helpers::e($search) ?>" placeholder="Ism, familiya, tabel yoki lavozim..."
                   class="w-full text-xs bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-3 py-2 text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-blue-500 focus:outline-hidden">
        </div>

        <?php if (!empty($search) || !empty($deptFilter)): ?>
            <a href="employees.php" class="text-xs text-rose-600 font-semibold hover:underline">
                Filtrni tozalash
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- XODIMLAR JADVALI -->
<div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs mb-6">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-left">
            <thead class="bg-slate-50/80">
                <tr class="text-[11px] font-bold text-slate-500 tracking-wider uppercase">
                    <th scope="col" class="py-3.5 pl-4 pr-3 sm:pl-6">Xodim</th>
                    <th scope="col" class="px-3 py-3.5">Tabel kodi</th>
                    <th scope="col" class="px-3 py-3.5">PIN-kod</th>
                    <th scope="col" class="px-3 py-3.5">Bo'lim</th>
                    <th scope="col" class="px-3 py-3.5">Telefon</th>
                    <th scope="col" class="px-3 py-3.5">Ish grafigi</th>
                    <th scope="col" class="px-3 py-3.5">Soatlik stavka</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 text-right">Amallar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white text-xs">
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colspan="8" class="py-12 text-center text-slate-400">
                            Xodimlar topilmadi.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employees as $emp): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 pl-4 pr-3 sm:pl-6">
                                <div class="flex items-center space-x-3">
                                    <img class="h-9 w-9 rounded-xl object-cover ring-1 ring-slate-200" 
                                         src="<?= Helpers::e($emp['avatar']) ?>" alt="">
                                    <div>
                                        <div class="font-bold text-slate-900">
                                            <?= Helpers::e($emp['first_name'] . ' ' . $emp['last_name']) ?>
                                        </div>
                                        <div class="text-[11px] text-slate-500">
                                            <?= Helpers::e($emp['position']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3 font-mono font-bold text-blue-600 whitespace-nowrap">
                                <?= Helpers::e($emp['employee_code']) ?>
                            </td>
                            <td class="px-3 py-3 font-mono text-slate-700 whitespace-nowrap">
                                <span class="bg-slate-100 px-2 py-0.5 rounded text-[11px] font-bold">
                                    <?= Helpers::e($emp['pin_code']) ?>
                                </span>
                            </td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">
                                <?= Helpers::e($emp['department_name'] ?? '-') ?>
                            </td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">
                                <?= Helpers::e($emp['phone']) ?>
                            </td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">
                                <?= substr($emp['start_time'] ?? '09:00', 0, 5) ?> - <?= substr($emp['end_time'] ?? '18:00', 0, 5) ?>
                            </td>
                            <td class="px-3 py-3 font-semibold text-slate-800 whitespace-nowrap">
                                <?= number_format((float)$emp['hourly_rate'], 0, '.', ' ') ?> so'm/soat
                            </td>
                            <td class="py-3 pl-3 pr-4 sm:pr-6 text-right whitespace-nowrap">
                                <a href="index.php?search=<?= urlencode($emp['employee_code']) ?>" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg text-xs" title="Davomatini ko'rish">
                                    <i class="fa-solid fa-clipboard-user"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Sahifalash -->
    <div class="px-4 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between sm:px-6">
        <p class="text-xs text-slate-600">
            Jami: <span class="font-bold text-slate-800"><?= $totalRows ?></span> ta xodim
        </p>
        <div class="flex space-x-1">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?department_id=<?= $deptFilter ?>&search=<?= Helpers::e($search) ?>&page=<?= $p ?>" 
                   class="px-3 py-1 text-xs rounded-lg border <?= $p === $page ? 'bg-blue-600 text-white border-blue-600 font-bold' : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- YANGI XODIM QO'SHISH MODAL -->
<div id="employeeModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-900">
                <i class="fa-solid fa-user-plus text-blue-600 mr-2"></i> Yangi xodim qo'shish
            </h3>
            <button onclick="closeEmployeeModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="createEmployeeForm" onsubmit="submitNewEmployee(event)" class="space-y-3 mt-4 text-xs">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Ism *</label>
                    <input type="text" name="first_name" required class="w-full border border-slate-200 rounded-xl px-3 py-2">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Familiya *</label>
                    <input type="text" name="last_name" required class="w-full border border-slate-200 rounded-xl px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Tabel kodi (Masalan: W-1121)</label>
                    <input type="text" name="employee_code" placeholder="Avto-generatsiya" class="w-full border border-slate-200 rounded-xl px-3 py-2">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">4-xonali PIN kod</label>
                    <input type="text" name="pin_code" placeholder="Masalan: 1234" maxlength="4" class="w-full border border-slate-200 rounded-xl px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Bo'lim</label>
                    <select name="department_id" class="w-full border border-slate-200 rounded-xl px-3 py-2">
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= Helpers::e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Lavozim</label>
                    <input type="text" name="position" placeholder="Masalan: Yukchi yoki Skanerchi" class="w-full border border-slate-200 rounded-xl px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Telefon</label>
                    <input type="text" name="phone" placeholder="+998 90 123 45 67" class="w-full border border-slate-200 rounded-xl px-3 py-2">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Soatlik ish haqi (so'm)</label>
                    <input type="number" name="hourly_rate" value="25000" step="1000" class="w-full border border-slate-200 rounded-xl px-3 py-2">
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                <button type="button" onclick="closeEmployeeModal()" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50">
                    Bekor qilish
                </button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700">
                    Qo'shish
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEmployeeModal() { document.getElementById('employeeModal').classList.remove('hidden'); }
    function closeEmployeeModal() { document.getElementById('employeeModal').classList.add('hidden'); }

    async function submitNewEmployee(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        try {
            const res = await fetch('api/employees.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                showToast(result.message, 'success');
                closeEmployeeModal();
                setTimeout(() => location.reload(), 700);
            } else {
                showToast(result.message, 'error');
            }
        } catch (err) {
            showToast('Aloqa xatosi: ' + err.message, 'error');
        }
    }
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
