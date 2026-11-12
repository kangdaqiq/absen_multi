<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;

class PrayerTimeService
{
    /**
     * Hitung jadwal sholat lengkap untuk tanggal dan koordinat tertentu.
     * Menggunakan metode hisab standar Kementerian Agama Republik Indonesia (Kemenag RI):
     * - Subuh: Sudut depresi 20 derajat
     * - Isya: Sudut depresi 18 derajat
     * - Ashar: Madzhab Syafi'i (bayangan objek = 1x tinggi benda + bayangan zawal)
     * - Dhuha: Ketinggian matahari 4.5 derajat (atau terbit + offset)
     *
     * @param float $latitude Lintang lokasi (derajat desimal, misal -6.2088)
     * @param float $longitude Bujur lokasi (derajat desimal, misal 106.8456)
     * @param int|float $timezoneOffset Offset jam dari UTC (WIB = 7, WITA = 8, WIT = 9)
     * @param Carbon|null $date Tanggal hisab (default hari ini)
     * @param array $customOffsets Array offset per sholat dalam menit (opsional)
     * @return array Jadwal sholat terformat H:i
     */
    public function calculatePrayerTimes(
        float $latitude,
        float $longitude,
        float $timezoneOffset = 7.0,
        ?Carbon $date = null,
        array $customOffsets = []
    ): array {
        $date = $date ? $date->copy() : now();
        $year = $date->year;
        $month = $date->month;
        $day = $date->day;

        // 1. Hitung Julian Day Number
        $jd = $this->julianDay($year, $month, $day);

        // 2. Hitung Waktu Transit Matahari (Solar Noon / Dzuhur astronomis)
        $d = $jd - 2451545.0;
        $g = 357.529 + 0.98560028 * $d;
        $q = 280.459 + 0.98564736 * $d;
        $L = $q + 1.915 * sin(deg2rad($g)) + 0.020 * sin(deg2rad(2 * $g));
        $e = 23.439 - 0.00000036 * $d;

        $RA = rad2deg(atan2(cos(deg2rad($e)) * sin(deg2rad($L)), cos(deg2rad($L)))) / 15.0;
        if ($RA < 0) {
            $RA += 24.0;
        }

        $EqT = ($q / 15.0) - $RA;
        while ($EqT > 12.0) $EqT -= 24.0;
        while ($EqT < -12.0) $EqT += 24.0;

        $sinDec = sin(deg2rad($e)) * sin(deg2rad($L));
        $dec = rad2deg(asin($sinDec)); // Deklinasi Matahari

        // Solar Noon (titik tengah hari matahari)
        $noon = 12.0 + $timezoneOffset - ($longitude / 15.0) - $EqT;

        // 3. Hitung Waktu Setiap Sholat (dalam jam desimal)
        // Subuh: sudut depresi 20 derajat (altitude = -20)
        $tSubuh = $noon - $this->hourAngle(-20.0, $latitude, $dec);

        // Terbit (Sunrise / Syuruq): altitude = -0.8333 derajat
        $tSunrise = $noon - $this->hourAngle(-0.8333, $latitude, $dec);

        // Dhuha: ketinggian matahari +4.5 derajat
        $tDhuha = $noon - $this->hourAngle(4.5, $latitude, $dec);

        // Dzuhur: saat solar noon
        $tDzuhur = $noon;

        // Ashar: bayangan = 1 (Madzhab Syafi'i)
        $altAshar = rad2deg(atan(1.0 / (1.0 + tan(deg2rad(abs($latitude - $dec))))));
        $tAshar = $noon + $this->hourAngle($altAshar, $latitude, $dec);

        // Maghrib (Sunset): altitude = -0.8333 derajat
        $tMaghrib = $noon + $this->hourAngle(-0.8333, $latitude, $dec);

        // Isya: sudut depresi 18 derajat (altitude = -18)
        $tIsya = $noon + $this->hourAngle(-18.0, $latitude, $dec);

        // 4. Terapkan Ihtiyat (Pengaman) & Custom Offsets
        $ihtiyat = (int) ($customOffsets['ihtiyat'] ?? 2); // Default Kemenag +2 menit

        $offsetSubuh   = $ihtiyat + (int) ($customOffsets['subuh'] ?? 0);
        $offsetSunrise = 0 + (int) ($customOffsets['terbit'] ?? 0);
        $offsetDhuha   = $ihtiyat + (int) ($customOffsets['dhuha'] ?? 0);
        $offsetDzuhur  = $ihtiyat + (int) ($customOffsets['dzuhur'] ?? 0);
        $offsetAshar   = $ihtiyat + (int) ($customOffsets['ashar'] ?? 0);
        $offsetMaghrib = $ihtiyat + (int) ($customOffsets['maghrib'] ?? 0);
        $offsetIsya    = $ihtiyat + (int) ($customOffsets['isya'] ?? 0);

        return [
            'subuh'   => $this->floatToTime($tSubuh, $offsetSubuh),
            'terbit'  => $this->floatToTime($tSunrise, $offsetSunrise),
            'dhuha'   => $this->floatToTime($tDhuha, $offsetDhuha),
            'dzuhur'  => $this->floatToTime($tDzuhur, $offsetDzuhur),
            'ashar'   => $this->floatToTime($tAshar, $offsetAshar),
            'maghrib' => $this->floatToTime($tMaghrib, $offsetMaghrib),
            'isya'    => $this->floatToTime($tIsya, $offsetIsya),
            'raw_decimal' => [
                'subuh'   => $tSubuh,
                'terbit'  => $tSunrise,
                'dhuha'   => $tDhuha,
                'dzuhur'  => $tDzuhur,
                'ashar'   => $tAshar,
                'maghrib' => $tMaghrib,
                'isya'    => $tIsya,
            ]
        ];
    }

