<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AbsenceReportController extends Controller
{
    public function index(Request $request)
    {
        // Get settings
        $threshold = (int) Setting::where('setting_key', 'absence_threshold_days')->value('setting_value') ?? 3;
        $periodDays = (int) Setting::where('setting_key', 'absence_check_period_days')->value('setting_value') ?? 7;

        // Get filter parameters
        $kelasId = $request->get('kelas_id');

        // Calculate date range from period
        $startDate = now()->subDays($periodDays)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        // Query students with frequent absences
        $query = Attendance::selectRaw('student_id, COUNT(*) as absence_count')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->whereIn('status', ['A', 'B'])
            ->whereHas('student.kelas', function ($q) use ($kelasId) {
                $q->where('is_active_attendance', true);
                if ($kelasId) {
                    $q->where('kelas.id', $kelasId);
                }
            })
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) >= ?', [$customThreshold]);

        $frequentAbsences = $query->with(['student.kelas'])->get();

        // Get detailed breakdown for each student
        $students = [];
        foreach ($frequentAbsences as $record) {
            $student = $record->student;

            // Get detailed attendance records
            $details = Attendance::where('student_id', $student->id)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->whereIn('status', ['A', 'B'])
                ->get();

            $alphaCount = $details->where('status', 'A')->count();
            $bolosCount = $details->where('status', 'B')->count();

            $students[] = [
                'id' => $student->id,
                'nama' => $student->nama,
                'nis' => $student->nis,
                'kelas' => $student->kelas->nama_kelas ?? '-',
                'kelas_id' => $student->kelas_id,
                'wa_ortu' => $student->wa_ortu,
                'total' => $record->absence_count,
                'alpha' => $alphaCount,
                'bolos' => $bolosCount,
                'details' => $details
            ];
        }

        // Sort by total absences (descending)
        usort($students, function ($a, $b) {
            return $b['total'] - $a['total'];
        });

        // Get all classes for filter
        $kelasList = Kelas::where('is_active_attendance', true)
            ->orderBy('nama_kelas')
            ->get();

        return view('absence-report.index', compact(
            'students',
            'kelasList',
            'kelasId',
            'startDate',
            'endDate',
            'threshold',
            'periodDays'
        ));
    }

    public function export(Request $request)
    {
        // Get settings
        $threshold = (int) Setting::where('setting_key', 'absence_threshold_days')->value('setting_value') ?? 3;
        $periodDays = (int) Setting::where('setting_key', 'absence_check_period_days')->value('setting_value') ?? 7;

        // Get filter parameters
        $kelasId = $request->get('kelas_id');

        // Calculate date range from period
        $startDate = now()->subDays($periodDays)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        // Query students with frequent absences
        $query = Attendance::selectRaw('student_id, COUNT(*) as absence_count')
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->whereIn('status', ['A', 'B'])
            ->whereHas('student.kelas', function ($q) use ($kelasId) {
                $q->where('is_active_attendance', true);
                if ($kelasId) {
                    $q->where('kelas.id', $kelasId);
                }
            })
            ->groupBy('student_id')
            ->havingRaw('COUNT(*) >= ?', [$customThreshold]);

        $frequentAbsences = $query->with(['student.kelas'])->get();

        // Prepare CSV data
        $csvData = [];
        $csvData[] = ['NIS', 'Nama', 'Kelas', 'Total Tidak Masuk', 'Alpha', 'Bolos', 'No WA Ortu'];

        foreach ($frequentAbsences as $record) {
            $student = $record->student;

            $details = Attendance::where('student_id', $student->id)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->whereIn('status', ['A', 'B'])
                ->get();

            $alphaCount = $details->where('status', 'A')->count();
            $bolosCount = $details->where('status', 'B')->count();

            $csvData[] = [
                $student->nis,
                $student->nama,
                $student->kelas->nama_kelas ?? '-',
                $record->absence_count,
                $alphaCount,
                $bolosCount,
                $student->wa_ortu ?? '-'
            ];
        }

        // Generate CSV
        $filename = 'laporan_ketidakhadiran_' . $startDate . '_' . $endDate . '.csv';

        $handle = fopen('php://output', 'w');
        ob_start();

        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }

        fclose($handle);
        $csv = ob_get_clean();

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
