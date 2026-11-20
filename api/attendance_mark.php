<?php
/**
 * api/attendance_mark.php - Face ID va PIN orqali Davomat Qayd Etish
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Employee.php';
require_once __DIR__ . '/../src/TimeCalculator.php';
require_once __DIR__ . '/../src/TelegramService.php';

header('Content-Type: application/json; charset=utf-8');

$employeeModel = new Employee();
$telegramService = new TelegramService();
$db = Database::getInstance();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

try {
    $identifier = trim($input['identifier'] ?? $input['pin'] ?? $input['code'] ?? '');
    $employeeId = !empty($input['employee_id']) ? (int)$input['employee_id'] : 0;
    $action = $input['action'] ?? 'auto'; // 'auto', 'check_in', 'check_out'
    $method = $input['method'] ?? 'face_id'; // 'face_id', 'pin', 'manual'
    $photoBase64 = $input['photo'] ?? null;
    $confidence = (float)($input['confidence'] ?? 98.5);

    // 1. Xodimni aniqlash
    $employee = null;
    if ($employeeId > 0) {
        $employee = $employeeModel->findById($employeeId);
    } elseif (!empty($identifier)) {
        $employee = $employeeModel->findByCodeOrPin($identifier);
    }

    if (!$employee) {
        Helpers::jsonResponse([
            'success' => false,
            'message' => 'Xodim aniqlanmadi! PIN yoki tabel raqamini tekshiring.'
        ], 404);
    }

    $empId = (int)$employee['id'];
    $today = date('Y-m-d');
    $now = date('Y-m-d H:i:s');

    // 2. Rasmni xavfsiz saqlash
    $photoPath = null;
    if (!empty($photoBase64)) {
        $photoPath = Helpers::saveBase64Image($photoBase64);
    }

    // 3. Bugungi mavjud davomat yozuvini tekshirish
    $existing = $db->fetch("SELECT * FROM attendances WHERE employee_id = ? AND date = ?", [$empId, $today]);

    // 4. 'auto' rejimida Kirish yoki Chiqishni o'zi aniqlash
    if ($action === 'auto') {
        if (!$existing || empty($existing['check_in'])) {
            $action = 'check_in';
        } elseif (empty($existing['check_out'])) {
            $action = 'check_out';
        } else {
            // Allaqachon chiqish qilgan: qo'shimcha sessiya yoki qayta kirish
            $action = 'check_out'; // yangilash
        }
    }

    // 5. KIRISH QAYD ETISH
    if ($action === 'check_in') {
        if ($existing && !empty($existing['check_in'])) {
            Helpers::jsonResponse([
                'success' => false,
                'message' => 'Bu xodim bugun allaqachon soat ' . Helpers::formatTime($existing['check_in']) . ' da kirgan!',
                'attendance' => $existing
            ], 400);
        }

        $schedStart = $employee['start_time'] ?? '09:00:00';
        $grace = (int)($employee['grace_period_minutes'] ?? 15);
        $lateMinutes = TimeCalculator::calculateLateMinutes($now, $schedStart, $grace);
        $status = ($lateMinutes > 0) ? 'late' : 'on_time';

        if ($existing) {
            $sql = "UPDATE attendances 
                    SET check_in = ?, check_in_photo = ?, status = ?, late_minutes = ?, method = ?, updated_at = ?
                    WHERE id = ?";
            $db->execute($sql, [$now, $photoPath, $status, $lateMinutes, $method, $now, $existing['id']]);
            $attendanceId = (int)$existing['id'];
        } else {
            $sql = "INSERT INTO attendances (employee_id, date, check_in, check_in_photo, status, late_minutes, method)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $db->execute($sql, [$empId, $today, $now, $photoPath, $status, $lateMinutes, $method]);
            $attendanceId = (int)$db->lastInsertId();
        }

        // Attendance log yozish
        $db->execute(
            "INSERT INTO attendance_logs (attendance_id, employee_id, event_type, event_time, photo_path, device, confidence_score)
             VALUES (?, ?, 'check_in', ?, ?, 'Warehouse Face ID Gate #1', ?)",
            [$attendanceId, $empId, $now, $photoPath, $confidence]
        );

        $attendanceData = [
            'id' => $attendanceId,
            'check_in' => $now,
            'check_out' => null,
            'status' => $status,
            'late_minutes' => $lateMinutes,
            'worked_minutes' => 0,
            'undertime_minutes' => 0,
            'check_in_photo' => $photoPath
        ];

        // Telegram Bot orqali bildirishnoma yuborish
        $tgResult = $telegramService->sendAttendanceNotification($employee, $attendanceData, 'check_in');

        Helpers::jsonResponse([
            'success' => true,
            'action' => 'check_in',
            'message' => ($status === 'late') ? "Kechikish bilan kirish qayd etildi (+{$lateMinutes} daqiqa)" : "Kirish muvaffaqiyatli qayd etildi!",
            'employee' => $employee,
            'attendance' => $attendanceData,
            'telegram' => $tgResult
        ]);
    }

    // 6. CHIQISH QAYD ETISH
    if ($action === 'check_out') {
        if (!$existing || empty($existing['check_in'])) {
            Helpers::jsonResponse([
                'success' => false,
                'message' => 'Xodim bugun hali kirish qayd etmagan!'
            ], 400);
        }

        $checkIn = $existing['check_in'];
        $workedMinutes = TimeCalculator::calculateSessionWorkedMinutes($checkIn, $now);
        $undertimeMinutes = TimeCalculator::calculateUndertimeMinutes($workedMinutes, 480);

        $sql = "UPDATE attendances 
                SET check_out = ?, check_out_photo = ?, status = 'completed', worked_minutes = ?, undertime_minutes = ?, updated_at = ?
                WHERE id = ?";
        $db->execute($sql, [$now, $photoPath, $workedMinutes, $undertimeMinutes, $now, $existing['id']]);

        // Attendance log yozish
        $db->execute(
            "INSERT INTO attendance_logs (attendance_id, employee_id, event_type, event_time, photo_path, device, confidence_score)
             VALUES (?, ?, 'check_out', ?, ?, 'Warehouse Face ID Gate #1', ?)",
            [$existing['id'], $empId, $now, $photoPath, $confidence]
        );

        $attendanceData = [
            'id' => $existing['id'],
            'check_in' => $checkIn,
            'check_out' => $now,
            'status' => 'completed',
            'late_minutes' => (int)$existing['late_minutes'],
            'worked_minutes' => $workedMinutes,
            'undertime_minutes' => $undertimeMinutes,
            'check_out_photo' => $photoPath
        ];

        // Telegram Bot orqali bildirishnoma yuborish
        $tgResult = $telegramService->sendAttendanceNotification($employee, $attendanceData, 'check_out');

        Helpers::jsonResponse([
            'success' => true,
            'action' => 'check_out',
            'message' => "Chiqish qayd etildi! Jami ishlangan: " . Helpers::formatMinutes($workedMinutes) . ", Ishlanmagan: " . Helpers::formatMinutes($undertimeMinutes),
            'employee' => $employee,
            'attendance' => $attendanceData,
            'telegram' => $tgResult
        ]);
    }

    Helpers::jsonResponse(['success' => false, 'message' => 'Noto\'g\'ri parametrlar'], 400);

} catch (Throwable $e) {
    Helpers::jsonResponse([
        'success' => false,
        'message' => 'Server xatosi: ' . $e->getMessage()
    ], 500);
}
