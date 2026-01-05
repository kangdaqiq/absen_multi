<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use App\Models\MessageQueue;
use App\Models\Siswa;
use App\Models\Attendance;
use Carbon\Carbon;

class AutoBolosCommand extends Command
{
    protected $signature = 'absen:process-daily {--force}';
    protected $description = 'Process daily attendance: Mark Bolos (B) if no checkout, and Alpha (A) if no checkin by 13:30';

    public function handle()
    {
        $today = now()->format('Y-m-d');

        // 1. Debounce check
        $lastRun = Setting::where('setting_key', 'last_daily_process_date')->value('setting_value');
        if ($lastRun === $today && !$this->option('force')) {
            $this->info("Daily Process already ran today ($today). Use --force to run anyway.");
            return;
        }

        $this->info("Running Daily Attendance Process for $today...");

        // 2. Check if Sunday
        if (Carbon::parse($today)->isSunday()) {
            $this->info("Today is Sunday. Process skipped.");
            return;
        }

        // 3. Check Hari Libur
        if (\App\Models\HariLibur::where('tanggal', $today)->exists()) {
            $this->info("Today is Holiday. Process skipped.");
            return;
        }

        // --- STEP 1: Mark BOLOS (Checked In but No Checkout) ---
        // Only for students in Active Attendance Classes
        $countB = DB::table('attendance')
            ->join('siswa', 'attendance.student_id', '=', 'siswa.id')
            ->join('kelas', 'siswa.kelas_id', '=', 'kelas.id')
            ->where('attendance.tanggal', $today)
            ->whereNotNull('attendance.jam_masuk')
            ->whereNull('attendance.jam_pulang')
            ->whereNotIn('attendance.status', ['I', 'S'])
            ->where('kelas.is_active_attendance', true)
            ->update([
                'attendance.status' => 'B',
                'attendance.keterangan' => DB::raw("CONCAT(IFNULL(attendance.keterangan, ''), ' [Auto: Tidak Absen Pulang]')"),
                'attendance.updated_at' => now()
            ]);

        $this->info("Marked $countB records as Bolos (B).");


        // --- STEP 2: Mark ALPHA (No Record at all) ---
        // 2. Get all students who don't have attendance record for today
        // AND belong to a class with active attendance
        $studentsWithoutAttendance = Siswa::whereDoesntHave('attendance', function ($query) use ($today) {
            $query->where('tanggal', $today);
        })
            ->whereHas('kelas', function ($q) {
                $q->where('is_active_attendance', true);
            })
            ->get();

        $countA = 0;
        foreach ($studentsWithoutAttendance as $s) {
            \App\Models\Attendance::create([
                'student_id' => $s->id,
                'tanggal' => $today,
                'jam_masuk' => null,
                'jam_pulang' => null,
                'jam_kerja' => null,
                'status' => 'A',
                'keterangan' => 'Alpha (Tidak Hadir)',
                'lokasi_masuk' => 'System',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $countA++;
        }

        $this->info("Marked $countA students as Alpha (A).");

        // Update Setting
        Setting::updateOrCreate(
            ['setting_key' => 'last_daily_process_date'],
            ['setting_value' => $today]
        );

        // Send Absence Report
        $this->sendAbsenceReport($today);
    }

    private function sendAbsenceReport($today)
    {
        // Get classes with WhatsApp Group ID
        $kelasWithGroupId = \App\Models\Kelas::whereNotNull('wa_group_id')
            ->where('wa_group_id', '!=', '')
            ->where('is_active_attendance', true)
            ->where('is_active_report', true)
            ->get();

        $legacyTarget = Setting::where('setting_key', 'report_target_jid')->value('setting_value');

        if ($kelasWithGroupId->isEmpty() && !$legacyTarget) {
            $this->warn("No classes with WhatsApp Group ID and legacy target not set. Skipping absence report.");
            return;
        }

        // Get all absent students (A, B, I, S)
        // Ensure we only retrieve those from Active Attendance Classes
        $absentStudents = Attendance::where('tanggal', $today)
            ->whereIn('status', ['A', 'B', 'I', 'S'])
            ->whereHas('student.kelas', function ($q) {
                $q->where('is_active_attendance', true);
            })
            ->with(['student.kelas'])
            ->orderBy('status')
            ->get();

        if ($absentStudents->isEmpty()) {
            $this->info("No absent students today. Skipping absence report.");
            return;
        }

        // Group by status
        $grouped = $absentStudents->groupBy('status');

        // Build message
        $message = "📋 *LAPORAN FINAL KETIDAKHADIRAN*\n";
        $message .= "📅 Tanggal: " . Carbon::parse($today)->format('d/m/Y') . "\n";
        $message .= str_repeat("─", 30) . "\n\n";

        $statusLabels = [
            'A' => '❌ Alpha',
            'B' => '🏃 Bolos (Tidak Absen Pulang)',
            'I' => '📝 Izin',
            'S' => '🤒 Sakit'
        ];

        $totalAbsent = 0;
        foreach (['A', 'B', 'I', 'S'] as $status) {
            if (!isset($grouped[$status]))
                continue;

            $students = $grouped[$status];
            $count = $students->count();
            $totalAbsent += $count;

            $message .= "*{$statusLabels[$status]}* ({$count} siswa)\n";
            foreach ($students as $att) {
                $kelas = $att->student->kelas->nama_kelas ?? '-';
                $message .= "  • {$att->student->nama} ({$kelas})\n";
            }
            $message .= "\n";
        }

        $message .= str_repeat("─", 30) . "\n";
        $message .= "Total: *{$totalAbsent} Siswa* tidak hadir\n";
        $message .= "\n_Laporan otomatis setelah proses harian_";

        // Queue message to all classes with WhatsApp Group ID
        foreach ($kelasWithGroupId as $kelas) {
            MessageQueue::create([
                'phone_number' => $kelas->wa_group_id,
                'message' => $message,
                'status' => 'pending',
                'created_at' => now()
            ]);
        }

        // Also send to legacy target if exists
        if ($legacyTarget) {
            MessageQueue::create([
                'phone_number' => $legacyTarget,
                'message' => $message,
                'status' => 'pending',
                'created_at' => now()
            ]);
        }

        $this->info("✓ Absence report queued to " . ($kelasWithGroupId->count() + ($legacyTarget ? 1 : 0)) . " recipients ({$totalAbsent} absent students)");
    }
}
