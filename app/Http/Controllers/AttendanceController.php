<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\Guru;
use App\Models\Jadwal;
use App\Models\Setting;
use App\Models\GateCard;
use App\Models\TeacherCheckoutSession;
use App\Models\AbsensiGuru;
use App\Models\Kegiatan;
use App\Models\KegiatanAttendance;
use App\Models\UserFaceData;
use App\Models\User;
use App\Services\WhatsAppService;
use App\Services\FcmNotificationService;
use App\Services\AttendanceCoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected $wa;
    protected $attendanceCore;

    public function __construct(WhatsAppService $wa, AttendanceCoreService $attendanceCore)
    {
        $this->wa = $wa;
        $this->attendanceCore = $attendanceCore;
    }

    public function scannerUsbView(Request $request)
    {
        $today = date('Y-m-d');
        $user = auth()->user();
        $schoolId = $user ? $user->school_id : null;

        $siswaQuery = Siswa::query();
        $attendanceQuery = Attendance::with(['student.kelas'])->where('tanggal', $today);

        if ($schoolId) {
            $siswaQuery->where('school_id', $schoolId);
            $attendanceQuery->whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }

        $totalSiswa = $siswaQuery->count();
        $totalMasuk = (clone $attendanceQuery)->whereNotNull('jam_masuk')->count();
        $totalTepatWaktu = (clone $attendanceQuery)->where('status', 'H')->count();
        $totalTerlambat = (clone $attendanceQuery)->where('status', 'T')->count();
        $totalIzinSakit = (clone $attendanceQuery)->whereIn('status', ['I', 'S'])->count();
        $totalPulang = (clone $attendanceQuery)->whereNotNull('jam_pulang')->count();
        $totalSudahAbsen = (clone $attendanceQuery)->count();
        $totalBelumAbsen = max(0, $totalSiswa - $totalMasuk);

        $recentAttendances = (clone $attendanceQuery)->orderBy('updated_at', 'desc')->limit(50)->get();

        $initialAttendances = $recentAttendances->map(function($att) {
            $student = $att->student;
            $masukFormatted = $att->jam_masuk ? \Carbon\Carbon::parse($att->jam_masuk)->format('H:i') : '--:--';
            $pulangFormatted = $att->jam_pulang ? \Carbon\Carbon::parse($att->jam_pulang)->format('H:i') : '--:--';
            $waktuFormatted = $att->updated_at ? \Carbon\Carbon::parse($att->updated_at)->format('H:i') : ($pulangFormatted !== '--:--' ? $pulangFormatted : $masukFormatted);

            $statusLabel = 'Hadir';
            $statusType = 'success';
            if ($att->jam_pulang) {
                $statusLabel = 'Pulang';
                $statusType = 'pulang';
            } elseif ($att->status === 'T') {
                $statusLabel = 'Terlambat';
                $statusType = 'warning';
            } elseif (in_array($att->status, ['I', 'S'])) {
                $statusLabel = $att->status === 'S' ? 'Sakit' : 'Izin';
                $statusType = 'info';
            }

            return [
                'id' => $att->id,
                'personKey' => 'siswa:' . $att->student_id,
                'waktu' => $waktuFormatted,
                'nama' => $student->nama ?? 'Siswa',
                'nis' => $student->nis ?? '-',
                'foto' => $student->foto_url ?? null,
                'kelas' => $student?->kelas?->nama_kelas ?? '-',
                'masuk' => $masukFormatted,
                'pulang' => $pulangFormatted,
                'status' => $statusLabel,
                'statusType' => $statusType,
                'keterangan' => $att->keterangan ?: ($statusLabel === 'Terlambat' ? 'Terlambat' : 'Tepat Waktu'),
            ];
        })->values()->toArray();

        $indexHari = (int) now()->format('N');
        $jadwal = Jadwal::where('index_hari', $indexHari)
            ->where('is_active', 1)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->first();

        return view('absensi.scanner-usb', compact(
            'today', 'totalSiswa', 'totalMasuk', 'totalTepatWaktu', 'totalTerlambat', 'totalIzinSakit', 'totalPulang', 'totalSudahAbsen', 'totalBelumAbsen', 'recentAttendances', 'initialAttendances', 'jadwal'
        ));
    }

    public function scannerFaceView(Request $request)
    {
        $today = date('Y-m-d');
        $user = auth()->user();
        $schoolId = $user ? $user->school_id : null;

        $siswaQuery = Siswa::query();
        $attendanceQuery = Attendance::with(['student.kelas'])->where('tanggal', $today);

        if ($schoolId) {
            $siswaQuery->where('school_id', $schoolId);
            $attendanceQuery->whereHas('student', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }

        $totalSiswa = $siswaQuery->count();
        $totalMasuk = (clone $attendanceQuery)->whereNotNull('jam_masuk')->count();
        $totalTepatWaktu = (clone $attendanceQuery)->where('status', 'H')->count();
        $totalTerlambat = (clone $attendanceQuery)->where('status', 'T')->count();
        $totalIzinSakit = (clone $attendanceQuery)->whereIn('status', ['I', 'S'])->count();
        $totalPulang = (clone $attendanceQuery)->whereNotNull('jam_pulang')->count();
        $totalSudahAbsen = (clone $attendanceQuery)->count();
        $totalBelumAbsen = max(0, $totalSiswa - $totalMasuk);

        $recentAttendances = (clone $attendanceQuery)->orderBy('updated_at', 'desc')->limit(50)->get();

        $initialAttendances = $recentAttendances->map(function($att) {
            $student = $att->student;
            $masukFormatted = $att->jam_masuk ? \Carbon\Carbon::parse($att->jam_masuk)->format('H:i') : '--:--';
            $pulangFormatted = $att->jam_pulang ? \Carbon\Carbon::parse($att->jam_pulang)->format('H:i') : '--:--';
            $waktuFormatted = $att->updated_at ? \Carbon\Carbon::parse($att->updated_at)->format('H:i') : ($pulangFormatted !== '--:--' ? $pulangFormatted : $masukFormatted);

            $statusLabel = 'Hadir';
            $statusType = 'success';
            if ($att->jam_pulang) {
                $statusLabel = 'Pulang';
                $statusType = 'pulang';
            } elseif ($att->status === 'T') {
                $statusLabel = 'Terlambat';
                $statusType = 'warning';
            } elseif (in_array($att->status, ['I', 'S'])) {
                $statusLabel = $att->status === 'S' ? 'Sakit' : 'Izin';
                $statusType = 'info';
            }

            return [
                'id' => $att->id,
                'personKey' => 'siswa:' . $att->student_id,
                'waktu' => $waktuFormatted,
                'nama' => $student->nama ?? 'Siswa',
                'nis' => $student->nis ?? '-',
                'foto' => $student->foto_url ?? null,
                'kelas' => $student?->kelas?->nama_kelas ?? '-',
                'masuk' => $masukFormatted,
                'pulang' => $pulangFormatted,
                'status' => $statusLabel,
                'statusType' => $statusType,
                'keterangan' => $att->keterangan ?: ($statusLabel === 'Terlambat' ? 'Terlambat' : 'Tepat Waktu'),
                'is_face' => (bool) $att->is_face_matched,
            ];
        })->values()->toArray();

        $indexHari = (int) now()->format('N');
        $jadwal = Jadwal::where('index_hari', $indexHari)
            ->where('is_active', 1)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->first();

        $registeredFaceCount = UserFaceData::whereNotNull('face_embedding')
            ->when($schoolId, function($q) use ($schoolId) {
                $q->where(function($sub) use ($schoolId) {
                    $sub->whereHas('siswa', fn($s) => $s->where('school_id', $schoolId))
                        ->orWhereHas('guru', fn($g) => $g->where('school_id', $schoolId));
                });
            })
            ->count();

        $isFaceEnabled = app(\App\Services\LicenseService::class)->isFaceFeatureEnabled($user?->school);
        $school = $user?->school;

        return view('absensi.scanner-face', compact(
            'today', 'totalSiswa', 'totalMasuk', 'totalTepatWaktu', 'totalTerlambat', 'totalIzinSakit', 'totalPulang', 'totalSudahAbsen', 'totalBelumAbsen', 'recentAttendances', 'initialAttendances', 'jadwal', 'registeredFaceCount', 'isFaceEnabled', 'school'
        ));
    }

    public function getFaceDescriptors(Request $request)
    {
        $user = auth()->user();
        $isFaceEnabled = app(\App\Services\LicenseService::class)->isFaceFeatureEnabled($user?->school);
        if (!$isFaceEnabled) {
            return response()->json([
                'ok' => false,
                'message' => 'Fitur Face Recognition scanner dinonaktifkan oleh SuperAdmin.',
                'descriptors' => [],
                'students' => []
            ], 403);
        }

        $schoolId = $user?->school_id;

        $faceRecords = UserFaceData::whereNotNull('face_embedding')
            ->with(['siswa.kelas', 'guru'])
            ->when($schoolId, function ($q) use ($schoolId) {
                $q->where(function ($sub) use ($schoolId) {
                    $sub->whereHas('siswa', fn($s) => $s->where('school_id', $schoolId))
                        ->orWhereHas('guru', fn($g) => $g->where('school_id', $schoolId));
                });
            })
            ->get();

        $descriptors = [];
        $registeredSiswaMap = [];
        $server512Count = 0;

        foreach ($faceRecords as $fr) {
            $unpacked = \App\Services\FaceRecognitionService::unpackEmbedding($fr->face_embedding);
            $v512 = $unpacked['v512'];

            if (empty($v512)) {
                continue;
            }
            $server512Count++;

            if ($fr->siswa) {
                $registeredSiswaMap[$fr->siswa->id] = true;
                $descriptors[] = [
                    'id' => $fr->siswa->id,
                    'type' => 'siswa',
                    'nama' => $fr->siswa->nama,
                    'nis' => $fr->siswa->nis ?? '-',
                    'kelas' => $fr->siswa->kelas->nama_kelas ?? '-',
                    'foto' => $fr->siswa->foto_url ?? null,
                    'has_512' => true,
                ];
            } elseif ($fr->guru) {
                $descriptors[] = [
                    'id' => $fr->guru->id,
                    'type' => 'guru',
                    'nama' => $fr->guru->nama,
                    'nis' => $fr->guru->nip ?? '-',
                    'kelas' => 'Guru / Pegawai',
                    'foto' => $fr->guru->foto_url ?? null,
                    'has_512' => true,
                ];
            }
        }

        // Return candidate students list for quick search & enrollment in kiosk (O(1) lookup, bebas N+1 query)
        $studentsList = Siswa::select('id', 'nama', 'nis', 'kelas_id', 'foto')
            ->with('kelas:id,nama_kelas')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('nama', 'asc')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'nama' => $s->nama,
                'nis' => $s->nis ?? '-',
                'kelas' => $s->kelas->nama_kelas ?? '-',
                'foto' => $s->foto_url ?? null,
                'has_face' => isset($registeredSiswaMap[$s->id]),
            ]);

        return response()->json([
            'ok' => true,
            'count' => count($descriptors),
            'server_512_count' => $server512Count,
            'descriptors' => $descriptors,
            'students' => $studentsList,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
          ->header('Pragma', 'no-cache');
    }

    public function scanFaceAttendance(Request $request)
    {
        $user = auth()->user();
        $isFaceEnabled = app(\App\Services\LicenseService::class)->isFaceFeatureEnabled($user?->school);
        if (!$isFaceEnabled) {
            return response()->json([
                'ok' => false,
                'status' => 'locked',
                'message' => 'Fitur Face Recognition scanner dinonaktifkan oleh SuperAdmin untuk sekolah ini.'
            ], 403);
        }

        $request->validate([
            'mode' => 'nullable|string|in:auto,masuk,pulang',
            'photo_snapshot' => 'required|string',
        ]);

        $personId = null;
        $type = 'siswa';
        $similarity = 0.85;
        $reqMode = $request->input('mode', 'auto');
        $photoSnapshot = $request->input('photo_snapshot');

        $schoolId = auth()->user()?->school_id;

        // Identifikasi 1:N server-side murni via Python NumPy Matrix (512-dimensi InsightFace + CLAHE)
        $serverErrorMsg = null;
        $engineOffline = false;
        $tempPath = null;
        try {
            $imageParts = explode(';base64,', $photoSnapshot);
            $imgData = base64_decode($imageParts[1] ?? $photoSnapshot);
            $tempPath = sys_get_temp_dir() . '/kiosk_scan_' . uniqid() . '.jpg';
            file_put_contents($tempPath, $imgData);

            $candidates = UserFaceData::whereNotNull('face_embedding')
                ->with(['siswa', 'guru'])
                ->when($schoolId, function ($q) use ($schoolId) {
                    $q->where(function ($sub) use ($schoolId) {
                        $sub->whereHas('siswa', fn($s) => $s->where('school_id', $schoolId))
                            ->orWhereHas('guru', fn($g) => $g->where('school_id', $schoolId));
                    });
                })
                ->get();

            $identifyRes = \App\Services\FaceRecognitionService::identifyFace($tempPath, $candidates);

            if (!empty($identifyRes['is_match']) && !empty($identifyRes['matched_record'])) {
                $matchedRecord = $identifyRes['matched_record'];
                $personId = $matchedRecord->siswa_id ?: $matchedRecord->guru_id;
                $type = $matchedRecord->siswa_id ? 'siswa' : 'guru';
                $similarity = (float) ($identifyRes['similarity'] ?? 0.85);
            } else {
                $engineOffline = !empty($identifyRes['engine_offline']);
                $simPct = $identifyRes['similarity_percent'] ?? 0;
                if ($engineOffline) {
                    $serverErrorMsg = $identifyRes['message'] ?? 'Layanan AI Face Recognition sedang tidak aktif/offline.';
                } elseif ($simPct > 0) {
                    $serverErrorMsg = "Wajah tidak cocok (Kemiripan: {$simPct}%, minimal " . round(\App\Services\FaceRecognitionService::getThreshold() * 100) . "%).";
                } elseif (!empty($identifyRes['message'])) {
                    $serverErrorMsg = $identifyRes['message'];
                }
            }
        } catch (\Throwable $e) {
            Log::error('Server recognition error: ' . $e->getMessage());
            $engineOffline = true;
            $serverErrorMsg = 'Gagal terhubung ke layanan AI Face Recognition: ' . $e->getMessage();
        } finally {
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }

        if (empty($personId)) {
            return response()->json([
                'ok' => false,
                'status' => $engineOffline ? 'error' : 'warning',
                'sound' => $engineOffline ? 'error' : 'warning',
                'message' => $serverErrorMsg ?: 'Wajah tidak dikenali dalam database sistem.',
            ], $engineOffline ? 503 : 404);
        }

        $response = $this->processFaceAttendanceFor($type, (int) $personId, $similarity, $reqMode, $schoolId);

        return $this->withFacePerson($response, $type, (int) $personId);
    }

    /**
     * Jarak minimal (menit) antara absen masuk dan absen pulang pada mode 'auto'.
     * Mencegah siswa/guru yang masih berdiri di depan kiosk langsung tercatat PULANG
     * beberapa detik setelah absen masuk (mis. saat ada sesi pembuka gerbang aktif).
     */
    private const FACE_MIN_CHECKOUT_GAP_MINUTES = 10;

    /**
     * Sisipkan identitas orang yang dikenali ke payload JSON agar kiosk dapat
     * menerapkan cooldown per orang & tidak menduplikasi baris aktivitas.
     */
    private function withFacePerson($response, string $type, int $personId)
    {
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $payload = $response->getData(true);
            if (is_array($payload)) {
                $payload['person'] = [
                    'type' => $type,
                    'id' => $personId,
                    'key' => $type . ':' . $personId,
                ];
                $response->setData($payload);
            }
        }

        return $response;
    }

    /**
     * Cek apakah absen masuk baru saja terjadi (di bawah FACE_MIN_CHECKOUT_GAP_MINUTES).
     */
    private function isWithinCheckoutGap($tanggal, $jamMasuk, Carbon $now): bool
    {
        return $this->attendanceCore->isWithinCheckoutGap($tanggal, $jamMasuk, $now);
    }

    private function processFaceAttendanceFor(string $type, int $personId, float $similarity, string $reqMode, $schoolId)
    {
        if ($type === 'siswa') {
            $siswa = Siswa::with(['kelas', 'school'])->where('id', $personId)->first();
            if (!$siswa) {
                return response()->json([
                    'ok' => false,
                    'status' => 'error',
                    'sound' => 'error',
                    'message' => 'Data siswa tidak ditemukan di sistem.',
                ], 404);
            }

            if ($schoolId && $siswa->school_id != $schoolId) {
                return response()->json([
                    'ok' => false,
                    'status' => 'error',
                    'sound' => 'error',
                    'message' => 'Siswa tidak terdaftar di unit sekolah ini.',
                ], 403);
            }

            $res = $this->attendanceCore->recordStudentAttendance($siswa, [
                'school_id' => $schoolId,
                'mode' => $reqMode,
                'channel' => 'web_face',
                'similarity' => $similarity,
            ]);

            $httpCode = 200;
            if (!$res['ok'] && !in_array($res['status'] ?? '', ['warning'])) {
                $httpCode = 422;
            }

            return response()->json($res, $httpCode);
        }

        if ($type === 'guru') {
            $guru = Guru::with('school')->where('id', $personId)->first();
            if (!$guru) {
                return response()->json([
                    'ok' => false,
                    'status' => 'error',
                    'sound' => 'error',
                    'message' => 'Data guru/staf tidak ditemukan di sistem.',
                ], 404);
            }

            if ($schoolId && $guru->school_id != $schoolId) {
                return response()->json([
                    'ok' => false,
                    'status' => 'error',
                    'sound' => 'error',
                    'message' => 'Guru tidak terdaftar di unit sekolah ini.',
                ], 403);
            }

            $res = $this->attendanceCore->recordTeacherAttendance($guru, [
                'school_id' => $schoolId,
                'mode' => $reqMode,
                'channel' => 'web_face',
                'similarity' => $similarity,
            ]);

            $httpCode = 200;
            if (!$res['ok'] && !in_array($res['status'] ?? '', ['warning'])) {
                $httpCode = 422;
            }

            return response()->json($res, $httpCode);
        }

        return response()->json(['ok' => false, 'status' => 'error', 'message' => 'Tipe person tidak valid'], 400);
    }

    public function registerFaceKiosk(Request $request)
    {
        $user = auth()->user();
        $isFaceEnabled = app(\App\Services\LicenseService::class)->isFaceFeatureEnabled($user?->school);
        if (!$isFaceEnabled) {
            return response()->json([
                'ok' => false,
                'message' => 'Fitur Face Recognition scanner dinonaktifkan oleh SuperAdmin.'
            ], 403);
        }

        $request->validate([
            'person_id' => 'required|integer',
            'type' => 'required|in:siswa,guru',
            'photo' => 'required|string|max:6000000', // base64 (maks ~4.5 MB biner)
            'enroll_mode' => 'nullable|in:append,replace',
        ]);

        $schoolId = $user?->school_id;
        $type = $request->input('type');
        $enrollMode = $request->input('enroll_mode', 'append');
        $label = $type === 'siswa' ? 'Siswa' : 'Guru';

        // 1. Ambil data orang HANYA dari sekolah operator (cegah IDOR lintas sekolah)
        $personQuery = $type === 'siswa' ? Siswa::with('kelas') : Guru::query();
        $person = $personQuery
            ->where('id', $request->input('person_id'))
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->first();

        if (!$person) {
            return response()->json([
                'ok' => false,
                'message' => "{$label} tidak ditemukan di sekolah ini."
            ], 404);
        }

        // 2. Validasi & decode foto base64 (harus gambar JPEG/PNG/WEBP yang valid)
        $imageData = $this->decodeBase64Image((string) $request->input('photo'));
        if ($imageData === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Format foto tidak valid. Silakan ulangi pengambilan foto.'
            ], 422);
        }

        // Decode photo ke file temporary (tidak disimpan ke disk permanen untuk hemat storage)
        $tempPath = sys_get_temp_dir() . '/kiosk_enroll_' . uniqid() . '.jpg';
        try {
            file_put_contents($tempPath, $imageData);

            // 3. Ekstrak vektor 512-d mentah dari foto baru (blending dilakukan di PHP setelah validasi)
            $extract = \App\Services\FaceRecognitionService::extractVector($tempPath);
            if (empty($extract['success']) || empty($extract['embedding']) || !is_array($extract['embedding']) || count($extract['embedding']) !== 512) {
                $statusCode = !empty($extract['engine_offline']) ? 503 : 422;
                return response()->json([
                    'ok' => false,
                    'message' => $extract['message'] ?? 'Gagal memproses data wajah. Pastikan Face Engine aktif.'
                ], $statusCode);
            }

            $newVec = \App\Services\FaceRecognitionService::normalizeVector($extract['embedding']);
            $threshold = \App\Services\FaceRecognitionService::getThreshold();

            // 4. Cegah wajah yang sama terdaftar atas nama orang lain
            $duplicate = \App\Services\FaceRecognitionService::findDuplicateFace($newVec, $schoolId, $type, (int) $person->id, $threshold);
            if ($duplicate) {
                return response()->json([
                    'ok' => false,
                    'code' => 'duplicate_face',
                    'message' => "Wajah ini sudah terdaftar atas nama {$duplicate['nama']} ({$duplicate['label']}, kemiripan {$duplicate['similarity_percent']}%). "
                        . "Pastikan orang di depan kamera adalah {$person->nama}. Jika data lama salah, hapus Face ID {$duplicate['nama']} terlebih dahulu.",
                    'duplicate' => $duplicate,
                ], 409);
            }

            $fkColumn = $type === 'siswa' ? 'siswa_id' : 'guru_id';
            $existingFace = UserFaceData::where($fkColumn, $person->id)->first();
            $oldVec = $existingFace
                ? \App\Services\FaceRecognitionService::unpackEmbedding($existingFace->face_embedding)['v512']
                : null;

            // 5. Mode tambah sampel: wajah baru wajib cocok dengan Face ID lama agar template tidak tercampur wajah orang lain
            $isMerged = false;
            if (!empty($oldVec) && $enrollMode === 'append') {
                $selfSim = \App\Services\FaceRecognitionService::cosineSimilarity($newVec, $oldVec);
                if ($selfSim < $threshold) {
                    $selfPct = round($selfSim * 100, 1);
                    $minPct = round($threshold * 100);
                    return response()->json([
                        'ok' => false,
                        'code' => 'requires_replace',
                        'message' => "Wajah tidak cocok dengan Face ID {$person->nama} yang sudah terdaftar (kemiripan {$selfPct}%, minimal {$minPct}%). "
                            . "Jika Anda yakin ini orang yang benar, pilih mode 'Ganti Total' lalu daftarkan ulang.",
                        'similarity_percent' => $selfPct,
                    ], 409);
                }
                $finalVec = \App\Services\FaceRecognitionService::blendCentroid($oldVec, $newVec, 0.5);
                $isMerged = true;
            } else {
                $finalVec = $newVec;
            }

            $targetUser = $this->resolveFaceOwnerUser($person, $type);

            DB::transaction(function () use ($person, $targetUser, $fkColumn, $finalVec) {
                if ($person->user_id !== $targetUser->id) {
                    $person->user_id = $targetUser->id;
                    $person->save();
                }

                UserFaceData::updateOrCreate(
                    [$fkColumn => $person->id],
                    [
                        'user_id' => $targetUser->id,
                        'face_embedding' => \App\Services\FaceRecognitionService::packEmbedding($finalVec),
                        'sample_photo_path' => null,
                        'is_verified' => true,
                    ]
                );
            });

            if ($isMerged) {
                $message = "Sampel wajah tambahan {$person->nama} berhasil digabungkan!";
            } elseif (!empty($oldVec)) {
                $message = "Face ID {$person->nama} berhasil diganti total dengan wajah baru!";
            } else {
                $message = "Face ID " . strtolower($label) . " {$person->nama} berhasil didaftarkan!";
            }

            return response()->json([
                'ok' => true,
                'message' => $message,
                'descriptor' => [
                    'id' => $person->id,
                    'type' => $type,
                    'nama' => $person->nama,
                    'nis' => ($type === 'siswa' ? $person->nis : $person->nip) ?? '-',
                    'kelas' => $type === 'siswa' ? ($person->kelas->nama_kelas ?? '-') : 'Dewan Guru',
                    'foto' => $person->foto_url ?? null,
                    'has_512' => true,
                ]
            ]);
        } finally {
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Decode data URL / base64 menjadi biner gambar. Mengembalikan null jika bukan gambar valid.
     */
    private function decodeBase64Image(string $data): ?string
    {
        $parts = explode(';base64,', $data, 2);
        $raw = base64_decode($parts[1] ?? $parts[0], true);
        if ($raw === false || strlen($raw) < 1024) {
            return null;
        }

        $info = @getimagesizefromstring($raw);
        if (!$info || !in_array($info[2] ?? null, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }

        return $raw;
    }

    /**
     * Generate password default dari tanggal lahir dengan format ddmmyyyy (contoh: 17082005).
     * Jika tanggal lahir kosong, gunakan fallback NIS/NIP atau 01012000.
     */
    private function generateDefaultBirthDatePassword($person, ?string $fallback = null): string
    {
        if (!empty($person->tgl_lahir)) {
            try {
                return Carbon::parse($person->tgl_lahir)->format('dmY');
            } catch (\Throwable $e) {
                // Ignore parse error, use fallback
            }
        }

        return $fallback ?: '01012000';
    }

    /**
     * Cari / buat akun User pemilik data wajah (kolom user_id pada user_face_data wajib terisi).
     * Akun baru dibuat dengan password default dari tanggal lahir format ddmmyyyy.
     */
    private function resolveFaceOwnerUser($person, string $type): User
    {
        $targetUser = $person->user;

        if ($type === 'siswa') {
            if (!$targetUser && !empty($person->nis)) {
                $targetUser = User::where('username', 'siswa_' . $person->nis)
                    ->orWhere('username', $person->nis)
                    ->first();
            }
            if (!$targetUser) {
                $defaultPass = $this->generateDefaultBirthDatePassword($person, (string) ($person->nis ?: '01012000'));
                $targetUser = User::create([
                    'full_name' => $person->nama,
                    'username' => 'siswa_' . ($person->nis ?: $person->id),
                    'email' => ($person->nis ?: $person->id) . '@siswa.local',
                    'password_hash' => Hash::make($defaultPass),
                    'plain_password' => $defaultPass,
                    'role' => 'student',
                    'school_id' => $person->school_id,
                ]);
            }
            return $targetUser;
        }

        if (!$targetUser && !empty($person->nip)) {
            $targetUser = User::where('username', $person->nip)->first();
        }
        if (!$targetUser) {
            $defaultPass = $this->generateDefaultBirthDatePassword($person, (string) ($person->nip ?: '01012000'));
            $targetUser = User::create([
                'full_name' => $person->nama,
                'username' => $person->nip ?: 'guru_' . $person->id,
                'email' => ($person->nip ?: 'guru_' . $person->id) . '@guru.local',
                'password_hash' => Hash::make($defaultPass),
                'plain_password' => $defaultPass,
                'role' => 'teacher',
                'school_id' => $person->school_id,
            ]);
        }
        return $targetUser;
    }

    public function index(Request $request)
    {
        // Filter by Date (default today)
        $tanggal = $request->input('tanggal', date('Y-m-d'));

        // Filter by Class (optional)
        $kelasId = $request->input('kelas_id');
        
        // Filter by Status (optional)
        $statusFilter = $request->input('status');

        // Fetch all students (filtered by class if needed) to ensure we list everyone
        $siswaQuery = Siswa::with('kelas')->orderBy('nama');

        // Filter by school_id for non-super admin users
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $siswaQuery->where('school_id', auth()->user()->school_id);
        }

        // Apply Wali Kelas logic (supports guru who also acts as wali kelas for 1 or more classes)
        if (auth()->user() && auth()->user()->isWaliKelas()) {
            $managedKelasIds = auth()->user()->getManagedKelasIds();
            if (!empty($managedKelasIds)) {
                $siswaQuery->whereIn('kelas_id', $managedKelasIds);
                // Restrict the class filter dropdown list strictly to classes managed by this teacher
                $kelasQuery = Kelas::whereIn('id', $managedKelasIds)->orderBy('nama_kelas');
            } else {
                $siswaQuery->where('id', -1);
                $kelasQuery = Kelas::where('id', -1);
            }
        } else {
            $kelasQuery = Kelas::orderBy('nama_kelas');
            if (auth()->user() && !auth()->user()->isSuperAdmin()) {
                $kelasQuery->where('school_id', auth()->user()->school_id);
            }
        }

        if ($kelasId) {
            $siswaQuery->where('kelas_id', $kelasId);
        }
        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $siswaQuery->where('nama', 'like', "%{$search}%");
        }
        
        // Apply Status Filter
        if ($statusFilter && $statusFilter !== '') {
            if ($statusFilter === 'A') {
                // Alpha means NO attendance record for that date, or an explicit 'A' record
                $siswaQuery->where(function($q) use ($tanggal) {
                    $q->whereDoesntHave('attendance', function($q2) use ($tanggal) {
                        $q2->where('tanggal', $tanggal);
                    })->orWhereHas('attendance', function($q2) use ($tanggal) {
                        $q2->where('tanggal', $tanggal)->where('status', 'A');
                    });
                });
            } else {
                $siswaQuery->whereHas('attendance', function($q) use ($tanggal, $statusFilter) {
                    $q->where('tanggal', $tanggal)->where('status', $statusFilter);
                });
            }
        }

        $allSiswa = $siswaQuery->paginate(50)->withQueryString();

        // Fetch attendance for the date
        $attendance = Attendance::where('tanggal', $tanggal)
            ->whereIn('student_id', $allSiswa->pluck('id'))
            ->get()->keyBy('student_id');

        // Prepare data for view
        $data = [];
        foreach ($allSiswa as $s) {
            $att = $attendance[$s->id] ?? null;
            
            $status = 'A';
            $keterangan = '-';
            $jamMasuk = '-';
            
            if ($att) {
                $status = $att->status;
                $keterangan = $att->keterangan ?: '-';
                $jamMasuk = $att->jam_masuk ?: '-';
            } else if ($s->is_khusus) {
                $dayIndex = \Carbon\Carbon::parse($tanggal)->dayOfWeekIso;
                $isSchoolDay = \App\Models\Jadwal::where('school_id', $s->school_id)
                    ->where('index_hari', $dayIndex)
                    ->where('is_active', true)
                    ->exists();
                if ($isSchoolDay) {
                    $status = 'H';
                    $keterangan = 'Siswa Khusus (Masuk Otomatis)';
                    $jamMasuk = '07:00';
                }
            }

            $data[] = (object) [
                'id' => $s->id, // Siswa ID
                'nama' => $s->nama,
                'kelas' => $s->kelas->nama_kelas ?? '-',
                'absen_id' => $att ? $att->id : null,
                'jam_masuk' => $jamMasuk,
                'jam_pulang' => ($att && $att->jam_pulang) ? $att->jam_pulang : '-',
                'status' => $status,
                'keterangan' => $keterangan,
            ];
        }

        $allKelas = $kelasQuery->get();

        return view('absensi.index', compact('data', 'allSiswa', 'tanggal', 'allKelas', 'kelasId', 'statusFilter'));
    }

    // Manual Update (e.g., Izin, Sakit)
    public function update(Request $request)
    {
        $request->validate([
            'student_id' => 'required',
            'tanggal' => 'required|date',
            'status' => 'required|in:H,I,S,A,B,T',
            'keterangan' => 'nullable',
            'jam_masuk' => 'nullable', // Allow time format
            'jam_pulang' => 'nullable',
        ]);

        $status = $request->status;
        $studentId = $request->student_id;
        $date = $request->tanggal; // Should match the filter date

        // Check if record exists
        $att = Attendance::where('student_id', $studentId)->where('tanggal', $date)->first();

        // format time or null (Time only)
        $jamMasuk = ($request->jam_masuk && $request->jam_masuk != '-') ? \Carbon\Carbon::parse($request->jam_masuk)->format('H:i:s') : null;
        $jamPulang = ($request->jam_pulang && $request->jam_pulang != '-') ? \Carbon\Carbon::parse($request->jam_pulang)->format('H:i:s') : null;

        if ($att) {
            $att->update([
                'status' => $status,
                'keterangan' => $request->keterangan,
                'jam_masuk' => $jamMasuk,
                'jam_pulang' => $jamPulang
            ]);
        } else {
            // Create new record
            Attendance::create([
                'student_id' => $studentId,
                'tanggal' => $date,
                'status' => $status,
                'keterangan' => $request->keterangan,
                'jam_masuk' => $jamMasuk,
                'jam_pulang' => $jamPulang
            ]);
        }

        // Send FCM Push Notification if status is Alpha or Bolos
        try {
            $student = Siswa::find($studentId);
            if ($student) {
                if ($status === 'A') {
                    FcmNotificationService::sendToSiswa(
                        $student,
                        "Pemberitahuan Tidak Hadir (Alpha)",
                        "Halo {$student->nama}, status absensi Anda untuk tanggal {$date} telah dicatat sebagai Alpha.",
                        [
                            'type' => 'checkin_siswa',
                            'status' => 'Alpha',
                            'jam' => now()->format('H:i'),
                            'tanggal' => $date,
                            'sub_type' => 'attendance_alpha',
                            'category' => 'alpha_siswa',
                        ]
                    );
                } elseif ($status === 'B') {
                    FcmNotificationService::sendToSiswa(
                        $student,
                        "Peringatan Bolos",
                        "Halo {$student->nama}, status absensi Anda untuk tanggal {$date} telah dicatat sebagai Bolos.",
                        [
                            'type' => 'checkout_siswa',
                            'status' => 'Bolos',
                            'jam' => now()->format('H:i'),
                            'tanggal' => $date,
                            'sub_type' => 'attendance_bolos',
                            'category' => 'bolos_siswa',
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return back()->with('success', 'Status absensi berhasil diperbarui.');
    }

    // Delete Attendance Record
    public function destroy(Request $request)
    {
        $request->validate([
            'student_id' => 'required',
            'tanggal' => 'required|date',
        ]);

        $studentId = $request->student_id;
        $date = $request->tanggal;

        // Find and delete the attendance record
        $att = Attendance::where('student_id', $studentId)->where('tanggal', $date)->first();

        if ($att) {
            $att->delete();
            return back()->with('success', 'Data absensi berhasil dihapus.');
        }

        return back()->with('error', 'Data absensi tidak ditemukan.');
    }

    // Bulk Update
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|string',
            'tanggal' => 'required|date',
            'status' => 'required|in:H,I,S,A,B,T',
            'keterangan' => 'nullable',
        ]);

        $studentIds = explode(',', $request->student_ids);
        $date = $request->tanggal;
        $status = $request->status;

        foreach ($studentIds as $id) {
            $att = Attendance::where('student_id', $id)->where('tanggal', $date)->first();

            if ($att) {
                $att->update([
                    'status' => $status,
                    'keterangan' => $request->keterangan,
                ]);
            } else {
                Attendance::create([
                    'student_id' => $id,
                    'tanggal' => $date,
                    'status' => $status,
                    'keterangan' => $request->keterangan,
                ]);
            }
        }

        return back()->with('success', count($studentIds) . ' status absensi berhasil diperbarui.');
    }

    // Bulk Destroy
    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|string',
            'tanggal' => 'required|date',
        ]);

        $studentIds = array_filter(explode(',', $request->student_ids));
        $date = $request->tanggal;

        if (empty($studentIds)) {
            return back()->with('error', 'Tidak ada siswa yang dipilih.');
        }

        $deletedCount = Attendance::whereIn('student_id', $studentIds)
            ->where('tanggal', $date)
            ->delete();

        return back()->with('success', count($studentIds) . ' data absensi siswa berhasil dihapus.');
    }

    /**
     * Process Scan from USB RFID Reader connected directly to PC/Browser.
     */
    public function scanUsbRfid(Request $request)
    {
        $request->validate([
            'uid' => 'required|string',
            'mode' => 'nullable|string|in:auto,masuk,pulang',
        ]);

        $rawUid = trim($request->input('uid'));
        $reqMode = $request->input('mode', 'auto');
        $user = auth()->user();

        return $this->processUsbRfidScan($rawUid, $reqMode, $user, now());
    }

    /**
     * Batch Sync Offline Scans queued locally during offline/network disruption.
     */
    public function syncOfflineScans(Request $request)
    {
        $records = $request->input('records', $request->input('scans', []));
        if (empty($records) || !is_array($records)) {
            return response()->json([
                'ok' => false,
                'message' => 'Tidak ada data antrean absensi offline untuk disinkronkan.',
            ], 422);
        }

        $user = auth()->user();
        $schoolTz = ($user && $user->school_id)
            ? Setting::where('school_id', $user->school_id)->where('setting_key', 'timezone')->value('setting_value')
            : null;
        $activeTz = $schoolTz ?: config('app.timezone', 'Asia/Jakarta');

        $syncedCount = 0;
        $failedCount = 0;
        $results = [];

        foreach ($records as $item) {
            $rawUid = trim($item['uid']);
            $reqMode = $item['mode'] ?? 'auto';
            try {
                $tappedAt = Carbon::parse($item['tapped_at'], $activeTz)->setTimezone($activeTz);
            } catch (\Exception $e) {
                $tappedAt = now($activeTz);
            }

            try {
                $res = $this->processUsbRfidScan($rawUid, $reqMode, $user, $tappedAt);
                $data = $res->getData(true);
                $isOk = !empty($data['ok']);
                if ($isOk) {
                    $syncedCount++;
                } else {
                    $failedCount++;
                }

                $results[] = [
                    'uid' => $rawUid,
                    'tapped_at' => $tappedAt->format('Y-m-d H:i:s'),
                    'ok' => $isOk,
                    'status' => $data['status'] ?? 'unknown',
                    'type' => $data['type'] ?? '',
                    'message' => $data['message'] ?? '',
                    'nama' => $data['data']['nama'] ?? '-',
                ];
            } catch (\Throwable $e) {
                $failedCount++;
                $results[] = [
                    'uid' => $rawUid,
                    'tapped_at' => $tappedAt->format('Y-m-d H:i:s'),
                    'ok' => false,
                    'status' => 'error',
                    'message' => $e->getMessage(),
                    'nama' => '-',
                ];
            }
        }

        return response()->json([
            'ok' => true,
            'message' => "Sinkronisasi offline selesai: {$syncedCount} berhasil, {$failedCount} gagal/peringatan.",
            'total' => count($records),
            'synced' => $syncedCount,
            'failed' => $failedCount,
            'results' => $results,
        ]);
    }

    /**
     * Get offline cache master data (students, teachers, gate cards) for the active school.
     */
    public function getScannerCache(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user ? $user->school_id : null;

        if ($request->boolean('ping')) {
            return response()->json([
                'ok' => true,
                'authenticated' => auth()->check(),
                'csrf_token' => csrf_token(),
                'school_id' => $schoolId,
                'timestamp' => now()->toIso8601String(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
              ->header('Pragma', 'no-cache');
        }

        if (!$schoolId) {
            return response()->json([
                'ok' => false,
                'csrf_token' => csrf_token(),
                'message' => 'Konteks sekolah tidak ditemukan.',
                'students' => [],
                'teachers' => [],
            ], 422);
        }

        $students = Siswa::where('school_id', $schoolId)
            ->whereNotNull('uid_rfid')
            ->where('uid_rfid', '!=', '')
            ->with('kelas:id,nama_kelas')
            ->get()
            ->map(function ($s) {
                return [
                    'uid' => strtoupper(trim($s->uid_rfid)),
                    'id' => $s->id,
                    'nama' => $s->nama,
                    'nis' => $s->nis ?? '-',
                    'kelas' => $s->kelas->nama_kelas ?? '-',
                    'foto' => $s->foto_url ?? null,
                    'role' => 'Siswa',
                ];
            });

        $teachers = Guru::where('school_id', $schoolId)
            ->whereNotNull('uid_rfid')
            ->where('uid_rfid', '!=', '')
            ->get()
            ->map(function ($g) {
                return [
                    'uid' => strtoupper(trim($g->uid_rfid)),
                    'id' => $g->id,
                    'nama' => $g->nama,
                    'nis' => $g->nip ?? '-',
                    'kelas' => 'Guru / Staf',
                    'foto' => $g->foto_url ?? null,
                    'role' => 'Guru / Staf',
                ];
            });

        $gateCards = GateCard::where('school_id', $schoolId)
            ->whereNotNull('uid_rfid')
            ->where('uid_rfid', '!=', '')
            ->get()
            ->map(function ($gc) {
                return [
                    'uid' => strtoupper(trim($gc->uid_rfid)),
                    'nama' => $gc->name,
                    'role' => 'Kartu Gerbang',
                    'kelas' => 'Akses Gerbang',
                ];
            });

        $allData = $students->concat($teachers)->concat($gateCards)->values();

        return response()->json([
            'ok' => true,
            'csrf_token' => csrf_token(),
            'school_id' => $schoolId,
            'timestamp' => now()->toIso8601String(),
            'count' => $allData->count(),
            'data' => $allData,
            'students' => $students,
            'teachers' => $teachers,
            'gate_cards' => $gateCards,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
          ->header('Pragma', 'no-cache');
    }

    /**
     * Core RFID Attendance Logic (usable for real-time USB scan and batch offline sync)
     */
    public function processUsbRfidScan(string $rawUid, string $reqMode = 'auto', $user = null, ?Carbon $customTime = null): \Illuminate\Http\JsonResponse
    {
        $uid = strtoupper(trim($rawUid));

        // Get school_id from authenticated user
        $schoolId = $user ? $user->school_id : null;

        // If user is super admin with null school_id, try to infer school from card
        if (!$schoolId) {
            $matchedSiswa = Siswa::whereRaw('UPPER(uid_rfid) = ?', [$uid])->first();
            if ($matchedSiswa) {
                $schoolId = $matchedSiswa->school_id;
            } else {
                $matchedGuru = Guru::whereRaw('UPPER(uid_rfid) = ?', [$uid])->first();
                if ($matchedGuru) {
                    $schoolId = $matchedGuru->school_id;
                }
            }
        }

        if (!$schoolId) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'sound' => 'error',
                'message' => 'Sekolah tidak ditemukan atau akun SuperAdmin belum memilih konteks sekolah.',
            ], 422);
        }

        // 1. Cek Kartu Gerbang (Gate Card)
        $gateCard = GateCard::with('guru')
            ->whereRaw('UPPER(uid_rfid) = ?', [$uid])
            ->where('school_id', $schoolId)
            ->first();

        if ($gateCard) {
            $res = $this->attendanceCore->handleGateCardScan($gateCard, [
                'school_id' => $schoolId,
                'uid' => $uid,
                'now' => $customTime,
            ]);
            return response()->json($res, $res['ok'] ? 200 : 422);
        }

        // 2. Cek Guru / Staf
        $matchedGuru = Guru::whereRaw('UPPER(uid_rfid) = ?', [$uid])
            ->where('school_id', $schoolId)
            ->first();

        if ($matchedGuru) {
            $res = $this->attendanceCore->recordTeacherAttendance($matchedGuru, [
                'school_id' => $schoolId,
                'mode' => $reqMode,
                'channel' => 'web_usb',
                'uid' => $uid,
                'now' => $customTime,
            ]);
            $httpCode = 200;
            if (!$res['ok'] && !in_array($res['status'] ?? '', ['warning'])) {
                $httpCode = 422;
            }
            return response()->json($res, $httpCode);
        }

        // 3. Cek Siswa
        $matchedSiswa = Siswa::with('kelas')
            ->whereRaw('UPPER(uid_rfid) = ?', [$uid])
            ->where('school_id', $schoolId)
            ->first();

        if ($matchedSiswa) {
            $res = $this->attendanceCore->recordStudentAttendance($matchedSiswa, [
                'school_id' => $schoolId,
                'mode' => $reqMode,
                'channel' => 'web_usb',
                'uid' => $uid,
                'now' => $customTime,
            ]);
            $httpCode = 200;
            if (!$res['ok'] && !in_array($res['status'] ?? '', ['warning'])) {
                $httpCode = 422;
            }
            return response()->json($res, $httpCode);
        }

        // 4. Kartu tidak terdaftar
        return response()->json([
            'ok' => false,
            'status' => 'error',
            'sound' => 'error',
            'message' => 'Kartu RFID tidak terdaftar pada Siswa maupun Guru/Staf.',
            'data' => [
                'nama' => 'Tidak Dikenal',
                'role' => 'Unknown',
                'kelas' => '-',
                'status' => 'Tidak Terdaftar',
                'jam' => ($customTime ? $customTime->format('H:i:s') : now()->format('H:i:s')),
                'uid' => $uid,
            ]
        ], 404);
    }

    /**
     * Helper to auto-record attendance for active kegiatan scheduled now.
     */
    private function recordActiveKegiatans($schoolId, $siswa, $now, $today, $keterangan = 'Auto dari Scan Mesin'): array
    {
        return $this->attendanceCore->recordActiveKegiatans($schoolId, $siswa, $now, $today, $keterangan);
    }

    private function calculateCosineSimilarity(array $vecA, array $vecB): float
    {
        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;
        $len = min(count($vecA), count($vecB));
        for ($i = 0; $i < $len; $i++) {
            $dot += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] * $vecA[$i];
            $normB += $vecB[$i] * $vecB[$i];
        }
        return ($normA > 0 && $normB > 0) ? ($dot / (sqrt($normA) * sqrt($normB))) : 0.0;
    }
}
