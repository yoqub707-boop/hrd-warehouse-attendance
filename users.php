<?php
/**
 * users.php - Tizim Foydalanuvchilari Boshqaruvi va Xavfsizlik Monitoringi
 */

declare(strict_types=1);

require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/Database.php';

$auth = Auth::getInstance();
$auth->requireAuth();

$currentUser = $auth->user();
$db = Database::getInstance();

$tab = $_GET['tab'] ?? 'users';
$action = $_POST['action'] ?? '';

// POST Amallarini bajarish
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = "Xavfsizlik tekshiruvi (CSRF) xatosi!";
        header('Location: users.php?tab=' . urlencode($tab));
        exit;
    }

    try {
        if ($action === 'create_user') {
            $username = trim($_POST['username'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'manager';
            $status = $_POST['status'] ?? 'active';

            if (empty($username) || empty($name) || empty($password)) {
                throw new Exception("Ism, login va parol to'ldirilishi shart!");
            }

            // Username mavjudligini tekshirish
            $exists = $db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
            if ($exists) {
                throw new Exception("Ushbu login band! Boshqa login tanlang.");
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $db->execute(
                "INSERT INTO users (username, name, email, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?)",
                [$username, $name, $email, $hash, $role, $status]
            );

            $_SESSION['flash_success'] = "Yangi foydalanuvchi muvaffaqiyatli qo'shildi!";
        }

        if ($action === 'update_user') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = $_POST['role'] ?? 'manager';
            $status = $_POST['status'] ?? 'active';
            $newPassword = $_POST['new_password'] ?? '';

            if ($userId <= 0 || empty($name)) {
                throw new Exception("Noto'g'ri foydalanuvchi ma'lumoti!");
            }

            // O'zining hisobini bloklashdan himoya
            if ($userId === (int)$currentUser['id'] && $status === 'blocked') {
                throw new Exception("O'zingizning faol profilingizni bloklay olmaysiz!");
            }

            if (!empty($newPassword)) {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);
                $db->execute(
                    "UPDATE users SET name = ?, email = ?, role = ?, status = ?, password_hash = ? WHERE id = ?",
                    [$name, $email, $role, $status, $hash, $userId]
                );
            } else {
                $db->execute(
                    "UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?",
                    [$name, $email, $role, $status, $userId]
                );
            }

            $_SESSION['flash_success'] = "Foydalanuvchi ma'lumotlari yangilandi!";
        }

        if ($action === 'toggle_status') {
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId === (int)$currentUser['id']) {
                throw new Exception("O'zingizning profilingiz holatini o'zgartira olmaysiz!");
            }
            $user = $db->fetch("SELECT status FROM users WHERE id = ?", [$userId]);
            if ($user) {
                $newStatus = ($user['status'] === 'active') ? 'blocked' : 'active';
                $db->execute("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $userId]);
                $_SESSION['flash_success'] = "Foydalanuvchi holati o'zgartirildi: " . ($newStatus === 'active' ? 'Faollashtirildi' : 'Bloklandi');
            }
        }

        if ($action === 'delete_user') {
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId === (int)$currentUser['id']) {
                throw new Exception("O'zingizning profilingizni o'chira olmaysiz!");
            }
            $db->execute("DELETE FROM users WHERE id = ?", [$userId]);
            $_SESSION['flash_success'] = "Foydalanuvchi muvaffaqiyatli o'chirildi!";
        }

        if ($action === 'unblock_ip') {
            $ip = trim($_POST['ip_address'] ?? '');
            if (!empty($ip)) {
                $db->execute("DELETE FROM ip_rate_limits WHERE ip_address = ?", [$ip]);
                $_SESSION['flash_success'] = "IP ({$ip}) muvaffaqiyatli blokdan chiqarildi!";
            }
        }

    } catch (Exception $e) {
        $_SESSION['flash_error'] = $e->getMessage();
    }

    header('Location: users.php?tab=' . urlencode($tab));
    exit;
}

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Foydalanuvchilar ro'yxati
$users = $db->fetchAll("SELECT * FROM users ORDER BY id ASC");

// Oxirgi 50 ta login va xavfsizlik logi
$loginLogs = $db->fetchAll("SELECT * FROM login_logs ORDER BY id DESC LIMIT 50");

// Bloklangan IP lar
$lockedIps = $db->fetchAll("SELECT * FROM ip_rate_limits WHERE locked_until > CURRENT_TIMESTAMP");

$pageTitle = "Foydalanuvchilar & Xavfsizlik Monitoringi";
$activeNav = 'users';

require __DIR__ . '/includes/header.php';
?>

