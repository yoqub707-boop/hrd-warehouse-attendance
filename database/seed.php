<?php
/**
 * 100 dan ortiq xodimlar va davomat ma'lumotlarini bazaga kirituvchi Seeder
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Database.php';

class Seeder
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function run(): void
    {
        echo "=============================================\n";
        echo "HRD Omborxona Tizimi Seeder ishga tushmoqda...\n";
        echo "=============================================\n";

        $this->seedDepartments();
        $this->seedSchedules();
        $this->seedEmployees();
        $this->seedAttendances();
        $this->seedSettings();

        echo "\n[Muvaffaqiyatli] Barcha ma'lumotlar bazaga muvaffaqiyatli yuklandi!\n";
    }

    private function seedDepartments(): void
    {
        $departments = [
            ['Ombor ma\'muriyati', 'Ombor boshqaruvi, hisob-kitob va nazorat guruhi'],
            ['Qabul qilish bo\'limi', 'Kelgan tovarlarni qabul qilish, yuk xatlarini tekshirish'],
            ['Saralash va joylashtirish', 'Tovarlarni qutilarga ajratish va javonlarga terish'],
            ['Yuk ortish va tushirish', 'Og\'ir yuklarni tushirish, mashinalarga yuklash guruhi'],
            ['Saqlash va inventarizatsiya', 'Tovarlar zaxirasini hisoblash va joylashuvini nazorat qilish'],
            ['Qadoqlash va komplektatsiya', 'Buyurtmalarni yig\'ish, qadoqlash va etiketkalash'],
            ['Logistika va jo\'natish', 'Yuklarni yo\'nalishlarga taqsimlash va haydovchilarga topshirish'],
            ['Xavfsizlik va nazorat', 'Omborxonaning kirish-chiqish nazorati va texnika xavfsizligi']
        ];

        $check = $this->db->fetch("SELECT COUNT(*) as cnt FROM departments");
        if ((int)$check['cnt'] > 0) {
            echo "-> Bo'limlar allaqachon mavjud, o'tkazib yuborildi.\n";
            return;
        }

        foreach ($departments as $dept) {
            $this->db->execute(
                "INSERT INTO departments (name, description) VALUES (?, ?)",
                [$dept[0], $dept[1]]
            );
        }
        echo "-> 8 ta bo'lim yaratildi.\n";
    }

    private function seedSchedules(): void
    {
        $schedules = [
            ['Standart kunduzgi smena', '09:00:00', '18:00:00', 15, 60, '1,2,3,4,5,6'],
            ['Ertalabki ombor smenasi', '08:00:00', '17:00:00', 15, 60, '1,2,3,4,5,6'],
            ['Kechki ombor smenasi', '13:00:00', '22:00:00', 15, 60, '1,2,3,4,5,6'],
            ['Tungi smena', '20:00:00', '08:00:00', 15, 60, '1,2,3,4,5,6'],
        ];

        $check = $this->db->fetch("SELECT COUNT(*) as cnt FROM schedules");
        if ((int)$check['cnt'] > 0) {
            echo "-> Ish jadvallari allaqachon mavjud, o'tkazib yuborildi.\n";
            return;
        }

        foreach ($schedules as $sch) {
            $this->db->execute(
                "INSERT INTO schedules (name, start_time, end_time, grace_period_minutes, break_minutes, work_days) VALUES (?, ?, ?, ?, ?, ?)",
                $sch
            );
        }
        echo "-> 4 ta ish jadvali yaratildi.\n";
    }

    private function seedEmployees(): void
    {
        $check = $this->db->fetch("SELECT COUNT(*) as cnt FROM employees");
        if ((int)$check['cnt'] >= 100) {
            echo "-> Xodimlar allaqachon mavjud ({$check['cnt']} ta), o'tkazib yuborildi.\n";
            return;
        }

        $firstNames = [
            'Jamshid', 'Sardor', 'Shavkat', 'Rustam', 'Ulug\'bek', 'Farhod', 'Bobur', 'Alisher',
            'Elyor', 'Jasur', 'Sherzod', 'Dilshod', 'Otabek', 'Nodir', 'Jahongir', 'Sanjar',
            'Kamoliddin', 'Behruz', 'Asadbek', 'Abbos', 'Aziz', 'Shuhrat', 'Doniyor', 'Muzaffar',
            'Furqat', 'Temur', 'Ravshan', 'Mirzohid', 'Shahboz', 'Akmal', 'Bekzod', 'Xurshid',
            'Davron', 'Ilhom', 'Sobir', 'Anvar', 'Odil', 'Qahramon', 'Bahodir', 'Elyorbek',
            'Tohir', 'Botir', 'Mansur', 'Akbar', 'Rustambek', 'Maqsud', 'Sirojiddin', 'G\'ayrat',
            'Sunnat', 'Husan', 'Hasan', 'Umar', 'Usmon', 'Abror', 'Hamidulla', 'Erkin'
        ];

        $lastNames = [
            'Karimov', 'Rahimov', 'Toshmatov', 'Eshmatov', 'Yusupov', 'Alimov', 'Nazarov', 'Qodirov',
            'Mahmudov', 'Jo\'rayev', 'Mirzayev', 'Xoliqov', 'Bozorov', 'Normatov', 'Saidov', 'G\'aniyev',
            'Ismoilov', 'Tursunov', 'Abdullayev', 'Ergashev', 'Sobirov', 'Rustamov', 'Oripov', 'Shokirov',
            'Zokirov', 'Mamatov', 'Fayziyev', 'Rajabov', 'Sultonov', 'Hakimov', 'Vohidov', 'Qosimov',
            'Axmedov', 'Hasanov', 'Xudoyberdiyev', 'Jalolov', 'Sharipov', 'Yuldoshev', 'Nematov'
        ];

        $positions = [
            1 => ['Ombor bosh mudiri', 'Katta hisobchi-operator', 'Ombor dispetcheri'],
            2 => ['Qabul bo\'limi boshlig\'i', 'Qabul qiluvchi inspektor', 'Shtrix-kod skanerlovchi', 'Hujjat nazoratchisi'],
            3 => ['Katta saralovchi', 'Kategoriya bo\'yicha saralovchi', 'Javon joylashtiruvchisi', 'Markirovka ustasi'],
            4 => ['Katta yuk ortuvchi', 'Yuk ortuvchi (Gruzchik)', 'Forklift (Yuk ko\'targich) haydovchisi', 'Elektrokar haydovchisi', 'Shtabeler operatori'],
            5 => ['Zaxira hisobchisi', 'Inventarizator-inspektor', 'Muddati o\'tish nazoratchisi', 'Javon zonasi ma\'muri'],
            6 => ['Qadoqlash brigadiri', 'Buyurtma komplektori', 'Karton qadoqlovchi', 'Termoplyonka operatori'],
            7 => ['Logistika menejeri', 'Marshrut muvofiqlashtiruvchisi', 'Yuk topshiruvchi nazoratchi', 'Ekspeditor-kuryer'],
            8 => ['Xavfsizlik xizmati boshlig\'i', 'Kirish-chiqish nazoratchisi', 'Videokuzatuv operatori', 'Yong\'in xavfsizligi mas\'uli']
        ];

        $totalToCreate = 115;
        $created = 0;

        for ($i = 1; $i <= $totalToCreate; $i++) {
            $code = 'W-' . (1000 + $i);
            $pin = str_pad((string)(1000 + $i), 4, '0', STR_PAD_LEFT);
            $firstName = $firstNames[($i - 1) % count($firstNames)];
            $lastName = $lastNames[(($i * 3) + 7) % count($lastNames)];
            $phone = '+998 90 ' . rand(100, 999) . ' ' . rand(10, 99) . ' ' . rand(10, 99);
            
            // Bo'lim tanlash
            $deptId = (($i - 1) % 8) + 1;
            $deptPositions = $positions[$deptId];
            $position = $deptPositions[($i % count($deptPositions))];

            // Ish jadvali: ko'pchilik 1 yoki 2-smena
            $scheduleId = ($i % 10 === 0) ? 4 : (($i % 4 === 0) ? 3 : (($i % 2 === 0) ? 2 : 1));

            // Soatlik ish haqi
            $hourlyRate = rand(22, 45) * 1000;

            // Avatar generatsiyasi (harflar bilan SVG / UI Avatar)
            $initials = mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1);
            $bgColors = ['2563EB', '059669', 'D97706', 'DC2626', '7C3AED', 'DB2777', '0891B2', '4F46E5'];
            $color = $bgColors[$i % count($bgColors)];
            $avatar = "https://ui-avatars.com/api/?name=" . urlencode($firstName . '+' . $lastName) . "&background={$color}&color=fff&size=128&bold=true";

            $this->db->execute(
                "INSERT INTO employees (employee_code, first_name, last_name, phone, pin_code, department_id, schedule_id, position, hourly_rate, avatar, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')",
                [$code, $firstName, $lastName, $phone, $pin, $deptId, $scheduleId, $position, $hourlyRate, $avatar]
            );
            $created++;
        }

        echo "-> {$created} ta omborxona ishchisi bazaga muvaffaqiyatli kiritildi!\n";
    }

    private function seedAttendances(): void
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $check = $this->db->fetch("SELECT COUNT(*) as cnt FROM attendances WHERE date = ?", [$today]);
        if ((int)$check['cnt'] > 0) {
            echo "-> Bugungi davomat allaqachon mavjud, o'tkazib yuborildi.\n";
            return;
        }

        $employees = $this->db->fetchAll("SELECT id, schedule_id FROM employees ORDER BY id ASC");
        $total = count($employees);

        // Bugungi davomat taqsimoti:
        // 1 - 58: Ishda (o'z vaqtida kelgan, chiqmagan)
        // 59 - 84: Kech (kech kelgan, hali ishda)
        // 85 - 102: Ish tugagan (ertalab kelib, 8 soat ishlab chiqib ketgan)
        // 103 - 115: Ishda emas (umuman kelmagan)

        $todayCounts = ['present' => 0, 'late' => 0, 'completed' => 0, 'absent' => 0];

        foreach ($employees as $index => $emp) {
            $num = $index + 1;
            $empId = (int)$emp['id'];

            if ($num <= 58) {
                // Ishda (O'z vaqtida kelgan)
                $minute = rand(30, 58);
                $checkIn = "{$today} 08:{$minute}:" . rand(10, 59);
                $this->db->execute(
                    "INSERT INTO attendances (employee_id, date, check_in, check_out, status, late_minutes, early_leave_minutes, worked_minutes, method, note)
                     VALUES (?, ?, ?, NULL, 'on_time', 0, 0, 0, 'terminal', 'Ombor terminali orqali kirgan')",
                    [$empId, $today, $checkIn]
                );
                $todayCounts['present']++;
            } elseif ($num <= 84) {
                // Kech kelgan (Ishda)
                $lateMin = rand(16, 55);
                $hour = 9;
                $min = $lateMin;
                if ($min >= 60) {
                    $hour = 10;
                    $min -= 60;
                }
                $checkIn = sprintf('%s %02d:%02d:%02d', $today, $hour, $min, rand(10, 59));
                $this->db->execute(
                    "INSERT INTO attendances (employee_id, date, check_in, check_out, status, late_minutes, early_leave_minutes, worked_minutes, method, note)
                     VALUES (?, ?, ?, NULL, 'late', ?, 0, 0, 'terminal', 'Transport kechikishi sababli')",
                    [$empId, $today, $checkIn, $lateMin]
                );
                $todayCounts['late']++;
            } elseif ($num <= 102) {
                // Ish tugagan (08:00 kelib, 17:00 da ketgan)
                $checkIn = "{$today} 07:55:12";
                $checkOut = "{$today} 17:05:40";
                $worked = 480; // 8 soat
                $this->db->execute(
                    "INSERT INTO attendances (employee_id, date, check_in, check_out, status, late_minutes, early_leave_minutes, worked_minutes, method, note)
                     VALUES (?, ?, ?, ?, 'completed', 0, 0, ?, 'terminal', 'Smena yakunlandi')",
                    [$empId, $today, $checkIn, $checkOut, $worked]
                );
                $todayCounts['completed']++;
            } else {
                // Ishda emas
                $this->db->execute(
                    "INSERT INTO attendances (employee_id, date, check_in, check_out, status, late_minutes, early_leave_minutes, worked_minutes, undertime_minutes, method, note)
                     VALUES (?, ?, NULL, NULL, 'absent', 0, 0, 0, 480, 'manual', 'Sababsiz kelmagan')",
                    [$empId, $today]
                );
                $todayCounts['absent']++;
            }

            // Kechagi kun uchun ham davomat yozish (Monitoring va hisobotlar uchun)
            $yStatus = ($num % 10 === 0) ? 'absent' : (($num % 5 === 0) ? 'late' : 'completed');
            if ($yStatus === 'completed') {
                $this->db->execute(
                    "INSERT INTO attendances (employee_id, date, check_in, check_out, status, late_minutes, early_leave_minutes, worked_minutes, method)
                     VALUES (?, ?, ?, ?, 'completed', 0, 0, 480, 'terminal')",
                    [$empId, $yesterday, "{$yesterday} 08:52:10", "{$yesterday} 18:04:15"]
                );
            } elseif ($yStatus === 'late') {
                $this->db->execute(
                    "INSERT INTO attendances (employee_id, date, check_in, check_out, status, late_minutes, early_leave_minutes, worked_minutes, method)
                     VALUES (?, ?, ?, ?, 'completed', 25, 0, 455, 'terminal')",
                    [$empId, $yesterday, "{$yesterday} 09:25:00", "{$yesterday} 18:00:00"]
                );
            } else {
                $this->db->execute(
                    "INSERT INTO attendances (employee_id, date, check_in, check_out, status, late_minutes, early_leave_minutes, worked_minutes, undertime_minutes, method)
                     VALUES (?, ?, NULL, NULL, 'absent', 0, 0, 0, 480, 'manual')",
                    [$empId, $yesterday]
                );
            }
        }

        echo "-> Bugungi kun ({$today}) uchun davomat shakllantirildi:\n";
        echo "   - Ishda (O'z vaqtida): {$todayCounts['present']}\n";
        echo "   - Kech qolgan: {$todayCounts['late']}\n";
        echo "   - Ish tugagan: {$todayCounts['completed']}\n";
        echo "   - Ishda emas: {$todayCounts['absent']}\n";
        echo "   - Jami: {$total}\n";
    }

    private function seedSettings(): void
    {
        $settings = [
            ['telegram_bot_token', Helpers::env('TELEGRAM_BOT_TOKEN', ''), 'Telegram Bot Token'],
            ['telegram_chat_id', Helpers::env('TELEGRAM_CHAT_ID', ''), 'Telegram Kanal yoki Guruh ID'],
            ['telegram_notifications_enabled', '1', 'Telegram orqali rasm va xabar yuborish faol'],
            ['terminal_camera_active', '1', 'Kamera orqali rasmga olish majburiy'],
            ['warehouse_title', 'Toshkent Markaziy Logistika Omborxonasi', 'Omborxona rasmiy nomi'],
            ['late_tolerance_minutes', '15', 'Kechikish uchun berilgan ruxsat vaqti (daqiqa)']
        ];

        foreach ($settings as $s) {
            $existing = $this->db->fetch("SELECT setting_key FROM system_settings WHERE setting_key = ?", [$s[0]]);
            if (!$existing) {
                $this->db->execute(
                    "INSERT INTO system_settings (setting_key, setting_value, description) VALUES (?, ?, ?)",
                    [$s[0], $s[1], $s[2]]
                );
            }
        }
        echo "-> Tizim sozlamalari yangilandi.\n";
    }
}

// CLI orqali to'g'ridan-to'g'ri ishga tushirish imkoni
if (php_sapi_name() === 'cli' && isset($argv[0]) && basename($argv[0]) === 'seed.php') {
    $seeder = new Seeder();
    $seeder->run();
}
