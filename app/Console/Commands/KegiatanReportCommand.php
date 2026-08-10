<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\School;
use App\Models\Kegiatan;
use App\Models\KegiatanAttendance;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\Setting;
use App\Models\MessageQueue;
use App\Services\WhatsAppMessageTemplates;

class KegiatanReportCommand extends Command
{
    protected $signature = 'absen:kegiatan-report {--force}';
    protected $description = 'Process and send per-activity attendance reports after activity jam_selesai has passed.';

    public function handle()
    {
        $today = now()->format('Y-m-d');
        $nowTime = now()->format('H:i');

        $schools = School::where('is_active', true)->get();

        foreach ($schools as $school) {
            $this->processSchoolKegiatanReports($school, $today, $nowTime);
        }
    }

    private function processSchoolKegiatanReports(School $school, string $today, string $nowTime)
    {
        $schoolId = $school->id;

        // Fetch active activities
        $activeKegiatans = Kegiatan::where('school_id', $schoolId)
            ->where('is_active', true)
            ->get();

        if ($activeKegiatans->isEmpty()) {
            return;
        }

        $telegramEnabled = $school->telegram_enabled;
        $telegramToken = $school->telegram_bot_token;
        $targetJid = Setting::where('school_id', $schoolId)->where('setting_key', 'report_target_jid')->value('setting_value');

        foreach ($activeKegiatans as $kegiatan) {
            $startDateStr = $kegiatan->tanggal_mulai->format('Y-m-d');
            if ($today < $startDateStr) {
                continue;
            }

            // Check frequency match today
            $isToday = false;
            if ($kegiatan->frekuensi === 'sekali') {
                $isToday = ($today === $startDateStr);
            } elseif ($kegiatan->frekuensi === 'mingguan') {
                $isToday = (now()->dayOfWeek === $kegiatan->tanggal_mulai->dayOfWeek);
            } elseif ($kegiatan->frekuensi === 'bulanan') {
                $isToday = (now()->day === $kegiatan->tanggal_mulai->day);
            } else {
                // harian
                if (!empty($kegiatan->hari) && is_array($kegiatan->hari)) {
                    $dayIndex = now()->dayOfWeekIso; // 1 (Mon) - 7 (Sun)
                    $isToday = in_array($dayIndex, array_map('intval', $kegiatan->hari));
                } else {
                    $isToday = true;
                }
            }

            if (!$isToday) {
                continue;
            }

            // Check if jam_selesai has passed
            if ($kegiatan->jam_selesai) {
                $jamSelesaiStr = \Carbon\Carbon::parse($kegiatan->jam_selesai)->format('H:i');
                if ($nowTime < $jamSelesaiStr && !$this->option('force')) {
                    continue; // activity is still ongoing
                }
            }

            // Debounce: check if report for this activity on this date was already sent today
            $settingKey = "kegiatan_report_{$kegiatan->id}_last_date";
            $lastRun = Setting::where('school_id', $schoolId)->where('setting_key', $settingKey)->value('setting_value');

            if ($lastRun === $today && !$this->option('force')) {
                continue; // already reported today
            }

            $this->info("Sending Activity Report for '{$kegiatan->nama_kegiatan}' in school {$school->name}...");

            // Fetch all attendance for this activity today
            $attendancesToday = KegiatanAttendance::where('school_id', $schoolId)
                ->where('kegiatan_id', $kegiatan->id)
                ->where('tanggal', $today)
                ->get()
                ->keyBy('student_id');

            // 1. GLOBAL ACTIVITY REPORT
            $totalHadir = $attendancesToday->where('status', 'H')->count();
            $totalIzin  = $attendancesToday->where('status', 'I')->count();
            $totalSakit = $attendancesToday->where('status', 'S')->count();

            // Total active students in school for context
            $allStudents = Siswa::where('school_id', $schoolId)
                ->whereHas('kelas', fn($q) => $q->where('is_active_attendance', true))
                ->with('kelas')
                ->get();

            $totalAlpha = max(0, $allStudents->count() - ($totalHadir + $totalIzin + $totalSakit));

            // Stats per class for global report
            $statsByKelas = [];
            foreach ($allStudents->groupBy('kelas_id') as $kelasId => $classStudents) {
                $kelasName = $classStudents->first()->kelas->nama_kelas ?? 'Tanpa Kelas';
                $h = 0; $i = 0; $s = 0; $a = 0;
                foreach ($classStudents as $st) {
                    if ($attendancesToday->has($st->id)) {
                        $status = $attendancesToday[$st->id]->status;
                        if ($status === 'H') $h++;
                        elseif ($status === 'I') $i++;
                        elseif ($status === 'S') $s++;
                    } else {
                        $a++;
                    }
                }
                $statsByKelas[$kelasName] = ['H' => $h, 'I' => $i, 'S' => $s, 'A' => $a];
            }
            ksort($statsByKelas);

            $msgGlobal = WhatsAppMessageTemplates::kegiatanReportGlobal(
                namaKegiatan: $kegiatan->nama_kegiatan,
                totalHadir: $totalHadir,
                totalIzin: $totalIzin,
                totalSakit: $totalSakit,
                totalAlpha: $totalAlpha,
                statsByKelas: $statsByKelas
            );

            // Send Global WA
            if ($targetJid) {
                MessageQueue::create([
                    'school_id'    => $schoolId,
                    'phone_number' => $targetJid,
                    'message'      => $msgGlobal,
                    'status'       => 'pending',
                    'priority'     => 10,
                    'created_at'   => now()
                ]);
            }

            $guruGlobal = Guru::where('school_id', $schoolId)
                ->where('is_global_report', true)
                ->whereNotNull('no_wa')
                ->where('no_wa', '!=', '')
                ->get();

            foreach ($guruGlobal as $guru) {
                if ($guru->isWithinLastSeen(168)) {
                    $noWa = preg_replace('/^0/', '62', $guru->no_wa);
                    $mq = new MessageQueue([
                        'school_id'    => $schoolId,
                        'phone_number' => $noWa,
                        'message'      => $msgGlobal,
                        'status'       => 'pending',
                        'priority'     => 10,
                        'created_at'   => now()
                    ]);
                    $mq->bypass_last_seen = true;
                    $mq->save();
                }

                if ($telegramEnabled && $telegramToken && !empty($guru->telegram_chat_id)) {
                    $teleMsg = preg_replace('/\*([^*]+)\*/', '<b>$1</b>', $msgGlobal);
                    $teleMsg = preg_replace('/\_([^_]+)\_/', '<i>$1</i>', $teleMsg);
                    if (!empty($school->name)) {
                        $teleMsg .= "\n\n<b>" . trim($school->name) . "</b>";
                    }
                    \App\Jobs\SendTelegramMessageJob::dispatch($telegramToken, $guru->telegram_chat_id, $teleMsg, $schoolId);
                }
            }

            // 2. WALI KELAS ACTIVITY REPORT
            $kelasWithWali = Kelas::where('school_id', $schoolId)
                ->where(function($q) {
                    $q->whereNotNull('wali_kelas_id')->orWhereNotNull('wali_kelas_2_id');
                })
                ->where('is_active_attendance', true)
                ->with(['waliKelas', 'waliKelas2'])
                ->get();

            $studentsByClass = $allStudents->groupBy('kelas_id');

            foreach ($kelasWithWali as $kelas) {
                $classStudents = $studentsByClass[$kelas->id] ?? collect();
                if ($classStudents->isEmpty()) {
                    continue;
                }

                $walis = array_filter([$kelas->waliKelas, $kelas->waliKelas2]);
                if (empty($walis)) {
                    continue;
                }

                $h = 0; $i = 0; $s = 0; $a = 0;
                $listAbsenClass = [];

                foreach ($classStudents as $st) {
                    if ($attendancesToday->has($st->id)) {
                        $attStatus = $attendancesToday[$st->id]->status;
                        if ($attStatus === 'H') {
                            $h++;
                        } elseif ($attStatus === 'I') {
                            $i++;
                            $listAbsenClass[] = "{$st->nama} (Izin)";
                        } elseif ($attStatus === 'S') {
                            $s++;
                            $listAbsenClass[] = "{$st->nama} (Sakit)";
                        }
                    } else {
                        $a++;
                        $listAbsenClass[] = "{$st->nama} (Belum Absen)";
                    }
                }

                foreach ($walis as $wali) {
                    $msgWali = WhatsAppMessageTemplates::kegiatanReportWaliKelas(
                        namaKegiatan: $kegiatan->nama_kegiatan,
                        namaKelas: $kelas->nama_kelas,
                        namaWali: $wali->nama,
                        hadir: $h,
                        izin: $i,
                        sakit: $s,
                        alpha: $a,
                        listAbsen: $listAbsenClass
                    );

                    if (!empty($wali->no_wa) && $wali->isWithinLastSeen(168)) {
                        $mq = new MessageQueue([
                            'school_id'    => $schoolId,
                            'phone_number' => $wali->no_wa,
                            'message'      => $msgWali,
                            'status'       => 'pending',
                            'priority'     => 10,
                            'created_at'   => now()
                        ]);
                        $mq->bypass_last_seen = true;
                        $mq->save();
                    }

                    if ($telegramEnabled && $telegramToken && !empty($wali->telegram_chat_id)) {
                        $teleWaliMsg = preg_replace('/\*([^*]+)\*/', '<b>$1</b>', $msgWali);
                        $teleWaliMsg = preg_replace('/\_([^_]+)\_/', '<i>$1</i>', $teleWaliMsg);
                        if (!empty($school->name)) {
                            $teleWaliMsg .= "\n\n<b>" . trim($school->name) . "</b>";
                        }
                        \App\Jobs\SendTelegramMessageJob::dispatch($telegramToken, $wali->telegram_chat_id, $teleWaliMsg, $schoolId);
                    }
                }
            }

            // Mark reported for today
            Setting::updateOrCreate(
                ['school_id' => $schoolId, 'setting_key' => $settingKey],
                ['setting_value' => $today]
            );

            $this->info("Completed Activity Report for '{$kegiatan->nama_kegiatan}'");
        }
    }
}