<!-- Sarlavha va Tablar -->
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <div class="flex items-center space-x-3">
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">Foydalanuvchilar & Xavfsizlik</h1>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                Kirish Nazorati & Audit
            </span>
        </div>
        <p class="text-xs text-slate-500 mt-1">
            Admin paneli foydalanuvchilari, huquqlar va tizimga kirish loglari (IP va vaqt monitoringi)
        </p>
    </div>

    <!-- O'ng tomondagi tugmalar -->
    <div class="flex items-center space-x-2">
        <button onclick="openCreateUserModal()" class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm transition">
            <i class="fa-solid fa-user-shield text-xs"></i>
            <span>+ Yangi foydalanuvchi</span>
        </button>
    </div>
</div>

<!-- FLASH XABARNOMALAR -->
<?php if (!empty($flashSuccess)): ?>
    <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
            <span class="font-semibold"><?= Helpers::e($flashSuccess) ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-sm">&times;</button>
    </div>
<?php endif; ?>

<?php if (!empty($flashError)): ?>
    <div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-triangle-exclamation text-rose-500 text-base"></i>
            <span class="font-semibold"><?= Helpers::e($flashError) ?></span>
        </div>
        <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700 text-sm">&times;</button>
    </div>
<?php endif; ?>

<!-- ASOSIY TABLAR (FOYDALANUVCHILAR VA XAVFSIZLIK LOGLARI) -->
<div class="border-b border-slate-200 mb-6">
    <nav class="flex space-x-6 text-xs font-bold" aria-label="Tabs">
        <a href="?tab=users" class="py-3 px-1 border-b-2 flex items-center space-x-2 <?= $tab === 'users' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' ?>">
            <i class="fa-solid fa-users-gear text-sm"></i>
            <span>Tizim Foydalanuvchilari (<?= count($users) ?>)</span>
        </a>
        <a href="?tab=logs" class="py-3 px-1 border-b-2 flex items-center space-x-2 <?= $tab === 'logs' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' ?>">
            <i class="fa-solid fa-shield-halved text-sm"></i>
            <span>Kirishlar Audit Jurnali & IP Monitoring (<?= count($loginLogs) ?>)</span>
            <?php if (!empty($lockedIps)): ?>
                <span class="px-1.5 py-0.2 rounded-full bg-rose-600 text-white text-[10px]"><?= count($lockedIps) ?> IP bloklangan</span>
            <?php endif; ?>
        </a>
    </nav>
</div>

