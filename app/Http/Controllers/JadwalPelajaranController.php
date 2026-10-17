<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelajaran;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\JamPelajaran;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JadwalPelajaranController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = auth()->user()->school_id;

        $query = JadwalPelajaran::where('school_id', $schoolId)
            ->with(['guru', 'kelas', 'mapel', 'jamPelajaran']);

        if ($request->filled('hari')) {
            $query->where('hari', $request->hari);
        }

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        if ($request->filled('guru_id')) {
            $query->where('guru_id', $request->guru_id);
        }

        if ($request->filled('mapel_id')) {
            $query->where('mapel_id', $request->mapel_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('mapel', function ($m) use ($search) {
                    $m->where('nama_mapel', 'like', "%{$search}%");
                })->orWhereHas('guru', function ($g) use ($search) {
                    $g->where('nama', 'like', "%{$search}%");
                })->orWhereHas('kelas', function ($k) use ($search) {
                    $k->where('nama_kelas', 'like', "%{$search}%");
                })->orWhere('jam_ke', 'like', "%{$search}%")
                  ->orWhere('ruangan', 'like', "%{$search}%");
            });
        }

        // Urutkan berdasarkan hari (Senin - Minggu) lalu jam mulai
        $jadwals = $query->with(['guru', 'kelas', 'mapel', 'jamPelajaran', 'jamPelajaranAkhir'])
            ->orderByRaw("FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')")
            ->orderBy('jam_mulai')
            ->paginate(25)
            ->withQueryString();

        $kelases = Kelas::where('school_id', $schoolId)->orderBy('nama_kelas')->get();
        $gurus = Guru::where('school_id', $schoolId)->orderBy('nama')->get();
        $mapels = Mapel::where('school_id', $schoolId)->with('kelases:id,nama_kelas')->orderBy('nama_mapel')->get();
        $jamPelajarans = JamPelajaran::where('school_id', $schoolId)->where('is_active', true)->orderBy('urutan')->orderBy('jam_mulai')->get();

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        return view('jadwal-pelajaran.index', compact(
            'jadwals',
            'kelases',
            'gurus',
            'mapels',
            'jamPelajarans',
            'hariList'
        ));
    }

    public function store(Request $request)
    {
        $schoolId = auth()->user()->school_id;

        // Normalisasi kelas_ids (mendukung multi-select kelas_ids[] maupun single kelas_id)
        if ($request->has('kelas_ids') && is_array($request->kelas_ids)) {
            $kelasIds = array_values(array_unique(array_filter($request->kelas_ids)));
        } elseif ($request->filled('kelas_id')) {
            $kelasIds = [$request->kelas_id];
        } else {
            $kelasIds = [];
        }

        $request->merge(['kelas_ids' => $kelasIds]);

        $request->validate([
            'jam_pelajaran_id'       => 'required|exists:jam_pelajaran,id',
            'jam_pelajaran_id_akhir' => 'nullable|exists:jam_pelajaran,id',
            'guru_id'                => 'required|exists:guru,id',
            'kelas_ids'              => 'required|array|min:1',
            'kelas_ids.*'            => 'required|exists:kelas,id',
            'mapel_id'               => 'required|exists:mapel,id',
            'hari'                   => 'required|string|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'ruangan'                => 'nullable|string|max:100',
            'keterangan'             => 'nullable|string|max:255',
        ], [
            'kelas_ids.required' => 'Pilih minimal satu kelas.',
            'kelas_ids.min'      => 'Pilih minimal satu kelas.',
        ]);

        $jpAwal = JamPelajaran::findOrFail($request->jam_pelajaran_id);
        $jpAkhir = !empty($request->jam_pelajaran_id_akhir) && $request->jam_pelajaran_id_akhir != $jpAwal->id
            ? JamPelajaran::find($request->jam_pelajaran_id_akhir)
            : null;

        $jamMulai = Carbon::parse($jpAwal->jam_mulai)->format('H:i');
        $jamSelesai = $jpAkhir
            ? Carbon::parse($jpAkhir->jam_selesai)->format('H:i')
            : Carbon::parse($jpAwal->jam_selesai)->format('H:i');

        $jamKeAwal = $jpAwal->jam_ke ?: ($jpAwal->nama_jam ?? '-');
        if ($jpAkhir && $jpAkhir->id !== $jpAwal->id) {
            $jamKeAkhir = $jpAkhir->jam_ke ?: ($jpAkhir->nama_jam ?? '-');
            $jamKe = "{$jamKeAwal} - {$jamKeAkhir}";
        } else {
            $jamKe = $jamKeAwal;
        }

        $createdCount = 0;
        foreach ($kelasIds as $kId) {
            JadwalPelajaran::create([
                'school_id'               => $schoolId,
                'jam_pelajaran_id'        => $jpAwal->id,
                'jam_pelajaran_id_akhir'  => $jpAkhir?->id,
                'guru_id'                 => $request->guru_id,
                'kelas_id'                => $kId,
                'mapel_id'                => $request->mapel_id,
                'hari'                    => $request->hari,
                'jam_ke'                  => $jamKe,
                'jam_mulai'               => $jamMulai,
                'jam_selesai'             => $jamSelesai,
                'ruangan'                 => $request->ruangan,
                'keterangan'              => $request->keterangan,
            ]);
            $createdCount++;
        }

        $msg = $createdCount > 1 
            ? "Berhasil menambahkan jadwal pelajaran untuk {$createdCount} kelas sekaligus." 
            : 'Jadwal pembelajaran berhasil ditambahkan.';

        return redirect()->back()->with('success', $msg);
    }

    public function update(Request $request, $id)
    {
        $schoolId = auth()->user()->school_id;
        $jadwal = JadwalPelajaran::where('school_id', $schoolId)->findOrFail($id);

        // Normalisasi kelas_ids (mendukung multi-select kelas_ids[] maupun single kelas_id)
        if ($request->has('kelas_ids') && is_array($request->kelas_ids)) {
            $kelasIds = array_filter($request->kelas_ids);
        } elseif ($request->filled('kelas_id')) {
            $kelasIds = [$request->kelas_id];
        } else {
            $kelasIds = [];
        }
        $kelasIds = array_values(array_unique(array_filter($kelasIds)));
        $request->merge(['kelas_ids' => $kelasIds]);

        $request->validate([
            'jam_pelajaran_id'       => 'required|exists:jam_pelajaran,id',
            'jam_pelajaran_id_akhir' => 'nullable|exists:jam_pelajaran,id',
            'guru_id'                => 'required|exists:guru,id',
            'kelas_ids'              => 'required|array|min:1',
            'kelas_ids.*'            => 'required|exists:kelas,id',
            'mapel_id'               => 'required|exists:mapel,id',
            'hari'                   => 'required|string|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'ruangan'                => 'nullable|string|max:100',
            'keterangan'             => 'nullable|string|max:255',
        ], [
            'kelas_ids.required' => 'Pilih minimal satu kelas.',
            'kelas_ids.min'      => 'Pilih minimal satu kelas.',
        ]);

        $jpAwal = JamPelajaran::findOrFail($request->jam_pelajaran_id);
        $jpAkhir = !empty($request->jam_pelajaran_id_akhir) && $request->jam_pelajaran_id_akhir != $jpAwal->id
            ? JamPelajaran::find($request->jam_pelajaran_id_akhir)
            : null;

        $jamMulai = Carbon::parse($jpAwal->jam_mulai)->format('H:i');
        $jamSelesai = $jpAkhir
            ? Carbon::parse($jpAkhir->jam_selesai)->format('H:i')
            : Carbon::parse($jpAwal->jam_selesai)->format('H:i');

        $jamKeAwal = $jpAwal->jam_ke ?: ($jpAwal->nama_jam ?? '-');
        if ($jpAkhir && $jpAkhir->id !== $jpAwal->id) {
            $jamKeAkhir = $jpAkhir->jam_ke ?: ($jpAkhir->nama_jam ?? '-');
            $jamKe = "{$jamKeAwal} - {$jamKeAkhir}";
        } else {
            $jamKe = $jamKeAwal;
        }

        $firstKelasId = array_shift($kelasIds);

        // Update jadwal yang sedang diedit dengan kelas pertama
        $jadwal->update([
            'jam_pelajaran_id'        => $jpAwal->id,
            'jam_pelajaran_id_akhir'  => $jpAkhir?->id,
            'guru_id'                 => $request->guru_id,
            'kelas_id'                => $firstKelasId,
            'mapel_id'                => $request->mapel_id,
            'hari'                    => $request->hari,
            'jam_ke'                  => $jamKe,
            'jam_mulai'               => $jamMulai,
            'jam_selesai'             => $jamSelesai,
            'ruangan'                 => $request->ruangan,
            'keterangan'              => $request->keterangan,
        ]);

        // Buat jadwal baru untuk kelas-kelas tambahan jika user memilih lebih dari 1 kelas saat edit
        $extraCount = 0;
        foreach ($kelasIds as $extraKelasId) {
            JadwalPelajaran::create([
                'school_id'               => $schoolId,
                'jam_pelajaran_id'        => $jpAwal->id,
                'jam_pelajaran_id_akhir'  => $jpAkhir?->id,
                'guru_id'                 => $request->guru_id,
                'kelas_id'                => $extraKelasId,
                'mapel_id'                => $request->mapel_id,
                'hari'                    => $request->hari,
                'jam_ke'                  => $jamKe,
                'jam_mulai'               => $jamMulai,
                'jam_selesai'             => $jamSelesai,
                'ruangan'                 => $request->ruangan,
                'keterangan'              => $request->keterangan,
            ]);
            $extraCount++;
        }

        $msg = $extraCount > 0
            ? "Jadwal berhasil diperbarui dan diterapkan ke {$extraCount} kelas lainnya."
            : 'Jadwal pembelajaran berhasil diperbarui.';

        return redirect()->back()->with('success', $msg);
    }

    public function destroy($id)
    {
        $schoolId = auth()->user()->school_id;
        $jadwal = JadwalPelajaran::where('school_id', $schoolId)->findOrFail($id);

        $jadwal->delete();

        return redirect()->back()->with('success', 'Jadwal pembelajaran berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $schoolId = auth()->user()->school_id;
        $ids = $request->input('selected_ids', []);

        if (!empty($ids)) {
            JadwalPelajaran::where('school_id', $schoolId)
                ->whereIn('id', $ids)
                ->delete();
            return redirect()->back()->with('success', count($ids) . ' Jadwal berhasil dihapus.');
        }

        return redirect()->back()->with('error', 'Tidak ada jadwal yang dipilih.');
    }

    /**
     * Download Template Excel Jadwal Pelajaran dengan Dropdown Interaktif
     */
    public function downloadTemplate()
    {
        $schoolId = auth()->user()->school_id;

        $kelases = Kelas::where('school_id', $schoolId)->orderBy('nama_kelas')->pluck('nama_kelas')->toArray();
        $mapels = Mapel::where('school_id', $schoolId)->orderBy('nama_mapel')->pluck('nama_mapel')->toArray();
        $gurus = Guru::where('school_id', $schoolId)->orderBy('nama')->pluck('nama')->toArray();
        $haris = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        // Ambil master Jam Pelajaran sekolah dengan label sesi lengkap
        $jamPelajarans = JamPelajaran::where('school_id', $schoolId)
            ->where('is_active', true)
            ->orderBy('urutan')
            ->orderBy('jam_mulai')
            ->get();

        $jams = [];
        foreach ($jamPelajarans as $jp) {
            $mulai = substr($jp->jam_mulai, 0, 5);
            $selesai = substr($jp->jam_selesai, 0, 5);
            $timeStr = "({$mulai} - {$selesai})";

            if ($jp->jam_ke && !str_contains(strtolower($jp->nama_jam), 'jam ' . $jp->jam_ke) && !str_contains(strtolower($jp->nama_jam), 'jam ke ' . $jp->jam_ke)) {
                $lbl = "{$jp->nama_jam} - Jam {$jp->jam_ke} {$timeStr}";
            } else {
                $lbl = "{$jp->nama_jam} {$timeStr}";
            }

            // Tambahkan penanda hari jika jam ini terikat hari tertentu (cukup [Jumat], [Sabtu], [Senin, Selasa, Rabu], dll)
            if ($jp->hari && strtolower(trim($jp->hari)) !== 'semua hari') {
                $lbl .= " [" . trim($jp->hari) . "]";
            }

            $jams[] = $lbl;
        }
        $jams = array_values(array_unique($jams));
        if (empty($jams)) {
            $jams = ['Jam 1 (07:15 - 08:00)', 'Jam 2 (08:00 - 08:45)', 'Jam 3 (08:45 - 09:30)', 'Jam 4 (09:30 - 10:15)'];
        }

        $spreadsheet = new Spreadsheet();

        // 1. Sheet Utama (Jadwal Pelajaran)
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Jadwal Pelajaran');

        // Headers
        $headers = [
            'A1' => 'Hari',
            'B1' => 'Kelas',
            'C1' => 'Mata Pelajaran',
            'D1' => 'Guru Pengajar',
            'E1' => 'Jam Ke (Mulai)',
            'F1' => 'Jam Ke (Selesai)',
            'G1' => 'Ruangan',
            'H1' => 'Keterangan',
        ];

        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        // Contoh Data Baris 2
        $sheet->setCellValue('A2', 'Senin');
        $sheet->setCellValue('B2', $kelases[0] ?? 'X-DKV-A');
        $sheet->setCellValue('C2', $mapels[0] ?? 'Matematika');
        $sheet->setCellValue('D2', $gurus[0] ?? 'Nama Guru');
        $sheet->setCellValue('E2', $jams[0] ?? 'Jam 1');
        $sheet->setCellValue('F2', $jams[1] ?? ($jams[0] ?? 'Jam 2'));
        $sheet->setCellValue('G2', 'R. Teori 1');
        $sheet->setCellValue('H2', 'Praktik/Teori');

        // Contoh Baris 3 (Multi-kelas)
        $sheet->setCellValue('A3', 'Senin');
        $multiExample = count($kelases) >= 2 ? ($kelases[0] . ', ' . $kelases[1]) : 'X-DKV-A, X-DKV-B';
        $sheet->setCellValue('B3', $multiExample);
        $sheet->setCellValue('C3', $mapels[1] ?? ($mapels[0] ?? 'Dasar Desain Grafis'));
        $sheet->setCellValue('D3', $gurus[0] ?? 'Nama Guru');
        $sheet->setCellValue('E3', $jams[2] ?? ($jams[0] ?? 'Jam 3'));
        $sheet->setCellValue('F3', $jams[3] ?? ($jams[1] ?? 'Jam 4'));
        $sheet->setCellValue('G3', 'Lab Komputer');
        $sheet->setCellValue('H3', 'Kelas Gabungan');

        // Lebar Kolom
        $sheet->getColumnDimension('A')->setWidth(16);
        $sheet->getColumnDimension('B')->setWidth(26);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(30);
        $sheet->getColumnDimension('E')->setWidth(42);
        $sheet->getColumnDimension('F')->setWidth(42);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getColumnDimension('H')->setWidth(24);

        // Styling Header
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getStyle('A1:H1')->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF2563EB');
        $sheet->getStyle('A1:H1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Alignment Data
        $sheet->getStyle('A2:A300')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E2:F300')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 2. Hidden Sheet (_MasterRef) untuk Sumber Dropdown
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('_MasterRef');
        $refSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

        // Header ref
        $refSheet->setCellValue('A1', 'Hari');
        $refSheet->setCellValue('B1', 'Kelas');
        $refSheet->setCellValue('C1', 'Mapel');
        $refSheet->setCellValue('D1', 'Guru');
        $refSheet->setCellValue('E1', 'Jam');

        // Isi data ref
        $r = 2;
        foreach ($haris as $h) {
            $refSheet->setCellValue("A{$r}", $h);
            $r++;
        }
        $totalHari = max(2, $r - 1);

        $r = 2;
        foreach ($kelases as $k) {
            $refSheet->setCellValue("B{$r}", $k);
            $r++;
        }
        $totalKelas = max(2, $r - 1);

        $r = 2;
        foreach ($mapels as $m) {
            $refSheet->setCellValue("C{$r}", $m);
            $r++;
        }
        $totalMapel = max(2, $r - 1);

        $r = 2;
        foreach ($gurus as $g) {
            $refSheet->setCellValue("D{$r}", $g);
            $r++;
        }
        $totalGuru = max(2, $r - 1);

        $r = 2;
        foreach ($jams as $j) {
            $refSheet->setCellValue("E{$r}", $j);
            $r++;
        }
        $totalJam = max(2, $r - 1);

        // 3. Pasang Data Validation (Dropdown) ke Sheet Utama
        $valHari = new DataValidation();
        $valHari->setType(DataValidation::TYPE_LIST);
        $valHari->setErrorStyle(DataValidation::STYLE_STOP);
        $valHari->setAllowBlank(true);
        $valHari->setShowDropDown(true);
        $valHari->setShowErrorMessage(true);
        $valHari->setErrorTitle('Pilihan Hari Tidak Valid');
        $valHari->setError('Silakan pilih salah satu hari dari dropdown.');
        $valHari->setFormula1("'_MasterRef'!\$A\$2:\$A\${$totalHari}");

        $valKelas = new DataValidation();
        $valKelas->setType(DataValidation::TYPE_LIST);
        $valKelas->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $valKelas->setAllowBlank(true);
        $valKelas->setShowDropDown(true);
        $valKelas->setShowErrorMessage(false); // agar boleh ketik manual dipisah koma untuk multi-kelas
        $valKelas->setFormula1("'_MasterRef'!\$B\$2:\$B\${$totalKelas}");

        $valMapel = new DataValidation();
        $valMapel->setType(DataValidation::TYPE_LIST);
        $valMapel->setErrorStyle(DataValidation::STYLE_STOP);
        $valMapel->setAllowBlank(true);
        $valMapel->setShowDropDown(true);
        $valMapel->setShowErrorMessage(true);
        $valMapel->setErrorTitle('Mapel Tidak Valid');
        $valMapel->setError('Silakan pilih mata pelajaran dari dropdown.');
        $valMapel->setFormula1("'_MasterRef'!\$C\$2:\$C\${$totalMapel}");

        $valGuru = new DataValidation();
        $valGuru->setType(DataValidation::TYPE_LIST);
        $valGuru->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $valGuru->setAllowBlank(true);
        $valGuru->setShowDropDown(true);
        $valGuru->setShowErrorMessage(false); // agar boleh isi NIP atau Nama
        $valGuru->setFormula1("'_MasterRef'!\$D\$2:\$D\${$totalGuru}");

        $valJam = new DataValidation();
        $valJam->setType(DataValidation::TYPE_LIST);
        $valJam->setErrorStyle(DataValidation::STYLE_INFORMATION);
        $valJam->setAllowBlank(true);
        $valJam->setShowDropDown(true);
        $valJam->setShowErrorMessage(false); // fleksibel boleh pilih label lengkap atau ketik angka jam
        $valJam->setPromptTitle('Pilihan Sesi Jam Pelajaran');
        $valJam->setPrompt('Pilih sesi jam pelajaran dari dropdown (misal: Kelas 1 - Jam 1 (07:15 - 08:00)).');
        $valJam->setShowInputMessage(true);
        $valJam->setFormula1("'_MasterRef'!\$E\$2:\$E\${$totalJam}");

        // Pasangkan dropdown dari baris 2 hingga baris 300
        for ($rowIdx = 2; $rowIdx <= 300; $rowIdx++) {
            $sheet->getCell("A{$rowIdx}")->setDataValidation(clone $valHari);
            $sheet->getCell("B{$rowIdx}")->setDataValidation(clone $valKelas);
            $sheet->getCell("C{$rowIdx}")->setDataValidation(clone $valMapel);
            $sheet->getCell("D{$rowIdx}")->setDataValidation(clone $valGuru);
            $sheet->getCell("E{$rowIdx}")->setDataValidation(clone $valJam);
            $sheet->getCell("F{$rowIdx}")->setDataValidation(clone $valJam);
        }

        // Pastikan sheet aktif saat dibuka adalah Sheet 1
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $filename = 'Template_Import_Jadwal_Pelajaran.xlsx';
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    /**
     * Proses Impor File Excel Jadwal Pelajaran
     */
    public function import(Request $request)
    {
        $request->validate([
            'fileExcel' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'mode'      => 'nullable|string|in:append,replace',
        ], [
            'fileExcel.required' => 'Pilih file Excel jadwal yang akan diimpor.',
            'fileExcel.mimes'    => 'Format file harus berupa Excel (.xlsx, .xls) atau .csv.',
            'fileExcel.max'      => 'Ukuran file maksimal 10 MB.',
        ]);

        $schoolId = auth()->user()->school_id;
        $mode = $request->input('mode', 'append');

        try {
            $file = $request->file('fileExcel');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);

            if (empty($rows) || count($rows) <= 1) {
                return redirect()->back()->with('error', 'File Excel kosong atau tidak memiliki baris data.');
            }

            // Pre-load master data sekolah
            $kelases = Kelas::where('school_id', $schoolId)->get()->keyBy(function ($k) {
                return strtolower(trim($k->nama_kelas));
            });

            $mapels = Mapel::where('school_id', $schoolId)->get()->keyBy(function ($m) {
                return strtolower(trim($m->nama_mapel));
            });

            $gurus = Guru::where('school_id', $schoolId)->get();
            $guruByName = [];
            $guruByNip = [];
            foreach ($gurus as $g) {
                $guruByName[strtolower(trim($g->nama))] = $g->id;
                if ($g->nip) {
                    $guruByNip[trim($g->nip)] = $g->id;
                }
            }

            // Load master Jam Pelajaran
            $allJamPelajaran = JamPelajaran::where('school_id', $schoolId)
                ->where('is_active', true)
                ->orderBy('urutan')
                ->orderBy('jam_mulai')
                ->get();

            $failures = [];
            $recordsToInsert = [];
            $excelRowIndex = 1;

            $validHariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

            // Mulai dari baris ke-2 (skip header)
            for ($i = 1; $i < count($rows); $i++) {
                $excelRowIndex = $i + 1;
                $row = $rows[$i];

                // Cek apakah baris kosong
                $hasContent = false;
                foreach ($row as $cell) {
                    if ($cell !== null && trim((string)$cell) !== '') {
                        $hasContent = true;
                        break;
                    }
                }
                if (!$hasContent) {
                    continue;
                }

                $hariRaw      = trim((string)($row[0] ?? ''));
                $kelasRaw     = trim((string)($row[1] ?? ''));
                $mapelRaw     = trim((string)($row[2] ?? ''));
                $guruRaw      = trim((string)($row[3] ?? ''));
                $jamMulaiRaw  = trim((string)($row[4] ?? ''));
                $jamAkhirRaw  = trim((string)($row[5] ?? ''));
                $ruangan      = trim((string)($row[6] ?? ''));
                $keterangan   = trim((string)($row[7] ?? ''));

                // 1. Validasi Hari
                $hari = ucfirst(strtolower($hariRaw));
                if (!in_array($hari, $validHariList)) {
                    $failures[] = [
                        'row'    => $excelRowIndex,
                        'info'   => "Hari: '{$hariRaw}'",
                        'reason' => "Hari tidak valid. Gunakan salah satu dari: " . implode(', ', $validHariList),
                    ];
                    continue;
                }

                // 2. Validasi Kelas (Bisa multi-kelas dipisah koma)
                if (empty($kelasRaw)) {
                    $failures[] = [
                        'row'    => $excelRowIndex,
                        'info'   => "Kelas kosong",
                        'reason' => "Kolom Kelas wajib diisi.",
                    ];
                    continue;
                }

                $rawKelasItems = array_map('trim', explode(',', $kelasRaw));
                $kelasIds = [];
                $unresolvedKelas = [];

                foreach ($rawKelasItems as $kItem) {
                    if ($kItem === '') continue;
                    $keyK = strtolower($kItem);
                    if (isset($kelases[$keyK])) {
                        $kelasIds[] = $kelases[$keyK]->id;
                    } else {
                        $unresolvedKelas[] = $kItem;
                    }
                }

                if (!empty($unresolvedKelas)) {
                    $failures[] = [
                        'row'    => $excelRowIndex,
                        'info'   => "Kelas: '{$kelasRaw}'",
                        'reason' => "Kelas berikut tidak ditemukan di database: " . implode(', ', $unresolvedKelas),
                    ];
                    continue;
                }

                if (empty($kelasIds)) {
                    $failures[] = [
                        'row'    => $excelRowIndex,
                        'info'   => "Kelas: '{$kelasRaw}'",
                        'reason' => "Tidak ada nama kelas yang valid.",
                    ];
                    continue;
                }

                $kelasIds = array_values(array_unique($kelasIds));

                // 3. Validasi Mapel
                if (empty($mapelRaw)) {
                    $failures[] = [
                        'row'    => $excelRowIndex,
                        'info'   => "Mapel kosong",
                        'reason' => "Kolom Mata Pelajaran wajib diisi.",
                    ];
                    continue;
                }
                $keyM = strtolower($mapelRaw);
                if (!isset($mapels[$keyM])) {
                    $failures[] = [
                        'row'    => $excelRowIndex,
                        'info'   => "Mapel: '{$mapelRaw}'",
                        'reason' => "Mata pelajaran '{$mapelRaw}' belum terdaftar di menu Mata Pelajaran.",
                    ];
                    continue;
                }
                $mapelId = $mapels[$keyM]->id;

                // 4. Validasi Guru
                if (empty($guruRaw)) {
                    $failures[] = [
                        'row'    => $excelRowIndex,
                        'info'   => "Guru kosong",
                        'reason' => "Kolom Guru Pengajar wajib diisi.",
                    ];
                    continue;
                }
                $guruId = null;
                if (isset($guruByNip[$guruRaw])) {
                    $guruId = $guruByNip[$guruRaw];
                } elseif (isset($guruByName[strtolower($guruRaw)])) {
                    $guruId = $guruByName[strtolower($guruRaw)];
                } else {
                    $failures[] = [
                        'row'    => $excelRowIndex,
                        'info'   => "Guru: '{$guruRaw}'",
                        'reason' => "Guru '{$guruRaw}' tidak ditemukan berdasarkan Nama maupun NIP.",
                    ];
                    continue;
                }

                // 5. Validasi Sesi Jam Pelajaran
                if (empty($jamMulaiRaw)) {
                    $failures[] = [
                        'row'    => $excelRowIndex,
                        'info'   => "Jam Mulai kosong",
                        'reason' => "Kolom Jam Ke (Mulai) wajib diisi sesi jam.",
                    ];
                    continue;
                }

                // Cocokkan jam awal menggunakan resolveJamPelajaranRecord
                $jpAwal = $this->resolveJamPelajaranRecord($jamMulaiRaw, $hari, $allJamPelajaran);

                if (!$jpAwal) {
                    $failures[] = [
                        'row'    => $excelRowIndex,
                        'info'   => "Jam: '{$jamMulaiRaw}' ({$hari})",
                        'reason' => "Sesi jam pelajaran '{$jamMulaiRaw}' untuk hari {$hari} tidak ditemukan di master Jam Pelajaran sekolah.",
                    ];
                    continue;
                }

                // Jam akhir (opsional untuk jam gandeng)
                $jpAkhir = null;
                if (!empty($jamAkhirRaw) && (string)$jamAkhirRaw !== (string)$jamMulaiRaw) {
                    $jpAkhir = $this->resolveJamPelajaranRecord($jamAkhirRaw, $hari, $allJamPelajaran);
                }

                $jamMulai = Carbon::parse($jpAwal->jam_mulai)->format('H:i');
                $jamSelesai = $jpAkhir ? Carbon::parse($jpAkhir->jam_selesai)->format('H:i') : Carbon::parse($jpAwal->jam_selesai)->format('H:i');

                $jamKeAwal = $jpAwal->jam_ke ?: ($jpAwal->nama_jam ?? '-');
                if ($jpAkhir && $jpAkhir->id !== $jpAwal->id) {
                    $jamKeAkhir = $jpAkhir->jam_ke ?: ($jpAkhir->nama_jam ?? '-');
                    $jamKe = "{$jamKeAwal} - {$jamKeAkhir}";
                } else {
                    $jamKe = $jamKeAwal;
                }

                $now = now();
                foreach ($kelasIds as $kId) {
                    $recordsToInsert[] = [
                        'school_id'              => $schoolId,
                        'jam_pelajaran_id'       => $jpAwal->id,
                        'jam_pelajaran_id_akhir' => $jpAkhir?->id,
                        'guru_id'                => $guruId,
                        'kelas_id'               => $kId,
                        'mapel_id'               => $mapelId,
                        'hari'                   => $hari,
                        'jam_ke'                 => $jamKe,
                        'jam_mulai'              => $jamMulai,
                        'jam_selesai'            => $jamSelesai,
                        'ruangan'                => $ruangan ?: null,
                        'keterangan'             => $keterangan ?: null,
                        'created_at'             => $now,
                        'updated_at'             => $now,
                    ];
                }
            }

            if (empty($recordsToInsert)) {
                $countFail = count($failures);
                return redirect()->back()
                    ->with('error', "Import gagal: 0 jadwal berhasil diimpor. {$countFail} baris data ditolak.")
                    ->with('import_errors', $failures);
            }

            DB::transaction(function () use ($mode, $schoolId, $recordsToInsert) {
                if ($mode === 'replace') {
                    JadwalPelajaran::where('school_id', $schoolId)->delete();
                }
                foreach (array_chunk($recordsToInsert, 100) as $chunk) {
                    JadwalPelajaran::insert($chunk);
                }
            });

            $countSuccess = count($recordsToInsert);
            $countFail = count($failures);

            if ($countFail > 0) {
                $msg = "Import selesai sebagian: {$countSuccess} jadwal berhasil diimpor, {$countFail} baris data dilewati.";
                return redirect()->back()->with('warning', $msg)->with('import_errors', $failures);
            }

            return redirect()->back()->with('success', "Import berhasil! {$countSuccess} jadwal pelajaran berhasil diimpor.");

        } catch (\Exception $e) {
            Log::error('Import Jadwal Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal memproses file Excel: ' . $e->getMessage());
        }
    }

    /**
     * Resolves JamPelajaran record from Excel input string (e.g. "Kelas 1 - Jam 1 (07:15 - 08:00)" or "1").
     * Prioritizes matching by the given day ($hari), but gracefully falls back to any active session
     * so that the import never fails simply because a session wasn't explicitly mapped to that day.
     */
    private function resolveJamPelajaranRecord(string $raw, string $hari, $allJamPelajaran)
    {
        $raw = trim($raw);
        if ($raw === '') return null;

        $matchInCollection = function ($collection) use ($raw) {
            // Bersihkan penanda kurung hari [ ... ] dari teks Excel jika ada
            $rawClean = trim(preg_replace('/\s*\[.*?\]\s*$/', '', $raw));

            // 1. Cocokkan eksak dengan label bawaan template
            foreach ($collection as $jp) {
                $mulai = substr($jp->jam_mulai, 0, 5);
                $selesai = substr($jp->jam_selesai, 0, 5);
                $lbl1 = "{$jp->nama_jam} - Jam {$jp->jam_ke} ({$mulai} - {$selesai})";
                $lbl2 = "{$jp->nama_jam} ({$mulai} - {$selesai})";
                $lbl3 = $jp->label; // from model

                $hariTrim = ($jp->hari && strtolower(trim($jp->hari)) !== 'semua hari') ? trim($jp->hari) : '';
                $lbl4 = $hariTrim ? "{$lbl1} [{$hariTrim}]" : $lbl1;
                $lbl5 = $hariTrim ? "{$lbl2} [{$hariTrim}]" : $lbl2;
                $lbl6 = $hariTrim ? "{$lbl1} [Khusus {$hariTrim}]" : $lbl1;

                if (strcasecmp($raw, $lbl1) === 0 
                    || strcasecmp($raw, $lbl2) === 0 
                    || strcasecmp($raw, $lbl3) === 0 
                    || strcasecmp($raw, $lbl4) === 0 
                    || strcasecmp($raw, $lbl5) === 0
                    || strcasecmp($raw, $lbl6) === 0
                    || strcasecmp($rawClean, $lbl1) === 0
                    || strcasecmp($rawClean, $lbl2) === 0) {
                    return $jp;
                }
            }

            // 2. Cocokkan pola: "Nama Sesi - Jam X"
            if (preg_match('/^(.*?)\s*-\s*Jam\s*(\d+)/i', $rawClean, $m)) {
                $sessionName = trim($m[1]);
                $jamKe = trim($m[2]);
                $found = $collection->first(function ($jp) use ($sessionName, $jamKe) {
                    return strcasecmp($jp->nama_jam, $sessionName) === 0 && (string)$jp->jam_ke === (string)$jamKe;
                });
                if ($found) return $found;
            }

            // 3. Cocokkan berdasarkan rentang jam "(07:15 - 08:00)" jika ada
            if (preg_match('/(\d{2}:\d{2})\s*-\s*(\d{2}:\d{2})/', $rawClean, $m)) {
                $found = $collection->first(function ($jp) use ($m) {
                    return str_starts_with($jp->jam_mulai, $m[1]);
                });
                if ($found) return $found;
            }

            // 4. Fallback jika user hanya mengisikan angka jam (misal "1" atau "Jam 1")
            $found = $collection->first(function ($jp) use ($rawClean) {
                return (string)$jp->jam_ke === (string)$rawClean
                    || (string)$jp->urutan === (string)$rawClean
                    || strcasecmp($jp->nama_jam, $rawClean) === 0
                    || strcasecmp($jp->nama_jam, "Jam ke {$rawClean}") === 0
                    || strcasecmp($jp->nama_jam, "Jam {$rawClean}") === 0;
            });

            return $found;
        };

        // Prioritas 1: Cari jam yang cocok khusus untuk HARI ini
        $jamHari = $allJamPelajaran->filter(function ($jp) use ($hari) {
            if (!$jp->hari || strtolower($jp->hari) === 'semua hari') return true;
            $days = array_map('trim', explode(',', strtolower($jp->hari)));
            return in_array(strtolower($hari), $days);
        });

        $found = $matchInCollection($jamHari);
        if ($found) {
            return $found;
        }

        // Prioritas 2 (Fallback Fleksibel): Jika tidak ditemukan di hari tersebut, cari di seluruh jam master sekolah
        // Ini memastikan jadwal tetap berhasil diimpor dengan jam & waktu yang dipilih tanpa ditolak error
        return $matchInCollection($allJamPelajaran);
    }
}
