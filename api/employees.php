<?php
/**
 * Xodimlar API - Qidiruv, qo'shish va ma'lumot olish
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Employee.php';

header('Content-Type: application/json; charset=utf-8');

$employeeModel = new Employee();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'search':
            $q = trim($_GET['q'] ?? '');
            $limit = min(50, (int)($_GET['limit'] ?? 20));
            $employees = $employeeModel->getAll(['search' => $q], $limit, 0);
            Helpers::jsonResponse(['success' => true, 'data' => $employees]);
            break;

        case 'get':
            $id = (int)($_GET['id'] ?? 0);
            $emp = $employeeModel->findById($id);
            if (!$emp) {
                Helpers::jsonResponse(['success' => false, 'message' => 'Xodim topilmadi'], 404);
            }
            Helpers::jsonResponse(['success' => true, 'data' => $emp]);
            break;

        case 'create':
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            if (!Helpers::verifyCsrfToken($input['csrf_token'] ?? '')) {
                Helpers::jsonResponse(['success' => false, 'message' => 'CSRF xavfsizlik tekshiruvi xatosi'], 403);
            }

            if (empty($input['first_name']) || empty($input['last_name'])) {
                Helpers::jsonResponse(['success' => false, 'message' => 'Ism va familiya majburiy'], 400);
            }

            $newId = $employeeModel->create($input);
            Helpers::jsonResponse(['success' => true, 'id' => $newId, 'message' => 'Yangi xodim muvaffaqiyatli qo\'shildi!']);
            break;

        default:
            Helpers::jsonResponse(['success' => false, 'message' => 'Noma\'lum amal'], 400);
    }
} catch (Throwable $e) {
    Helpers::jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
}
