<?php
/**
 * login.php - Xavfsiz Autentifikatsiya Sahifasi (HRD.uz dizayni)
 */

declare(strict_types=1);

require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Auth.php';

$auth = Auth::getInstance();

// Agar allaqachon kirgan bo'lsa, asosiy panelga yo'naltirish
if ($auth->check()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';
$redirect = $_GET['redirect'] ?? 'index.php';
$clientIp = $auth->getClientIp();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!Helpers::verifyCsrfToken($csrfToken)) {
        $error = "Xavfsizlik tekshiruvi (CSRF) muvaffaqiyatsiz bo'ldi. Sahifani yangilab qayta urinib ko'ring.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = "Iltimos, login va parolni kiriting!";
        } else {
            $res = $auth->login($username, $password);
            if ($res['success']) {
                $target = (!empty($redirect) && !str_contains($redirect, 'login.php')) ? $redirect : 'index.php';
                header('Location: ' . $target);
                exit;
            } else {
                $error = $res['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="uz" class="h-full bg-slate-900 select-none">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tizimga Kirish | HRD Omborxona Davomat</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full flex items-center justify-center p-4 bg-radial from-slate-900 via-slate-950 to-black text-slate-100">

    <div class="max-w-md w-full">
        
        <!-- LOGO VA BREND -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-600 text-white text-2xl font-bold shadow-xl shadow-blue-900/50 mb-3 ring-4 ring-blue-500/20">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div class="flex items-center justify-center space-x-2">
                <span class="text-2xl font-extrabold tracking-tight text-white">HRD</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded bg-blue-500/20 text-blue-400 border border-blue-500/30">OMBORXONA</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">Davomat va Ish Vaqti Hisobi Boshqaruv Paneli</p>
        </div>

        <!-- ASOSIY LOGIN KARTASI -->
        <div class="bg-slate-900/90 backdrop-blur-md border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl">
            
            <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-5">
                <div>
                    <h2 class="text-base font-bold text-white">Xavfsiz Tizimga Kirish</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Faqat ruxsat berilgan foydalanuvchilar uchun</p>
                </div>
                <span class="p-2 rounded-xl bg-slate-800 text-emerald-400 text-xs" title="SSL / TLS Xavfsiz ulanish">
                    <i class="fa-solid fa-shield-halved"></i>
                </span>
            </div>

            <!-- Xatolik xabarnomasi -->
            <?php if (!empty($error)): ?>
                <div class="mb-4 p-3.5 rounded-xl bg-rose-950/60 border border-rose-800/80 text-rose-300 text-xs flex items-start space-x-2.5 animate-in fade-in duration-200">
                    <i class="fa-solid fa-triangle-exclamation text-rose-400 text-sm mt-0.5"></i>
                    <div class="flex-1 font-medium"><?= Helpers::e($error) ?></div>
                </div>
            <?php endif; ?>

            <!-- LOGIN FORMASI -->
            <form method="POST" action="login.php?redirect=<?= urlencode($redirect) ?>" class="space-y-4 text-xs">
                <input type="hidden" name="csrf_token" value="<?= Helpers::csrfToken() ?>">

                <!-- Foydalanuvchi nomi -->
                <div>
                    <label class="block font-semibold text-slate-300 mb-1.5">Foydalanuvchi nomi</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <i class="fa-solid fa-user text-xs"></i>
                        </div>
                        <input type="text" name="username" required autofocus value="<?= Helpers::e($_POST['username'] ?? '') ?>"
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl pl-9 pr-3 py-2.5 text-white placeholder-slate-500 focus:outline-hidden focus:border-blue-500 text-xs">
                    </div>
                </div>

                <!-- Parol -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="font-semibold text-slate-300">Parol</label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500">
                            <i class="fa-solid fa-lock text-xs"></i>
                        </div>
                        <input type="password" name="password" id="loginPassword" required
                               class="w-full bg-slate-950 border border-slate-700 rounded-xl pl-9 pr-10 py-2.5 text-white placeholder-slate-500 focus:outline-hidden focus:border-blue-500 text-xs">
                        <button type="button" onclick="togglePasswordVisibility()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white">
                            <i id="passwordEye" class="fa-solid fa-eye text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Kirish tugmasi -->
                <button type="submit" class="w-full mt-2 py-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-extrabold text-xs shadow-lg shadow-blue-900/50 transition transform active:scale-95 flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-right-to-bracket text-sm"></i>
                    <span>TIZIMGA KIRISH</span>
                </button>

            </form>

        </div>

        <!-- IP MONITORING BANNERI -->
        <div class="mt-4 text-center text-xs text-slate-500 flex items-center justify-center space-x-2">
            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            <span>Xavfsizlik monitoringi faol &bull; Sizning IP manzilingiz: <b class="font-mono text-slate-400"><?= Helpers::e($clientIp) ?></b></span>
        </div>

    </div>

    <script>
        function togglePasswordVisibility() {
            const input = document.getElementById('loginPassword');
            const icon = document.getElementById('passwordEye');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fa-solid fa-eye-slash text-xs';
            } else {
                input.type = 'password';
                icon.className = 'fa-solid fa-eye text-xs';
            }
        }
    </script>
</body>
</html>
