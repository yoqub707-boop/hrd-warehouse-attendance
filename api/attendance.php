<?php
/**
 * Davomat API - Terminal va Boshqaruv paneli uchun
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Attendance.php';
require_once __DIR__ . '/../src/Employee.php';

header('Content-Type: application/json; charset=utf-8');

$attendance = new Attendance();
$employeeModel = new Employee();

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'check_in':
            // Terminal orqali kirish
            $identifier = trim($input['identifier'] ?? $input['pin'] ?? $input['code'] ?? '');
            $photoBase64 = $input['photo'] ?? null;
            $note = $input['note'] ?? 'Terminal orqali kirish';

            $employee = null;
            if (!empty($input['employee_id'])) {
                $employee = $employeeModel->findById((int)$input['employee_id']);
            } elseif (!empty($identifier)) {
                $employee = $employeeModel->findByCodeOrPin($identifier);
            }

            if (!$employee) {
                Helpers::jsonResponse(['success' => false, 'message' => 'Xodim topilmadi! PIN yoki tabel raqamini tekshiring.'], 404);
            }

            $res = $attendance->recordCheckIn((int)$employee['id'], $photoBase64, 'terminal', $note);
            Helpers::jsonResponse($res, $res['success'] ? 200 : 400);
            break;

        case 'check_out':
            // Terminal orqali chiqish
            $identifier = trim($input['identifier'] ?? $input['pin'] ?? $input['code'] ?? '');
            $photoBase64 = $input['photo'] ?? null;
            $note = $input['note'] ?? 'Terminal orqali chiqish';

            $employee = null;
            if (!empty($input['employee_id'])) {
                $employee = $employeeModel->findById((int)$input['employee_id']);
            } elseif (!empty($identifier)) {
                $employee = $employeeModel->findByCodeOrPin($identifier);
            }

            if (!$employee) {
                Helpers::jsonResponse(['success' => false, 'message' => 'Xodim topilmadi! PIN yoki tabel raqamini tekshiring.'], 404);
            }

            $res = $attendance->recordCheckOut((int)$employee['id'], $photoBase64, 'terminal', $note);
            Helpers::jsonResponse($res, $res['success'] ? 200 : 400);
            break;

        case 'manual_create':
            // Qo'lda davomat yaratish modal
            if (!Helpers::verifyCsrfToken($input['csrf_token'] ?? '')) {
                Helpers::jsonResponse(['success' => false, 'message' => 'CSRF xavfsizlik tekshiruvi xatosi'], 403);
            }

            $res = $attendance->createManual($input);
            Helpers::jsonResponse($res);
            break;

        case 'delete':
            // Davomatni o'chirish
            if (!Helpers::verifyCsrfToken($input['csrf_token'] ?? '')) {
                Helpers::jsonResponse(['success' => false, 'message' => 'CSRF xavfsizlik tekshiruvi xatosi'], 403);
            }

            $attId = (int)($input['attendance_id'] ?? 0);
            $deleted = $attendance->deleteAttendance($attId);
            Helpers::jsonResponse(['success' => $deleted, 'message' => $deleted ? 'O\'chirildi' : 'O\'chirishda xato']);
            break;

        default:
            Helpers::jsonResponse(['success' => false, 'message' => 'Noto\'g\'ri amal: ' . $action], 400);
    }
} catch (Throwable $e) {
    Helpers::jsonResponse([
        'success' => false,
        'message' => 'Server xatosi: ' . $e->getMessage()
    ], 500);
}
