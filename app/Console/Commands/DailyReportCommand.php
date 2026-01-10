<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Siswa;
use App\Models\Attendance;
use App\Models\MessageQueue;
use App\Models\Setting;
use App\Models\HariLibur;
use App\Models\Kelas;
use App\Services\WhatsAppMessageTemplates;
use Carbon\Carbon;

class DailyReportCommand extends Command
{
    protected $signature = 'absen:daily-report {targetJid?}';
    protected $description = 'Generate daily attendance report and send to WhatsApp Group';

    public function handle()
    {
        $targetJid = $this->argument('targetJid');

        // Look up setting if not provided (legacy support)
        if (!$targetJid) {
            $targetJid = Setting::where('setting_key', 'report_target_jid')->value('setting_value');
        }

        // Get classes with WhatsApp Group ID
        $kelasWithGroupId = Kelas::whereNotNull('wa_group_id')
            ->where('wa_group_id', '!=', '')
            ->where('is_active_attendance', true)
            ->where('is_active_report', true)
            ->get();

        if ($kelasWithGroupId->isEmpty() && !$targetJid) {
            $this->error("No classes with WhatsApp Group ID and target JID not found in settings.");
            return;
        }

        $today = now()->format('Y-m-d');

        // CHECK 1: Sunday
        if (now()->isSunday()) {
            $this->info("Today is Sunday. Report Skipped.");
            return;
        }

        // CHECK 2: Holiday (Hari Libur)
        if (HariLibur::where('tanggal', $today)->exists()) {
            $this->info("Today is Holiday ($today). Report Skipped.");
            return;
        }

        // Debounce check
        $lastRun = Setting::where('setting_key', 'last_daily_report_date')->value('setting_value');
        if ($lastRun === $today) {
            $this->info("DailyReport already ran today ($today).");
            return;
        }

        $this->info("Generating report for $today...");

        // --- AUTO-EXTEND SAKIT FROM YESTERDAY (2 DAYS) ---
        $yesterday = now()->subDay()->format('Y-m-d');

        // Find students who were SAKIT yesterday (only S, not I)
        // Exclude auto-extended records to prevent loop (only extend manual entries)
        $yesterdaySakit = Attendance::where('tanggal', $yesterday)
            ->where('status', 'S')
            ->where('is_auto_extended', false) // Only manual entries
            ->get();

        $autoExtendCount = 0;
        foreach ($yesterdaySakit as $att) {
            // Check if student already has attendance record for today
            $existsToday = Attendance::where('student_id', $att->student_id)
                ->where('tanggal', $today)
                ->exists();

            // Only create if no record exists (student hasn't checked in)
            if (!$existsToday) {
                Attendance::create([
                    'student_id' => $att->student_id,
                    'tanggal' => $today,
                    'jam_masuk' => null,
                    'jam_pulang' => null,
                    'jam_kerja' => null,
                    'status' => 'S',
                    'keterangan' => '[Auto-Lanjut] Sakit (Hari ke-2)',
                    'is_auto_extended' => true, // Mark as auto-extended
                    'lokasi_masuk' => 'System',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $autoExtendCount++;
            }
        }

        $this->info("Auto-extended $autoExtendCount Sakit records from yesterday.");


        $siswaAll = Siswa::whereHas('kelas', function ($q) {
            $q->where('is_active_attendance', true);
        })->with('kelas')->orderBy('nama')->get();

        $attendance = Attendance::where('tanggal', $today)->get()->keyBy('student_id');

        $totalMasuk = 0;
        $absentByStatus = [
            'A' => [],
            'I' => [],
            'S' => [],
            'B' => []
        ];

        foreach ($siswaAll as $s) {
            if ($attendance->has($s->id)) {
                $att = $attendance[$s->id];
                if ($att->status === 'H') {
                    $totalMasuk++;
                } else {
                    // Group by status
                    $status = $att->status;
                    if (isset($absentByStatus[$status])) {
                        $kelas = $s->kelas->nama_kelas ?? '-';
                        $absentByStatus[$status][] = "{$s->nama} ({$kelas})";
                    }
                }
            } else {
                // No record = Alpha
                $kelas = $s->kelas->nama_kelas ?? '-';
                $absentByStatus['A'][] = "{$s->nama} ({$kelas})";
            }
        }

        // Calculate total absent
        $totalTidakMasuk = 0;
        foreach ($absentByStatus as $students) {
            $totalTidakMasuk += count($students);
        }

        // --- SEND CLASS-SPECIFIC REPORTS TO CLASS GROUPS ---
        $this->info("Processing class-specific group reports...");
        $studentsByClass = $siswaAll->groupBy('kelas_id');

        foreach ($kelasWithGroupId as $kelas) {
            // Get students only from this class
            $siswaKelas = $studentsByClass[$kelas->id] ?? collect();

            if ($siswaKelas->isEmpty()) {
                $this->warn("No students in class {$kelas->nama_kelas}. Skipping group report.");
                continue;
            }

            $totalSiswa = $siswaKelas->count();
            $masuk = 0;
            $tidakMasuk = 0;
            $absentByStatusClass = [
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
                    } else {
                        $tidakMasuk++;
                        $status = $att->status;
                        if (isset($absentByStatusClass[$status])) {
                            $absentByStatusClass[$status][] = $s->nama;
                        }
                    }
                } else {
                    // No record = Alpha
                    $tidakMasuk++;
                    $absentByStatusClass['A'][] = $s->nama;
                }
            }

            // Use template for class-specific message
            $msgClass = WhatsAppMessageTemplates::dailyReportClass(
                namaKelas: $kelas->nama_kelas,
                masuk: $masuk,
                tidakMasuk: $tidakMasuk,
                absentByStatus: $absentByStatusClass
            );

            // Queue Class-Specific Message
            MessageQueue::create([
                'phone_number' => $kelas->wa_group_id,
                'message' => $msgClass,
                'status' => 'pending',
                'created_at' => now()
            ]);

            $this->info("Queued class-specific report to {$kelas->nama_kelas} group ({$kelas->wa_group_id})");
        }

        // --- SEND GLOBAL REPORT TO LEGACY TARGET (if exists) ---
        if ($targetJid) {
            // Use template for global message
            $msg = WhatsAppMessageTemplates::dailyReportGlobal(
                totalMasuk: $totalMasuk,
                totalTidakMasuk: $totalTidakMasuk,
                absentByStatus: $absentByStatus
            );

            MessageQueue::create([
                'phone_number' => $targetJid,
                'message' => $msg,
                'status' => 'pending',
                'created_at' => now()
            ]);
            $this->info("Queued global report to legacy target ({$targetJid})");
        }



        // --- NEW: Laporan Per Wali Kelas ---
        $this->info("Processing Wali Kelas reports...");

        $kelasWithWali = \App\Models\Kelas::whereNotNull('wali_kelas_id')->with('waliKelas')->get();
        $studentsByClass = $siswaAll->groupBy('kelas_id');

        foreach ($kelasWithWali as $kelas) {
            $wali = $kelas->waliKelas;

            // Skip if Wali Kelas has no phone number or no students
            if (!$wali || !$wali->no_wa || !isset($studentsByClass[$kelas->id])) {
                continue;
            }

            $siswaKelas = $studentsByClass[$kelas->id];
            $totalSiswa = $siswaKelas->count();

            $masuk = 0;
            $tidakMasuk = 0;
            $listAbsen = [];

            foreach ($siswaKelas as $s) {
                if ($attendance->has($s->id)) {
                    $att = $attendance[$s->id];
                    if ($att->status === 'H') {
                        $masuk++;
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
                } else {
                    $tidakMasuk++;
                    $listAbsen[] = "{$s->nama} (Alpha)";
                }
            }

            // Use template for wali kelas message
            $msgWali = WhatsAppMessageTemplates::dailyReportWaliKelas(
                namaKelas: $kelas->nama_kelas,
                namaWali: $wali->nama,
                masuk: $masuk,
                tidakMasuk: $tidakMasuk,
                listAbsen: $listAbsen
            );

            // Queue Message
            MessageQueue::create([
                'phone_number' => $wali->no_wa,
                'message' => $msgWali,
                'status' => 'pending',
                'created_at' => now()
            ]);

            $this->info("Queued report for {$kelas->nama_kelas} -> {$wali->nama} ({$wali->no_wa})");
        }


        // --- NEW: Send Notifications to Alpha Students and Parents ---
        $this->info("Processing alpha student notifications...");

        // Get all alpha students with their full data
        $alphaStudentIds = [];
        foreach ($siswaAll as $s) {
            if (!$attendance->has($s->id)) {
                // No attendance record = Alpha
                $alphaStudentIds[] = $s->id;
            }
        }

        if (!empty($alphaStudentIds)) {
            $alphaStudents = Siswa::with('kelas')
                ->whereIn('id', $alphaStudentIds)
                ->get();

            foreach ($alphaStudents as $student) {
                $studentName = $student->nama;
                $studentPhone = $student->no_wa;
                $parentPhone = $student->wa_ortu;
                $kelasName = $student->kelas->nama_kelas ?? '-';

                // Send to student if phone exists
                if ($studentPhone) {
                    $msgStudent = "❌ *Pemberitahuan Ketidakhadiran*\n\n" .
                        "Assalamualaikum, *{$studentName}*,\n\n" .
                        "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
                        "📊 Status: Alpha (Tidak Hadir)\n\n" .
                        "Anda tercatat tidak hadir hari ini tanpa keterangan.\n" .
                        "Mohon segera konfirmasi ke wali kelas atau bagian kesiswaan.\n\n" .
                        "_Notifikasi otomatis dari sistem absensi sekolah._";

                    MessageQueue::create([
                        'phone_number' => $studentPhone,
                        'message' => $msgStudent,
                        'status' => 'pending',
                        'created_at' => now()
                    ]);

                    $this->info("Queued alpha notification to student: {$studentName} ({$studentPhone})");
                }

                // Send to parent if phone exists
                if ($parentPhone) {
                    $msgParent = "❌ *Pemberitahuan Ketidakhadiran Anak*\n\n" .
                        "Assalamualaikum, Orang Tua/Wali dari *{$studentName}*,\n\n" .
                        "📅 Tanggal: " . now()->format('d/m/Y') . "\n" .
                        "📊 Status: Alpha (Tidak Hadir)\n" .
                        "⚠️ Kelas: {$kelasName}\n\n" .
                        "Anak Anda tercatat tidak hadir hari ini tanpa keterangan.\n" .
                        "Mohon konfirmasi kepada wali kelas atau bagian kesiswaan.\n\n" .
                        "_Notifikasi otomatis dari sistem absensi sekolah._";

                    MessageQueue::create([
                        'phone_number' => $parentPhone,
                        'message' => $msgParent,
                        'status' => 'pending',
                        'created_at' => now()
                    ]);

                    $this->info("Queued alpha notification to parent: {$studentName} ({$parentPhone})");
                }

                // Log if student has no contact numbers
                if (!$studentPhone && !$parentPhone) {
                    $this->warn("No contact numbers for alpha student: {$studentName}");
                }
            }

            $this->info("Alpha notifications queued for " . count($alphaStudents) . " students.");
        } else {
            $this->info("No alpha students today. Skipping alpha notifications.");
        }

        // Mark done
        Setting::updateOrCreate(
            ['setting_key' => 'last_daily_report_date'],
            ['setting_value' => $today]
        );
    }
}
