<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Siswa;
use App\Models\Attendance;
use App\Models\MessageQueue;
use App\Models\Setting;

use App\Models\Kelas;
use App\Services\WhatsAppMessageTemplates;
use Carbon\Carbon;

class DailyReportCommand extends Command
{
    protected $signature = 'absen:daily-report {targetJid?} {--force}';
    protected $description = 'Generate daily attendance report and send to WhatsApp Group';

    public function handle()
    {
        $targetJid = $this->argument('targetJid');
        $today = now()->format('Y-m-d');

        // Global Sunday check REMOVED to support per-school schedules
        // if (now()->isSunday()) { ... }

        // Iterate through ALL Active Schools
        $schools = \App\Models\School::where('is_active', true)->get();

        foreach ($schools as $school) {
            $this->processSchoolReport($school, $today, $targetJid);
        }
    }

    private function processSchoolReport($school, $today, $targetJidOverride = null)
    {
        $schoolId = $school->id;
        $this->info("------------------------------------------------");
        $this->info("Processing Daily Report for School: {$school->name} (ID: $schoolId)");

        $telegramEnabled = $school->telegram_enabled;
        $telegramToken = $school->telegram_bot_token;

        // 1. Check for Holiday (Dynamic: if no student attendance exists today, assume holiday)
        $hasAttendance = \App\Models\Attendance::where('tanggal', $today)
            ->whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->exists();

        if (!$hasAttendance && !$this->option('force')) {
            $this->info("Today has no student attendance for school ID $schoolId. Assumed Holiday. Skipped.");
            return;
        }

        // 2. Check Weekly Holiday via Schedule (Jadwal)
        $dayIndex = now()->dayOfWeekIso; // 1-7
        $isSchoolDay = \App\Models\Jadwal::where('school_id', $schoolId)
            ->where('index_hari', $dayIndex)
            ->where('is_active', true) // Only active days
            ->exists();

        if (!$isSchoolDay) {
            $this->info("Today (Day $dayIndex) is NOT an active school day (No Schedule). Skipped.");
            return;
        }


        // 2. Check Schedule Time (Per School)
        $scheduleTime = Setting::where('school_id', $schoolId)
            ->where('setting_key', 'schedule_daily_report')
            ->value('setting_value') ?? '08:15';

        if (now()->format('H:i') < $scheduleTime && !$this->option('force')) {
            $this->info("Too early for school ID $schoolId. Schedule: $scheduleTime. Skipped.");
            return;
        }

        // 3. Look up setting (Per School)
        $targetJid = $targetJidOverride;
        if (!$targetJid) {
            $targetJid = Setting::where('school_id', $schoolId)->where('setting_key', 'report_target_jid')->value('setting_value');
        }

        // 4. Debounce check (Per School)
        $lastRun = Setting::where('school_id', $schoolId)->where('setting_key', 'last_daily_report_date')->value('setting_value');
        if ($lastRun === $today && !$this->option('force')) {
            $this->info("DailyReport already ran today for this school.");
            return;
        }

        // 4. Get classes with WhatsApp Group ID (Per School)
        $kelasWithGroupId = Kelas::where('school_id', $schoolId)
            ->whereNotNull('wa_group_id')
            ->where('wa_group_id', '!=', '')
            ->where('is_active_attendance', true)
            ->where('is_active_report', true)
            ->get();

        // 5. Check if we have anything to report (Groups, Admin, or Wali Kelas)
        $hasWaliKelas = Kelas::where('school_id', $schoolId)
            ->where(function($q) {
                $q->whereNotNull('wali_kelas_id')
                  ->orWhereNotNull('wali_kelas_2_id');
            })
            ->where('is_active_attendance', true)
            ->exists();

        if ($kelasWithGroupId->isEmpty() && !$targetJid && !$hasWaliKelas) {
            $this->warn("No report targets (Groups, Admin, or Wali Kelas) found for this school. Skipped.");
            return;
        }

        $this->info("Generating report...");

        // --- AUTO-EXTEND SAKIT ---
        $maxSakitDays = (int) (Setting::where('school_id', $schoolId)->where('setting_key', 'sakit_max_days')->value('setting_value') ?? 2);
        $autoExtendCount = 0;

        if ($maxSakitDays > 1) {
            $yesterday = now()->subDay()->format('Y-m-d');

            // Find students who were SAKIT yesterday SCOPED
            $yesterdaySakit = Attendance::where('tanggal', $yesterday)
                ->whereHas('student', function ($q) use ($schoolId) {
                    $q->where('school_id', $schoolId);
                })
                ->where('status', 'S')
                ->get();

            foreach ($yesterdaySakit as $att) {
                // Count consecutive "Sakit" days backwards starting from yesterday
                $consecutiveDays = 1;
                $checkDate = \Carbon\Carbon::parse($yesterday)->subDay();

                // Maximum safety loop
                for ($i = 0; $i < 30; $i++) {
                    $prevRecord = Attendance::where('student_id', $att->student_id)
                        ->where('tanggal', $checkDate->format('Y-m-d'))
                        ->where('status', 'S')
                        ->exists();

                    if ($prevRecord) {
                        $consecutiveDays++;
                        $checkDate->subDay();
                        if ($consecutiveDays >= $maxSakitDays)
                            break;
                    } else {
                        break;
                    }
                }

                // If consecutive days (up to yesterday) is less than max allowed, extend to today
                if ($consecutiveDays < $maxSakitDays) {
                    // Check if student already has attendance record for today
                    $existsToday = Attendance::where('student_id', $att->student_id)
                        ->where('tanggal', $today)
                        ->exists();

                    // Only create if no record exists
                    if (!$existsToday) {
                        Attendance::create([
                            'student_id' => $att->student_id,
                            'tanggal' => $today,
                            'jam_masuk' => null,
                            'jam_pulang' => null,
                            'jam_kerja' => null,
                            'status' => 'S',
                            'keterangan' => '[Auto-Lanjut] Sakit (Hari ke-' . ($consecutiveDays + 1) . ')',
                            'is_auto_extended' => true,
                            'lokasi_masuk' => 'System',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        $autoExtendCount++;
                    }
                }
            }
        }
        $this->info("Auto-extended $autoExtendCount Sakit records.");

        // Get All Students SCOPED
        $siswaAll = Siswa::where('school_id', $schoolId)
            ->whereHas('kelas', function ($q) {
                $q->where('is_active_attendance', true);
            })->with(['kelas', 'kelas.jurusan'])->orderBy('nama')->get();

        // Get Attendance SCOPED (Implicit by student_id, but good to optimize)
        // We can just fetch all attendance for today and filter by student IDs in memory or query
        $studentIds = $siswaAll->pluck('id');
        $attendance = Attendance::where('tanggal', $today)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $totalMasuk = 0;
        $absentByStatus = [
            'T' => [],
            'A' => [],
            'I' => [],
            'S' => [],
            'B' => []
        ];

        $statsByJurusan = [];

        foreach ($siswaAll as $s) {
            $kelasObj = $s->kelas;
            $kelasName = $kelasObj->nama_kelas ?? 'Tanpa Kelas';
            $jurusanName = ($kelasObj && $kelasObj->jurusan) ? $kelasObj->jurusan->nama_jurusan : 'belum di mapping';

            if (!isset($statsByJurusan[$jurusanName])) {
                $statsByJurusan[$jurusanName] = [];
            }
            if (!isset($statsByJurusan[$jurusanName][$kelasName])) {
                $statsByJurusan[$jurusanName][$kelasName] = [
                    'total' => 0,
                    'H' => 0,
                    'T' => 0,
                    'A' => 0,
                    'S' => 0,
                    'I' => 0,
                    'B' => 0
                ];
            }

            // Skip student if they are on PKL and did not attend, OR if they are Siswa Khusus, today is not their entry day and they did not attend
            $isPklExempt = $s->is_khusus && !$attendance->has($s->id);
            $isKhususExempt = $s->is_siswa_khusus && !$s->isEntryDay($today) && !$attendance->has($s->id);

            if ($isPklExempt || $isKhususExempt) {
                continue;
            }

            $statsByJurusan[$jurusanName][$kelasName]['total']++;

            if ($attendance->has($s->id)) {
                $att = $attendance[$s->id];
                $status = $att->status;

                if (isset($statsByJurusan[$jurusanName][$kelasName][$status])) {
                    $statsByJurusan[$jurusanName][$kelasName][$status]++;
                }

                if ($status === 'H') {
                    $totalMasuk++;
                } elseif ($status === 'T') {
                    $totalMasuk++; // Terlambat tetap dihitung masuk
                    $absentByStatus['T'][] = "{$s->nama} ({$kelasName})";
                } else {
                    if (isset($absentByStatus[$status])) {
                        $absentByStatus[$status][] = "{$s->nama} ({$kelasName})";
                    }
                }
            } else {
                // If they reach here, it's either a normal student, or a special student on their entry day.
                // Both must be marked as Alpha because they did not attend.
                $statsByJurusan[$jurusanName][$kelasName]['A']++;
                $absentByStatus['A'][] = "{$s->nama} ({$kelasName})";

                $keterangan = $s->is_siswa_khusus ? 'Alpha (Tidak Hadir - Hari Masuk Khusus)' : 'Alpha';

                Attendance::firstOrCreate(
                    ['student_id' => $s->id, 'tanggal' => $today],
                    [
                        'jam_masuk' => null,
                        'jam_pulang' => null,
                        'jam_kerja' => null,
                        'status' => 'A',
                        'keterangan' => $keterangan,
                        'is_auto_alpha' => true,
                        'lokasi_masuk' => 'System',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // Sort by Jurusan Name, then by Kelas Name
        ksort($statsByJurusan);
        foreach ($statsByJurusan as &$kelasArr) {
            ksort($kelasArr);
        }
        unset($kelasArr);

        // Calculate total absent (T tidak dihitung sebagai tidak masuk)
        $totalTidakMasuk = 0;
        foreach (['A', 'I', 'S', 'B'] as $st) {
            $totalTidakMasuk += count($absentByStatus[$st]);
        }

        // --- SEND CLASS-SPECIFIC REPORTS TO CLASS GROUPS ---
        $this->info("Processing class-specific group reports...");
        $studentsByClass = $siswaAll->groupBy('kelas_id');

        foreach ($kelasWithGroupId as $kelas) {
            $siswaKelas = $studentsByClass[$kelas->id] ?? collect();

            if ($siswaKelas->isEmpty()) {
                continue;
            }

            $masuk = 0;
            $tidakMasuk = 0;
            $absentByStatusClass = [
                'T' => [],
                'A' => [],
                'I' => [],
                'S' => [],
                'B' => []
            ];

            foreach ($siswaKelas as $s) {
                if ($attendance->has($s->id)) {
                    $att = $attendance[$s->id];
                    if ($att->status === 'H') {
                        $masuk++;
                    } elseif ($att->status === 'T') {
                        $masuk++; // Terlambat tetap dihitung masuk
                        $absentByStatusClass['T'][] = $s->nama;
                    } else {
                        $tidakMasuk++;
                        $status = $att->status;
                        if (isset($absentByStatusClass[$status])) {
                            $absentByStatusClass[$status][] = $s->nama;
                        }
                    }
                } elseif ($s->is_khusus || $s->is_siswa_khusus) {
                    if ($s->is_siswa_khusus && $s->isEntryDay($today)) {
                        $tidakMasuk++;
                        $absentByStatusClass['A'][] = $s->nama;
                    } else {
                        // Siswa PKL or Siswa Khusus outside entry day -> Exempt, leave empty.
                    }
                } else {
                    $tidakMasuk++;
                    $absentByStatusClass['A'][] = $s->nama;
                }
            }

            $msgClass = WhatsAppMessageTemplates::dailyReportClass(
                namaKelas: $kelas->nama_kelas,
                masuk: $masuk,
                tidakMasuk: $tidakMasuk,
                absentByStatus: $absentByStatusClass
            );

            MessageQueue::create([
                'school_id' => $schoolId,
                'phone_number' => $kelas->wa_group_id,
                'message' => $msgClass,
                'status' => 'pending',
                'priority' => 10,
                'created_at' => now()
            ]);
            $this->info("Queued class report: {$kelas->nama_kelas}");
        }

        // --- SEND GLOBAL REPORT ---
        $guruGlobal = \App\Models\Guru::where('school_id', $schoolId)
            ->where('is_global_report', true)
            ->whereNotNull('no_wa')
            ->where('no_wa', '!=', '')
            ->get();

        if ($targetJid || $guruGlobal->isNotEmpty()) {
            $msg = WhatsAppMessageTemplates::dailyReportGlobal(
                totalMasuk: $totalMasuk,
                totalTidakMasuk: $totalTidakMasuk,
                absentByStatus: $absentByStatus,
                statsByJurusan: $statsByJurusan
            );

            if ($targetJid) {
                MessageQueue::create([
                    'school_id' => $schoolId,
                    'phone_number' => $targetJid,
                    'message' => $msg,
                    'status' => 'pending',
                    'priority' => 10,
                    'created_at' => now()
                ]);
                $this->info("Queued global report to legacy admin ($targetJid)");
            }

            foreach ($guruGlobal as $guru) {
                $noWa = $guru->no_wa;
                if (!str_contains($noWa, '@')) {
                    $noWa = preg_replace('/^0/', '62', $noWa);
                    // $noWa = $noWa . '@s.whatsapp.net';
                }

                MessageQueue::create([
                    'school_id' => $schoolId,
                    'phone_number' => $noWa,
                    'message' => $msg,
                    'status' => 'pending',
                    'priority' => 10,
                    'created_at' => now()
                ]);
                $this->info("Queued global report to Guru: {$guru->nama}");
            }

            // Telegram Global Report
            if ($telegramEnabled && $telegramToken) {
                foreach ($guruGlobal as $guru) {
                    if (!empty($guru->telegram_chat_id)) {
                        $msgGlobalTelegram = $msg;
                        $msgGlobalTelegram = preg_replace('/\*([^*]+)\*/', '<b>$1</b>', $msgGlobalTelegram);
                        $msgGlobalTelegram = preg_replace('/\_([^_]+)\_/', '<i>$1</i>', $msgGlobalTelegram);
                        
                        if (!empty($school->name)) {
                            $msgGlobalTelegram = rtrim($msgGlobalTelegram) . "\n\n<b>" . trim($school->name) . "</b>";
                        }

                        \App\Jobs\SendTelegramMessageJob::dispatch($telegramToken, $guru->telegram_chat_id, $msgGlobalTelegram, $schoolId);
                    }
                }
            }
        }

        // --- LAPORAN PER WALI KELAS ---
        $this->info("Processing Wali Kelas reports...");
        $kelasWithWali = \App\Models\Kelas::where('school_id', $schoolId)
            ->where(function($q) {
                $q->whereNotNull('wali_kelas_id')
                  ->orWhereNotNull('wali_kelas_2_id');
            })
            ->with(['waliKelas', 'waliKelas2'])
            ->get();

        foreach ($kelasWithWali as $kelas) {
            if (!isset($studentsByClass[$kelas->id])) {
                continue;
            }

            $walis = array_filter([$kelas->waliKelas, $kelas->waliKelas2]);
            if (empty($walis)) {
                continue;
            }

            $siswaKelas = $studentsByClass[$kelas->id];
            $masuk = 0;
            $tidakMasuk = 0;
            $listAbsen = [];
            $listTerlambat = [];

            foreach ($siswaKelas as $s) {
                if ($attendance->has($s->id)) {
                    $att = $attendance[$s->id];
                    if ($att->status === 'H') {
                        $masuk++;
                    } elseif ($att->status === 'T') {
                        $masuk++; // Terlambat tetap dihitung masuk
                        $listTerlambat[] = $s->nama;
                    } else {
                        $tidakMasuk++;
                        $statusKet = match ($att->status) {
                            'I' => 'Izin',
                            'S' => 'Sakit',
                            'A' => 'Alpha',
                            'B' => 'Bolos',
                            default => $att->status
                        };
                        $listAbsen[] = "{$s->nama} ({$statusKet})";
                    }
                } elseif ($s->is_khusus || $s->is_siswa_khusus) {
                    if ($s->is_siswa_khusus && $s->isEntryDay($today)) {
                        $tidakMasuk++;
                        $listAbsen[] = "{$s->nama} (Alpha)";
                    } else {
                        // Siswa PKL or Siswa Khusus outside entry day -> Exempt, leave empty.
                    }
                } else {
                    $tidakMasuk++;
                    $listAbsen[] = "{$s->nama} (Alpha)";
                }
            }

            foreach ($walis as $wali) {
                if (!$wali->no_wa) {
                    continue;
                }

                $msgWali = WhatsAppMessageTemplates::dailyReportWaliKelas(
                    namaKelas: $kelas->nama_kelas,
                    namaWali: $wali->nama,
                    masuk: $masuk,
                    tidakMasuk: $tidakMasuk,
                    listAbsen: $listAbsen,
                    listTerlambat: $listTerlambat
                );

                MessageQueue::create([
                    'school_id' => $schoolId,
                    'phone_number' => $wali->no_wa,
                    'message' => $msgWali,
                    'status' => 'pending',
                    'priority' => 10,
                    'created_at' => now()
                ]);

                // Telegram Wali Kelas Report
                if ($telegramEnabled && $telegramToken && !empty($wali->telegram_chat_id)) {
                    $msgWaliTelegram = $msgWali;
                    $msgWaliTelegram = preg_replace('/\*([^*]+)\*/', '<b>$1</b>', $msgWaliTelegram);
                    $msgWaliTelegram = preg_replace('/\_([^_]+)\_/', '<i>$1</i>', $msgWaliTelegram);
                    
                    if (!empty($school->name)) {
                        $msgWaliTelegram = rtrim($msgWaliTelegram) . "\n\n<b>" . trim($school->name) . "</b>";
                    }

                    \App\Jobs\SendTelegramMessageJob::dispatch($telegramToken, $wali->telegram_chat_id, $msgWaliTelegram, $schoolId);
                }
            }
        }

        // --- ALPHA NOTIFICATIONS ---
        $this->info("Processing alpha notifications...");
        // Filter alphaStudentIds for THIS SCHOOL
        $alphaStudentIds = [];
        foreach ($siswaAll as $s) {
            if (!$attendance->has($s->id)) {
                if ($s->is_khusus) {
                    // Siswa PKL is always exempt (left empty)
                } elseif ($s->is_siswa_khusus) {
                    if ($s->isEntryDay($today)) {
                        $alphaStudentIds[] = $s->id;
                    }
                } else {
                    $alphaStudentIds[] = $s->id;
                }
            }
        }

        if (!empty($alphaStudentIds)) {
            $alphaStudents = Siswa::with('kelas')
                ->whereIn('id', $alphaStudentIds)
                ->get(); // Already scoped by $siswaAll

            foreach ($alphaStudents as $student) {
                $this->sendAlphaNotification($student, $schoolId);
            }
        }

        // Mark done for this school
        Setting::updateOrCreate(
            ['school_id' => $schoolId, 'setting_key' => 'last_daily_report_date'],
            ['setting_value' => $today]
        );
    }

    private function sendAlphaNotification($student, $schoolId)
    {
        $studentName = $student->nama;
        $studentPhone = $student->no_wa;
        $parentPhone = $student->wa_ortu;
        $kelasName = $student->kelas->nama_kelas ?? '-';

        $waSiswaEnabled = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_wa_siswa')->value('setting_value') !== 'false';
        $waOrtuEnabled = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_wa_ortu')->value('setting_value') !== 'false';
        $teleSiswaEnabled = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_tele_siswa')->value('setting_value') !== 'false';
        $teleOrtuEnabled = \App\Models\Setting::where('school_id', $schoolId)->where('setting_key', 'notification_tele_ortu')->value('setting_value') !== 'false';

        if ($studentPhone && $waSiswaEnabled) {
            $msgStudent = "❌ *Pemberitahuan Ketidakhadiran*\n\n" .
                "Halo, *{$studentName}*,\n\n" .
                "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
                "📊 Status: Alpha (Tidak Hadir)\n\n" .
                "Anda tercatat tidak hadir hari ini tanpa keterangan.\n" .
                "Mohon segera konfirmasi ke wali kelas atau bagian kesiswaan.\n\n" .
                "_Notifikasi otomatis dari sistem absensi sekolah._";

            MessageQueue::create([
                'school_id' => $schoolId,
                'phone_number' => $studentPhone,
                'message' => $msgStudent,
                'status' => 'pending',
                'created_at' => now()
            ]);
        }

        if ($parentPhone && $waOrtuEnabled) {
            $msgParent = "❌ *Pemberitahuan Ketidakhadiran Anak*\n\n" .
                "Halo, Orang Tua/Wali dari *{$studentName}*,\n\n" .
                "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
                "📊 Status: Alpha (Tidak Hadir)\n" .
                "⚠️ Kelas: {$kelasName}\n\n" .
                "Anak Anda tercatat tidak hadir hari ini tanpa keterangan.\n" .
                "Mohon konfirmasi kepada wali kelas atau bagian kesiswaan.\n\n" .
                "_Notifikasi otomatis dari sistem absensi sekolah._";

            MessageQueue::create([
                'school_id' => $schoolId,
                'phone_number' => $parentPhone,
                'message' => $msgParent,
                'status' => 'pending',
                'created_at' => now()
            ]);
        }

        // Telegram Alpha Notification
        $school = \App\Models\School::find($schoolId);
        $telegramEnabled = $school ? $school->telegram_enabled : false;
        $telegramToken = $school ? $school->telegram_bot_token : null;

        if ($telegramEnabled && $telegramToken) {
            if (!empty($student->telegram_chat_id) && $teleSiswaEnabled) {
                // Ensure msgStudent is compiled even if WA student notification is off
                $msgStudentText = "❌ *Pemberitahuan Ketidakhadiran*\n\n" .
                    "Halo, *{$studentName}*,\n\n" .
                    "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
                    "📊 Status: Alpha (Tidak Hadir)\n\n" .
                    "Anda tercatat tidak hadir hari ini tanpa keterangan.\n" .
                    "Mohon segera konfirmasi ke wali kelas atau bagian kesiswaan.\n\n" .
                    "_Notifikasi otomatis dari sistem absensi sekolah._";

                $msgStudentTelegram = $msgStudentText;
                $msgStudentTelegram = preg_replace('/\*([^*]+)\*/', '<b>$1</b>', $msgStudentTelegram);
                $msgStudentTelegram = preg_replace('/\_([^_]+)\_/', '<i>$1</i>', $msgStudentTelegram);
                
                if ($school && !empty($school->name)) {
                    $msgStudentTelegram = rtrim($msgStudentTelegram) . "\n\n<b>" . trim($school->name) . "</b>";
                }
                
                \App\Jobs\SendTelegramMessageJob::dispatch($telegramToken, $student->telegram_chat_id, $msgStudentTelegram, $schoolId);
            }
            if (!empty($student->telegram_ortu_chat_id) && $teleOrtuEnabled) {
                // Ensure msgParent is compiled even if WA parent notification is off
                $msgParentText = "❌ *Pemberitahuan Ketidakhadiran Anak*\n\n" .
                    "Halo, Orang Tua/Wali dari *{$studentName}*,\n\n" .
                    "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
                    "📊 Status: Alpha (Tidak Hadir)\n" .
                    "⚠️ Kelas: {$kelasName}\n\n" .
                    "Anak Anda tercatat tidak hadir hari ini tanpa keterangan.\n" .
                    "Mohon konfirmasi kepada wali kelas atau bagian kesiswaan.\n\n" .
                    "_Notifikasi otomatis dari sistem absensi sekolah._";

                $msgParentTelegram = $msgParentText;
                $msgParentTelegram = preg_replace('/\*([^*]+)\*/', '<b>$1</b>', $msgParentTelegram);
                $msgParentTelegram = preg_replace('/\_([^_]+)\_/', '<i>$1</i>', $msgParentTelegram);
                
                if ($school && !empty($school->name)) {
                    $msgParentTelegram = rtrim($msgParentTelegram) . "\n\n<b>" . trim($school->name) . "</b>";
                }

                \App\Jobs\SendTelegramMessageJob::dispatch($telegramToken, $student->telegram_ortu_chat_id, $msgParentTelegram, $schoolId);
            }
        }
    }
}
