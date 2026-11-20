<?php
/**
 * Ish Jadvallari va Smenalar modeli
 */

declare(strict_types=1);

require_once __DIR__ . '/Database.php';

class Schedule
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM schedules ORDER BY id ASC");
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetch("SELECT * FROM schedules WHERE id = ?", [$id]);
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO schedules (name, start_time, end_time, grace_period_minutes, break_minutes, work_days)
                VALUES (?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            $data['name'],
            $data['start_time'],
            $data['end_time'],
            (int)($data['grace_period_minutes'] ?? 15),
            (int)($data['break_minutes'] ?? 60),
            $data['work_days'] ?? '1,2,3,4,5,6'
        ]);
        return (int)$this->db->lastInsertId();
    }
}
