<?php
/**
 * api/attendance_list.php - Davomat Ro'yxati va Statistika API
 */

declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers.php';
require_once __DIR__ . '/../src/Attendance.php';

header('Content-Type: application/json; charset=utf-8');

$attendanceModel = new Attendance();

$date = $_GET['date'] ?? date('Y-m-d');
$status = $_GET['status'] ?? 'all';
$deptId = !empty($_GET['department_id']) ? (int)$_GET['department_id'] : null;
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, max(1, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$filters = [
    'status' => $status,
    'department_id' => $deptId,
    'search' => $search
];

try {
    $stats = $attendanceModel->getDashboardStats($date);
    $rows = $attendanceModel->getDailyAttendance($date, $filters, $limit, $offset);
    $totalCount = $attendanceModel->getDailyAttendanceCount($date, $filters);
    $totalPages = max(1, (int)ceil($totalCount / $limit));

    Helpers::jsonResponse([
        'success' => true,
        'date' => $date,
        'stats' => $stats,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_rows' => $totalCount,
            'total_pages' => $totalPages
        ],
        'data' => $rows
    ]);
} catch (Throwable $e) {
    Helpers::jsonResponse([
        'success' => false,
        'message' => 'Xatolik: ' . $e->getMessage()
    ], 500);
}
