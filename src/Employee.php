<?php
/**
 * Xodimlar modeli va biznes mantig'i
 */

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class Employee
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Barcha xodimlarni filtrlash va sahifalash bilan olish
     */
    public function getAll(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $params = [];
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

        $whereClause = implode(' AND ', $where);
        $sql = "SELECT e.*, d.name AS department_name, s.name AS schedule_name, s.start_time, s.end_time
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN schedules s ON e.schedule_id = s.id
                WHERE {$whereClause}
                ORDER BY e.id ASC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Umumiy xodimlar soni
     */
    public function count(array $filters = []): int
    {
        $params = [];
        $where = ["status = 'active'"];

        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where[] = "(first_name LIKE ? OR last_name LIKE ? OR employee_code LIKE ? OR position LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['department_id'])) {
            $where[] = "department_id = ?";
            $params[] = (int)$filters['department_id'];
        }

        $whereClause = implode(' AND ', $where);
        $res = $this->db->fetch("SELECT COUNT(*) as total FROM employees WHERE {$whereClause}", $params);
        return (int)($res['total'] ?? 0);
    }

    /**
     * ID bo'yicha olish
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT e.*, d.name AS department_name, s.name AS schedule_name, s.start_time, s.end_time, s.grace_period_minutes
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN schedules s ON e.schedule_id = s.id
                WHERE e.id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * PIN kod yoki Tabel raqami orqali xodimni topish (Terminal uchun)
     */
    public function findByCodeOrPin(string $identifier): ?array
    {
        $clean = trim($identifier);
        $sql = "SELECT e.*, d.name AS department_name, s.name AS schedule_name, s.start_time, s.end_time, s.grace_period_minutes
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN schedules s ON e.schedule_id = s.id
                WHERE (e.pin_code = ? OR e.employee_code = ? OR e.first_name LIKE ? OR e.last_name LIKE ?) AND e.status = 'active'
                LIMIT 1";
        return $this->db->fetch($sql, [$clean, $clean, $clean, $clean]);
    }

    /**
     * PIN kod bo'yicha topish
     */
    public function findByPin(string $pin): ?array
    {
        $clean = trim($pin);
        $sql = "SELECT e.*, d.name AS department_name, s.name AS schedule_name, s.start_time, s.end_time, s.grace_period_minutes
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN schedules s ON e.schedule_id = s.id
                WHERE e.pin_code = ? AND e.status = 'active'
                LIMIT 1";
        return $this->db->fetch($sql, [$clean]);
    }

    /**
     * Yangi xodim yaratish
     */
    public function create(array $data): int
    {
        $code = trim($data['employee_code'] ?? '');
        if (empty($code)) {
            $last = $this->db->fetch("SELECT MAX(id) as max_id FROM employees");
            $nextId = ((int)($last['max_id'] ?? 1000)) + 1;
            $code = 'W-' . $nextId;
        }

        $pin = trim($data['pin_code'] ?? '');
        if (empty($pin)) {
            $pin = (string)rand(1000, 9999);
        }

        $hourlyRate = (float)($data['hourly_rate'] ?? 25000);
        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $deptId = !empty($data['department_id']) ? (int)$data['department_id'] : 1;
        $scheduleId = !empty($data['schedule_id']) ? (int)$data['schedule_id'] : 1;
        $position = trim($data['position'] ?? 'Ombor xodimi');

        $avatar = $data['avatar'] ?? "https://ui-avatars.com/api/?name=" . urlencode($firstName . '+' . $lastName) . "&background=2563EB&color=fff&size=128";

        $sql = "INSERT INTO employees (employee_code, first_name, last_name, phone, pin_code, department_id, schedule_id, position, hourly_rate, avatar, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";

        $this->db->execute($sql, [
            $code, $firstName, $lastName, $phone, $pin, $deptId, $scheduleId, $position, $hourlyRate, $avatar
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Xodim ma'lumotlarini yangilash
     */
    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        $allowed = ['first_name', 'last_name', 'phone', 'pin_code', 'department_id', 'schedule_id', 'position', 'hourly_rate', 'status'];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE employees SET " . implode(', ', $fields) . " WHERE id = ?";
        return $this->db->execute($sql, $params);
    }

    /**
     * Xodimni o'chirish (yoki nofaol qilish)
     */
    public function delete(int $id): bool
    {
        return $this->db->execute("UPDATE employees SET status = 'inactive' WHERE id = ?", [$id]);
    }

    /**
     * Barcha bo'limlarni olish
     */
    public function getDepartments(): array
    {
        return $this->db->fetchAll("SELECT * FROM departments ORDER BY id ASC");
    }
}
