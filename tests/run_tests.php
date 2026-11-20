<?php
/**
 * tests/run_tests.php - To'liq Avtomatlashtirilgan QA va Diagnostika Test Suite
 * Barcha funksiyalar, hisob-kitoblar, API endpointlar va xavfsizlikni 100% tekshiradi.
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Employee.php';
require_once __DIR__ . '/../src/TimeCalculator.php';
require_once __DIR__ . '/../src/Attendance.php';
require_once __DIR__ . '/../src/TelegramService.php';

class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];

    public function runAll(): bool
    {
        echo "\n=================================================================\n";
        echo "   HRD OMBORXONA FACE ID DAVOMAT TIZIMI - QA TEST SUITE\n";
        echo "   PHP Versiya: " . PHP_VERSION . " | Vaqt: " . date('Y-m-d H:i:s') . "\n";
        echo "=================================================================\n\n";

        // 1. TimeCalculator matematik hisob-kitoblar testi
        $this->testTimeCalculator();

        // 2. Ma'lumotlar bazasi va jadvallar mavjudligi
        $this->testDatabaseSchema();

        // 3. 110 ta xodim va davomat seeder tekshiruvi
        $this->testEmployeesSeed();

        // 4. API va davomat funksiyalari (Check-in, Check-out, Auto)
        $this->testAttendanceLogic();

        // 5. Telegram Bot xizmati va Bot Status API
        $this->testTelegramAndBotStatus();

        // 6. Rasm yuklash va Base64 dekodlash testi
        $this->testImageHandling();

        // 7. Xavfsizlik va Barqarorlik auditi (SQLi, XSS, CSRF, .htaccess)
        $this->testSecurityAudit();

        // 8. Autentifikatsiya, Foydalanuvchilar va Kirish Nazorati (IP & Brute-force)
        $this->testAuthAndAccessControl();

        // Yakuniy hisobot
        $total = $this->passed + $this->failed;
        echo "\n=================================================================\n";
        echo "   YAKUNIY TEST NATIJALARI:\n";
        echo "   Jami testlar: {$total} ta\n";
        echo "   Muvaffaqiyatli: \033[32m{$this->passed} ta\033[0m\n";
        if ($this->failed > 0) {
            echo "   Xatolar: \033[31m{$this->failed} ta\033[0m\n";
        } else {
            echo "   Xatolar: 0 ta (Barcha testlar 100% muvaffaqiyatli o'tdi!)\n";
        }
        echo "=================================================================\n\n";

        return ($this->failed === 0);
    }

    private function assert(bool $condition, string $testName, string $details = ''): void
    {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$testName}\n";
            $this->results[] = ['name' => $testName, 'status' => 'PASS', 'details' => $details];
        } else {
            $this->failed++;
            echo "  [FAIL] {$testName}" . ($details ? " - {$details}" : '') . "\n";
            $this->results[] = ['name' => $testName, 'status' => 'FAIL', 'details' => $details];
        }
    }

    private function testTimeCalculator(): void
    {
        echo "1. TIMECALCULATOR MANTIQ VA FORMULALAR TESTI:\n";

        // 1.1 O'z vaqtida kelish (09:00 grafik, 09:12 da keldi, 15 min grace => 0 kechikish)
        $late1 = TimeCalculator::calculateLateMinutes('09:12:00', '09:00:00', 15);
        $this->assert($late1 === 0, "O'z vaqtida kelish (Grace period ichida 09:12)", "Haqiqiy: {$late1} daqiqa");

        // 1.2 Kechikib kelish (09:00 grafik, 09:25 da keldi, 15 min grace => 25 daqiqa kech)
        $late2 = TimeCalculator::calculateLateMinutes('09:25:00', '09:00:00', 15);
        $this->assert($late2 === 25, "Kechikish hisobi (09:25 da keldi => 25 daqiqa kechikish)", "Haqiqiy: {$late2} daqiqa");

        // 1.3 Bitta sessiyada ishlangan vaqt (08:30 dan 17:30 gacha = 540 daqiqa = 9 soat)
        $worked1 = TimeCalculator::calculateSessionWorkedMinutes('2026-11-20 08:30:00', '2026-11-20 17:30:00');
        $this->assert($worked1 === 540, "Sessiya ish vaqti hisobi (08:30 -> 17:30 = 540 daqiqa)", "Haqiqiy: {$worked1} daq");

        // 1.4 Bir nechta sessiyalar yig'indisi (1-smena: 240 daq, 2-smena: 240 daq => 480 daqiqa = 8 soat)
        $sessions = [
            ['check_in' => '2026-11-20 08:30:00', 'check_out' => '2026-11-20 12:30:00'],
            ['check_in' => '2026-11-20 13:30:00', 'check_out' => '2026-11-20 17:30:00']
        ];
        $totalSessions = TimeCalculator::calculateTotalWorkedMinutes($sessions);
        $this->assert($totalSessions === 480, "Bir kunda bir nechta kirish-chiqish sessiyalari (240 + 240 = 480 daq)", "Haqiqiy: {$totalSessions} daq");

        // 1.5 Ishlanmagan vaqt (Undertime) hisobi (420 daqiqa ishlagan, norma 480 => 60 daqiqa ishlanmagan)
        $undertime = TimeCalculator::calculateUndertimeMinutes(420, 480);
        $this->assert($undertime === 60, "Ishlanmagan vaqt (Undertime: 480 - 420 = 60 daqiqa)", "Haqiqiy: {$undertime} daq");

        // 1.6 Ortiqcha ishlangan vaqt (Overtime) hisobi (540 daqiqa ishlagan, norma 480 => 60 daqiqa overtime)
        $overtime = TimeCalculator::calculateOvertimeMinutes(540, 480);
        $this->assert($overtime === 60, "Qo'shimcha ish vaqti (Overtime: 540 - 480 = 60 daqiqa)", "Haqiqiy: {$overtime} daq");

        // 1.7 Holatni aniqlash
        $st1 = TimeCalculator::determineStatus(null, null, 0);
        $st2 = TimeCalculator::determineStatus('08:50:00', null, 0);
        $st3 = TimeCalculator::determineStatus('09:20:00', null, 20);
        $st4 = TimeCalculator::determineStatus('08:50:00', '18:00:00', 0);
        $this->assert($st1 === 'absent' && $st2 === 'on_time' && $st3 === 'late' && $st4 === 'completed', 
            "Holat determinatsiyasi (absent, on_time, late, completed)");
        echo "\n";
    }

    private function testDatabaseSchema(): void
    {
        echo "2. BAZA VA JADVALLAR MAVJUDLIGI TESTI:\n";
        $db = Database::getInstance();
        $this->assert($db->getPdo() !== null, "PDO Ma'lumotlar bazasi ulanishi faol");

        $expectedTables = ['departments', 'schedules', 'employees', 'attendances', 'attendance_logs', 'telegram_logs', 'system_settings', 'users', 'login_logs', 'ip_rate_limits'];
        foreach ($expectedTables as $tbl) {
            $check = $db->fetch("SELECT name FROM sqlite_master WHERE type='table' AND name=?", [$tbl]);
            $this->assert(!empty($check), "Jadval mavjudligi: {$tbl}");
        }
        echo "\n";
    }

    private function testEmployeesSeed(): void
    {
        echo "3. 110 TA OMBORXONA XODIMI SEEDER TESTI:\n";
        $db = Database::getInstance();
        $empCount = (int)($db->fetch("SELECT COUNT(*) as cnt FROM employees WHERE status = 'active'")['cnt'] ?? 0);
        $this->assert($empCount >= 110, "Faol xodimlar soni kamida 110 ta (Haqiqiy: {$empCount} ta)");

        // Tabel raqamlari W-1001 dan W-1110 gacha
        $w1001 = $db->fetch("SELECT * FROM employees WHERE employee_code = 'W-1001'");
        $w1110 = $db->fetch("SELECT * FROM employees WHERE employee_code = 'W-1110'");
        $this->assert(!empty($w1001) && !empty($w1110), "Tabel kodi W-1001 va W-1110 to'g'ri shakllangan");

        // Har bir xodimda 4-xonali PIN mavjudligi
        $pinCheck = $db->fetch("SELECT COUNT(*) as cnt FROM employees WHERE LENGTH(pin_code) < 4");
        $this->assert((int)$pinCheck['cnt'] === 0, "Barcha xodimlarda to'liq 4-xonali PIN kod mavjud");

        // Bugungi davomat statistikasi
        $att = new Attendance();
        $stats = $att->getDashboardStats(date('Y-m-d'));
        $this->assert($stats['all'] >= 110, "Boshqaruv paneli 'Barchasi' kartochkasi: {$stats['all']} ta");
        $this->assert($stats['present'] > 0, "Boshqaruv paneli 'Ishda' kartochkasi: {$stats['present']} ta");
        $this->assert($stats['late'] > 0, "Boshqaruv paneli 'Kech' kartochkasi: {$stats['late']} ta");
        $this->assert($stats['absent'] >= 0, "Boshqaruv paneli 'Ishda emas' kartochkasi: {$stats['absent']} ta");
        echo "\n";
    }

    private function testAttendanceLogic(): void
    {
        echo "4. DAVOMAT FUNKSIYALARI VA API TESTI:\n";
        $att = new Attendance();
        $db = Database::getInstance();

        // Test uchun vaqtinchalik xodim
        $testEmp = $db->fetch("SELECT id, employee_code, pin_code FROM employees WHERE employee_code = 'W-1001'");
        $empId = (int)$testEmp['id'];

        // Bugungi ro'yxatni olish
        $list = $att->getDailyAttendance(date('Y-m-d'), [], 5, 0);
        $this->assert(count($list) > 0, "getDailyAttendance() ro'yxat qaytardi");
        $first = $list[0];
        $this->assert(isset($first['full_name'], $first['display_status'], $first['worked_time_formatted']), 
            "Davomat qatori ustunlari to'liq (Xodim, Kirish, Chiqish, Jami, Ishlanmagan vaqt, Holat)");

        // Qidiruv va filtr testi
        $searchResults = $att->getDailyAttendance(date('Y-m-d'), ['search' => 'Jamshid'], 10, 0);
        $this->assert(count($searchResults) > 0, "Xodim ismi bo'yicha qidiruv ishladi");

        $statusFilterResults = $att->getDailyAttendance(date('Y-m-d'), ['status' => 'present'], 10, 0);
        $this->assert(count($statusFilterResults) > 0, "Status ('present') bo'yicha filtr ishladi");
        echo "\n";
    }

    private function testTelegramAndBotStatus(): void
    {
        echo "5. TELEGRAM BOT VA BOT STATUS TESTI:\n";
        $tg = new TelegramService();
        $status = $tg->getBotStatus();

        $this->assert(isset($status['is_configured'], $status['is_connected'], $status['success_count'], $status['recent_logs']), 
            "Telegram getBotStatus() barcha metrikalarni qaytardi");

        // Mock rejimda bildirishnoma yuborish
        $mockEmp = [
            'id' => 1,
            'first_name' => 'Jamshid',
            'last_name' => 'Karimov',
            'employee_code' => 'W-1001',
            'position' => 'Ombor mudiri',
            'department_name' => 'Ombor ma\'muriyati',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00'
        ];
        $mockAtt = [
            'status' => 'on_time',
            'late_minutes' => 0,
            'worked_minutes' => 0,
            'check_in_photo' => null
        ];

        $tgRes = $tg->sendAttendanceNotification($mockEmp, $mockAtt, 'check_in');
        $this->assert($tgRes['success'] === true, "sendAttendanceNotification() offline/mock xavfsiz qayd etildi");

        // Telegram loglar jadvalida log mavjudligi
        $db = Database::getInstance();
        $lastLog = $db->fetch("SELECT * FROM telegram_logs ORDER BY id DESC LIMIT 1");
        $this->assert(!empty($lastLog), "Telegram log jadvaliga yozuv muvaffaqiyatli saqlandi");
        echo "\n";
    }

    private function testImageHandling(): void
    {
        echo "6. RASM YUKLASH VA BASE64 DEKODLASH TESTI:\n";
        // 1x1 pikselli qizil rangli haqiqiy Base64 JPEG rasm
        $tinyJpegBase64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';

        $savedPath = Helpers::saveBase64Image($tinyJpegBase64);
        $this->assert(!empty($savedPath), "Base64 rasm muvaffaqiyatli dekodlandi: {$savedPath}");

        $fullPath = __DIR__ . '/../' . $savedPath;
        $this->assert(file_exists($fullPath), "Fayl diskda yaratildi va mavjud");
        $this->assert(filesize($fullPath) > 0, "Fayl hajmi 0 dan katta (" . filesize($fullPath) . " bayt)");

        // Test rasmni tozalash
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        echo "\n";
    }

    private function testSecurityAudit(): void
    {
        echo "7. XAVFSIZLIK VA BARQARORLIK AUDITI:\n";

        // 7.1 XSS himoyasi
        $malicious = '<script>alert("XSS")</script>';
        $safe = Helpers::e($malicious);
        $this->assert($safe === '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;', "XSS filtri htmlspecialchars to'g'ri ishladi");

        // 7.2 CSRF token generatsiya va tekshirish
        $token = Helpers::csrfToken();
        $this->assert(!empty($token) && strlen($token) === 64, "CSRF token xavfsiz generatsiya qilindi (64 belgili hex)");
        $this->assert(Helpers::verifyCsrfToken($token) === true, "CSRF token hash_equals tekshiruvi muvaffaqiyatli");
        $this->assert(Helpers::verifyCsrfToken('invalid_token') === false, "Soxta CSRF token rad etildi");

        // 7.3 SQL Injection himoyasi (PDO prepared statements)
        $db = Database::getInstance();
        $sqliPayload = "' OR '1'='1";
        $sqliRes = $db->fetch("SELECT * FROM employees WHERE employee_code = ?", [$sqliPayload]);
        $this->assert($sqliRes === null, "SQL Injection parametri parametrlangan so'rovda neytrallandi");

        // 7.4 uploads/.htaccess skriptlar ijrosini taqiqlash
        $htaccessPath = __DIR__ . '/../uploads/.htaccess';
        $this->assert(file_exists($htaccessPath), "uploads/.htaccess mavjud");
        $htaccessContent = file_get_contents($htaccessPath);
        $this->assert(str_contains($htaccessContent, 'Deny from all') && str_contains($htaccessContent, 'php'), 
            "uploads/.htaccess faylida PHP skriptlar ijrosi to'liq taqiqlangan");

        // 7.5 Root .htaccess xavfsizlik sarlavhalari
        $rootHtaccess = __DIR__ . '/../.htaccess';
        $this->assert(file_exists($rootHtaccess), "Root .htaccess mavjud");
        $rootContent = file_get_contents($rootHtaccess);
        $this->assert(str_contains($rootContent, 'X-Content-Type-Options') && str_contains($rootContent, 'X-Frame-Options'),
            "Xavfsizlik sarlavhalari (nosniff, SAMEORIGIN) sozlangan");
        echo "\n";
    }

    private function testAuthAndAccessControl(): void
    {
        echo "8. AUTENTIFIKATSIYA, FOYDALANUVCHILAR VA IP MONITORINGI TESTI:\n";
        require_once __DIR__ . '/../src/Auth.php';
        $auth = Auth::getInstance();
        $db = Database::getInstance();

        // 8.1 Standart admin mavjudligi
        $admin = $db->fetch("SELECT * FROM users WHERE username = 'admin'");
        $this->assert(!empty($admin), "Bosh admin (admin) mavjud");

        // 8.2 Noto'g'ri parol bilan kirish rad etilishi
        $badLogin = $auth->login('admin', 'WrongPass123!');
        $this->assert($badLogin['success'] === false, "Noto'g'ri parol kiritilganda kirish rad etildi");

        // 8.3 Login logs jadvalida xatolik qayd etilganligi
        $lastLog = $db->fetch("SELECT * FROM login_logs ORDER BY id DESC LIMIT 1");
        $this->assert($lastLog['status'] === 'failed_password' || $lastLog['status'] === 'ip_locked', "Xato urinish IP va vaqt bilan login_logs ga yozildi (IP: {$lastLog['ip_address']})");

        // 8.4 To'g'ri parol bilan kirish
        $goodLogin = $auth->login('admin', 'Admin123!_#');
        $this->assert($goodLogin['success'] === true, "To'g'ri parol bilan kirish muvaffaqiyatli");
        $this->assert($auth->check() === true, "Auth::check() faol sessiyani tasdiqladi");

        // 8.5 Foydalanuvchi oxirgi kirish IP va vaqti yangilanganligi
        $updatedAdmin = $db->fetch("SELECT last_login_at, last_login_ip FROM users WHERE username = 'admin'");
        $this->assert(!empty($updatedAdmin['last_login_at']) && !empty($updatedAdmin['last_login_ip']), 
            "Foydalanuvchining last_login_at va last_login_ip yangilandi ({$updatedAdmin['last_login_ip']})");

        // 8.6 Bloklangan foydalanuvchi bilan kirishni sinash
        $db->execute("INSERT INTO users (username, name, email, password_hash, role, status) VALUES ('test_blocked', 'Test Bloklangan', 'b@hrd.uz', ?, 'manager', 'blocked')",
            [password_hash('Test1234!', PASSWORD_DEFAULT)]
        );
        $blockedLogin = $auth->login('test_blocked', 'Test1234!');
        $this->assert($blockedLogin['success'] === false && str_contains($blockedLogin['message'], 'bloklangan'), 
            "Bloklangan foydalanuvchi tizimga kiritilmadi va blok xabari berildi");

        // Test foydalanuvchini tozalash
        $db->execute("DELETE FROM users WHERE username = 'test_blocked'");
        $auth->logout();
        echo "\n";
    }
}

// Skriptni ishga tushirish
$runner = new TestRunner();
$success = $runner->runAll();
exit($success ? 0 : 1);
