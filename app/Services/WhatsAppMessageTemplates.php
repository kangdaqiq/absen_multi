<?php

namespace App\Services;

use Carbon\Carbon;

class WhatsAppMessageTemplates
{
    /**
     * Check-in notification
     */
    public static function checkIn(string $nama, string $jamMasuk, string $kelas, string $status = 'Hadir'): string
    {
        return "✅ *Notifikasi Absen Masuk*\n\n" .
            "Halo, *{$nama}*,\n\n" .
            "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
            "🕐 Jam Masuk: {$jamMasuk}\n" .
            "📊 Status: {$status}\n" .
            "🏫 Kelas: {$kelas}\n\n" .
            "_Notifikasi otomatis dari sistem absensi sekolah._";
    }

    /**
     * Check-out notification
     */
    public static function checkOut(
        string $nama,
        string $jamMasuk,
        string $jamPulang,
        int $hours,
        int $minutes,
        string $authorizedBy,
        ?string $tanggal = null
    ): string {
        $tgl = $tanggal ?? now()->format('d/m/Y');
        return "🏠 *Notifikasi Absen Pulang*\n\n" .
            "Halo, *{$nama}*,\n\n" .
            "📅 Tanggal: " . $tgl . "\n" .
            "🕐 Jam Masuk: {$jamMasuk}\n" .
            "🕐 Jam Pulang: {$jamPulang}\n" .
            "⏱️ Durasi: {$hours} jam {$minutes} menit\n" .
            "👤 Diotorisasi oleh: {$authorizedBy}\n\n" .
            "_Notifikasi otomatis dari sistem absensi sekolah._";
    }

    /**
     * Late check-in notification
     */
    public static function checkInLate(
        string $nama,
        string $jamMasuk,
        string $kelas,
        int $lateHours = 0,
        int $lateMinutes = 0
    ): string {
        // Format durasi: "1 jam 30 menit", "30 menit", atau "1 jam"
        if ($lateHours > 0 && $lateMinutes > 0) {
            $lateDuration = "{$lateHours} jam {$lateMinutes} menit";
        } elseif ($lateHours > 0) {
            $lateDuration = "{$lateHours} jam";
        } else {
            $lateDuration = "{$lateMinutes} menit";
        }

        return "⚠️ *Notifikasi Terlambat*\n\n" .
            "Halo, *{$nama}*,\n\n" .
            "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
            "🕐 Jam Masuk: {$jamMasuk}\n" .
            "⏰ Keterlambatan: {$lateDuration}\n" .
            "📊 Status: Terlambat\n" .
            "🏫 Kelas: {$kelas}\n\n" .
            "Mohon lebih disiplin waktu kedepannya.\n\n" .
            "_Notifikasi otomatis dari sistem absensi sekolah._";
    }

    /**
     * Check-in notification to parent
     */
    public static function checkInParent(string $nama, string $jamMasuk, string $kelas, string $status = 'Hadir'): string
    {
        return "✅ *Notifikasi Absen Masuk Anak*\n\n" .
            "Halo, Orang Tua/Wali dari *{$nama}*,\n\n" .
            "Anak Anda telah tercatat hadir di sekolah.\n\n" .
            "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
            "🕐 Jam Masuk: {$jamMasuk}\n" .
            "📊 Status: {$status}\n" .
            "🏫 Kelas: {$kelas}\n\n" .
            "_Notifikasi otomatis dari sistem absensi sekolah._";
    }

    /**
     * Late check-in notification to parent
     */
    public static function checkInLateParent(
        string $nama,
        string $jamMasuk,
        string $kelas,
        int $lateHours = 0,
        int $lateMinutes = 0
    ): string {
        if ($lateHours > 0 && $lateMinutes > 0) {
            $lateDuration = "{$lateHours} jam {$lateMinutes} menit";
        } elseif ($lateHours > 0) {
            $lateDuration = "{$lateHours} jam";
        } else {
            $lateDuration = "{$lateMinutes} menit";
        }

        return "⚠️ *Notifikasi Terlambat Anak*\n\n" .
            "Halo, Orang Tua/Wali dari *{$nama}*,\n\n" .
            "Anak Anda telah tercatat hadir di sekolah, namun *terlambat*.\n\n" .
            "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
            "🕐 Jam Masuk: {$jamMasuk}\n" .
            "⏰ Keterlambatan: {$lateDuration}\n" .
            "📊 Status: Terlambat\n" .
            "🏫 Kelas: {$kelas}\n\n" .
            "Mohon bantuannya untuk mengingatkan anak agar lebih disiplin.\n\n" .
            "_Notifikasi otomatis dari sistem absensi sekolah._";
    }

