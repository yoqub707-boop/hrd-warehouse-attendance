<?php
/**
 * Hisobotlar va Monitoring Xizmati (Monitoring & Reports)
 */

declare(strict_types=1);

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';

class ReportService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Oylik yoki haftalik umumiy monitoring hisoboti
     */
    public function getSummary(string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                    COUNT(DISTINCT date) as total_days,
                    COUNT(check_in) as total_check_ins,
                    SUM(CASE WHEN late_minutes > 0 THEN 1 ELSE 0 END) as total_lates,
                    SUM(worked_minutes) as total_worked_minutes,
                    SUM(undertime_minutes) as total_undertime_minutes,
                    COUNT(CASE WHEN status = 'absent' THEN 1 END) as total_absences
                FROM attendances
                WHERE date BETWEEN ? AND ?";

        $row = $this->db->fetch($sql, [$startDate, $endDate]);

        $totalWorked = (int)($row['total_worked_minutes'] ?? 0);
        $totalCheckIns = (int)($row['total_check_ins'] ?? 0);
        $totalLates = (int)($row['total_lates'] ?? 0);

        $punctualityRate = ($totalCheckIns > 0)
            ? round((($totalCheckIns - $totalLates) / $totalCheckIns) * 100, 1)
            : 100;

        return [
            'total_days' => (int)($row['total_days'] ?? 0),
            'total_check_ins' => $totalCheckIns,
            'total_lates' => $totalLates,
            'total_worked_formatted' => Helpers::formatMinutes($totalWorked),
            'total_worked_hours' => round($totalWorked / 60, 1),
            'total_undertime_hours' => round(((int)($row['total_undertime_minutes'] ?? 0)) / 60, 1),
            'total_absences' => (int)($row['total_absences'] ?? 0),
            'punctuality_rate' => $punctualityRate
        ];
    }

    /**
     * Bo'limlar kesimida davomat intizomi
     */
    public function getDepartmentStats(string $startDate, string $endDate): array
    {
        $sql = "SELECT 
                    d.id,
                    d.name as department_name,
                    COUNT(e.id) as employee_count,
                    COUNT(a.check_in) as check_in_count,
                    SUM(CASE WHEN a.late_minutes > 0 THEN 1 ELSE 0 END) as late_count,
                    SUM(a.worked_minutes) as worked_minutes
                FROM departments d
                LEFT JOIN employees e ON d.id = e.department_id AND e.status = 'active'
                LEFT JOIN attendances a ON e.id = a.employee_id AND a.date BETWEEN ? AND ?
                GROUP BY d.id, d.name
                ORDER BY d.id ASC";

        $rows = $this->db->fetchAll($sql, [$startDate, $endDate]);

        foreach ($rows as &$r) {
            $in = (int)$r['check_in_count'];
            $late = (int)$r['late_count'];
            $r['punctuality'] = ($in > 0) ? round((($in - $late) / $in) * 100, 1) : 100;
            $r['worked_hours'] = round(((int)($r['worked_minutes'] ?? 0)) / 60, 1);
        }

        return $rows;
    }

    /**
     * CSV eksport yaratish
     */
    public function exportCsv(string $date): void
    {
        $sql = "SELECT 
                    e.employee_code,
                    e.first_name,
                    e.last_name,
                    d.name as department,
                    e.position,
                    a.check_in,
                    a.check_out,
                    a.status,
                    a.late_minutes,
                    a.worked_minutes,
                    a.method,
                    a.note
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN attendances a ON e.id = a.employee_id AND a.date = ?
                WHERE e.status = 'active'
                ORDER BY e.id ASC";

        $rows = $this->db->fetchAll($sql, [$date]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=davomat_' . $date . '.csv');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM qo'shish (Excel uchun)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        fputcsv($output, [
            'Tabel raqami', 'Ism', 'Familiya', 'Bo\'lim', 'Lavozim',
            'Kirish vaqti', 'Chiqish vaqti', 'Holati', 'Kechikish (daq)',
            'Ishlangan vaqt (daq)', 'Usul', 'Izoh'
        ]);

        foreach ($rows as $r) {
            fputcsv($output, [
                $r['employee_code'],
                $r['first_name'],
                $r['last_name'],
                $r['department'] ?? '',
                $r['position'],
                $r['check_in'] ?? '-',
                $r['check_out'] ?? '-',
                $r['status'] ?? 'absent',
                $r['late_minutes'] ?? 0,
                $r['worked_minutes'] ?? 0,
                $r['method'] ?? '-',
                $r['note'] ?? ''
            ]);
        }

        fclose($output);
        exit;
    }
}