    /**
     * Ambil pengaturan waktu sholat lengkap untuk sekolah tertentu
     */
    public function getSchoolPrayerSettings(int $schoolId): array
    {
        $lat = Setting::where('school_id', $schoolId)->where('setting_key', 'school_latitude')->value('setting_value');
        if (empty($lat)) {
            $lat = Setting::where('school_id', 0)->where('setting_key', 'school_latitude')->value('setting_value') ?: '-6.2088'; // Default Jakarta
        }

        $lng = Setting::where('school_id', $schoolId)->where('setting_key', 'school_longitude')->value('setting_value');
        if (empty($lng)) {
            $lng = Setting::where('school_id', 0)->where('setting_key', 'school_longitude')->value('setting_value') ?: '106.8456';
        }

        $tzVal = Setting::where('school_id', $schoolId)->where('setting_key', 'school_timezone')->value('setting_value');
        if (empty($tzVal)) {
            $tzVal = Setting::where('school_id', 0)->where('setting_key', 'school_timezone')->value('setting_value') ?: 'Asia/Jakarta';
        }

        $tzOffset = match (strtoupper($tzVal)) {
            'ASIA/MAKASSAR', 'WITA', 'UTC+8', 'GMT+8', '8' => 8.0,
            'ASIA/JAYAPURA', 'WIT', 'UTC+9', 'GMT+9', '9'  => 9.0,
            default => 7.0, // WIB UTC+7
        };

        // Sholat aktif di sekolah (default Dhuha, Dzuhur, Ashar)
        $activeRaw = Setting::where('school_id', $schoolId)->where('setting_key', 'prayer_active_list')->value('setting_value');
        if (empty($activeRaw)) {
            $activeList = ['dhuha', 'dzuhur', 'ashar'];
        } else {
            $decoded = json_decode($activeRaw, true);
            $activeList = is_array($decoded) ? $decoded : ['dhuha', 'dzuhur', 'ashar'];
        }

        // Offsets
        $ihtiyat = (int) (Setting::where('school_id', $schoolId)->where('setting_key', 'prayer_ihtiyat_minutes')->value('setting_value') ?? 2);
        $offsets = [
            'ihtiyat' => $ihtiyat,
            'subuh'   => (int) (Setting::where('school_id', $schoolId)->where('setting_key', 'prayer_offset_subuh')->value('setting_value') ?? 0),
            'dhuha'   => (int) (Setting::where('school_id', $schoolId)->where('setting_key', 'prayer_offset_dhuha')->value('setting_value') ?? 0),
            'dzuhur'  => (int) (Setting::where('school_id', $schoolId)->where('setting_key', 'prayer_offset_dzuhur')->value('setting_value') ?? 0),
            'ashar'   => (int) (Setting::where('school_id', $schoolId)->where('setting_key', 'prayer_offset_ashar')->value('setting_value') ?? 0),
            'maghrib' => (int) (Setting::where('school_id', $schoolId)->where('setting_key', 'prayer_offset_maghrib')->value('setting_value') ?? 0),
            'isya'    => (int) (Setting::where('school_id', $schoolId)->where('setting_key', 'prayer_offset_isya')->value('setting_value') ?? 0),
        ];

        // Windows
        $windowBefore = (int) (Setting::where('school_id', $schoolId)->where('setting_key', 'prayer_window_before')->value('setting_value') ?? 15);
        $windowAfter  = (int) (Setting::where('school_id', $schoolId)->where('setting_key', 'prayer_window_after')->value('setting_value') ?? 45);
        $tolerance    = (int) (Setting::where('school_id', $schoolId)->where('setting_key', 'prayer_ontime_tolerance')->value('setting_value') ?? 15);

        return [
            'latitude'        => (float) $lat,
            'longitude'       => (float) $lng,
            'timezone'        => $tzVal,
            'timezone_offset' => $tzOffset,
            'active_prayers'  => array_map('strtolower', $activeList),
            'offsets'         => $offsets,
            'window_before'   => max(5, $windowBefore),
            'window_after'    => max(10, $windowAfter),
            'ontime_tolerance'=> max(0, $tolerance),
        ];
    }

