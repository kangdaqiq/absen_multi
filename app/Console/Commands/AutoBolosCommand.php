<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use App\Models\MessageQueue;
use App\Models\Siswa;
use App\Models\Attendance;
use App\Services\WhatsAppMessageTemplates;
use Carbon\Carbon;

class AutoBolosCommand extends Command
{
    protected $signature = 'absen:process-daily {--force}';
    protected $description = 'Process daily attendance: Mark Bolos (B) if no checkout, and Alpha (A) if no checkin by 13:30';

    public function handle()
    {
        $schools = \App\Models\School::where('is_active', true)->get();

        foreach ($schools as $school) {
            $this->processSchool($school);
        }
    }

    private function processSchool($school)
    {
        $today = now()->format('Y-m-d');
        $schoolId = $school->id;

        $this->info("Processing School: {$school->name} (ID: $schoolId) for $today");

        // 0. Check Schedule Time (Per School)
        $scheduleTime = Setting::where('school_id', $schoolId)
            ->where('setting_key', 'schedule_process_daily')
            ->value('setting_value') ?? '13:30';

        if (now()->format('H:i') < $scheduleTime) {
            // Too early
            return;
        }

        // 1. Debounce check
        $lastRun = Setting::where('school_id', $schoolId)->where('setting_key', 'last_daily_process_date')->value('setting_value');
        if ($lastRun === $today && !$this->option('force')) {
            $this->info("Daily Process already ran today ($today) for school ID $schoolId. Use --force to run anyway.");
            return;
        }

        // 2. Check Weekly Holiday via Schedule (Jadwal)
        // If today has NO active schedule, skip process
        $dayIndex = \Carbon\Carbon::parse($today)->dayOfWeekIso; // 1-7
        $isSchoolDay = \App\Models\Jadwal::where('school_id', $schoolId)
            ->where('index_hari', $dayIndex)
            ->where('is_active', true)
            ->exists();

        if (!$isSchoolDay) {
            $this->info("Today (Day $dayIndex) is NOT an active school day (No Schedule). Process skipped for school ID $schoolId.");
            return;
        }

        // 3. Check for Holiday (Dynamic: if no student attendance exists today, assume holiday)
        $hasAttendance = \App\Models\Attendance::where('tanggal', $today)
            ->whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })->exists();

        if (!$hasAttendance) {
            $this->info("Today has no student attendance for school ID $schoolId. Assumed Holiday. Process skipped.");
            return;
        }

        // --- STEP 1: Mark BOLOS (Checked In but No Checkout) ---
        // Only if checkout attendance is enabled
        $checkoutEnabled = Setting::where('school_id', $schoolId)->where('setting_key', 'enable_checkout_attendance')
            ->value('setting_value') ?? 'true';

        $countB = 0;
        if ($checkoutEnabled === 'true') {
            // Fetch students who are about to be marked as Bolos
            $bolosStudentsQuery = DB::table('attendance')
                ->join('siswa', 'attendance.student_id', '=', 'siswa.id')
                ->join('kelas', 'siswa.kelas_id', '=', 'kelas.id')
                ->where('siswa.school_id', $schoolId)
                ->where('attendance.tanggal', $today)
                ->whereNotNull('attendance.jam_masuk')
                ->whereNull('attendance.jam_pulang')
                ->whereNotIn('attendance.status', ['I', 'S'])
                ->where('kelas.is_active_attendance', true);

            $bolosStudents = $bolosStudentsQuery->select('siswa.nama', 'siswa.no_wa', 'siswa.wa_ortu', 'siswa.telegram_chat_id', 'siswa.telegram_ortu_chat_id', 'kelas.nama_kelas')->get();

            // Perform bulk update to mark as Bolos
            $countB = $bolosStudentsQuery->update([
                'attendance.status' => 'B',
                'attendance.keterangan' => DB::raw("CONCAT(IFNULL(attendance.keterangan, ''), ' [Auto: Tidak Absen Pulang]')"),
                'attendance.updated_at' => now()
            ]);

            // Queue personal notifications for Bolos students
            $telegramEnabled = $school->telegram_enabled;
            $telegramToken = $school->telegram_bot_token;

            foreach ($bolosStudents as $bs) {
                if (!empty($bs->no_wa)) {
                    MessageQueue::create([
                        'school_id' => $schoolId,
                        'phone_number' => $bs->no_wa,
                        'message' => \App\Services\WhatsAppMessageTemplates::bolosStudent($bs->nama),
                        'status' => 'pending',
                        'created_at' => now()
                    ]);
                }
                if (!empty($bs->wa_ortu)) {
                    MessageQueue::create([
                        'school_id' => $schoolId,
                        'phone_number' => $bs->wa_ortu,
                        'message' => \App\Services\WhatsAppMessageTemplates::bolosParent($bs->nama, $bs->nama_kelas),
                        'status' => 'pending',
                        'created_at' => now()
                    ]);
                }

                // Telegram Notification
                if ($telegramEnabled && $telegramToken) {
                    if (!empty($bs->telegram_chat_id)) {
                        $msgTelegram = "⚠️ <b>PERINGATAN BOLOS</b> ⚠️\n\n" .
                            "Halo, <b>{$bs->nama}</b> 👋,\n\n" .
                            "Anda terdeteksi belum melakukan absen pulang (check-out) hingga waktu yang ditentukan hari ini.\n" .
                            "Status kehadiran Anda hari ini telah diubah menjadi <b>Bolos</b>.";
                        
                        if (!empty($school->name)) {
                            $msgTelegram .= "\n\n<b>" . trim($school->name) . "</b>";
                        }
                        
                        \App\Jobs\SendTelegramMessageJob::dispatch($telegramToken, $bs->telegram_chat_id, $msgTelegram, $schoolId);
                    }
                    if (!empty($bs->telegram_ortu_chat_id)) {
                        $msgTelegramOrtu = "⚠️ <b>Pemberitahuan Bolos</b> ⚠️\n\n" .
                            "Bapak/Ibu Orang Tua/Wali dari <b>{$bs->nama}</b> (Kelas: {$bs->nama_kelas}),\n\n" .
                            "Menginfokan bahwa putra/putri Anda terdeteksi belum melakukan absen pulang hingga waktu yang ditentukan hari ini, sehingga status kehadirannya diubah menjadi <b>Bolos (B)</b>.";
                        
                        if (!empty($school->name)) {
                            $msgTelegramOrtu .= "\n\n<b>" . trim($school->name) . "</b>";
                        }

                        \App\Jobs\SendTelegramMessageJob::dispatch($telegramToken, $bs->telegram_ortu_chat_id, $msgTelegramOrtu, $schoolId);
                    }
                }
            }

            $this->info("Marked $countB records as Bolos (B) and queued personal notifications for school ID $schoolId.");
        } else {
            $this->info("Checkout attendance is disabled for school ID $schoolId.");
        }

        // --- STEP 2: Mark ALPHA (No Record at all) ---
        // 2. Get all students who don't have attendance record for today AND belong to this school
        $studentsWithoutAttendance = Siswa::where('school_id', $schoolId)
            ->whereDoesntHave('attendance', function ($query) use ($today) {
                $query->where('tanggal', $today);
            })
            ->whereHas('kelas', function ($q) {
                $q->where('is_active_attendance', true);
            })
            ->with('kelas')
            ->get();

        $countA = 0;
        $countExempt = 0;
        foreach ($studentsWithoutAttendance as $s) {
            $isPkl = $s->is_khusus;
            $isKhususNotEntry = $s->is_siswa_khusus && !$s->isEntryDay($today);

            if ($isPkl || $isKhususNotEntry) {
                // Left empty (no record created)
                $countExempt++;
            } else {
                // Either normal student, or Siswa Khusus on entry day. Must be marked Alpha!
                \App\Models\Attendance::create([
                    'student_id' => $s->id,
                    'tanggal' => $today,
                    'jam_masuk' => null,
                    'jam_pulang' => null,
                    'jam_kerja' => null,
                    'status' => 'A',
                    'keterangan' => $s->is_siswa_khusus ? 'Alpha (Tidak Hadir - Hari Masuk Khusus)' : 'Alpha (Tidak Hadir)',
                    'lokasi_masuk' => 'System',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $countA++;
            }
        }

        $this->info("Marked $countA students as Alpha (A) and left $countExempt special/PKL students empty for school ID $schoolId.");

        // Update Setting for this school
        Setting::updateOrCreate(
            ['school_id' => $schoolId, 'setting_key' => 'last_daily_process_date'],
            ['setting_value' => $today]
        );

        // Send Absence Report
        $this->sendAbsenceReport($today, $schoolId);
        $this->info("------------------------------------------------");
    }

    private function sendAbsenceReport($today, $schoolId)
    {
        $school = \App\Models\School::find($schoolId);
        $telegramEnabled = $school ? $school->telegram_enabled : false;
        $telegramToken = $school ? $school->telegram_bot_token : null;

        $legacyTarget = Setting::where('school_id', $schoolId)->where('setting_key', 'report_target_jid')->value('setting_value');

        // Cek apakah ada penerima laporan: grup kelas, legacyTarget, wali kelas, atau guru global
        $hasWaGroup = \App\Models\Kelas::where('school_id', $schoolId)
            ->whereNotNull('wa_group_id')->where('wa_group_id', '!=', '')
            ->where('is_active_attendance', true)->where('is_active_report', true)
            ->exists();

        $hasWaliKelas = \App\Models\Kelas::where('school_id', $schoolId)
            ->whereNotNull('wali_kelas_id')
            ->where('is_active_attendance', true)->where('is_active_report', true)
            ->exists();

        $hasGuruGlobal = \App\Models\Guru::where('school_id', $schoolId)
            ->where('is_global_report', true)->whereNotNull('no_wa')->where('no_wa', '!=', '')->exists();

        // Jika tidak ada penerima sama sekali, skip
        if (!$hasWaGroup && !$legacyTarget && !$hasWaliKelas && !$hasGuruGlobal) {
            $this->info("No report recipients configured for school ID $schoolId. Skipped.");
            return;
        }

        // Get all absent/late students (A, B, I, S, T) for this school
        $absentStudents = Attendance::where('tanggal', $today)
            ->whereIn('status', ['A', 'B', 'I', 'S', 'T'])
            ->whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->whereHas('student.kelas', function ($q) {
                $q->where('is_active_attendance', true);
            })
            ->with(['student.kelas', 'student.kelas.jurusan'])
            ->orderBy('status')
            ->get();

        // Get all present students (H only) for this school to count
        $presentStudents = Attendance::where('tanggal', $today)
            ->where('status', 'H')
            ->whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            })
            ->whereHas('student.kelas', function ($q) {
                $q->where('is_active_attendance', true);
            })
            ->with(['student.kelas', 'student.kelas.jurusan'])
            ->get();

        // Jika tidak ada siswa absen (non-T) SAMA SEKALI di sekolah, bisa langsung return
        $hasRealAbsent = $absentStudents->whereIn('status', ['A', 'B', 'I', 'S'])->isNotEmpty();
        if (!$hasRealAbsent && $absentStudents->where('status', 'T')->isEmpty()) {
            return;
        }

        // Kirim laporan per kelas ke Grup WA Kelas dan Wali Kelas
        $allKelas = \App\Models\Kelas::where('school_id', $schoolId)
            ->where('is_active_attendance', true)
            ->where('is_active_report', true)
            ->with('waliKelas')
            ->get();

        foreach ($allKelas as $kelas) {
            // Ambil siswa tidak hadir khusus kelas ini
            $absenKelas = $absentStudents->filter(function ($att) use ($kelas) {
                return $att->student->kelas_id == $kelas->id;
            });

            if ($absenKelas->isEmpty()) {
                continue; // Tidak ada yang absen di kelas ini, skip
            }

            // Hitung siswa hadir di kelas ini
            $totalPresentKelas = $presentStudents->filter(function ($att) use ($kelas) {
                return $att->student->kelas_id == $kelas->id;
            })->count();

            $wali = $kelas->waliKelas;
            $namaWali = $wali ? $wali->nama : '-';

            $groupedKelas = $absenKelas->groupBy('status');
            $msgKelas = WhatsAppMessageTemplates::finalAbsenceReport(
                totalPresent: $totalPresentKelas,
                totalAbsent: $absenKelas->count(),
                absentStudentsGrouped: $groupedKelas
            );

            $msgKelas = "📋 *Laporan Kelas {$kelas->nama_kelas}*\n" .
                       "👤 Wali Kelas: {$namaWali}\n\n" . $msgKelas;

            // 1. Kirim ke Grup WA Kelas (jika diatur)
            if (!empty($kelas->wa_group_id)) {
                MessageQueue::create([
                    'school_id'    => $schoolId,
                    'phone_number' => $kelas->wa_group_id,
                    'message'      => $msgKelas,
                    'status'       => 'pending',
                    'created_at'   => now()
                ]);
            }

            // 2. Kirim ke Nomor WA Pribadi Wali Kelas (jika ada)
            if ($wali && !empty($wali->no_wa)) {
                $noWa = $wali->no_wa;
                if (!str_contains($noWa, '@')) {
                    $noWa = preg_replace('/^0/', '62', $noWa);
                    // $noWa = $noWa . '@s.whatsapp.net';
                }

                MessageQueue::create([
                    'school_id'    => $schoolId,
                    'phone_number' => $noWa,
                    'message'      => $msgKelas,
                    'status'       => 'pending',
                    'created_at'   => now()
                ]);
            }

            // 3. Kirim ke Telegram Pribadi Wali Kelas (jika ada)
            if ($telegramEnabled && $telegramToken && $wali && !empty($wali->telegram_chat_id)) {
                $msgKelasTelegram = $msgKelas;
                $msgKelasTelegram = preg_replace('/\*([^*]+)\*/', '<b>$1</b>', $msgKelasTelegram);
                $msgKelasTelegram = preg_replace('/\_([^_]+)\_/', '<i>$1</i>', $msgKelasTelegram);
                
                if (!empty($school->name)) {
                    $msgKelasTelegram = rtrim($msgKelasTelegram) . "\n\n<b>" . trim($school->name) . "</b>";
                }

                \App\Jobs\SendTelegramMessageJob::dispatch($telegramToken, $wali->telegram_chat_id, $msgKelasTelegram, $schoolId);
            }
        }

        // Persiapkan laporan global jika diperlukan (untuk legacy target atau Guru Global)
        $guruGlobal = \App\Models\Guru::where('school_id', $schoolId)
            ->where('is_global_report', true)
            ->whereNotNull('no_wa')
            ->where('no_wa', '!=', '')
            ->get();

        if ($legacyTarget || $guruGlobal->isNotEmpty()) {
            $groupedGlobal = $absentStudents->groupBy('status');
            $totalPresentGlobal = $presentStudents->count();

            $statsByJurusan = [];
            $allStudents = $presentStudents->concat($absentStudents);
            
            foreach ($allStudents as $att) {
                $s = $att->student;
                $kelasObj = $s->kelas;
                $kelasName = $kelasObj->nama_kelas ?? 'Tanpa Kelas';
                $jurusanName = ($kelasObj && $kelasObj->jurusan) ? $kelasObj->jurusan->nama_jurusan : 'Tanpa Jurusan';

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

                $statsByJurusan[$jurusanName][$kelasName]['total']++;
                $status = $att->status;
                if (isset($statsByJurusan[$jurusanName][$kelasName][$status])) {
                    $statsByJurusan[$jurusanName][$kelasName][$status]++;
                }
            }

            // Sort by Jurusan Name, then by Kelas Name
            ksort($statsByJurusan);
            foreach ($statsByJurusan as &$kelasArr) {
                ksort($kelasArr);
            }
            unset($kelasArr);

            $messageGlobal = WhatsAppMessageTemplates::finalAbsenceReportGlobal(
                totalPresent: $totalPresentGlobal,
                totalAbsent: $absentStudents->whereIn('status', ['A', 'B', 'I', 'S'])->count(),
                absentStudentsGrouped: $groupedGlobal,
                statsByJurusan: $statsByJurusan
            );

            // Legacy target
            if ($legacyTarget) {
                MessageQueue::create([
                    'school_id'    => $schoolId,
                    'phone_number' => $legacyTarget,
                    'message'      => $messageGlobal,
                    'status'       => 'pending',
                    'created_at'   => now()
                ]);
            }

            // Guru dengan akses report global
            foreach ($guruGlobal as $guru) {
                $noWa = $guru->no_wa;
                if (!str_contains($noWa, '@')) {
                    $noWa = preg_replace('/^0/', '62', $noWa);
                    // $noWa = $noWa . '@s.whatsapp.net';
                }

                MessageQueue::create([
                    'school_id'    => $schoolId,
                    'phone_number' => $noWa,
                    'message'      => $messageGlobal,
                    'status'       => 'pending',
                    'created_at'   => now()
                ]);
            }

            // Telegram Laporan Global
            if ($telegramEnabled && $telegramToken) {
                foreach ($guruGlobal as $guru) {
                    if (!empty($guru->telegram_chat_id)) {
                        $msgGlobalTelegram = $messageGlobal;
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

        $this->info("✓ Absence report queued for school ID $schoolId.");
    }
}
