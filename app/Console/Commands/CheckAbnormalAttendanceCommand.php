<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\MessageQueue;
use App\Models\Setting;
use Carbon\Carbon;

class CheckAbnormalAttendanceCommand extends Command
{
    protected $signature = 'absen:check-abnormal';
    protected $description = 'Daily check for students with abnormal attendance (Alpha/Bolos) > Threshold';

    public function handle()
    {
        // Get settings
        $threshold = (int) Setting::where('setting_key', 'absence_threshold_days')->value('setting_value') ?? 3;
        $periodDays = (int) Setting::where('setting_key', 'absence_check_period_days')->value('setting_value') ?? 7;
        $enabled = Setting::where('setting_key', 'absence_notification_enabled')->value('setting_value') ?? 'true';

        if ($enabled !== 'true') {
            $this->info("Abnormal attendance check is disabled in settings.");
            return;
        }

        $startDate = now()->subDays($periodDays)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');
        $today = now()->format('Y-m-d');

        $this->info("Checking absences from $startDate to $endDate (threshold: $threshold)...");

        // 1. First, find students who exceed the threshold in the period
        $candidates = Attendance::selectRaw('student_id, COUNT(*) as absence_count')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->whereIn('status', ['A', 'B'])
            ->whereHas('student.kelas', function ($q) {
                $q->where('is_active_attendance', true);
            })
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) >= ?', [$threshold])
            ->with(['student.kelas.waliKelas'])
            ->get();

        if ($candidates->isEmpty()) {
            $this->info("No students with frequent absences found.");
            return;
        }

        // 2. Filter: Only report if they are ALSO absent TODAY (A or B)
        $frequentAbsences = collect();
        foreach ($candidates as $candidate) {
            $isAbsentToday = Attendance::where('student_id', $candidate->student_id)
                ->where('tanggal', $today)
                ->whereIn('status', ['A', 'B'])
                ->exists();

            if ($isAbsentToday) {
                $frequentAbsences->push($candidate);
            }
        }

        if ($frequentAbsences->isEmpty()) {
            $this->info("Students found with high absences, but none are absent today. No report sent.");
            return;
        }

        // Group by class
        $byClass = [];
        foreach ($frequentAbsences as $record) {
            $student = $record->student;
            $kelas = $student->kelas;

            if (!$kelas)
                continue;

            $kelasId = $kelas->id;
            if (!isset($byClass[$kelasId])) {
                $byClass[$kelasId] = [
                    'kelas' => $kelas,
                    'students' => []
                ];
            }

            // Get detailed breakdown
            $details = Attendance::where('student_id', $student->id)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->whereIn('status', ['A', 'B'])
                ->get();

            $alphaCount = $details->where('status', 'A')->count();
            $bolosCount = $details->where('status', 'B')->count();

            $byClass[$kelasId]['students'][] = [
                'student' => $student,
                'total' => $record->absence_count,
                'alpha' => $alphaCount,
                'bolos' => $bolosCount
            ];
        }

        $this->info("Found " . $frequentAbsences->count() . " students to report.");

        // Send global report only
        $this->sendGlobalReport($byClass, $startDate, $endDate);

        $this->info("✓ Abnormal attendance report sent successfully.");
    }

    private function sendGlobalReport($byClass, $startDate, $endDate)
    {
        $message = $this->buildGlobalMessage($byClass, $startDate, $endDate);

        // Send to classes with wa_group_id
        $kelasWithGroupId = Kelas::whereNotNull('wa_group_id')
            ->where('wa_group_id', '!=', '')
            ->where('is_active_attendance', true)
            ->where('is_active_report', true)
            ->get();

        foreach ($kelasWithGroupId as $kelas) {
            MessageQueue::create([
                'phone_number' => $kelas->wa_group_id,
                'message' => $message,
                'status' => 'pending',
                'created_at' => now()
            ]);
            $this->info("Queued report to {$kelas->nama_kelas} group");
        }

        // Send to teacher group if configured
        $teacherGroupId = Setting::where('setting_key', 'report_target_jid')->value('setting_value');
        if ($teacherGroupId) {
            MessageQueue::create([
                'phone_number' => $teacherGroupId,
                'message' => $message,
                'status' => 'pending',
                'created_at' => now()
            ]);
            $this->info("Queued report to teacher group");
        }

        if ($kelasWithGroupId->isEmpty() && !$teacherGroupId) {
            $this->warn("No groups configured for sending reports.");
        }
    }

    private function buildGlobalMessage($byClass, $startDate, $endDate)
    {
        $message = "⚠️ *PERINGATAN SISWA BERMASALAH*\n";
        $message .= "📅 Cek Periode: " . Carbon::parse($startDate)->format('d/m/Y') . " - " . Carbon::parse($endDate)->format('d/m/Y') . "\n";
        $message .= "ℹ️ *Siswa di bawah ini Absen/Bolos HARI INI dan sudah melebihi batas toleransi.*\n";
        $message .= str_repeat("─", 40) . "\n\n";

        $totalStudents = 0;
        foreach ($byClass as $data) {
            $kelas = $data['kelas'];
            $students = $data['students'];
            $totalStudents += count($students);

            $message .= "*{$kelas->nama_kelas}* (" . count($students) . " siswa)\n";
            foreach ($students as $studentData) {
                $s = $studentData['student'];
                $breakdown = [];
                if ($studentData['alpha'] > 0)
                    $breakdown[] = "{$studentData['alpha']} Alpha";
                if ($studentData['bolos'] > 0)
                    $breakdown[] = "{$studentData['bolos']} Bolos";

                $message .= "  • {$s->nama} - Total {$studentData['total']}x (" . implode(', ', $breakdown) . ")\n";
            }
            $message .= "\n";
        }

        $message .= str_repeat("─", 40) . "\n";
        $message .= "Total: *{$totalStudents} siswa* bermasalah hari ini\n\n";
        $message .= "_Laporan otomatis sistem absensi_";

        return $message;
    }
}