    /**
     * Check-out notification to parent
     */
    public static function checkOutParent(
        string $nama,
        string $jamMasuk,
        string $jamPulang,
        int $hours,
        int $minutes,
        string $authorizedBy,
        ?string $tanggal = null
    ): string {
        $tgl = $tanggal ?? now()->format('d/m/Y');
        return "🏠 *Notifikasi Absen Pulang Anak*\n\n" .
            "Halo, Orang Tua/Wali dari *{$nama}*,\n\n" .
            "Anak Anda telah tercatat pulang dari sekolah.\n\n" .
            "📅 Tanggal: " . $tgl . "\n" .
            "🕐 Jam Masuk: {$jamMasuk}\n" .
            "🕐 Jam Pulang: {$jamPulang}\n" .
            "⏱️ Durasi: {$hours} jam {$minutes} menit\n" .
            "👤 Diotorisasi oleh: {$authorizedBy}\n\n" .
            "_Notifikasi otomatis dari sistem absensi sekolah._";
    }

    /**
     * Alpha (absent) notification to student
     */
    public static function alphaStudent(string $nama): string
    {
        return "❌ *Pemberitahuan Ketidakhadiran*\n\n" .
            "Halo, *{$nama}*,\n\n" .
            "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
            "📊 Status: Alpha (Tidak Hadir)\n\n" .
            "Anda tercatat tidak hadir hari ini tanpa keterangan.\n" .
            "Mohon segera konfirmasi ke wali kelas atau bagian kesiswaan.\n\n" .
            "_Notifikasi otomatis dari sistem absensi sekolah._";
    }

    /**
     * Alpha (absent) notification to parent
     */
    public static function alphaParent(string $nama, string $kelas): string
    {
        return "❌ *Pemberitahuan Ketidakhadiran Anak*\n\n" .
            "Halo, Orang Tua/Wali dari *{$nama}*,\n\n" .
            "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
            "📊 Status: Alpha (Tidak Hadir)\n" .
            "⚠️ Kelas: {$kelas}\n\n" .
            "Anak Anda tercatat tidak hadir hari ini tanpa keterangan.\n" .
            "Mohon konfirmasi kepada wali kelas atau bagian kesiswaan.\n\n" .
            "_Notifikasi otomatis dari sistem absensi sekolah._";
    }

    /**
     * Bolos (skipped checkout) notification to student
     */
    public static function bolosStudent(string $nama): string
    {
        return "🏃 *Pemberitahuan Indikasi Bolos*\n\n" .
            "Halo, *{$nama}*,\n\n" .
            "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
            "📊 Status: Bolos (Tidak Absen Pulang)\n\n" .
            "Anda tercatat sudah absen masuk, namun tidak melakukan absen pulang hingga batas waktu yang ditentukan.\n" .
            "Mohon segera konfirmasi ke wali kelas atau bagian kesiswaan jika Anda lupa melakukan absen pulang.\n\n" .
            "_Notifikasi otomatis dari sistem absensi sekolah._";
    }

    /**
     * Bolos (skipped checkout) notification to parent
     */
    public static function bolosParent(string $nama, string $kelas): string
    {
        return "🏃 *Pemberitahuan Indikasi Bolos Anak*\n\n" .
            "Halo, Orang Tua/Wali dari *{$nama}*,\n\n" .
            "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
            "📊 Status: Bolos (Tidak Absen Pulang)\n" .
            "⚠️ Kelas: {$kelas}\n\n" .
            "Anak Anda tercatat sudah absen masuk hari ini, namun tidak melakukan absen pulang hingga batas waktu yang ditentukan.\n" .
            "Mohon konfirmasi kepada anak Anda atau wali kelas terkait hal ini.\n\n" .
            "_Notifikasi otomatis dari sistem absensi sekolah._";
    }

