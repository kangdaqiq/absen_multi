<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\Device;
use App\Models\ApiLog;
use App\Models\SiswaFingerprint;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SiswaController extends Controller
{
    public function index()
    {
        $siswa = Siswa::with(['kelas', 'user'])->orderBy('nama')->get();
        $kelas = Kelas::orderBy('nama_kelas')->get();
        $devices = Device::where('active', true)
            ->whereIn('type', ['fingerprint', 'rfid_fingerprint'])
            ->orderBy('name')
            ->get();

        return view('siswa.index', compact('siswa', 'kelas', 'devices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'nis' => 'required|string|max:20|unique:siswa,nis',
            'tgl_lahir' => 'nullable|date',
            'kelas_id' => 'required|exists:kelas,id',
            'no_wa' => 'nullable|string|max:20|unique:siswa,no_wa|regex:/^(08|628)[0-9]{8,13}$/',
            'wa_ortu' => 'nullable|string|max:20|regex:/^(08|628)[0-9]{8,13}$/',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $input = $request->all();
        // Force null if empty string to avoid unique constraint issues on empty strings
        if (empty($input['no_wa']))
            $input['no_wa'] = null;
        if (empty($input['wa_ortu']))
            $input['wa_ortu'] = null;

        $siswa = Siswa::create($input);

        // Auto-Generate User Account if not provided
        if (!$request->user_id) {
            $this->createUserForSiswa($siswa);
        }

        return redirect()->route('siswa.index')->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $siswa = Siswa::findOrFail($id);

        $request->validate([
            'nama' => 'required|string|max:100',
            'nis' => 'required|string|max:20|unique:siswa,nis,' . $siswa->id,
            'tgl_lahir' => 'nullable|date',
            'kelas_id' => 'required|exists:kelas,id',
            'no_wa' => 'nullable|string|max:20|unique:siswa,no_wa,' . $siswa->id . '|regex:/^(08|628)[0-9]{8,13}$/',
            'wa_ortu' => 'nullable|string|max:20|regex:/^(08|628)[0-9]{8,13}$/',
            'uid_rfid' => 'nullable|string|max:50',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $input = $request->all();
        if (empty($input['no_wa']))
            $input['no_wa'] = null;
        if (empty($input['wa_ortu']))
            $input['wa_ortu'] = null;

        $siswa->update($input);

        return redirect()->route('siswa.index')->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $siswa = Siswa::findOrFail($id);

        // Optional: Delete linked user? For now keep it or manual delete.
        // If we want to clean up:
        // if ($siswa->user_id) { \App\Models\User::destroy($siswa->user_id); }

        $siswa->delete();

        return redirect()->route('siswa.index')->with('success', 'Siswa berhasil dihapus.');
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
            $kelasMap = Kelas::pluck('id', 'nama_kelas')->toArray();
            $existingNis = Siswa::pluck('nis')->toArray();

            foreach ($rows as $row) {
                if ($firstRow) {
                    $firstRow = false;
                    continue;
                }

                try {
                    $nama = trim($row[0] ?? '');
                    $nis = trim($row[1] ?? '');

                    // Column C (Index 2): Tgl Lahir
                    $tglLahirRaw = trim($row[2] ?? '');
                    $tglLahir = null;
                    if ($tglLahirRaw) {
                        try {
                            // Attempt to parse YYYY-MM-DD or standard formats
                            $tglLahir = \Carbon\Carbon::parse($tglLahirRaw)->format('Y-m-d');
                        } catch (\Exception $e) {
                            $tglLahir = null;
                        }
                    }

                    // Column D (Index 3): Kelas
                    $namaKelas = trim($row[3] ?? '');

                    // Column E (Index 4): WA Siswa
                    $wa = isset($row[4]) ? trim($row[4]) : null;

                    // Column F (Index 5): WA Ortu
                    $waOrtu = isset($row[5]) ? trim($row[5]) : null;

                    if ($nama === '' || $nis === '') {
                        $countSkip++;
                        continue;
                    }

                    if (in_array($nis, $existingNis)) {
                        $countSkip++;
                        continue;
                    }

                    // Resolve Kelas
                    $kelasId = null;
                    if ($namaKelas !== '') {
                        if (isset($kelasMap[$namaKelas])) {
                            $kelasId = $kelasMap[$namaKelas];
                        } else {
                            $newKelas = Kelas::create(['nama_kelas' => $namaKelas]);
                            $kelasId = $newKelas->id;
                            $kelasMap[$namaKelas] = $kelasId;
                        }
                    }

                    $siswa = Siswa::create([
                        'nama' => $nama,
                        'nis' => $nis,
                        'tgl_lahir' => $tglLahir ?: null, // Ensure null if empty string
                        'kelas_id' => $kelasId,
                        'no_wa' => $this->normalizeWa($wa) ?: null,
                        'wa_ortu' => $this->normalizeWa($waOrtu) ?: null,
                        'created_at' => now()
                    ]);
                    // Auto-create Account
                    $this->createUserForSiswa($siswa);

                    $existingNis[] = $nis;
                    $countSuccess++;
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Import Row Error (NIS: " . ($row[1] ?? 'unknown') . "): " . $e->getMessage());
                    $countSkip++;
                }
            }

            return redirect()->route('siswa.index')->with('success', "Import selesai. Berhasil: $countSuccess. Dilewati/Gagal: $countSkip.");

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Import Siswa Error: ' . $e->getMessage());
            return redirect()->route('siswa.index')->with('error', 'Gagal import: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Nama Lengkap');
        $sheet->setCellValue('B1', 'NIS');
        $sheet->setCellValue('C1', 'Tanggal Lahir (YYYY-MM-DD)');
        $sheet->setCellValue('D1', 'Nama Kelas');
        $sheet->setCellValue('E1', 'No WhatsApp Siswa');
        $sheet->setCellValue('F1', 'No WhatsApp Ortu');

        // Example
        $sheet->setCellValue('A2', 'Ahmad Dani');
        $sheet->setCellValue('B2', '12345');
        $sheet->setCellValue('C2', '2005-01-01');
        $sheet->setCellValue('D2', 'X TKJ 1');
        $sheet->setCellValue('E2', '081234567890');
        $sheet->setCellValue('F2', '081234567891');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="Template_Import_Siswa.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    // Helper to auto-create User
    private function createUserForSiswa($siswa)
    {
        // Use NIS as username
        $username = $siswa->nis;

        // Check if user exists (to prevent dupes if re-generating)
        $existingUser = \App\Models\User::where('email', $username)
            ->orWhere('username', $username)
            ->first();

        if (!$existingUser) {
            $user = \App\Models\User::create([
                'full_name' => $siswa->nama,
                'username' => $username,  // Add username field
                'email' => $username . '@siswa.smkassuniyah.sch.id',  // Use school domain
                'password_hash' => \Illuminate\Support\Facades\Hash::make($siswa->nis), // Password = NIS
                'role' => 'student'
            ]);

            // Link to Siswa
            $siswa->user_id = $user->id;
            $siswa->save();
        } else {
            // Already exists, just link it if not linked
            if (!$siswa->user_id) {
                $siswa->user_id = $existingUser->id;
                $siswa->save();
            }
        }
    }

    public function generateAccounts()
    {
        // Increase execution time and memory for large datasets
        set_time_limit(300); // 5 minutes
        ini_set('memory_limit', '256M');

        try {
            // Drop all existing student users
            \App\Models\User::where('role', 'student')->delete();

            // Reset user_id for all siswa
            Siswa::query()->update(['user_id' => null]);

            $count = 0;
            $errors = 0;

            // Process in chunks to avoid memory issues
            Siswa::chunk(50, function ($siswas) use (&$count, &$errors) {
                foreach ($siswas as $siswa) {
                    try {
                        $this->createUserForSiswa($siswa);
                        $count++;
                    } catch (\Exception $e) {
                        $errors++;
                        \Log::error("Failed to create user for siswa {$siswa->id}: " . $e->getMessage());
                    }
                }
            });

            $message = "Berhasil generate $count akun siswa.";
            if ($errors > 0) {
                $message .= " Gagal: $errors akun (cek log untuk detail).";
            }

            return redirect()->route('siswa.index')->with('success', $message);

        } catch (\Exception $e) {
            \Log::error('Generate accounts error: ' . $e->getMessage());
            return redirect()->route('siswa.index')->with('error', 'Error: ' . $e->getMessage());
        }
    }

    private function normalizeWa($wa)
    {
        if (!$wa)
            return null;
        $wa = preg_replace('/[^0-9]/', '', $wa);
        if (strpos($wa, '08') === 0) {
            $wa = '62' . substr($wa, 1);
        }
        return $wa;
    }

    // Enrollment Methods
    public function enrollRequest($id)
    {
        // Reset ALL other pending requests first (Single Active Request Policy)
        Siswa::where('enroll_status', 'requested')->update(['enroll_status' => 'none']);
        \App\Models\Guru::where('enroll_status', 'requested')->update(['enroll_status' => 'none']);

        $siswa = Siswa::findOrFail($id);
        $siswa->update(['enroll_status' => 'requested']);
        return response()->json(['ok' => true]);
    }

    public function cancelEnroll($id)
    {
        $siswa = Siswa::findOrFail($id);
        if ($siswa->enroll_status === 'requested') {
            $siswa->update(['enroll_status' => 'none']);
        }
        return response()->json(['ok' => true]);
    }

    public function enrollCheck($id)
    {
        $siswa = Siswa::findOrFail($id);
        if ($siswa->enroll_status === 'done' && $siswa->uid_rfid) {
            return response()->json(['ok' => true, 'uid' => $siswa->uid_rfid]);
        }
        return response()->json(['ok' => true, 'uid' => null]);
    }

    public function deleteUid($id)
    {
        $siswa = Siswa::findOrFail($id);
        $siswa->update(['uid_rfid' => null, 'enroll_status' => 'none']);
        return response()->json(['ok' => true]);
    }

    // Fingerprint Enrollment Methods
    public function enrollFingerRequest(Request $request, $id)
    {
        $request->validate([
            'device_id' => 'required|exists:api_keys,id'
        ]);

        // Reset ALL other pending requests
        Siswa::where('enroll_finger_status', 'requested')->update(['enroll_finger_status' => 'none']);
        \App\Models\Guru::where('enroll_finger_status', 'requested')->update(['enroll_finger_status' => 'none']);

        $siswa = Siswa::findOrFail($id);
        $siswa->update(['enroll_finger_status' => 'requested']);

        // Get device IP and send push notification
        $device = Device::find($request->device_id);
        $latestLog = ApiLog::where('api_key', $device->api_key)
            ->whereNotNull('ip_address')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestLog && $latestLog->ip_address) {
            try {
                $url = "http://{$latestLog->ip_address}/enroll-finger?id={$siswa->id}";
                Http::timeout(3)->get($url);
                \Log::info("Fingerprint enrollment push sent to {$latestLog->ip_address} for siswa {$siswa->id}");
            } catch (\Exception $e) {
                \Log::error("Failed to send enrollment push: " . $e->getMessage());
            }
        }

        return response()->json(['ok' => true]);
    }

    public function cancelFingerEnroll($id)
    {
        $siswa = Siswa::findOrFail($id);
        if ($siswa->enroll_finger_status === 'requested') {
            $siswa->update(['enroll_finger_status' => 'none']);
        }
        return response()->json(['ok' => true]);
    }

    public function enrollFingerCheck($id)
    {
        $siswa = Siswa::findOrFail($id);

        if ($siswa->enroll_finger_status === 'done' && $siswa->id_finger) {
            return response()->json(['ok' => true, 'id_finger' => $siswa->id_finger, 'status' => 'done']);
        }

        return response()->json(['ok' => true, 'id_finger' => null, 'status' => 'requested']);
    }

    public function deleteFingerId($id)
    {
        $siswa = Siswa::findOrFail($id);

        // Get all fingerprints for this student
        $fingerprints = SiswaFingerprint::where('student_id', $siswa->id)->get();

        foreach ($fingerprints as $fingerprint) {
            $device = Device::find($fingerprint->device_id);
            if ($device) {
                // Get device IP
                $latestLog = ApiLog::where('api_key', $device->api_key)
                    ->whereNotNull('ip_address')
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($latestLog && $latestLog->ip_address) {
                    try {
                        $url = "http://{$latestLog->ip_address}/delete-finger?id={$fingerprint->finger_id}";
                        Http::timeout(3)->get($url);
                        \Log::info("Delete fingerprint push sent to {$latestLog->ip_address} for finger_id {$fingerprint->finger_id}");
                    } catch (\Exception $e) {
                        \Log::error("Failed to send delete push: " . $e->getMessage());
                    }
                }
            }
        }

        // Delete from database
        SiswaFingerprint::where('student_id', $siswa->id)->delete();
        $siswa->update(['id_finger' => null, 'enroll_finger_status' => 'none']);

        return response()->json(['ok' => true]);
    }
}
