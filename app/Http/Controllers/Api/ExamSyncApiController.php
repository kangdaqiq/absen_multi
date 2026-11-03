<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamSyncApiController extends Controller
{
    /**
     * Verifikasi API Key
     */
    protected function verifyKey(Request $request): bool
    {
        $expectedKey = env('CBT_SYNC_KEY', 'cbt_secret_sync_2026');
        $providedKey = $request->header('X-Exam-Key') ?: $request->input('api_key', $request->bearerToken());

        if (!empty($expectedKey) && $providedKey === $expectedKey) {
            return true;
        }

        // Allow local requests if no key is enforced
        if (in_array($request->ip(), ['127.0.0.1', '::1']) && empty($expectedKey)) {
            return true;
        }

        return false;
    }

    /**
     * Tes koneksi dan ringkasan data
     * GET /api/cbt-sync/ping
     */
    public function ping(Request $request): JsonResponse
    {
        if (!$this->verifyKey($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: API Key tidak valid.',
            ], 401);
        }

        $schools = School::select('id', 'name', 'code')->get();

        return response()->json([
            'success' => true,
            'message' => 'Koneksi ke Web Absensi berhasil!',
            'server_name' => config('app.name', 'Sistem Absensi'),
            'timestamp' => now()->toIso8601String(),
            'counts' => [
                'gurus' => Guru::count(),
                'mapels' => Mapel::count(),
                'kelases' => Kelas::count(),
                'jadwals' => JadwalPelajaran::count(),
                'schools' => $schools->count(),
            ],
            'schools' => $schools,
        ]);
    }

    /**
     * Cek nama sekolah & jumlah data berdasarkan custom School ID
     * GET /api/cbt-sync/check-school
     */
    public function checkSchool(Request $request): JsonResponse
    {
        if (!$this->verifyKey($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: API Key tidak valid.',
            ], 401);
        }

        $schoolId = $request->input('school_id');
        if (empty($schoolId)) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter school_id wajib disertakan.',
            ], 400);
        }

        $school = School::find($schoolId);
        if (!$school) {
            return response()->json([
                'success' => false,
                'found' => false,
                'message' => "Sekolah dengan ID {$schoolId} tidak ditemukan di Web Absensi.",
            ], 404);
        }

        $guruCount = Guru::where('school_id', $schoolId)->count();
        $mapelCount = Mapel::where('school_id', $schoolId)->count();
        $kelasCount = Kelas::where('school_id', $schoolId)->count();
        $jadwalCount = JadwalPelajaran::where('school_id', $schoolId)
            ->whereNotNull('guru_id')
            ->whereNotNull('mapel_id')
            ->whereNotNull('kelas_id')
            ->select('guru_id', 'mapel_id', 'kelas_id')
            ->distinct()
            ->count();

        return response()->json([
            'success' => true,
            'found' => true,
            'school_id' => $school->id,
            'name' => $school->name,
            'code' => $school->code,
            'counts' => [
                'gurus' => $guruCount,
                'mapels' => $mapelCount,
                'kelases' => $kelasCount,
                'jadwals' => $jadwalCount,
            ],
            'message' => "Nama Sekolah Ditemukan: {$school->name} (Kode: {$school->code})",
        ]);
    }

    /**
     * Ambil data lengkap Guru, Mapel, Kelas, dan Jadwal Mengajar (Assignment Guru)
     * GET /api/cbt-sync/data
     */
    public function exportData(Request $request): JsonResponse
    {
        if (!$this->verifyKey($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: API Key tidak valid.',
            ], 401);
        }

        $schoolId = $request->input('school_id');
        $type = $request->input('type', 'all'); // 'all', 'guru', 'mapel', 'kelas'

        $schoolName = null;
        if (!empty($schoolId)) {
            $school = School::find($schoolId);
            $schoolName = $school?->name;
        }

        $result = [
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'school_id' => $schoolId,
            'school_name' => $schoolName,
        ];

        // 1. Ambil Data Guru
        if (in_array($type, ['all', 'guru'])) {
            $guruQuery = Guru::with(['user:id,email,username', 'school:id,name']);
            if (!empty($schoolId)) {
                $guruQuery->where('school_id', $schoolId);
            }

            $result['gurus'] = $guruQuery->get()->map(function ($guru) {
                return [
                    'id' => $guru->id,
                    'nama' => $guru->nama,
                    'nip' => $guru->nip,
                    'no_wa' => $guru->no_wa,
                    'email' => $guru->user?->email,
                    'school_id' => $guru->school_id,
                    'school_name' => $guru->school?->name,
                ];
            });
        }

        // 2. Ambil Data Mapel
        if (in_array($type, ['all', 'mapel'])) {
            $mapelQuery = Mapel::with(['school:id,name']);
            if (!empty($schoolId)) {
                $mapelQuery->where('school_id', $schoolId);
            }

            $result['mapels'] = $mapelQuery->get()->map(function ($mapel) {
                return [
                    'id' => $mapel->id,
                    'nama_mapel' => $mapel->nama_mapel,
                    'school_id' => $mapel->school_id,
                    'school_name' => $mapel->school?->name,
                ];
            });
        }

        // 3. Ambil Data Kelas
        if (in_array($type, ['all', 'kelas'])) {
            $kelasQuery = Kelas::with(['school:id,name', 'jurusan:id,nama_jurusan', 'waliKelas:id,nama']);
            if (!empty($schoolId)) {
                $kelasQuery->where('school_id', $schoolId);
            }

            $result['kelases'] = $kelasQuery->get()->map(function ($kelas) {
                return [
                    'id' => $kelas->id,
                    'nama_kelas' => $kelas->nama_kelas,
                    'tingkat' => $kelas->tingkat,
                    'jurusan' => $kelas->jurusan?->nama_jurusan,
                    'wali_kelas' => $kelas->waliKelas?->nama,
                    'school_id' => $kelas->school_id,
                    'school_name' => $kelas->school?->name,
                ];
            });
        }

        // 4. Ambil Pemetaan Jadwal Mengajar (Guru mengajar Mapel apa di Kelas mana)
        $jadwalQuery = JadwalPelajaran::whereNotNull('guru_id')
            ->whereNotNull('mapel_id')
            ->whereNotNull('kelas_id')
            ->select('guru_id', 'mapel_id', 'kelas_id', 'school_id')
            ->distinct();

        if (!empty($schoolId)) {
            $jadwalQuery->where('school_id', $schoolId);
        }

        $result['assignments'] = $jadwalQuery->get()->map(function ($j) {
            return [
                'guru_sync_id' => $j->guru_id,
                'mapel_sync_id' => $j->mapel_id,
                'kelas_sync_id' => $j->kelas_id,
                'school_id' => $j->school_id,
            ];
        });

        return response()->json($result);
    }
}
