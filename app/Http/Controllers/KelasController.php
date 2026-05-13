<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $kelasQuery = Kelas::with(['waliKelas', 'jurusan'])->orderBy('nama_kelas');
        $gurusQuery = \App\Models\Guru::orderBy('nama');
        $jurusansQuery = \App\Models\Jurusan::orderBy('nama_jurusan');

        // Filter by school_id for non-super admin users
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $kelasQuery->where('school_id', auth()->user()->school_id);
            $gurusQuery->where('school_id', auth()->user()->school_id);
            $jurusansQuery->where('school_id', auth()->user()->school_id);
        }

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $kelasQuery->where('nama_kelas', 'like', "%{$search}%");
        }

        $kelas = $kelasQuery->paginate(20)->withQueryString();
        $gurus = $gurusQuery->get();
        $jurusans = $jurusansQuery->get();
        return view('kelas.index', compact('kelas', 'gurus', 'jurusans'));
    }

    public function store(Request $request)
    {
        $schoolId = auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id;

        $request->validate([
            'nama_kelas' => [
                'required',
                'string',
                'max:50',
                Rule::unique('kelas')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })
            ],
            'wali_kelas_id' => 'nullable|exists:guru,id',
            'jurusan_id' => 'nullable|exists:jurusan,id',
            'wa_group_id' => 'nullable|string|max:100',
        ]);

        $data = $request->all();

        // Add school_id from authenticated user
        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        Kelas::create($data);

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $kelas = Kelas::findOrFail($id);
        $schoolId = auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id;

        $request->validate([
            'nama_kelas' => [
                'required',
                'string',
                'max:50',
                Rule::unique('kelas')->ignore($kelas->id)->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })
            ],
            'wali_kelas_id' => 'nullable|exists:guru,id',
            'jurusan_id' => 'nullable|exists:jurusan,id',
            'wa_group_id' => 'nullable|string|max:100',
        ]);

        $kelas->update($request->all());

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $kelas = Kelas::findOrFail($id);
        $kelas->delete();

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }

    public function toggleStatus($id)
    {
        $kelas = Kelas::findOrFail($id);
        $kelas->is_active_attendance = !$kelas->is_active_attendance;
        $kelas->save();

        $status = $kelas->is_active_attendance ? 'aktif' : 'nonaktif';
        return redirect()->route('kelas.index')->with('success', "Status absensi kelas berhasil diubah menjadi $status.");
    }

    public function toggleReport($id)
    {
        $kelas = Kelas::findOrFail($id);

        // Only allow toggle if attendance is active
        if (!$kelas->is_active_attendance) {
            return redirect()->route('kelas.index')->with('error', 'Tidak dapat mengaktifkan report jika absensi nonaktif.');
        }

        $kelas->is_active_report = !$kelas->is_active_report;
        $kelas->save();

        $status = $kelas->is_active_report ? 'aktif' : 'nonaktif';
        return redirect()->route('kelas.index')->with('success', "Status report WA kelas berhasil diubah menjadi $status.");
    }

    public function import(Request $request)
    {
        $request->validate([
            'fileExcel' => 'required|mimes:xlsx,xls,csv'
        ]);

        try {
            $file = $request->file('fileExcel');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $countSuccess = 0;
            $countSkip = 0;
            $firstRow = true;
            $schoolId = auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id;

            // Fetch gurus for matching by name
            $gurus = Guru::where('school_id', $schoolId)->get()->mapWithKeys(function ($guru) {
                return [strtolower(trim($guru->nama)) => $guru->id];
            })->toArray();

            // Fetch existing classes to avoid duplicates
            $existingClasses = Kelas::where('school_id', $schoolId)->pluck('nama_kelas')->map(function ($name) {
                return strtolower(trim($name));
            })->toArray();

            foreach ($rows as $row) {
                if ($firstRow) {
                    $firstRow = false;
                    continue;
                }

                $namaKelas = trim($row[0] ?? '');
                $namaWali = trim($row[1] ?? '');

                if ($namaKelas === '') {
                    $countSkip++;
                    continue;
                }

                // Skip if class already exists
                if (in_array(strtolower($namaKelas), $existingClasses)) {
                    $countSkip++;
                    continue;
                }

                $waliKelasId = null;
                if ($namaWali !== '') {
                    $waliKelasId = $gurus[strtolower($namaWali)] ?? null;
                }

                Kelas::create([
                    'nama_kelas' => $namaKelas,
                    'wali_kelas_id' => $waliKelasId,
                    'school_id' => $schoolId,
                    'is_active_attendance' => true,
                    'is_active_report' => false,
                ]);

                $existingClasses[] = strtolower($namaKelas);
                $countSuccess++;
            }

            return redirect()->route('kelas.index')->with('success', "Import selesai. Berhasil: $countSuccess. Dilewati/Gagal: $countSkip.");
        } catch (\Throwable $e) {
            \Log::error('Import Kelas Error: ' . $e->getMessage());
            return redirect()->route('kelas.index')->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Nama Kelas');
        $sheet->setCellValue('B1', 'Wali Kelas');

        // Style header
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getStyle('A1:B1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF3C50E0');
        $sheet->getStyle('A1:B1')->getFont()->getColor()->setARGB('FFFFFFFF');

        // Set column width
        $sheet->getColumnDimension('A')->setWidth(25);
        $sheet->getColumnDimension('B')->setWidth(30);

        // Example
        $sheet->setCellValue('A2', 'X TKJ 1');
        
        // Fetch teachers for dropdown
        $schoolId = auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id;
        $gurus = Guru::where('school_id', $schoolId)->orderBy('nama')->pluck('nama')->toArray();

        if (count($gurus) > 0) {
            // Put teachers in a hidden sheet or separate range to avoid 255 character limit in formula
            $guruSheet = $spreadsheet->createSheet();
            $guruSheet->setTitle('DaftarGuru');
            $guruSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
            
            foreach ($gurus as $index => $nama) {
                $guruSheet->setCellValue('A' . ($index + 1), $nama);
            }
            
            $guruRange = 'DaftarGuru!$A$1:$A$' . count($gurus);
            
            // Apply validation to column B (from B2 to B100)
            for ($i = 2; $i <= 100; $i++) {
                $validation = $sheet->getCell('B' . $i)->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_STOP);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setErrorTitle('Kesalahan Input');
                $validation->setError('Pilih nama guru yang tersedia di daftar.');
                $validation->setPromptTitle('Pilih Wali Kelas');
                $validation->setPrompt('Silakan pilih salah satu guru dari daftar.');
                $validation->setFormula1($guruRange);
            }
        }

        $writer = new Xlsx($spreadsheet);

        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="Template_Import_Kelas.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