    /**
     * Daily report for class group
     */
    public static function dailyReportClass(
        string $namaKelas,
        int $masuk,
        int $tidakMasuk,
        array $absentByStatus
    ): string {
        $msg = "📊 *Laporan Absensi Kelas {$namaKelas}*\n";
        $msg .= "📅 Tanggal: " . now()->format('d/m/Y') . "\n";
        $msg .= str_repeat("─", 30) . "\n";
        $msg .= "✅ Siswa Masuk: {$masuk}\n";

        $terlambat = isset($absentByStatus['T']) ? count($absentByStatus['T']) : 0;
        if ($terlambat > 0) {
            $msg .= "⚠️ Siswa Terlambat: {$terlambat}\n";
        }

        $msg .= "❌ Siswa Tidak Masuk: {$tidakMasuk}\n";
        $msg .= str_repeat("─", 30) . "\n\n";

        if ($tidakMasuk > 0 || $terlambat > 0) {
            $statusLabels = [
                'T' => '⚠️ Terlambat',
                'A' => '❌ Alpha',
                'I' => '📝 Izin',
                'S' => '🤒 Sakit',
                'B' => '🏃 Bolos'
            ];

            foreach (['T', 'A', 'I', 'S', 'B'] as $status) {
                if (empty($absentByStatus[$status])) {
                    continue;
                }

                $count = count($absentByStatus[$status]);
                $msg .= "*{$statusLabels[$status]}* ({$count} siswa)\n";
                foreach ($absentByStatus[$status] as $nama) {
                    $msg .= "  • {$nama}\n";
                }
                $msg .= "\n";
            }
        } else {
            $msg .= "🎉 *Nihil (Semua Masuk)*\n\n";
        }

        $msg .= "_Generated by System_";
        return $msg;
    }

    /**
     * Daily report for homeroom teacher (wali kelas)
     */
    public static function dailyReportWaliKelas(
        string $namaKelas,
        string $namaWali,
        int $masuk,
        int $tidakMasuk,
        array $listAbsen,
        array $listTerlambat = []
    ): string {
        $msg = "📊 *Laporan Absensi Kelas {$namaKelas}*\n";
        $msg .= "👤 Wali Kelas: {$namaWali}\n";
        $msg .= "📅 Tanggal: " . now()->format('d/m/Y') . "\n";
        $msg .= "---------------------------\n";
        $msg .= "✅ Hadir: {$masuk}\n";
        $terlambat = count($listTerlambat);
        if ($terlambat > 0) {
            $msg .= "⚠️ Terlambat: {$terlambat}\n";
        }
        $msg .= "❌ Tidak Hadir: {$tidakMasuk}\n";
        $msg .= "---------------------------\n";

        if ($terlambat > 0) {
            $msg .= "*Detail Terlambat:*\n";
            foreach ($listTerlambat as $item) {
                $msg .= "- {$item}\n";
            }
            $msg .= "\n";
        }

        if ($tidakMasuk > 0) {
            $msg .= "*Detail Tidak Hadir:*\n";
            foreach ($listAbsen as $item) {
                $msg .= "- {$item}\n";
            }
        }

        if ($tidakMasuk == 0 && $terlambat == 0) {
            $msg .= "🎉 *Nihil (Semua Masuk Tepat Waktu)*\n";
        }

        $msg .= "\n_Generated by System_";
        return $msg;
    }

