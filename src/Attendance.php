<?php
/**
 * Davomat va Ish Vaqti Hisobi Klasi
 * 100% deterministik hisob-kitoblar va SQL mantiq
 */

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';
require_once __DIR__ . '/Employee.php';
require_once __DIR__ . '/TelegramService.php';

class Attendance
{
    private Database $db;
    private Employee $employeeModel;
    private TelegramService $telegram;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->employeeModel = new Employee();
        $this->telegram = new TelegramService();
    }

    /**
     * Boshqaruv paneli uchun 4 ta asosiy statistika kartochkasi:
     * 1. Barchasi (All active employees)
     * 2. Ishda (Present - check_in mavjud, check_out yo'q)
     * 3. Kech (Late - kechikib kelganlar)
     * 4. Ishda emas (Absent - kelmaganlar)
     */
    public function getDashboardStats(string $date): array
    {
        $totalEmployees = $this->employeeModel->count();

        // Bugungi davomat yozuvlari
        $sql = "SELECT 
                    COUNT(CASE WHEN check_in IS NOT NULL AND check_out IS NULL THEN 1 END) as present_count,
                    COUNT(CASE WHEN late_minutes > 0 OR status = 'late' THEN 1 END) as late_count,
                    COUNT(CASE WHEN check_out IS NOT NULL THEN 1 END) as completed_count,
                    COUNT(CASE WHEN check_in IS NOT NULL THEN 1 END) as attended_count
                FROM attendances 
                WHERE date = ?";

        $row = $this->db->fetch($sql, [$date]);
        $present = (int)($row['present_count'] ?? 0);
        $late = (int)($row['late_count'] ?? 0);
        $completed = (int)($row['completed_count'] ?? 0);
        $attended = (int)($row['attended_count'] ?? 0);

        // Ishda emas = Umumiy xodimlar - (Ishda + Ish tugagan)
        $absent = max(0, $totalEmployees - $attended);

        return [
            'all' => $totalEmployees,
            'present' => $present,
            'late' => $late,
            'completed' => $completed,
            'absent' => $absent
        ];
    }

    /**
     * Kunlik davomat ro'yxati (HRD jadval tuzilmasi, qidiruv va sahifalash bilan)
     */
    public function getDailyAttendance(string $date, array $filters = [], int $limit = 15, int $offset = 0): array
    {
        $params = [$date];
        $where = ["e.status = 'active'"];

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR e.position LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['department_id'])) {
            $where[] = "e.department_id = ?";
            $params[] = (int)$filters['department_id'];
        }

        // Status filtri
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            switch ($filters['status']) {
                case 'present':
                    $where[] = "a.check_in IS NOT NULL AND a.check_out IS NULL";
                    break;
                case 'late':
                    $where[] = "(a.late_minutes > 0 OR a.status = 'late')";
                    break;
                case 'completed':
                    $where[] = "a.check_out IS NOT NULL";
                    break;
                case 'absent':
                    $where[] = "(a.check_in IS NULL OR a.status = 'absent')";
                    break;
            }
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT 
                    e.id AS employee_id,
                    e.employee_code,
                    e.first_name,
                    e.last_name,
                    e.phone,
                    e.position,
                    e.avatar,
                    d.name AS department_name,
                    s.name AS schedule_name,
                    s.start_time AS schedule_start,
                    s.end_time AS schedule_end,
                    s.grace_period_minutes,
                    a.id AS attendance_id,
                    a.date,
                    a.check_in,
                    a.check_out,
                    a.check_in_photo,
                    a.check_out_photo,
                    a.status AS raw_status,
                    a.late_minutes,
                    a.worked_minutes,
                    a.undertime_minutes,
                    a.method,
                    a.note
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN schedules s ON e.schedule_id = s.id
                LEFT JOIN attendances a ON e.id = a.employee_id AND a.date = ?
                WHERE {$whereClause}
                ORDER BY 
                    CASE 
                        WHEN a.check_in IS NOT NULL AND a.check_out IS NULL THEN 1
                        WHEN a.late_minutes > 0 THEN 2
                        WHEN a.check_out IS NOT NULL THEN 3
                        ELSE 4 
                    END,
                    e.id ASC
                LIMIT {$limit} OFFSET {$offset}";

        $rows = $this->db->fetchAll($sql, $params);

        // Hisob-kitoblarni yakuniy formatlash
        $result = [];
        foreach ($rows as $row) {
            $formatted = $this->computeRowStatus($row, $date);
            $result[] = $formatted;
        }

        return $result;
    }

    /**
     * Berilgan filtrlar bo'yicha jami qatorlar soni (sahifalash uchun)
     */
    public function getDailyAttendanceCount(string $date, array $filters = []): int
    {
        $params = [$date];
        $where = ["e.status = 'active'"];

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR e.position LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['department_id'])) {
            $where[] = "e.department_id = ?";
            $params[] = (int)$filters['department_id'];
        }

        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            switch ($filters['status']) {
                case 'present':
                    $where[] = "a.check_in IS NOT NULL AND a.check_out IS NULL";
                    break;
                case 'late':
                    $where[] = "(a.late_minutes > 0 OR a.status = 'late')";
                    break;
                case 'completed':
                    $where[] = "a.check_out IS NOT NULL";
                    break;
                case 'absent':
                    $where[] = "(a.check_in IS NULL OR a.status = 'absent')";
                    break;
            }
        }

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) as total 
                FROM employees e
                LEFT JOIN attendances a ON e.id = a.employee_id AND a.date = ?
                WHERE {$whereClause}";

        $res = $this->db->fetch($sql, $params);
        return (int)($res['total'] ?? 0);
    }

    /**
     * Jadval qatori holati va ish vaqtlarini hisoblash
     */
    private function computeRowStatus(array $row, string $date): array
    {
        $checkIn = $row['check_in'];
        $checkOut = $row['check_out'];
        $lateMin = (int)($row['late_minutes'] ?? 0);
        $workedMin = (int)($row['worked_minutes'] ?? 0);
        $undertimeMin = (int)($row['undertime_minutes'] ?? 0);

        // Holatni aniqlash: 'Ishda', 'Kech', 'Ish tugagan', 'Ishda emas'
        if (empty($checkIn)) {
            $displayStatus = 'Ishda emas';
            $statusBadgeClass = 'bg-rose-50 text-rose-700 ring-rose-600/20';
            $statusCode = 'absent';
        } elseif (!empty($checkOut)) {
            $displayStatus = 'Ish tugagan';
            $statusBadgeClass = 'bg-blue-50 text-blue-700 ring-blue-600/20';
            $statusCode = 'completed';
        } elseif ($lateMin > 0 || $row['raw_status'] === 'late') {
            $displayStatus = 'Kech';
            $statusBadgeClass = 'bg-amber-50 text-amber-700 ring-amber-600/20';
            $statusCode = 'late';
        } else {
            $displayStatus = 'Ishda';
            $statusBadgeClass = 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
            $statusCode = 'present';
        }

        // Hozirgi paytda ishlab turgan bo'lsa, jami vaqtni dinamik hisoblash
        if (!empty($checkIn) && empty($checkOut)) {
            $nowTime = ($date === date('Y-m-d')) ? time() : strtotime("{$date} 18:00:00");
            $inTime = strtotime($checkIn);
            if ($nowTime > $inTime) {
                $workedMin = (int)floor(($nowTime - $inTime) / 60);
            }
        }

        // Standart smena vaqti (8 soat = 480 daqiqa)
        $standardShiftMinutes = 480;
        if ($statusCode === 'absent') {
            $undertimeMin = $standardShiftMinutes;
        } elseif ($statusCode === 'completed') {
            $undertimeMin = max(0, $standardShiftMinutes - $workedMin);
        }

        $row['display_status'] = $displayStatus;
        $row['status_code'] = $statusCode;
        $row['badge_class'] = $statusBadgeClass;
        $row['worked_time_formatted'] = Helpers::formatMinutes($workedMin);
        $row['undertime_formatted'] = Helpers::formatMinutes($undertimeMin);
        $row['check_in_time'] = Helpers::formatTime($checkIn);
        $row['check_out_time'] = Helpers::formatTime($checkOut);
        $row['full_name'] = $row['first_name'] . ' ' . $row['last_name'];

        return $row;
    }

    /**
     * Kirishni qayd etish (Kamera rasm + Telegram xabar)
     */
    public function recordCheckIn(int $employeeId, ?string $photoBase64 = null, string $method = 'terminal', ?string $note = null): array
    {
        $employee = $this->employeeModel->findById($employeeId);
        if (!$employee) {
            return ['success' => false, 'message' => 'Xodim topilmadi'];
        }

        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');
        $nowTimeStr = date('H:i:s');

        // Oldin kirganligini tekshirish
        $existing = $this->db->fetch("SELECT * FROM attendances WHERE employee_id = ? AND date = ?", [$employeeId, $today]);
        if ($existing && !empty($existing['check_in'])) {
            return [
                'success' => false,
                'message' => 'Bu xodim bugun soat ' . Helpers::formatTime($existing['check_in']) . ' da kirgan!',
                'attendance' => $existing
            ];
        }

        // Rasmni saqlash
        $photoPath = null;
        if (!empty($photoBase64)) {
            $photoPath = Helpers::saveBase64Image($photoBase64);
        }

        // Kechikishni hisoblash
        $scheduleStart = $employee['start_time'] ?? '09:00:00';
        $grace = (int)($employee['grace_period_minutes'] ?? 15);

        $schedStartTimestamp = strtotime("{$today} {$scheduleStart}");
        $allowedTimestamp = $schedStartTimestamp + ($grace * 60);
        $currentTimestamp = strtotime($now);

        $lateMinutes = 0;
        $status = 'on_time';

        if ($currentTimestamp > $allowedTimestamp) {
            $lateMinutes = (int)ceil(($currentTimestamp - $schedStartTimestamp) / 60);
            $status = 'late';
        }

        if ($existing) {
            $sql = "UPDATE attendances 
                    SET check_in = ?, check_in_photo = ?, status = ?, late_minutes = ?, method = ?, note = ?, updated_at = ?
                    WHERE id = ?";
            $this->db->execute($sql, [$now, $photoPath, $status, $lateMinutes, $method, $note, $now, $existing['id']]);
            $attendanceId = (int)$existing['id'];
        } else {
            $sql = "INSERT INTO attendances (employee_id, date, check_in, check_in_photo, status, late_minutes, method, note)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->execute($sql, [$employeeId, $today, $now, $photoPath, $status, $lateMinutes, $method, $note]);
            $attendanceId = (int)$this->db->lastInsertId();
        }

        // Log yozish
        $this->db->execute(
            "INSERT INTO attendance_logs (attendance_id, employee_id, event_type, event_time, photo_path, device)
             VALUES (?, ?, 'check_in', ?, ?, 'Warehouse Terminal #1')",
            [$attendanceId, $employeeId, $now, $photoPath]
        );

        $attendanceData = [
            'id' => $attendanceId,
            'check_in' => $now,
            'status' => $status,
            'late_minutes' => $lateMinutes,
            'check_in_photo' => $photoPath
        ];

        // Telegramga yuborish
        $tgResult = $this->telegram->sendAttendanceNotification($employee, $attendanceData, 'check_in');

        return [
            'success' => true,
            'message' => ($status === 'late') ? "Kechikish qayd etildi (+{$lateMinutes} daqiqa)" : "Kirish muvaffaqiyatli qayd etildi!",
            'employee' => $employee,
            'attendance' => $attendanceData,
            'telegram' => $tgResult
        ];
    }

    /**
     * Chiqishni qayd etish (Kamera rasm + Telegram xabar)
     */
    public function recordCheckOut(int $employeeId, ?string $photoBase64 = null, string $method = 'terminal', ?string $note = null): array
    {
        $employee = $this->employeeModel->findById($employeeId);
        if (!$employee) {
            return ['success' => false, 'message' => 'Xodim topilmadi'];
        }

        $today = date('Y-m-d');
        $now = date('Y-m-d H:i:s');

        $existing = $this->db->fetch("SELECT * FROM attendances WHERE employee_id = ? AND date = ?", [$employeeId, $today]);
        if (!$existing || empty($existing['check_in'])) {
            return [
                'success' => false,
                'message' => 'Xodim hali bugun kirish qayd etmagan!'
            ];
        }

        if (!empty($existing['check_out'])) {
            return [
                'success' => false,
                'message' => 'Bu xodim allaqachon soat ' . Helpers::formatTime($existing['check_out']) . ' da chiqqan!'
            ];
        }

        // Rasmni saqlash
        $photoPath = null;
        if (!empty($photoBase64)) {
            $photoPath = Helpers::saveBase64Image($photoBase64);
        }

        // Ishlangan daqiqalarni hisoblash
        $inTime = strtotime($existing['check_in']);
        $outTime = strtotime($now);
        $workedMinutes = max(0, (int)round(($outTime - $inTime) / 60));

        // Standart smena vaqti (8 soat = 480 daq)
        $standardShift = 480;
        $undertime = max(0, $standardShift - $workedMinutes);

        $sql = "UPDATE attendances 
                SET check_out = ?, check_out_photo = ?, status = 'completed', worked_minutes = ?, undertime_minutes = ?, updated_at = ?
                WHERE id = ?";
        $this->db->execute($sql, [$now, $photoPath, $workedMinutes, $undertime, $now, $existing['id']]);

        // Log yozish
        $this->db->execute(
            "INSERT INTO attendance_logs (attendance_id, employee_id, event_type, event_time, photo_path, device)
             VALUES (?, ?, 'check_out', ?, ?, 'Warehouse Terminal #1')",
            [$existing['id'], $employeeId, $now, $photoPath]
        );

        $attendanceData = [
            'id' => $existing['id'],
            'check_in' => $existing['check_in'],
            'check_out' => $now,
            'status' => 'completed',
            'worked_minutes' => $workedMinutes,
            'check_out_photo' => $photoPath
        ];

        // Telegramga yuborish
        $tgResult = $this->telegram->sendAttendanceNotification($employee, $attendanceData, 'check_out');

        return [
            'success' => true,
            'message' => 'Chiqish qayd etildi! Jami ishlangan: ' . Helpers::formatMinutes($workedMinutes),
            'employee' => $employee,
            'attendance' => $attendanceData,
            'telegram' => $tgResult
        ];
    }

    /**
     * Qo'lda davomat yaratish (+ Qo'lda davomat yaratish tugmasi uchun)
     */
    public function createManual(array $data): array
    {
        $employeeId = (int)($data['employee_id'] ?? 0);
        $date = $data['date'] ?? date('Y-m-d');
        $checkInTime = !empty($data['check_in_time']) ? $data['check_in_time'] : null;
        $checkOutTime = !empty($data['check_out_time']) ? $data['check_out_time'] : null;
        $note = $data['note'] ?? 'Qo\'lda kiritildi';

        $employee = $this->employeeModel->findById($employeeId);
        if (!$employee) {
            return ['success' => false, 'message' => 'Xodim topilmadi'];
        }

        $checkIn = $checkInTime ? "{$date} {$checkInTime}:00" : null;
        $checkOut = $checkOutTime ? "{$date} {$checkOutTime}:00" : null;

        $workedMinutes = 0;
        $lateMinutes = 0;
        $status = 'absent';

        if ($checkIn) {
            $schedStart = $employee['start_time'] ?? '09:00:00';
            $grace = (int)($employee['grace_period_minutes'] ?? 15);
            $startTs = strtotime("{$date} {$schedStart}");
            $allowedTs = $startTs + ($grace * 60);
            $inTs = strtotime($checkIn);

            if ($inTs > $allowedTs) {
                $lateMinutes = (int)ceil(($inTs - $startTs) / 60);
                $status = 'late';
            } else {
                $status = 'on_time';
            }

            if ($checkOut) {
                $outTs = strtotime($checkOut);
                $workedMinutes = max(0, (int)round(($outTs - $inTs) / 60));
                $status = 'completed';
            }
        }

        $standardShift = 480;
        $undertime = max(0, $standardShift - $workedMinutes);

        // Bor bo'lsa yangilash, bo'lmasa yaratish
        $existing = $this->db->fetch("SELECT id FROM attendances WHERE employee_id = ? AND date = ?", [$employeeId, $date]);
        if ($existing) {
            $sql = "UPDATE attendances 
                    SET check_in = ?, check_out = ?, status = ?, late_minutes = ?, worked_minutes = ?, undertime_minutes = ?, method = 'manual', note = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
            $this->db->execute($sql, [$checkIn, $checkOut, $status, $lateMinutes, $workedMinutes, $undertime, $note, $existing['id']]);
        } else {
            $sql = "INSERT INTO attendances (employee_id, date, check_in, check_out, status, late_minutes, worked_minutes, undertime_minutes, method, note)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'manual', ?)";
            $this->db->execute($sql, [$employeeId, $date, $checkIn, $checkOut, $status, $lateMinutes, $workedMinutes, $undertime, $note]);
        }

        return ['success' => true, 'message' => 'Davomat muvaffaqiyatli saqlandi!'];
    }

    /**
     * Davomatni o'chirish
     */
    public function deleteAttendance(int $attendanceId): bool
    {
        return $this->db->execute("DELETE FROM attendances WHERE id = ?", [$attendanceId]);
    }
}
