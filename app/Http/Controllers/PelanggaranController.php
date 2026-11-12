<?php

namespace App\Http\Controllers;

use App\Models\KategoriPelanggaran;
use App\Models\Kelas;
use App\Models\Pelanggaran;
use App\Models\Siswa;
use App\Models\School;
use App\Services\FcmNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Barryvdh\DomPDF\Facade\Pdf;

class PelanggaranController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        $query = Pelanggaran::with(['student.kelas', 'kategoriPelanggaran', 'recorder'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId));

        $isWaliKelas = $user->isWaliKelas() && $user->guru;
        $waliKelasIds = $isWaliKelas ? $user->getManagedKelasIds() : [];

        if ($isWaliKelas) {
            $query->whereHas('student', function ($q) use ($waliKelasIds) {
                $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds);
            });
        }

        $statsQuery = clone $query;
        $totalAll    = (clone $statsQuery)->count();
        $totalRingan = (clone $statsQuery)->where('kategori', 'ringan')->count();
        $totalSedang = (clone $statsQuery)->where('kategori', 'sedang')->count();
        $totalBerat  = (clone $statsQuery)->where('kategori', 'berat')->count();
        $totalToday  = (clone $statsQuery)->whereDate('tanggal', today())->count();
        $totalPoin   = (int) (clone $statsQuery)->sum('poin');

        if ($request->filled('kategori') && in_array($request->kategori, ['ringan', 'sedang', 'berat'])) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('status') && in_array($request->status, ['tercatat', 'diproses', 'selesai'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kelas_id')) {
            $query->whereHas('student', fn($q) => $q->where('kelas_id', $request->kelas_id));
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_selesai);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('judul_pelanggaran', 'LIKE', "%{$search}%")
                    ->orWhere('keterangan', 'LIKE', "%{$search}%")
                    ->orWhere('tindak_lanjut', 'LIKE', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('nama', 'LIKE', "%{$search}%")
                            ->orWhere('nis', 'LIKE', "%{$search}%");
                    });
            });
        }

        $pelanggarans = $query->latest('tanggal')->latest('id')->paginate(15)->withQueryString();

        $kelasList = Kelas::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
            ->where('is_active_attendance', true)
            ->orderBy('nama_kelas')
            ->get();

        $siswaList = Siswa::with('kelas:id,nama_kelas')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
            ->orderBy('nama')
            ->get(['id', 'nama', 'nis', 'kelas_id']);

        $kategoriList = KategoriPelanggaran::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('kategori')
            ->orderBy('nama')
            ->get();

        return view('pelanggaran.index', compact(
            'pelanggarans',
            'kelasList',
            'siswaList',
            'kategoriList',
            'totalAll',
            'totalRingan',
            'totalSedang',
            'totalBerat',
            'totalToday',
            'totalPoin'
        ));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        $validated = $request->validate([
            'student_id'              => 'required|exists:siswa,id',
            'kategori_pelanggaran_id' => 'nullable|exists:kategori_pelanggarans,id',
            'tanggal'                 => 'required|date',
            'waktu'                   => 'nullable|string|max:10',
            'judul_pelanggaran'       => 'required_without:kategori_pelanggaran_id|nullable|string|max:200',
            'kategori'                => 'required_without:kategori_pelanggaran_id|nullable|in:ringan,sedang,berat',
            'poin'                    => 'nullable|integer|min:0|max:1000',
            'keterangan'              => 'nullable|string|max:2000',
            'tindak_lanjut'           => 'nullable|string|max:1000',
            'status'                  => 'nullable|in:tercatat,diproses,selesai',
            'bukti_foto'              => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        $student = Siswa::findOrFail($validated['student_id']);
        if ($schoolId && (int) $student->school_id !== (int) $schoolId) {
            abort(403, 'Siswa bukan berasal dari sekolah Anda.');
        }

        $master = !empty($validated['kategori_pelanggaran_id'])
            ? KategoriPelanggaran::find($validated['kategori_pelanggaran_id'])
            : null;

        $judul = ($validated['judul_pelanggaran'] ?? null) ?: ($master?->nama ?? 'Pelanggaran Tata Tertib');
        $kategori = ($validated['kategori'] ?? null) ?: ($master?->kategori ?? 'ringan');
        $poin = $validated['poin'] ?? ($master?->poin ?? 5);
        $tindakLanjut = $validated['tindak_lanjut'] ?? $master?->sanksi_default;

        $filePath = $request->hasFile('bukti_foto')
            ? $request->file('bukti_foto')->store('pelanggaran', 'public')
            : null;

        $pelanggaran = Pelanggaran::create([
            'school_id'               => $student->school_id,
            'student_id'              => $student->id,
            'kategori_pelanggaran_id' => $master?->id,
            'recorded_by'             => $user->id,
            'code'                    => Pelanggaran::generateCode(),
            'tanggal'                 => Carbon::parse($validated['tanggal'])->format('Y-m-d'),
            'waktu'                   => $validated['waktu'] ?? now()->format('H:i'),
            'judul_pelanggaran'       => $judul,
            'kategori'                => $kategori,
            'poin'                    => $poin,
            'keterangan'              => $validated['keterangan'] ?? null,
            'tindak_lanjut'           => $tindakLanjut,
            'bukti_foto'              => $filePath,
            'status'                  => $validated['status'] ?? 'tercatat',
        ]);

        try {
            FcmNotificationService::sendToSiswa(
                $student,
                "Catatan Pelanggaran ({$pelanggaran->kategori_label})",
                "{$pelanggaran->judul_pelanggaran} (+{$pelanggaran->poin} poin) tercatat pada " . $pelanggaran->tanggal->format('d/m/Y'),
                [
                    'type'           => 'student_violation_recorded',
                    'pelanggaran_id' => (string) $pelanggaran->id,
                    'code'           => $pelanggaran->code,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning("FCM violation notification failed: " . $e->getMessage());
        }

        return back()->with('success', "Catatan pelanggaran {$pelanggaran->code} an. {$student->nama} berhasil disimpan.");
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $pelanggaran = Pelanggaran::findOrFail($id);

        if ($user->school_id && (int) $pelanggaran->school_id !== (int) $user->school_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $validated = $request->validate([
            'status'        => 'required|in:tercatat,diproses,selesai',
            'tindak_lanjut' => 'nullable|string|max:1000',
            'keterangan'    => 'nullable|string|max:2000',
        ]);

        $pelanggaran->update([
            'status'        => $validated['status'],
            'tindak_lanjut' => $validated['tindak_lanjut'] ?? $pelanggaran->tindak_lanjut,
            'keterangan'    => array_key_exists('keterangan', $validated) ? $validated['keterangan'] : $pelanggaran->keterangan,
        ]);

        return back()->with('success', "Status pelanggaran {$pelanggaran->code} berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $pelanggaran = Pelanggaran::findOrFail($id);

        if ($user->school_id && (int) $pelanggaran->school_id !== (int) $user->school_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if ($pelanggaran->bukti_foto && Storage::disk('public')->exists($pelanggaran->bukti_foto)) {
            Storage::disk('public')->delete($pelanggaran->bukti_foto);
        }

        $pelanggaran->delete();

        return back()->with('success', 'Catatan pelanggaran berhasil dihapus.');
    }

    public function storeKategori(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id ?? 1;

        $validated = $request->validate([
            'kode'           => 'nullable|string|max:30',
            'nama'           => 'required|string|max:150',
            'kategori'       => 'required|in:ringan,sedang,berat',
            'poin'           => 'required|integer|min:1|max:1000',
            'sanksi_default' => 'nullable|string|max:500',
        ]);

        KategoriPelanggaran::create([
            'school_id'      => $schoolId,
            'kode'           => $validated['kode'] ?? null,
            'nama'           => $validated['nama'],
            'kategori'       => $validated['kategori'],
            'poin'           => $validated['poin'],
            'sanksi_default' => $validated['sanksi_default'] ?? null,
            'is_active'      => true,
        ]);

        return back()->with('success', 'Master kategori pelanggaran berhasil ditambahkan.');
    }

    public function destroyKategori($id)
    {
        $user = auth()->user();
        $kategori = KategoriPelanggaran::findOrFail($id);

        if ($user->school_id && (int) $kategori->school_id !== (int) $user->school_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $kategori->delete();

        return back()->with('success', 'Kategori pelanggaran berhasil dihapus.');
    }

    /**
     * Rekapitulasi Pelanggaran Siswa per Periode, Kelas, Kategori, & Status.
     */
    public function rekap(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $isWaliKelas = $user->isWaliKelas() && $user->guru;
        $waliKelasIds = $isWaliKelas ? $user->getManagedKelasIds() : [];

        $query = Pelanggaran::with(['student.kelas', 'kategoriPelanggaran', 'recorder'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->whereBetween('tanggal', [$startDate, $endDate]);

        if ($isWaliKelas) {
            $query->whereHas('student', function ($q) use ($waliKelasIds) {
                $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds);
            });
        }

        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->whereHas('student', fn($q) => $q->where('kelas_id', $request->kelas_id));
        }

        if ($request->filled('kategori') && $request->kategori !== 'all') {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('judul_pelanggaran', 'LIKE', "%{$search}%")
                    ->orWhere('keterangan', 'LIKE', "%{$search}%")
                    ->orWhere('tindak_lanjut', 'LIKE', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('nama', 'LIKE', "%{$search}%")
                            ->orWhere('nis', 'LIKE', "%{$search}%");
                    });
            });
        }

        $statsQuery = clone $query;
        $totalKasus   = (clone $statsQuery)->count();
        $totalPoin    = (int) (clone $statsQuery)->sum('poin');
        $totalRingan  = (clone $statsQuery)->where('kategori', 'ringan')->count();
        $totalSedang  = (clone $statsQuery)->where('kategori', 'sedang')->count();
        $totalBerat   = (clone $statsQuery)->where('kategori', 'berat')->count();
        $totalSelesai = (clone $statsQuery)->where('status', 'selesai')->count();

        // Rekap agregat per siswa (Top Siswa Pelanggar / Akumulasi Poin per Siswa)
        $topSiswa = (clone $query)
            ->selectRaw('student_id, COUNT(*) as total_pelanggaran, SUM(poin) as total_poin')
            ->groupBy('student_id')
            ->orderByDesc('total_poin')
            ->with('student.kelas')
            ->limit(10)
            ->get();

        $pelanggarans = $query->latest('tanggal')->latest('id')->paginate(20)->withQueryString();

        $kelasList = Kelas::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
            ->where('is_active_attendance', true)
            ->orderBy('nama_kelas')
            ->get();

        return view('pelanggaran.rekap', compact(
            'pelanggarans',
            'kelasList',
            'startDate',
            'endDate',
            'totalKasus',
            'totalPoin',
            'totalRingan',
            'totalSedang',
            'totalBerat',
            'totalSelesai',
            'topSiswa'
        ));
    }

    /**
     * Export Rekap Pelanggaran ke Excel (.xlsx)
     */
    public function exportExcel(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id ?? 0;

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $isWaliKelas = $user->isWaliKelas() && $user->guru;
        $waliKelasIds = $isWaliKelas ? $user->getManagedKelasIds() : [];

        $query = Pelanggaran::with(['student.kelas', 'kategoriPelanggaran', 'recorder'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->whereBetween('tanggal', [$startDate, $endDate]);

        if ($isWaliKelas) {
            $query->whereHas('student', function ($q) use ($waliKelasIds) {
                $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds);
            });
        }

        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->whereHas('student', fn($q) => $q->where('kelas_id', $request->kelas_id));
        }

        if ($request->filled('kategori') && $request->kategori !== 'all') {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('judul_pelanggaran', 'LIKE', "%{$search}%")
                    ->orWhere('keterangan', 'LIKE', "%{$search}%")
                    ->orWhere('tindak_lanjut', 'LIKE', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('nama', 'LIKE', "%{$search}%")
                            ->orWhere('nis', 'LIKE', "%{$search}%");
                    });
            });
        }

        $items = $query->orderBy('tanggal', 'asc')->orderBy('id', 'asc')->get();
        $school = $user->school ?? School::find($schoolId);
        $schoolName = $school?->name ?? 'Sekolah';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Pelanggaran');

        // Header Title
        $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI PELANGGARAN SISWA');
        $sheet->setCellValue('A2', strtoupper($schoolName));
        $sheet->setCellValue('A3', "Periode: {$startDate} s.d. {$endDate}");

        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');
        $sheet->mergeCells('A3:J3');

        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);
        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Table Columns
        $headers = ['No', 'Kode', 'Tanggal', 'NIS', 'Nama Siswa', 'Kelas', 'Pelanggaran', 'Kategori', 'Poin', 'Status', 'Tindak Lanjut'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '5', $h);
            $col++;
        }

        $sheet->getStyle('A5:K5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DC2626']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $row = 6;
        $no = 1;
        $totalPoinSum = 0;

        foreach ($items as $p) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $p->code);
            $sheet->setCellValue('C' . $row, $p->tanggal?->format('d/m/Y') . ' ' . $p->waktu);
            $sheet->setCellValueExplicit('D' . $row, $p->student?->nis ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('E' . $row, $p->student?->nama ?? '-');
            $sheet->setCellValue('F' . $row, $p->student?->kelas?->nama_kelas ?? '-');
            $sheet->setCellValue('G' . $row, $p->judul_pelanggaran);
            $sheet->setCellValue('H' . $row, ucfirst($p->kategori));
            $sheet->setCellValue('I' . $row, $p->poin);
            $sheet->setCellValue('J' . $row, ucfirst($p->status));
            $sheet->setCellValue('K' . $row, $p->tindak_lanjut ?? '-');

            $totalPoinSum += (int)$p->poin;
            $row++;
        }

        // Summary Total Row
        if ($row > 6) {
            $sheet->setCellValue('A' . $row, 'TOTAL POIN');
            $sheet->mergeCells('A' . $row . ':H' . $row);
            $sheet->setCellValue('I' . $row, $totalPoinSum);
            $sheet->getStyle('A' . $row . ':K' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('A5:K' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        foreach (range('A', 'K') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = 'rekap_pelanggaran_' . str_replace('-', '', $startDate) . '_' . str_replace('-', '', $endDate) . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Rekap Pelanggaran ke PDF
     */
    public function exportPdf(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id ?? 0;

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $isWaliKelas = $user->isWaliKelas() && $user->guru;
        $waliKelasIds = $isWaliKelas ? $user->getManagedKelasIds() : [];

        $query = Pelanggaran::with(['student.kelas', 'kategoriPelanggaran', 'recorder'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->whereBetween('tanggal', [$startDate, $endDate]);

        if ($isWaliKelas) {
            $query->whereHas('student', function ($q) use ($waliKelasIds) {
                $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds);
            });
        }

        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->whereHas('student', fn($q) => $q->where('kelas_id', $request->kelas_id));
        }

        if ($request->filled('kategori') && $request->kategori !== 'all') {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('judul_pelanggaran', 'LIKE', "%{$search}%")
                    ->orWhere('keterangan', 'LIKE', "%{$search}%")
                    ->orWhere('tindak_lanjut', 'LIKE', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('nama', 'LIKE', "%{$search}%")
                            ->orWhere('nis', 'LIKE', "%{$search}%");
                    });
            });
        }

        $items = $query->orderBy('tanggal', 'asc')->orderBy('id', 'asc')->get();
        $school = $user->school ?? School::find($schoolId);

        $schoolName = $school?->name ?? 'Sekolah';
        $schoolAddress = $school?->address ?? '';
        $kopSurat = $school?->kop_surat ?? null;

        $totalKasus = $items->count();
        $totalPoin  = $items->sum('poin');

        $pdf = Pdf::loadView('pelanggaran.export-pdf', compact(
            'items',
            'startDate',
            'endDate',
            'schoolName',
            'schoolAddress',
            'kopSurat',
            'totalKasus',
            'totalPoin'
        ));

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('rekap_pelanggaran_' . date('Ymd_His') . '.pdf');
    }
}

