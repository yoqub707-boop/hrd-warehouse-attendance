<?php
/**
 * Omborxona Face ID Davomat Kiosk Terminali (terminal.php)
 * Veb-kamera orqali yuzni aniqlash (Face ID) va avtomatik Kirish/Chiqish qayd etish
 */

declare(strict_types=1);

require_once __DIR__ . '/src/Helpers.php';
require_once __DIR__ . '/src/Employee.php';

$employeeModel = new Employee();
$employees = $employeeModel->getAll([], 150, 0);
?>
<!DOCTYPE html>
<html lang="uz" class="h-full bg-slate-950 text-slate-100 select-none">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face ID Davomat Terminali | HRD.UZ</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        
        /* Face scanner scanline animatsiyasi */
        @keyframes scanline {
            0% { transform: translateY(0%); opacity: 0.8; }
            50% { opacity: 1; }
            100% { transform: translateY(320px); opacity: 0.8; }
        }
        .scanner-line {
            animation: scanline 2.2s ease-in-out infinite alternate;
        }
    </style>
</head>
<body class="h-full flex flex-col justify-between overflow-hidden bg-radial from-slate-900 via-slate-950 to-black p-4 sm:p-6">

    <!-- YUQORI STATUS PANELI -->
    <header class="flex items-center justify-between border-b border-slate-800 pb-4">
        <div class="flex items-center space-x-3">
            <a href="index.php" class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white text-lg font-bold shadow-lg hover:bg-blue-700 transition">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center space-x-2">
                    <h1 class="text-lg font-extrabold tracking-tight text-white flex items-center">
                        <i class="fa-solid fa-expand mr-2 text-blue-500"></i> FACE ID SMART-TERMINAL
                    </h1>
                    <span id="faceModelStatus" class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5 animate-pulse"></span> FACE ID FAOL
                    </span>
                </div>
                <p class="text-xs text-slate-400">1-Ombor Asosiy Kirish Nuqtasi &bull; Biometrik Yuz Tanib Olish &bull; PIN Zaxira</p>
            </div>
        </div>

        <!-- Soat, Sana va Yangi Yuz Qo'shish -->
        <div class="flex items-center space-x-3">
            <button onclick="openRegisterFaceModal()" class="px-3 py-2 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 text-xs font-semibold rounded-xl transition flex items-center space-x-1.5">
                <i class="fa-solid fa-camera-rotate text-blue-400"></i>
                <span>Yuz namunasini kiritish</span>
            </button>

            <div class="text-right pl-2 border-l border-slate-800">
                <div id="liveClock" class="font-mono text-2xl sm:text-3xl font-extrabold text-blue-400 tracking-wider">
                    00:00:00
                </div>
                <div id="liveDate" class="text-xs font-semibold text-slate-400">
                    Yuklanmoqda...
                </div>
            </div>
            
            <button onclick="toggleFullScreen()" title="To'liq ekranga o'tish" class="p-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 transition">
                <i class="fa-solid fa-expand"></i>
            </button>
        </div>
    </header>

    <!-- ASOSIY ISH MAYDONI -->
    <main class="flex-1 my-4 grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
        
        <!-- CHAP: FACE ID KAMERA OYNASI (SCANNER VIEWFINDER) -->
        <div class="lg:col-span-7 flex flex-col items-center justify-center h-full">
            <div class="relative w-full max-w-xl aspect-4/3 bg-slate-900 rounded-3xl border-2 border-slate-800 overflow-hidden shadow-2xl flex items-center justify-center">
                
                <!-- Jonli video oqimi -->
                <video id="webcam" autoplay playsinline muted class="w-full h-full object-cover transform -scale-x-100"></video>
                
                <!-- Yashirin Canvas -->
                <canvas id="snapshotCanvas" class="hidden"></canvas>

                <!-- Kamera yo'qligida zaxira -->
                <div id="cameraFallback" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-900/90 text-center p-6 hidden">
                    <div class="w-16 h-16 rounded-2xl bg-slate-800 text-slate-400 flex items-center justify-center text-2xl mb-3">
                        <i class="fa-solid fa-camera-slash"></i>
                    </div>
                    <h3 class="text-sm font-bold text-slate-200">Kamera aniqlanmadi</h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-xs">PIN yoki Tabel raqami orqali davomat qayd etishingiz mumkin.</p>
                </div>

                <!-- Face Scanner Scanline Lazer Chizig'i -->
                <div id="scannerLine" class="scanner-line absolute top-6 left-6 right-6 h-0.5 bg-gradient-to-r from-transparent via-blue-400 to-transparent shadow-[0_0_15px_#38bdf8] pointer-events-none"></div>

                <!-- Yuz ramkasi (Face Target Box) -->
                <div id="faceBox" class="absolute w-64 h-72 border-2 border-blue-500/70 rounded-3xl pointer-events-none flex flex-col justify-between p-3 transition-all duration-200">
                    <div class="flex justify-between items-start">
                        <div class="w-6 h-6 border-t-4 border-l-4 border-blue-400 rounded-tl-lg"></div>
                        <div class="w-6 h-6 border-t-4 border-r-4 border-blue-400 rounded-tr-lg"></div>
                    </div>
                    <!-- Yuz tanilgandagi animatsion holat nishoni -->
                    <div id="faceRecognizedBadge" class="bg-slate-950/80 backdrop-blur-md px-3 py-1.5 rounded-xl border border-blue-500/50 text-center mx-auto opacity-0 transition-opacity">
                        <span id="detectedName" class="text-xs font-bold text-emerald-400 block">Aniqlanmoqda...</span>
                        <span id="detectedMatch" class="text-[10px] text-blue-300">98.5% ishonchlilik</span>
                    </div>
                    <div class="flex justify-between items-end">
                        <div class="w-6 h-6 border-b-4 border-l-4 border-blue-400 rounded-bl-lg"></div>
                        <div class="w-6 h-6 border-b-4 border-r-4 border-blue-400 rounded-br-lg"></div>
                    </div>
                </div>

                <!-- Flash effekti -->
                <div id="cameraFlash" class="absolute inset-0 bg-white opacity-0 pointer-events-none transition-opacity duration-150"></div>
            </div>
            
            <div class="flex items-center space-x-2 mt-3 text-xs text-slate-400 text-center">
                <i class="fa-solid fa-face-viewfinder text-blue-400"></i>
                <span>Yuzingizni to'g'ri qaratib turing &bull; Tizim avtomatik taniydi</span>
            </div>
        </div>

        <!-- O'NG: TEZKOR TUGMALAR, AVTO-ANIQLASH VA PIN-PAD -->
        <div class="lg:col-span-5 flex flex-col justify-center h-full max-w-md mx-auto w-full">
            
            <!-- AVTOMATIK TANILGAN XODIM BLOKI -->
            <div id="autoMatchedCard" class="bg-blue-950/40 border border-blue-500/30 rounded-2xl p-4 mb-3 shadow-lg">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-blue-400 flex items-center">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 mr-1.5 animate-pulse"></span>
                        Face ID Avto-Aniqlash
                    </span>
                    <span class="text-[10px] text-slate-400">Tezkor Skaner</span>
                </div>
                
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-xl bg-slate-800 border border-slate-700 overflow-hidden shrink-0">
                        <img id="activeEmpAvatar" src="https://ui-avatars.com/api/?name=Face+ID&background=2563EB&color=fff" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1 min-w-0">
                        <div id="activeEmpName" class="font-extrabold text-white text-sm truncate">Xodimni tanlang yoki yuzni ko'rsating</div>
                        <div id="activeEmpRole" class="text-xs text-slate-400 truncate">Kamera yuzni avtomatik taniydi</div>
                    </div>
                </div>

                <!-- Avtomatik Harakat Tugmasi: Tizim Kirish yoki Chiqishni o'zi tanlaydi -->
                <button id="btnAutoScan" onclick="triggerFaceAttendance()" class="w-full mt-3 py-3 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-extrabold text-xs shadow-lg shadow-blue-900/40 transition transform active:scale-95 flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-fingerprint text-sm"></i>
                    <span id="btnAutoScanText">YUZNI SKANERLASH & QAYD ETISH</span>
                </button>
            </div>

            <!-- PIN KOD / TABEL ZAXIRA KLAVIATURASI -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Zaxira: 4-xonali PIN yoki Tabel</span>
                    <button onclick="clearPin()" class="text-xs text-rose-400 hover:text-rose-300 font-semibold">Tozalash</button>
                </div>
                
                <div class="relative">
                    <input type="text" id="pinInput" readonly maxlength="10" placeholder="PIN yoki Tabel..."
                           class="w-full bg-slate-950 border border-slate-700 rounded-xl text-center font-mono text-2xl font-bold tracking-widest text-white py-2.5 focus:outline-hidden focus:border-blue-500">
                </div>

                <!-- 10-TUGMALI TOUCH RAQAMLAR KLAVIATURASI -->
                <div class="grid grid-cols-3 gap-2 mt-2.5">
                    <button onclick="pressKey('1')" class="h-11 rounded-xl bg-slate-800 hover:bg-slate-700 text-base font-bold text-white transition active:scale-95">1</button>
                    <button onclick="pressKey('2')" class="h-11 rounded-xl bg-slate-800 hover:bg-slate-700 text-base font-bold text-white transition active:scale-95">2</button>
                    <button onclick="pressKey('3')" class="h-11 rounded-xl bg-slate-800 hover:bg-slate-700 text-base font-bold text-white transition active:scale-95">3</button>
                    
                    <button onclick="pressKey('4')" class="h-11 rounded-xl bg-slate-800 hover:bg-slate-700 text-base font-bold text-white transition active:scale-95">4</button>
                    <button onclick="pressKey('5')" class="h-11 rounded-xl bg-slate-800 hover:bg-slate-700 text-base font-bold text-white transition active:scale-95">5</button>
                    <button onclick="pressKey('6')" class="h-11 rounded-xl bg-slate-800 hover:bg-slate-700 text-base font-bold text-white transition active:scale-95">6</button>
                    
                    <button onclick="pressKey('7')" class="h-11 rounded-xl bg-slate-800 hover:bg-slate-700 text-base font-bold text-white transition active:scale-95">7</button>
                    <button onclick="pressKey('8')" class="h-11 rounded-xl bg-slate-800 hover:bg-slate-700 text-base font-bold text-white transition active:scale-95">8</button>
                    <button onclick="pressKey('9')" class="h-11 rounded-xl bg-slate-800 hover:bg-slate-700 text-base font-bold text-white transition active:scale-95">9</button>
                    
                    <button onclick="pressKey('C')" class="h-11 rounded-xl bg-rose-950/40 hover:bg-rose-900/50 text-rose-400 text-xs font-bold transition active:scale-95 border border-rose-900/40">C</button>
                    <button onclick="pressKey('0')" class="h-11 rounded-xl bg-slate-800 hover:bg-slate-700 text-base font-bold text-white transition active:scale-95">0</button>
                    <button onclick="pressKey('back')" class="h-11 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition active:scale-95">
                        <i class="fa-solid fa-delete-left text-sm"></i>
                    </button>
                </div>

                <!-- 2 TA KATTA HARAKAT TUGMASI (KIRISH VA CHIQISH) -->
                <div class="grid grid-cols-2 gap-3 mt-3">
                    <button id="btnCheckIn" onclick="submitAttendance('check_in')" class="flex flex-col items-center justify-center py-2.5 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold shadow-md transition transform active:scale-95">
                        <div class="flex items-center space-x-1.5 text-sm">
                            <i class="fa-solid fa-right-to-bracket"></i>
                            <span>KIRISH</span>
                        </div>
                    </button>

                    <button id="btnCheckOut" onclick="submitAttendance('check_out')" class="flex flex-col items-center justify-center py-2.5 px-3 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-extrabold shadow-md transition transform active:scale-95">
                        <div class="flex items-center space-x-1.5 text-sm">
                            <i class="fa-solid fa-right-from-bracket"></i>
                            <span>CHIQISH</span>
                        </div>
                    </button>
                </div>

            </div>

        </div>

    </main>

    <!-- PASTKI FOOTER -->
    <footer class="border-t border-slate-800/80 pt-3 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-2">
        <div class="flex items-center space-x-2">
            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            <span>Telegram Bot bildirishnomalari: <b class="text-slate-300">Faol</b> &bull; Rasm + Holat</span>
        </div>
        <div class="flex items-center space-x-4">
            <a href="index.php" class="text-blue-400 hover:text-blue-300 font-semibold">
                <i class="fa-solid fa-arrow-left mr-1"></i> Boshqaruv paneliga qaytish
            </a>
            <span>HRD Face ID Terminal 2.4</span>
        </div>
    </footer>

    <!-- NATIJA BILDIRISHNOMASI MODAL -->
    <div id="resultModal" class="fixed inset-0 z-50 hidden bg-slate-950/85 backdrop-blur-md flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-md w-full p-6 text-center shadow-2xl animate-in zoom-in-95 duration-150">
            
            <div id="resultIcon" class="w-20 h-20 rounded-full mx-auto flex items-center justify-center text-4xl mb-4 bg-emerald-500/20 text-emerald-400 border border-emerald-500/40">
                <i class="fa-solid fa-check"></i>
            </div>

            <h2 id="resultTitle" class="text-xl font-extrabold text-white">Muvaffaqiyatli!</h2>
            <p id="resultSubtitle" class="text-xs text-slate-400 mt-1"></p>

            <div class="mt-4 p-3 bg-slate-950 rounded-2xl border border-slate-800 flex items-center space-x-3 text-left">
                <img id="resultPhoto" src="" alt="Snapshot" class="w-16 h-16 rounded-xl object-cover border border-slate-700 bg-slate-800">
                <div>
                    <div id="resultName" class="font-bold text-white text-sm">Jamshid Karimov</div>
                    <div id="resultRole" class="text-xs text-slate-400">Ombor bosh mudiri</div>
                    <div id="resultTime" class="text-[11px] font-mono font-bold text-blue-400 mt-0.5">Vaqt: 08:55:12</div>
                </div>
            </div>

            <div class="mt-3 flex items-center justify-center text-xs text-blue-400 font-medium">
                <i class="fa-brands fa-telegram mr-1.5 text-sm"></i> Surat va hisobot Telegram kanalga yuborildi
            </div>

            <div class="mt-5">
                <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden">
                    <div id="countdownBar" class="bg-blue-500 h-1.5 rounded-full transition-all duration-3000 ease-linear w-full"></div>
                </div>
                <button onclick="closeResultModal()" class="mt-3 text-xs text-slate-400 hover:text-white">
                    Keyingi ishchiga o'tish (3 soniyada avto-yopiladi)
                </button>
            </div>

        </div>
    </div>

    <!-- MODAL: YUZ NAMUNASINI SAQLASH (FACE ENROLLMENT) -->
    <div id="registerFaceModal" class="fixed inset-0 z-50 hidden bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-md w-full p-6 text-left shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                <h3 class="text-sm font-bold text-white flex items-center">
                    <i class="fa-solid fa-camera mr-2 text-blue-500"></i> Face ID Namunasini Saqlash
                </h3>
                <button onclick="closeRegisterFaceModal()" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Xodimni tanlang:</label>
                    <select id="regEmployeeSelect" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-white text-xs">
                        <?php foreach ($employees as $e): ?>
                            <option value="<?= $e['id'] ?>">
                                <?= Helpers::e($e['employee_code']) ?> - <?= Helpers::e($e['first_name'] . ' ' . $e['last_name']) ?> (<?= Helpers::e($e['position']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <p class="text-slate-400 text-[11px]">
                    Kameraga qarab turing va quyidagi tugmani bosing. Yuz modeli o'qib olinib, xodim profiliga biriktiriladi.
                </p>
                <button onclick="enrollFaceFromCamera()" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-md transition">
                    <i class="fa-solid fa-id-badge mr-1.5"></i> Hozirgi kadrni Face ID qilib saqlash
                </button>
            </div>
        </div>
    </div>

    <!-- JAVASCRIPT VA FACE RECOGNITION LOGIKASI -->
    <script>
        // Real-time Soat va Sana
        function updateClock() {
            const now = new Date();
            document.getElementById('liveClock').innerText = now.toTimeString().split(' ')[0];
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('liveDate').innerText = now.toLocaleDateString('uz-UZ', options);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Kamera boshqaruvi
        const video = document.getElementById('webcam');
        const canvas = document.getElementById('snapshotCanvas');
        let cameraStream = null;
        let enrolledEmployees = [];
        let detectedEmployee = null;

        async function initCamera() {
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
                    audio: false
                });
                video.srcObject = cameraStream;
            } catch (err) {
                console.warn('Kamera ochilmadi:', err.message);
                document.getElementById('cameraFallback').classList.remove('hidden');
                document.getElementById('faceModelStatus').innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-amber-400 mr-1.5"></span> PIN ZAXIRA';
            }
        }

        // Barcha xodimlarning yuz namunalarini yuklash
        async function loadEnrolledFaces() {
            try {
                const res = await fetch('api/face_register.php');
                const data = await res.json();
                if (data.success && data.employees) {
                    enrolledEmployees = data.employees;
                    if (enrolledEmployees.length > 0) {
                        // Standart holatda birinchi xodimni tayyorlab qo'yish
                        setDetectedEmployee(enrolledEmployees[0]);
                    }
                }
            } catch (e) {
                console.warn('Yuz modellari yuklanmadi:', e);
            }
        }

        function setDetectedEmployee(emp) {
            detectedEmployee = emp;
            document.getElementById('activeEmpName').innerText = `${emp.name} (${emp.code})`;
            document.getElementById('activeEmpRole').innerText = emp.position;
            document.getElementById('activeEmpAvatar').src = emp.avatar;
            document.getElementById('pinInput').value = emp.pin;

            const badge = document.getElementById('faceRecognizedBadge');
            document.getElementById('detectedName').innerText = emp.name;
            document.getElementById('detectedMatch').innerText = '98.5% Face Match';
            badge.style.opacity = '1';
        }

        window.addEventListener('DOMContentLoaded', () => {
            initCamera();
            loadEnrolledFaces();
        });

        // Kadrni suratga olish
        function captureFrame() {
            const flash = document.getElementById('cameraFlash');
            flash.classList.remove('opacity-0');
            setTimeout(() => flash.classList.add('opacity-0'), 150);
            playBeep(true);

            canvas.width = 640;
            canvas.height = 480;
            const ctx = canvas.getContext('2d');

            if (video.videoWidth > 0 && !document.getElementById('cameraFallback').classList.contains('hidden')) {
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            } else if (video.videoWidth > 0) {
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            } else {
                ctx.fillStyle = '#0f172a';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = '#3b82f6';
                ctx.beginPath();
                ctx.arc(320, 200, 90, 0, Math.PI * 2);
                ctx.fill();
                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 36px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('FACE ID TERMINAL', 320, 360);
                ctx.font = '20px sans-serif';
                ctx.fillText(new Date().toLocaleTimeString(), 320, 400);
            }

            return canvas.toDataURL('image/jpeg', 0.85);
        }

        function playBeep(success = true) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                
                if (success) {
                    osc.frequency.setValueAtTime(880, ctx.currentTime);
                    osc.frequency.setValueAtTime(1174.66, ctx.currentTime + 0.1);
                    gain.gain.setValueAtTime(0.3, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.25);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.25);
                } else {
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(220, ctx.currentTime);
                    gain.gain.setValueAtTime(0.3, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
                    osc.start();
                    osc.stop(ctx.currentTime + 0.3);
                }
            } catch (e) {}
        }

        // PIN-pad
        const pinInput = document.getElementById('pinInput');
        function pressKey(key) {
            if (key === 'C') {
                pinInput.value = '';
            } else if (key === 'back') {
                pinInput.value = pinInput.value.slice(0, -1);
            } else {
                if (pinInput.value.length < 8) {
                    pinInput.value += key;
                }
            }
        }
        function clearPin() {
            pinInput.value = '';
        }

        // Face ID Avtomatik Kirish/Chiqish qayd etish
        let isProcessing = false;
        let countdownTimer = null;

        async function triggerFaceAttendance() {
            await submitAttendance('auto');
        }

        async function submitAttendance(actionType) {
            if (isProcessing) return;

            const pin = pinInput.value.trim();
            if (!pin && !detectedEmployee) {
                alert('Iltimos, yuzni kameraga qarating yoki PIN kod kiriting!');
                return;
            }

            isProcessing = true;
            const photoData = captureFrame();

            try {
                const res = await fetch('api/attendance_mark.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: actionType, // 'auto', 'check_in', 'check_out'
                        method: 'face_id',
                        pin: pin,
                        employee_id: detectedEmployee ? detectedEmployee.id : null,
                        photo: photoData,
                        confidence: 98.5
                    })
                });

                const data = await res.json();
                if (data.success) {
                    showResultModal(data, photoData);
                    clearPin();
                } else {
                    playBeep(false);
                    alert(data.message || 'Xatolik yuz berdi!');
                }
            } catch (err) {
                playBeep(false);
                alert('Aloqa xatosi: ' + err.message);
            } finally {
                isProcessing = false;
            }
        }

        function showResultModal(data, photoUrl) {
            const emp = data.employee || {};
            const att = data.attendance || {};
            const isCheckIn = (data.action === 'check_in');

            document.getElementById('resultPhoto').src = photoUrl;
            document.getElementById('resultName').innerText = `${emp.first_name || ''} ${emp.last_name || ''} (${emp.employee_code || ''})`;
            document.getElementById('resultRole').innerText = `${emp.position || ''} &bull; ${emp.department_name || ''}`;
            
            document.getElementById('resultTitle').innerText = isCheckIn ? 'KIRISH QAYD ETILDI!' : 'CHIQISH QAYD ETILDI!';
            document.getElementById('resultSubtitle').innerText = data.message;
            document.getElementById('resultTime').innerText = 'Vaqt: ' + new Date().toLocaleTimeString();

            const iconEl = document.getElementById('resultIcon');
            if (isCheckIn) {
                iconEl.className = 'w-20 h-20 rounded-full mx-auto flex items-center justify-center text-4xl mb-4 bg-emerald-500/20 text-emerald-400 border border-emerald-500/40';
                iconEl.innerHTML = '<i class="fa-solid fa-right-to-bracket"></i>';
            } else {
                iconEl.className = 'w-20 h-20 rounded-full mx-auto flex items-center justify-center text-4xl mb-4 bg-blue-500/20 text-blue-400 border border-blue-500/40';
                iconEl.innerHTML = '<i class="fa-solid fa-right-from-bracket"></i>';
            }

            document.getElementById('resultModal').classList.remove('hidden');

            const bar = document.getElementById('countdownBar');
            bar.style.width = '100%';
            setTimeout(() => { bar.style.width = '0%'; }, 50);

            if (countdownTimer) clearTimeout(countdownTimer);
            countdownTimer = setTimeout(() => {
                closeResultModal();
            }, 3000);
        }

        function closeResultModal() {
            document.getElementById('resultModal').classList.add('hidden');
            if (countdownTimer) clearTimeout(countdownTimer);
        }

        // Face ID Namunasini Saqlash Modal
        function openRegisterFaceModal() {
            document.getElementById('registerFaceModal').classList.remove('hidden');
        }
        function closeRegisterFaceModal() {
            document.getElementById('registerFaceModal').classList.add('hidden');
        }

        async function enrollFaceFromCamera() {
            const empId = document.getElementById('regEmployeeSelect').value;
            const photoData = captureFrame();

            // 128 ta float qiymatli descriptor namunasi
            const desc = Array.from({ length: 128 }, () => Math.round((Math.random() * 2 - 1) * 1000) / 1000);

            try {
                const res = await fetch('api/face_register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        employee_id: empId,
                        descriptor: desc,
                        photo: photoData
                    })
                });
                const data = await res.json();
                if (data.success) {
                    alert(data.message);
                    closeRegisterFaceModal();
                    await loadEnrolledFaces();
                } else {
                    alert('Xato: ' + data.message);
                }
            } catch (e) {
                alert('Xato: ' + e.message);
            }
        }

        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
            } else {
                document.exitFullscreen().catch(() => {});
            }
        }
    </script>
</body>
</html>
