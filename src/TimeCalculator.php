<?php
/**
 * TimeCalculator - Ish Vaqti, Kechikish va Ishlanmagan Vaqt Hisoblagichi
 * 100% deterministik matematika va aniq vaqt logikasi.
 */

declare(strict_types=1);

class TimeCalculator
{
    public const DEFAULT_NORMATIVE_MINUTES = 480; // 8 soatlik standart norma
    public const DEFAULT_GRACE_MINUTES = 15;     // 15 daqiqalik ruxsat etilgan kechikish chegarasi

    /**
     * Kechikish daqiqalarini hisoblash
     *
     * Qoida:
     * Agar xodim smena boshlanish vaqti + grace perioddan keyin kelsa,
     * smena boshlanish vaqtidan hisoblangan to'liq kechikish daqiqalari qaytariladi.
     * Aks holda 0 daqiqa qaytariladi.
     */
    public static function calculateLateMinutes(string $checkInTime, string $scheduleStartTime, int $gracePeriodMinutes = self::DEFAULT_GRACE_MINUTES): int
    {
        $inSeconds = self::timeToSeconds($checkInTime);
        $schedSeconds = self::timeToSeconds($scheduleStartTime);
        $graceSeconds = $gracePeriodMinutes * 60;

        // Agar kirish vaqti smena boshlanishi + chegaradan katta bo'lsa
        if ($inSeconds > ($schedSeconds + $graceSeconds)) {
            $diffSeconds = $inSeconds - $schedSeconds;
            return (int)ceil($diffSeconds / 60);
        }

        return 0;
    }

    /**
     * Bitta sessiyada ishlangan daqiqalarni hisoblash
     */
    public static function calculateSessionWorkedMinutes(string $checkInTime, string $checkOutTime): int
    {
        $inTs = strtotime($checkInTime);
        $outTs = strtotime($checkOutTime);

        if (!$inTs || !$outTs || $outTs <= $inTs) {
            return 0;
        }

        return (int)round(($outTs - $inTs) / 60);
    }

    /**
     * Bir kunda bir nechta kirish-chiqish sessiyalari bo'yicha jami ishlangan daqiqalar
     * @param array $sessions Har bir element: ['check_in' => '...', 'check_out' => '...']
     */
    public static function calculateTotalWorkedMinutes(array $sessions): int
    {
        $totalMinutes = 0;
        foreach ($sessions as $session) {
            if (!empty($session['check_in']) && !empty($session['check_out'])) {
                $totalMinutes += self::calculateSessionWorkedMinutes($session['check_in'], $session['check_out']);
            }
        }
        return $totalMinutes;
    }

    /**
     * Ishlanmagan vaqtni hisoblash (Undertime)
     * Kunlik normadan (masalan 480 min = 8 soat) kam ishlangan qism.
     */
    public static function calculateUndertimeMinutes(int $workedMinutes, int $normativeMinutes = self::DEFAULT_NORMATIVE_MINUTES): int
    {
        return max(0, $normativeMinutes - $workedMinutes);
    }

    /**
     * Qo'shimcha ishlangan vaqtni hisoblash (Overtime)
     */
    public static function calculateOvertimeMinutes(int $workedMinutes, int $normativeMinutes = self::DEFAULT_NORMATIVE_MINUTES): int
    {
        return max(0, $workedMinutes - $normativeMinutes);
    }

    /**
     * Davomat holatini aniqlash:
     * - 'absent': kirish qilinmagan
     * - 'completed': chiqish qilingan
     * - 'late': kechikib kelgan va hali ishda
     * - 'on_time': o'z vaqtida kelgan va hali ishda
     */
    public static function determineStatus(?string $checkInTime, ?string $checkOutTime, int $lateMinutes): string
    {
        if (empty($checkInTime)) {
            return 'absent';
        }

        if (!empty($checkOutTime)) {
            return 'completed';
        }

        return ($lateMinutes > 0) ? 'late' : 'on_time';
    }

    /**
     * Daqiqalarni soat va daqiqaga o'girish
     */
    public static function formatMinutes(int $minutes, bool $short = false): string
    {
        if ($minutes <= 0) {
            return $short ? '00:00' : '0 daq';
        }

        $hours = floor($minutes / 60);
        $remaining = $minutes % 60;

        if ($short) {
            return sprintf('%02d:%02d', $hours, $remaining);
        }

        if ($hours > 0 && $remaining > 0) {
            return "{$hours} soat {$remaining} daq";
        } elseif ($hours > 0) {
            return "{$hours} soat";
        } else {
            return "{$remaining} daq";
        }
    }

    /**
     * Vaqt satrini sutka boshidan boshlab sekundlarga o'girish
     */
    private static function timeToSeconds(string $timeString): int
    {
        // Agar to'liq datetime bo'lsa (2026-11-20 09:25:00) faqat vaqt qismini olamiz
        if (str_contains($timeString, ' ')) {
            $parts = explode(' ', $timeString);
            $timeString = end($parts);
        }

        $parts = array_map('intval', explode(':', $timeString));
        $hours = $parts[0] ?? 0;
        $minutes = $parts[1] ?? 0;
        $seconds = $parts[2] ?? 0;

        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }
}
