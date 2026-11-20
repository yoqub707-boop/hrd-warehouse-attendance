<?php
/**
 * api/face_register.php - Face ID Yuz Namunasini Ro'yxatdan O'tkazish API
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Employee.php';

header('Content-Type: application/json; charset=utf-8');

$db = Database::getInstance();
$employeeModel = new Employee();

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Terminal uchun barcha ro'yxatdan o'tgan yuz namunalarini qaytarish
        $sql = "SELECT id, employee_code, first_name, last_name, position, department_id, pin_code, avatar, face_descriptor 
                FROM employees 
                WHERE status = 'active'";
        $rows = $db->fetchAll($sql);

        $enrolled = [];
        foreach ($rows as $r) {
            $desc = !empty($r['face_descriptor']) ? json_decode($r['face_descriptor'], true) : null;
            $enrolled[] = [
                'id' => (int)$r['id'],
                'code' => $r['employee_code'],
                'name' => $r['first_name'] . ' ' . $r['last_name'],
                'pin' => $r['pin_code'],
                'position' => $r['position'],
                'avatar' => $r['avatar'],
                'has_face' => !empty($desc),
                'descriptor' => $desc
            ];
        }

        Helpers::jsonResponse([
            'success' => true,
            'total' => count($enrolled),
            'employees' => $enrolled
        ]);
    }

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $employeeId = (int)($input['employee_id'] ?? 0);
        $descriptor = $input['descriptor'] ?? null; // array or json string
        $photoBase64 = $input['photo'] ?? null;

        if ($employeeId <= 0) {
            Helpers::jsonResponse(['success' => false, 'message' => 'Xodim ID tanlanmagan'], 400);
        }

        $emp = $employeeModel->findById($employeeId);
        if (!$emp) {
            Helpers::jsonResponse(['success' => false, 'message' => 'Xodim topilmadi'], 404);
        }

        $descJson = is_array($descriptor) ? json_encode($descriptor) : $descriptor;

        $photoPath = null;
        if (!empty($photoBase64)) {
            $photoPath = Helpers::saveBase64Image($photoBase64, 'faces');
        }

        if ($photoPath) {
            $sql = "UPDATE employees SET face_descriptor = ?, face_photo = ?, avatar = ? WHERE id = ?";
            $db->execute($sql, [$descJson, $photoPath, $photoPath, $employeeId]);
        } else {
            $sql = "UPDATE employees SET face_descriptor = ? WHERE id = ?";
            $db->execute($sql, [$descJson, $employeeId]);
        }

        Helpers::jsonResponse([
            'success' => true,
            'message' => "{$emp['first_name']} {$emp['last_name']} uchun Face ID namunasi muvaffaqiyatli saqlandi!",
            'employee_id' => $employeeId,
            'photo' => $photoPath
        ]);
    }

} catch (Throwable $e) {
    Helpers::jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