    /**
     * Hitung jadwal sholat sekolah untuk hari tertentu
     */
    public function getTodaySchedule(int $schoolId, ?Carbon $date = null): array
    {
        $settings = $this->getSchoolPrayerSettings($schoolId);
        $times = $this->calculatePrayerTimes(
            $settings['latitude'],
            $settings['longitude'],
            $settings['timezone_offset'],
            $date,
            $settings['offsets']
        );

        $times['settings'] = $settings;
        return $times;
    }

    /**
     * Cek apakah waktu $now saat kartu di-tap masuk ke dalam salah satu rentang window sholat aktif.
     * Jika ya, kembalikan detail sholat dan statusnya. Jika di luar semua window, kembalikan null.
     */
    public function determineActivePrayerWindow(int $schoolId, ?Carbon $now = null): ?array
    {
        $now = $now ? $now->copy() : now();
        $schedule = $this->getTodaySchedule($schoolId, $now);
        $settings = $schedule['settings'];
        $activePrayers = $settings['active_prayers'];
        $windowBefore = $settings['window_before'];
        $windowAfter = $settings['window_after'];
        $ontimeTol = $settings['ontime_tolerance'];

        $todayDateStr = $now->format('Y-m-d');
        $isFriday = ($now->dayOfWeekIso === 5); // 5 = Friday

        foreach ($activePrayers as $prayer) {
            $prayerKey = strtolower($prayer);
            if (!isset($schedule[$prayerKey])) {
                continue;
            }

            $timeStr = $schedule[$prayerKey]; // misal "11:52"
            $prayerCarbon = Carbon::createFromFormat('Y-m-d H:i', "{$todayDateStr} {$timeStr}");

            $windowStart = $prayerCarbon->copy()->subMinutes($windowBefore);
            $windowEnd   = $prayerCarbon->copy()->addMinutes($windowAfter);

            if ($now->gte($windowStart) && $now->lte($windowEnd)) {
                // Tentukan nama tampilan (pada hari Jumat, sholat Dzuhur dapat dilabeli sebagai Jumat)
                $sholatCode = $prayerKey;
                $label = ucfirst($prayerKey);
                if ($prayerKey === 'dzuhur' && $isFriday) {
                    $sholatCode = 'jumat';
                    $label = 'Jumat';
                }

                // Cek status keterlambatan (ontime vs terlambat)
                $lateThreshold = $prayerCarbon->copy()->addMinutes($ontimeTol);
                $isOntime = $now->lte($lateThreshold);
                $status = $isOntime ? 'hadir' : 'terlambat';

                return [
                    'sholat'        => $sholatCode,
                    'sholat_label'  => $label,
                    'waktu_sholat'  => $timeStr,
                    'window_start'  => $windowStart->format('H:i'),
                    'window_end'    => $windowEnd->format('H:i'),
                    'status'        => $status,
                    'is_ontime'     => $isOntime,
                    'scanned_at'    => $now->format('H:i'),
                    'now_carbon'    => $now,
                ];
            }
        }

        return null;
    }

    /**
     * Hitung Julian Day Number
     */
    private function julianDay(int $year, int $month, int $day): float
    {
        if ($month <= 2) {
            $year -= 1;
            $month += 12;
        }
        $A = floor($year / 100);
        $B = 2 - $A + floor($A / 4);
        return floor(365.25 * ($year + 4716)) + floor(30.6001 * ($month + 1)) + $day + $B - 1524.5;
    }

    /**
     * Hitung Hour Angle untuk sudut ketinggian matahari $altitude
     */
    private function hourAngle(float $altitude, float $latitude, float $declination): float
    {
        $cosH = (sin(deg2rad($altitude)) - sin(deg2rad($latitude)) * sin(deg2rad($declination))) /
            (cos(deg2rad($latitude)) * cos(deg2rad($declination)));

        if ($cosH > 1.0) {
            $cosH = 1.0;
        } elseif ($cosH < -1.0) {
            $cosH = -1.0;
        }

        return rad2deg(acos($cosH)) / 15.0;
    }

    /**
     * Konversi jam pecahan desimal ke format H:i string ditambah offset menit
     */
    private function floatToTime(float $hoursDecimal, int $offsetMinutes = 0): string
    {
        // Normalisasi jam
        while ($hoursDecimal < 0) $hoursDecimal += 24.0;
        while ($hoursDecimal >= 24.0) $hoursDecimal -= 24.0;

        $totalMinutes = round($hoursDecimal * 60) + $offsetMinutes;
        while ($totalMinutes < 0) $totalMinutes += 1440;
        while ($totalMinutes >= 1440) $totalMinutes -= 1440;

        $h = floor($totalMinutes / 60);
        $m = $totalMinutes % 60;

        return sprintf('%02d:%02d', $h, $m);
    }
}