    /**
     * Global daily report (all classes)
     */
    public static function dailyReportGlobal(
        int $totalMasuk,
        int $totalTidakMasuk,
        array $absentByStatus,
        array $statsByJurusan = []
    ): string {
        $msg = "📊 *Laporan Absensi Harian (Global)*\n";
        $msg .= "📅 Tanggal: " . now()->format('d/m/Y') . "\n";
        $msg .= str_repeat("─", 30) . "\n";
        $msg .= "✅ Siswa Masuk: {$totalMasuk}\n";
        $totalTerlambat = isset($absentByStatus['T']) ? count($absentByStatus['T']) : 0;
        if ($totalTerlambat > 0) {
            $msg .= "⚠️ Siswa Terlambat: {$totalTerlambat}\n";
        }
        $msg .= "❌ Siswa Tidak Masuk: {$totalTidakMasuk}\n";
        $msg .= str_repeat("─", 30) . "\n\n";

        if ($totalTidakMasuk > 0 || $totalTerlambat > 0) {
            $statusLabels = [
                'T' => '⚠️ Terlambat',
                'A' => '❌ Alpha',
                'I' => '📝 Izin',
                'S' => '🤒 Sakit',
                'B' => '🏃 Bolos'
            ];

            foreach (['T', 'A', 'I', 'S', 'B'] as $status) {
                if (empty($absentByStatus[$status])) {
                    continue;
                }

                $count = count($absentByStatus[$status]);
                $msg .= "*{$statusLabels[$status]}*: {$count} siswa\n";
            }
        } else {
            $msg .= "🎉 *Nihil (Semua Masuk)*\n";
        }

        if (!empty($statsByJurusan)) {
            $msg .= "------\n";
            foreach ($statsByJurusan as $jurusan => $kelasData) {
                $msg .= "*Jurusan {$jurusan}*\n";
                foreach ($kelasData as $kelas => $stats) {
                    $msg .= "{$kelas}\n";
                    $msg .= "Total Siswa : {$stats['total']}\n";
                    $msg .= "Hadir : {$stats['H']}\n";
                    $msg .= "Terlambat: {$stats['T']}\n";
                    $msg .= "Alpa : {$stats['A']}\n";
                    $msg .= "Sakit : {$stats['S']}\n";
                    $msg .= "Izin : {$stats['I']}\n";
                    $msg .= "Bolos : {$stats['B']}\n\n";
                }
            }
        }

        $schoolName = "NAMA SEKOLAH"; // Since template is static and school context is separate, we can pass it, or we rely on MessageQueue booting

        $msg .= "Generated by System\n\n";
        // $msg .= "_{$schoolName}_"; // The MessageQueue "booted" event will append the school name automatically!

        return $msg;
    }

    /**
     * Final absence report (after daily processing)
     */
    public static function finalAbsenceReport(
        int $totalPresent,
        int $totalAbsent,
        iterable $absentStudentsGrouped
    ): string {
        $msg = "📋 *LAPORAN FINAL ABSENSI*\n";
        $msg .= "📅 Tanggal: " . now()->format('d/m/Y') . "\n";
        $msg .= str_repeat("─", 30) . "\n";
        $msg .= "✅ Siswa Hadir: *{$totalPresent}*\n";
        $terlambat = isset($absentStudentsGrouped['T']) ? count($absentStudentsGrouped['T']) : 0;
        if ($terlambat > 0) {
            $msg .= "⚠️ Siswa Terlambat: *{$terlambat}*\n";
        }
        $msg .= "❌ Siswa Tidak Hadir: *{$totalAbsent}*\n";
        $msg .= str_repeat("─", 30) . "\n\n";

        $statusLabels = [
            'T' => '⚠️ Terlambat',
            'A' => '❌ Alpha',
            'B' => '🏃 Bolos (Tidak Absen Pulang)',
            'I' => '📝 Izin',
            'S' => '🤒 Sakit'
        ];

        foreach (['T', 'A', 'B', 'I', 'S'] as $status) {
            if (!isset($absentStudentsGrouped[$status])) {
                continue;
            }

            $students = $absentStudentsGrouped[$status];
            $count = is_countable($students) ? count($students) : iterator_count($students);

            $msg .= "*{$statusLabels[$status]}* ({$count} siswa)\n";
            foreach ($students as $att) {
                $kelas = $att->student->kelas->nama_kelas ?? '-';
                $msg .= "  • {$att->student->nama} ({$kelas})\n";
            }
            $msg .= "\n";
        }

        $msg .= str_repeat("─", 30) . "\n";
        $msg .= "\n_Laporan otomatis setelah proses harian_";

        return $msg;
    }