<?php if ($tab === 'users'): ?>
    <!-- 1-TAB: FOYDALANUVCHILAR RO'YXATI -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left">
                <thead class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 pl-6 pr-3">Foydalanuvchi</th>
                        <th class="px-3 py-3.5">Login (Username)</th>
                        <th class="px-3 py-3.5">Email</th>
                        <th class="px-3 py-3.5">Roli</th>
                        <th class="px-3 py-3.5">Holati</th>
                        <th class="px-3 py-3.5">Oxirgi kirish vaqti & IP</th>
                        <th class="py-3.5 pl-3 pr-6 text-right">Amallar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <?php foreach ($users as $u): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="py-3.5 pl-6 pr-3">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-xl bg-slate-800 text-white font-bold text-xs flex items-center justify-center">
                                        <?= strtoupper(mb_substr($u['name'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900"><?= Helpers::e($u['name']) ?></div>
                                        <?php if ((int)$u['id'] === (int)$currentUser['id']): ?>
                                            <span class="text-[10px] font-semibold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">Sizning profilingiz</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-3.5 font-mono font-bold text-slate-800"><?= Helpers::e($u['username']) ?></td>
                            <td class="px-3 py-3.5 text-slate-600"><?= Helpers::e($u['email'] ?: '-') ?></td>
                            <td class="px-3 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold <?= $u['role'] === 'admin' ? 'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20' : 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20' ?>">
                                    <i class="fa-solid <?= $u['role'] === 'admin' ? 'fa-crown mr-1 text-amber-500' : 'fa-user-check mr-1' ?>"></i>
                                    <?= strtoupper($u['role']) ?>
                                </span>
                            </td>
                            <td class="px-3 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold <?= $u['status'] === 'active' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20' ?>">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?= $u['status'] === 'active' ? 'bg-emerald-500' : 'bg-rose-500' ?>"></span>
                                    <?= $u['status'] === 'active' ? 'Faol' : 'Bloklangan' ?>
                                </span>
                            </td>
                            <td class="px-3 py-3.5">
                                <?php if (!empty($u['last_login_at'])): ?>
                                    <div class="font-semibold text-slate-800"><?= $u['last_login_at'] ?></div>
                                    <div class="text-[10px] font-mono text-slate-400">IP: <?= Helpers::e($u['last_login_ip'] ?? '-') ?></div>
                                <?php else: ?>
                                    <span class="text-slate-400">Hali kirmagan</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 pl-3 pr-6 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-1">
                                    <button onclick="openEditUserModal(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>)" title="Tahrirlash" class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-slate-100 rounded-lg transition">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </button>
                                    
                                    <?php if ((int)$u['id'] !== (int)$currentUser['id']): ?>
                                        <!-- Bloklash / Ochish formasi -->
                                        <form method="POST" action="users.php?tab=users" onsubmit="return confirm('Foydalanuvchi holatini o\'zgartirmoqchimisiz?')" class="inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                                            <button type="submit" title="<?= $u['status'] === 'active' ? 'Bloklash' : 'Faollashtirish' ?>" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-slate-100 rounded-lg transition">
                                                <i class="fa-solid <?= $u['status'] === 'active' ? 'fa-ban text-rose-500' : 'fa-check text-emerald-500' ?> text-xs"></i>
                                            </button>
                                        </form>

                                        <!-- O'chirish formasi -->
                                        <form method="POST" action="users.php?tab=users" onsubmit="return confirm('Haqiqatan ham bu foydalanuvchini o\'chirmoqchimisiz?')" class="inline">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                                            <button type="submit" title="O'chirish" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-slate-100 rounded-lg transition">
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- 2-TAB: XAVFSIZLIK MONITORINGI VA KIRISHLAR JURNALI -->
    
    <!-- Bloklangan IP lar bloki (agar mavjud bo'lsa) -->
    <?php if (!empty($lockedIps)): ?>
        <div class="mb-6 bg-rose-50 border border-rose-200 rounded-2xl p-4">
            <h4 class="text-xs font-bold text-rose-800 flex items-center mb-2">
                <i class="fa-solid fa-ban text-rose-600 mr-2"></i> Brute-force sababli bloklangan IP manzillar (5 ta xato urinish):
            </h4>
            <div class="space-y-2">
                <?php foreach ($lockedIps as $lip): ?>
                    <div class="flex items-center justify-between bg-white p-2.5 rounded-xl border border-rose-200 text-xs">
                        <div class="flex items-center space-x-3">
                            <span class="font-mono font-bold text-rose-700"><?= Helpers::e($lip['ip_address']) ?></span>
                            <span class="text-slate-500">Blok muddati: <b><?= $lip['locked_until'] ?></b></span>
                        </div>
                        <form method="POST" action="users.php?tab=logs">
                            <input type="hidden" name="action" value="unblock_ip">
                            <input type="hidden" name="ip_address" value="<?= Helpers::e($lip['ip_address']) ?>">
                            <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">
                            <button type="submit" class="px-3 py-1 bg-rose-600 hover:bg-rose-700 text-white font-semibold rounded-lg text-xs">
                                Blokdan chiqarish
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs mb-6">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-900">Kirishlar va Xavfsizlik Hodisalari Auditi</h3>
                <p class="text-xs text-slate-500 mt-0.5">Foydalanuvchilarning IP manzillari, aniq kirish vaqti va ruxsatsiz urinishlar monitoringi</p>
            </div>
            <span class="text-xs font-mono text-slate-400">Oxirgi 50 ta yozuv</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left">
                <thead class="bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 pl-6 pr-3">Vaqti</th>
                        <th class="px-3 py-3.5">Foydalanuvchi (Login)</th>
                        <th class="px-3 py-3.5">IP Manzil</th>
                        <th class="px-3 py-3.5">Natija / Holat</th>
                        <th class="px-3 py-3.5">Qurilma / User-Agent</th>
                        <th class="py-3.5 pl-3 pr-6">Tafsilotlar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <?php if (empty($loginLogs)): ?>
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">Hozircha kirish loglari mavjud emas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($loginLogs as $log): 
                            $isSuccess = ($log['status'] === 'success');
                            $isBlocked = in_array($log['status'], ['blocked_attempt', 'ip_locked']);
                        ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 pl-6 pr-3 font-mono font-semibold text-slate-700 whitespace-nowrap">
                                    <?= $log['created_at'] ?>
                                </td>
                                <td class="px-3 py-3 font-bold text-slate-900">
                                    <?= Helpers::e($log['username']) ?>
                                </td>
                                <td class="px-3 py-3 font-mono text-blue-600 font-bold whitespace-nowrap">
                                    <span class="bg-slate-100 px-2 py-0.5 rounded text-[11px] border border-slate-200">
                                        <?= Helpers::e($log['ip_address']) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold <?= $isSuccess ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : ($isBlocked ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20') ?>">
                                        <span class="w-1.5 h-1.5 rounded-full mr-1.5 <?= $isSuccess ? 'bg-emerald-500' : ($isBlocked ? 'bg-rose-500' : 'bg-amber-500') ?>"></span>
                                        <?= $isSuccess ? 'Muvaffaqiyatli' : ($isBlocked ? 'Bloklangan urinish' : 'Xato parol') ?>
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-slate-500 max-w-xs truncate" title="<?= Helpers::e($log['user_agent'] ?? '') ?>">
                                    <?= Helpers::e($log['user_agent'] ?? '-') ?>
                                </td>
                                <td class="py-3 pl-3 pr-6 text-slate-600">
                                    <?= Helpers::e($log['details'] ?? '-') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- MODAL: YANGI FOYDALANUVCHI QO'SHISH -->
<div id="createUserModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="text-sm font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-user-plus text-blue-600 mr-2"></i> Yangi Foydalanuvchi Qo'shish
            </h3>
            <button onclick="closeCreateUserModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="users.php?tab=users" class="space-y-3 text-xs">
            <input type="hidden" name="action" value="create_user">
            <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">

            <div>
                <label class="block font-semibold text-slate-700 mb-1">To'liq ism-familiya *</label>
                <input type="text" name="name" required placeholder="Masalan: Aziz Rahimov" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-slate-800">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Login (Username) *</label>
                    <input type="text" name="username" required placeholder="arahimov" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-slate-800">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" placeholder="rahimov@hrd.uz" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-slate-800">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Tizimdagi roli</label>
                    <select name="role" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-slate-800">
                        <option value="admin">Admin (To'liq huquq)</option>
                        <option value="manager" selected>Menejer (Nazorat va kiritish)</option>
                        <option value="viewer">Kuzatuvchi (Faqat ko'rish)</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Holati</label>
                    <select name="status" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-slate-800">
                        <option value="active" selected>Faol (Kirishga ruxsat)</option>
                        <option value="blocked">Bloklangan</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Maxfiy parol *</label>
                <input type="password" name="password" required placeholder="Kamida 8 ta belgi" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-slate-800">
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                <button type="button" onclick="closeCreateUserModal()" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50">
                    Bekor qilish
                </button>
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 shadow-sm">
                    Saqlash
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: FOYDALANUVCHINI TAHRIRLASH -->
<div id="editUserModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="text-sm font-bold text-slate-900 flex items-center">
                <i class="fa-solid fa-user-pen text-amber-500 mr-2"></i> Foydalanuvchini Tahrirlash
            </h3>
            <button onclick="closeEditUserModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="users.php?tab=users" class="space-y-3 text-xs">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" id="edit_user_id">
            <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">

            <div>
                <label class="block font-semibold text-slate-700 mb-1">To'liq ism-familiya *</label>
                <input type="text" name="name" id="edit_name" required class="w-full border border-slate-200 rounded-xl px-3 py-2 text-slate-800">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Login (O'zgartirib bo'lmaydi)</label>
                    <input type="text" id="edit_username" disabled class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3 py-2 text-slate-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Email</label>
                    <input type="email" name="email" id="edit_email" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-slate-800">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Roli</label>
                    <select name="role" id="edit_role" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-slate-800">
                        <option value="admin">Admin</option>
                        <option value="manager">Menejer</option>
                        <option value="viewer">Kuzatuvchi</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Holati</label>
                    <select name="status" id="edit_status" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-slate-800">
                        <option value="active">Faol</option>
                        <option value="blocked">Bloklangan</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Yangi parol (faqat o'zgartirish kerak bo'lsa to'ldiring)</label>
                <input type="password" name="new_password" placeholder="O'zgarishsiz qoldirish uchun bo'sh qoldiring" class="w-full border border-slate-200 rounded-xl px-3 py-2 text-slate-800">
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end space-x-2">
                <button type="button" onclick="closeEditUserModal()" class="px-4 py-2 border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50">
                    Bekor qilish
                </button>
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 shadow-sm">
                    Yangilash
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateUserModal() { document.getElementById('createUserModal').classList.remove('hidden'); }
    function closeCreateUserModal() { document.getElementById('createUserModal').classList.add('hidden'); }

    function openEditUserModal(user) {
        document.getElementById('edit_user_id').value = user.id;
        document.getElementById('edit_name').value = user.name;
        document.getElementById('edit_username').value = user.username;
        document.getElementById('edit_email').value = user.email || '';
        document.getElementById('edit_role').value = user.role;
        document.getElementById('edit_status').value = user.status;
        document.getElementById('editUserModal').classList.remove('hidden');
    }
    function closeEditUserModal() { document.getElementById('editUserModal').classList.add('hidden'); }
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
