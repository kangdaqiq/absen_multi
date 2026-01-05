<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\MessageQueue;
use App\Models\Setting;
use Carbon\Carbon;

class WeeklyAbsenceSummaryCommand extends Command
{
    protected $signature = 'absen:weekly-absence-summary';
    protected $description = 'Send weekly summary of students with frequent absences (Alpha/Bolos)';

    public function handle()
    {
        // Get settings
        $threshold = (int) Setting::where('setting_key', 'absence_threshold_days')->value('setting_value') ?? 3;
        $periodDays = (int) Setting::where('setting_key', 'absence_check_period_days')->value('setting_value') ?? 7;
        $enabled = Setting::where('setting_key', 'absence_notification_enabled')->value('setting_value') ?? 'true';

        if ($enabled !== 'true') {
            $this->info("Weekly absence summary is disabled in settings.");
            return;
        }

        $startDate = now()->subDays($periodDays)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $this->info("Checking absences from $startDate to $endDate (threshold: $threshold)...");

        // Query students with frequent absences
        $frequentAbsences = Attendance::selectRaw('student_id, COUNT(*) as absence_count')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->whereIn('status', ['A', 'B'])
            ->whereHas('student.kelas', function ($q) {
                $q->where('is_active_attendance', true);
            })
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) >= ?', [$threshold])
            ->with(['student.kelas.waliKelas'])
            ->get();

        if ($frequentAbsences->isEmpty()) {
            $this->info("No students with frequent absences found.");
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

        $this->info("Found " . $frequentAbsences->count() . " students with frequent absences.");

        // Send notifications
        $this->sendGlobalReport($byClass, $startDate, $endDate);
        $this->sendWaliKelasReports($byClass, $startDate, $endDate);
        $this->sendParentNotifications($byClass, $startDate, $endDate);

        $this->info("✓ Weekly absence summary sent successfully.");
    }

    private function sendGlobalReport($byClass, $startDate, $endDate)
    {
        // Send to classes with wa_group_id
        $kelasWithGroupId = Kelas::whereNotNull('wa_group_id')
            ->where('wa_group_id', '!=', '')
            ->where('is_active_attendance', true)
            ->where('is_active_report', true)
            ->get();

        if ($kelasWithGroupId->isEmpty()) {
            $this->warn("No classes with WhatsApp Group ID configured.");
            return;
        }

        $message = $this->buildGlobalMessage($byClass, $startDate, $endDate);

        foreach ($kelasWithGroupId as $kelas) {
            MessageQueue::create([
                'phone_number' => $kelas->wa_group_id,
                'message' => $message,
                'status' => 'pending',
                'created_at' => now()
            ]);
            $this->info("Queued global report to {$kelas->nama_kelas} group");
        }
    }

    private function sendWaliKelasReports($byClass, $startDate, $endDate)
    {
        foreach ($byClass as $data) {
            $kelas = $data['kelas'];
            $wali = $kelas->waliKelas;

            if (!$wali || !$wali->no_wa) {
                continue;
            }

            $message = $this->buildWaliKelasMessage($kelas, $data['students'], $startDate, $endDate);

            MessageQueue::create([
                'phone_number' => $wali->no_wa,
                'message' => $message,
                'status' => 'pending',
                'created_at' => now()
            ]);

            $this->info("Queued report to wali kelas {$wali->nama} ({$kelas->nama_kelas})");
        }
    }

    private function sendParentNotifications($byClass, $startDate, $endDate)
    {
        $count = 0;
        foreach ($byClass as $data) {
            foreach ($data['students'] as $studentData) {
                $student = $studentData['student'];

                if (!$student->wa_ortu) {
                    continue;
                }

                $message = $this->buildParentMessage($student, $studentData, $startDate, $endDate);

                MessageQueue::create([
                    'phone_number' => $student->wa_ortu,
                    'message' => $message,
                    'status' => 'pending',
                    'created_at' => now()
                ]);

                $count++;
            }
        }

        $this->info("Queued notifications to $count parents");
    }

    private function buildGlobalMessage($byClass, $startDate, $endDate)
    {
        $message = "⚠️ *LAPORAN MINGGUAN KETIDAKHADIRAN BERLEBIHAN*\n";
        $message .= "📅 Periode: " . Carbon::parse($startDate)->format('d/m/Y') . " - " . Carbon::parse($endDate)->format('d/m/Y') . "\n";
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

                $message .= "  • {$s->nama} - {$studentData['total']}x (" . implode(', ', $breakdown) . ")\n";
            }
            $message .= "\n";
        }

        $message .= str_repeat("─", 40) . "\n";
        $message .= "Total: *{$totalStudents} siswa* memerlukan perhatian khusus\n\n";
        $message .= "_Mohon tindak lanjut untuk siswa tersebut_";

        return $message;
    }

    private function buildWaliKelasMessage($kelas, $students, $startDate, $endDate)
    {
        $message = "⚠️ *LAPORAN MINGGUAN - {$kelas->nama_kelas}*\n";
        $message .= "📅 Periode: " . Carbon::parse($startDate)->format('d/m/Y') . " - " . Carbon::parse($endDate)->format('d/m/Y') . "\n";
        $message .= str_repeat("─", 40) . "\n\n";

        $message .= "Siswa dengan ketidakhadiran berlebihan:\n\n";
        foreach ($students as $studentData) {
            $s = $studentData['student'];
            $breakdown = [];
            if ($studentData['alpha'] > 0)
                $breakdown[] = "{$studentData['alpha']} Alpha";
            if ($studentData['bolos'] > 0)
                $breakdown[] = "{$studentData['bolos']} Bolos";

            $message .= "• *{$s->nama}*\n";
            $message .= "  Tidak masuk: {$studentData['total']}x (" . implode(', ', $breakdown) . ")\n";
            if ($s->wa_ortu) {
                $message .= "  Ortu: {$s->wa_ortu}\n";
            }
            $message .= "\n";
        }

        $message .= "_Mohon koordinasi dengan orang tua siswa_";

        return $message;
    }

    private function buildParentMessage($student, $studentData, $startDate, $endDate)
    {
        $kelas = $student->kelas->nama_kelas ?? '-';

        $message = "⚠️ *PEMBERITAHUAN KETIDAKHADIRAN*\n\n";
        $message .= "Yth. Orang Tua/Wali dari:\n";
        $message .= "*{$student->nama}* ({$kelas})\n\n";
        $message .= "Kami informasikan bahwa putra/putri Anda memiliki catatan ketidakhadiran dalam periode:\n";
        $message .= "📅 " . Carbon::parse($startDate)->format('d/m/Y') . " - " . Carbon::parse($endDate)->format('d/m/Y') . "\n\n";

        $message .= "Detail:\n";
        if ($studentData['alpha'] > 0) {
            $message .= "• Alpha (Tanpa Keterangan): {$studentData['alpha']}x\n";
        }
        if ($studentData['bolos'] > 0) {
            $message .= "• Bolos (Tidak Absen Pulang): {$studentData['bolos']}x\n";
        }
        $message .= "\nTotal: *{$studentData['total']} hari* tidak hadir\n\n";

        $message .= "Mohon perhatian dan bimbingan kepada putra/putri Anda.\n\n";
        $message .= "_Terima kasih atas kerjasamanya_";

        return $message;
    }
}