    /**
     * Final absence report global (after daily processing)
     */
    public static function finalAbsenceReportGlobal(
        int $totalPresent,
        int $totalAbsent,
        iterable $absentStudentsGrouped
    ): string {
        $msg = "📋 *LAPORAN FINAL ABSENSI*\n";
        $msg .= "📅 Tanggal: " . now()->format('d/m/Y') . "\n";
        $msg .= str_repeat("─", 30) . "\n";
        $msg .= "✅ Siswa Hadir: *{$totalPresent}*\n";
        $terlambat = isset($absentStudentsGrouped['T']) ? count($absentStudentsGrouped['T']) : 0;
        if ($terlambat > 0) {
            $msg .= "⚠️ Siswa Terlambat: *{$terlambat}*\n";
        }
        $msg .= "❌ Siswa Tidak Hadir: *{$totalAbsent}*\n";
        $msg .= str_repeat("─", 30) . "\n\n";

        $statusLabels = [
            'T' => '⚠️ Terlambat',
            'A' => '❌ Alpha',
            'B' => '🏃 Bolos (Tidak Absen Pulang)',
            'I' => '📝 Izin',
            'S' => '🤒 Sakit'
        ];

        foreach (['T', 'A', 'B', 'I', 'S'] as $status) {
            if (!isset($absentStudentsGrouped[$status])) {
                continue;
            }

            $students = $absentStudentsGrouped[$status];
            $count = is_countable($students) ? count($students) : iterator_count($students);

            $msg .= "*{$statusLabels[$status]}*: {$count} siswa\n";
        }

        $msg .= "\n" . str_repeat("─", 30) . "\n";
        $msg .= "\n_Laporan otomatis setelah proses harian_";

        return $msg;
    }

    /**
     * Abnormal attendance alert (frequent absences)
     */
    public static function abnormalAttendanceAlert(
        string $nama,
        string $kelas,
        int $alphaCount,
        int $bolosCount,
        int $totalDays,
        string $periodStart,
        string $periodEnd
    ): string {
        $msg = "⚠️ *PERINGATAN KETIDAKHADIRAN BERLEBIHAN*\n\n";
        $msg .= "Siswa: *{$nama}*\n";
        $msg .= "Kelas: {$kelas}\n";
        $msg .= "Periode: {$periodStart} - {$periodEnd}\n";
        $msg .= str_repeat("─", 30) . "\n";
        $msg .= "❌ Alpha: {$alphaCount} hari\n";
        $msg .= "🏃 Bolos: {$bolosCount} hari\n";
        $msg .= "📊 Total Ketidakhadiran: " . ($alphaCount + $bolosCount) . " dari {$totalDays} hari\n\n";
        $msg .= "Mohon segera ditindaklanjuti oleh wali kelas dan orang tua.\n\n";
        $msg .= "_Notifikasi otomatis dari sistem monitoring kehadiran._";

        return $msg;
    }

    /**
     * Teacher schedule notification
     */
    public static function teacherSchedule(string $namaGuru, array $jadwalHariIni): string
    {
        $msg = "📚 *Jadwal Mengajar Hari Ini*\n\n";
        $msg .= "Halo, *{$namaGuru}*,\n\n";
        $msg .= "📅 " . now()->locale('id')->isoFormat('dddd, D MMMM YYYY') . "\n";
        $msg .= str_repeat("─", 30) . "\n\n";

        if (empty($jadwalHariIni)) {
            $msg .= "Tidak ada jadwal mengajar hari ini.\n\n";
        } else {
            foreach ($jadwalHariIni as $jadwal) {
                $msg .= "🕐 {$jadwal['jam_mulai']} - {$jadwal['jam_selesai']}\n";
                $msg .= "📖 {$jadwal['mata_pelajaran']}\n";
                $msg .= "🏫 Kelas: {$jadwal['kelas']}\n\n";
            }
        }

        $msg .= "_Semangat mengajar!_\n";
        $msg .= "_Notifikasi otomatis dari sistem._";

        return $msg;
    }

    /**
     * Broadcast message template
     */
    public static function broadcast(string $title, string $message, ?string $footer = null): string
    {
        $msg = "📢 *{$title}*\n\n";
        $msg .= $message . "\n\n";

        if ($footer) {
            $msg .= "_{$footer}_";
        } else {
            $msg .= "_Pengumuman dari sistem sekolah._";
        }

        return $msg;
    }
}
