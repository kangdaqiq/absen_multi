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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    protected $wa;

    public function __construct(WhatsAppService $wa)
    {
        $this->wa = $wa;
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
        foreach ($faceRecords as $fr) {
            $embedding = json_decode($fr->face_embedding);
            if (is_string($embedding)) {
                $embedding = json_decode($embedding); // Handle double encoded json
            }
            // Kiosk web scanner uses 128-d embeddings (face-api.js). Skip 512-d (mobile InsightFace)
            if (!$embedding || !is_array($embedding) || count($embedding) !== 128) {
                continue;
            }

            if ($fr->siswa) {
                $descriptors[] = [
                    'id' => $fr->siswa->id,
                    'type' => 'siswa',
                    'nama' => $fr->siswa->nama,
                    'nis' => $fr->siswa->nis ?? '-',
                    'kelas' => $fr->siswa->kelas->nama_kelas ?? '-',
                    'foto' => $fr->siswa->foto_url ?? null,
                    'embedding' => $embedding,
                ];
            } elseif ($fr->guru) {
                $descriptors[] = [
                    'id' => $fr->guru->id,
                    'type' => 'guru',
                    'nama' => $fr->guru->nama,
                    'nis' => $fr->guru->nip ?? '-',
                    'kelas' => 'Guru / Pegawai',
                    'foto' => $fr->guru->foto_url ?? null,
                    'embedding' => $embedding,
                ];
            }
        }

        // Return candidate students list for quick search & enrollment in kiosk
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
                'has_face' => UserFaceData::where('siswa_id', $s->id)->whereNotNull('face_embedding')->exists()
            ]);

        return response()->json([
            'ok' => true,
            'count' => count($descriptors),
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
            'person_id' => 'required',
            'type' => 'nullable|string|in:siswa,guru',
            'similarity' => 'nullable|numeric',
            'mode' => 'nullable|string|in:auto,masuk,pulang',
            'photo_snapshot' => 'nullable|string',
        ]);

        $personId = $request->input('person_id');
        $type = $request->input('type', 'siswa');
        $similarity = (float) $request->input('similarity', 0.85);
        $reqMode = $request->input('mode', 'auto');
        $photoSnapshot = $request->input('photo_snapshot');

        $schoolId = auth()->user()?->school_id;
        $schoolTz = Setting::where('school_id', $schoolId)->where('setting_key', 'timezone')->value('setting_value') ?: config('app.timezone', 'Asia/Jakarta');
        $now = Carbon::now($schoolTz);
        $today = $now->format('Y-m-d');

        // Optional: Save face snapshot image for audit proof
        $savedSnapshotPath = null;
        if (!empty($photoSnapshot) && str_starts_with($photoSnapshot, 'data:image')) {
            try {
                $imageParts = explode(';base64,', $photoSnapshot);
                if (count($imageParts) === 2) {
                    $imageData = base64_decode($imageParts[1]);
                    $dir = 'attendance_photos/' . $today;
                    if (!Storage::disk('public')->exists($dir)) {
                        Storage::disk('public')->makeDirectory($dir);
                    }
                    $fileName = $dir . '/' . $type . '_' . $personId . '_' . time() . '.jpg';
                    Storage::disk('public')->put($fileName, $imageData);
                    $savedSnapshotPath = $fileName;
                }
            } catch (\Throwable $e) {
                Log::warning('Gagal menyimpan foto snapshot face kiosk: ' . $e->getMessage());
            }
        }

        // --- 1. PROSES ABSENSI SISWA ---
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

            $siswaSchoolId = $siswa->school_id ?: $schoolId;
            $indexHari = (int) $now->format('N');
            $jadwal = Jadwal::where('index_hari', $indexHari)
                ->where('is_active', 1)
                ->when($siswaSchoolId, fn($q) => $q->where('school_id', $siswaSchoolId))
                ->first();

            if (!$jadwal) {
                return response()->json([
                    'ok' => false,
                    'status' => 'warning',
                    'sound' => 'warning',
                    'message' => 'Tidak ada jadwal sekolah aktif hari ini (Hari Libur).',
                    'data' => [
                        'nama' => $siswa->nama,
                        'nis' => $siswa->nis ?? '-',
                        'foto' => $siswa->foto_url ?? null,
                        'role' => 'Siswa',
                        'kelas' => $siswa->kelas->nama_kelas ?? '-',
                        'status' => 'Hari Libur',
                        'jam' => $now->format('H:i:s'),
                    ]
                ]);
            }

            $jamMasuk = !empty($jadwal->jam_masuk) ? Carbon::parse($today . ' ' . $jadwal->jam_masuk) : null;
            $jamPulang = !empty($jadwal->jam_pulang) ? Carbon::parse($today . ' ' . $jadwal->jam_pulang) : null;
            $awalAbsenMasuk = !empty($jadwal->awal_absen_masuk) ? Carbon::parse($today . ' ' . $jadwal->awal_absen_masuk) : null;
            $akhirAbsenMasuk = !empty($jadwal->akhir_absen_masuk) ? Carbon::parse($today . ' ' . $jadwal->akhir_absen_masuk) : null;
            $akhirAbsenPulang = !empty($jadwal->akhir_absen_pulang) ? Carbon::parse($today . ' ' . $jadwal->akhir_absen_pulang) : null;
            $batasTelat = $jamMasuk;

            $schoolGateCardUids = GateCard::where('school_id', $siswaSchoolId)->pluck('uid_rfid')->filter()->toArray();
            $teacherSession = TeacherCheckoutSession::where('expires_at', '>', $now)
                ->where('status', 'open')
                ->where(function ($q) use ($siswaSchoolId, $schoolGateCardUids) {
                    $q->whereIn('uid_rfid', $schoolGateCardUids)
                      ->orWhereHas('teacher', fn($t) => $t->where('school_id', $siswaSchoolId));
                })
                ->orderBy('created_at', 'desc')
                ->first();

            $checkoutEnabled = Setting::where('school_id', $siswaSchoolId)
                ->where('setting_key', 'enable_checkout_attendance')
                ->value('setting_value') ?? 'true';

            try {
                DB::beginTransaction();

                $att = Attendance::where('student_id', $siswa->id)
                    ->where('tanggal', $today)
                    ->lockForUpdate()
                    ->first();

                // Override system Alpha/Izin/Sakit if scanning in person
                $isSystemAlpha = $att && $att->jam_masuk === null && ($att->is_auto_alpha || in_array($att->status, ['S', 'I', 'A', 'B']));
                if ($isSystemAlpha) {
                    $att->delete();
                    $att = null;
                }

                // Cek status lengkap (sudah masuk dan pulang)
                if ($att && $att->jam_pulang) {
                    $recordedKegiatans = $this->recordActiveKegiatans($siswaSchoolId, $siswa, $now, $today, 'Scan Mandiri Kegiatan Face');
                    if (!empty($recordedKegiatans)) {
                        DB::commit();
                        $namaKeg = implode(', ', $recordedKegiatans);
                        return response()->json([
                            'ok' => true,
                            'status' => 'success',
                            'sound' => 'ok',
                            'type' => 'absen_kegiatan',
                            'message' => "Absen Kegiatan Berhasil ({$namaKeg})",
                            'data' => [
                                'nama' => $siswa->nama,
                                'nis' => $siswa->nis ?? '-',
                                'foto' => $siswa->foto_url ?? null,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Kegiatan: ' . $namaKeg,
                                'jam' => $now->format('H:i:s'),
                                'jam_masuk' => Carbon::parse($att->jam_masuk)->format('H:i'),
                                'jam_pulang' => Carbon::parse($att->jam_pulang)->format('H:i'),
                                'similarity' => round($similarity * 100) . '%',
                            ]
                        ]);
                    }

                    DB::rollBack();
                    return response()->json([
                        'ok' => true,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'sudah_lengkap',
                        'message' => "Siswa {$siswa->nama} sudah lengkap absen Masuk & Pulang hari ini.",
                        'data' => [
                            'nama' => $siswa->nama,
                            'nis' => $siswa->nis ?? '-',
                            'foto' => $siswa->foto_url ?? null,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Lengkap',
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => Carbon::parse($att->jam_masuk)->format('H:i'),
                            'jam_pulang' => Carbon::parse($att->jam_pulang)->format('H:i'),
                            'keterangan' => 'Sudah lengkap Masuk & Pulang',
                            'similarity' => round($similarity * 100) . '%',
                        ]
                    ]);
                }

                // Cek apakah aksi ini Pulang atau Masuk
                $isPulangAction = false;
                if ($reqMode === 'pulang') {
                    $isPulangAction = true;
                } elseif ($reqMode === 'masuk') {
                    $isPulangAction = false;
                } else {
                    // Auto mode
                    $isPulangAction = ($att && $att->jam_masuk !== null);
                }

                // --- EKSEKUSI PULANG ---
                if ($isPulangAction) {
                    if (!$att || !$att->jam_masuk) {
                        DB::rollBack();
                        return response()->json([
                            'ok' => false,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'belum_absen_masuk',
                            'message' => "Siswa {$siswa->nama} belum melakukan absen masuk hari ini.",
                            'data' => [
                                'nama' => $siswa->nama,
                                'nis' => $siswa->nis ?? '-',
                                'foto' => $siswa->foto_url ?? null,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Belum Masuk',
                                'jam' => $now->format('H:i:s'),
                                'jam_masuk' => '--:--',
                                'jam_pulang' => '--:--',
                            ]
                        ]);
                    }

                    if ($checkoutEnabled === 'false') {
                        $this->recordActiveKegiatans($siswaSchoolId, $siswa, $now, $today, 'Auto Face Scan');
                        DB::rollBack();
                        return response()->json([
                            'ok' => true,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'sudah_absen_masuk',
                            'message' => "Siswa {$siswa->nama} sudah absen masuk (Kepulangan dinonaktifkan).",
                            'data' => [
                                'nama' => $siswa->nama,
                                'nis' => $siswa->nis ?? '-',
                                'foto' => $siswa->foto_url ?? null,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Sudah Masuk',
                                'jam' => $now->format('H:i:s'),
                                'jam_masuk' => Carbon::parse($att->jam_masuk)->format('H:i'),
                                'jam_pulang' => '--:--',
                                'keterangan' => 'Kepulangan dinonaktifkan',
                            ]
                        ]);
                    }

                    // Cek batas akhir absen pulang
                    if ($akhirAbsenPulang && $now->gt($akhirAbsenPulang) && !$teacherSession) {
                        $recordedKegiatans = $this->recordActiveKegiatans($siswaSchoolId, $siswa, $now, $today, 'Auto Face Pulang');
                        if (!empty($recordedKegiatans)) {
                            DB::commit();
                            $namaKeg = implode(', ', $recordedKegiatans);
                            return response()->json([
                                'ok' => true,
                                'status' => 'success',
                                'sound' => 'ok',
                                'type' => 'absen_kegiatan',
                                'message' => "Absen Kegiatan Berhasil ({$namaKeg})",
                                'data' => [
                                    'nama' => $siswa->nama,
                                    'nis' => $siswa->nis ?? '-',
                                    'foto' => $siswa->foto_url ?? null,
                                    'role' => 'Siswa',
                                    'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                    'status' => 'Kegiatan: ' . $namaKeg,
                                    'jam' => $now->format('H:i:s'),
                                    'similarity' => round($similarity * 100) . '%',
                                ]
                            ]);
                        }

                        DB::rollBack();
                        $akhirPulangStr = $akhirAbsenPulang->format('H:i');
                        return response()->json([
                            'ok' => false,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'checkout_closed',
                            'message' => "Waktu absen pulang telah ditutup (ditutup pukul {$akhirPulangStr}).",
                            'data' => [
                                'nama' => $siswa->nama,
                                'nis' => $siswa->nis ?? '-',
                                'foto' => $siswa->foto_url ?? null,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Pulang Ditutup',
                                'jam' => $now->format('H:i:s'),
                                'jam_masuk' => Carbon::parse($att->jam_masuk)->format('H:i'),
                                'jam_pulang' => '--:--',
                            ]
                        ]);
                    }

                    // Cek awal absen pulang (belum waktu pulang)
                    if ($jamPulang && $now->lt($jamPulang) && !$teacherSession) {
                        $recordedKegiatans = $this->recordActiveKegiatans($siswaSchoolId, $siswa, $now, $today, 'Auto Face Pulang');
                        if (!empty($recordedKegiatans)) {
                            DB::commit();
                            $namaKeg = implode(', ', $recordedKegiatans);
                            return response()->json([
                                'ok' => true,
                                'status' => 'success',
                                'sound' => 'ok',
                                'type' => 'absen_kegiatan',
                                'message' => "Absen Kegiatan Berhasil ({$namaKeg})",
                                'data' => [
                                    'nama' => $siswa->nama,
                                    'nis' => $siswa->nis ?? '-',
                                    'foto' => $siswa->foto_url ?? null,
                                    'role' => 'Siswa',
                                    'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                    'status' => 'Kegiatan: ' . $namaKeg,
                                    'jam' => $now->format('H:i:s'),
                                    'similarity' => round($similarity * 100) . '%',
                                ]
                            ]);
                        }

                        DB::rollBack();
                        $jamPulangStr = $jamPulang->format('H:i');
                        $isStillInCheckinWindow = ($awalAbsenMasuk && $now->between($awalAbsenMasuk, $akhirAbsenMasuk ?? $jamPulang));
                        if ($isStillInCheckinWindow) {
                            return response()->json([
                                'ok' => true,
                                'status' => 'warning',
                                'sound' => 'warning',
                                'type' => 'sudah_absen_masuk',
                                'message' => "Siswa {$siswa->nama} sudah absen masuk (Waktu pulang belum dimulai, dibuka pukul {$jamPulangStr}).",
                                'data' => [
                                    'nama' => $siswa->nama,
                                    'nis' => $siswa->nis ?? '-',
                                    'foto' => $siswa->foto_url ?? null,
                                    'role' => 'Siswa',
                                    'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                    'status' => 'Sudah Masuk',
                                    'jam' => $now->format('H:i:s'),
                                    'jam_masuk' => Carbon::parse($att->jam_masuk)->format('H:i'),
                                    'jam_pulang' => '--:--',
                                    'keterangan' => "Waktu pulang dibuka pukul {$jamPulangStr}",
                                ]
                            ]);
                        } else {
                            return response()->json([
                                'ok' => false,
                                'status' => 'warning',
                                'sound' => 'warning',
                                'type' => 'no_authorization',
                                'message' => "Belum waktu kepulangan (dibuka pukul {$jamPulangStr}) dan tidak ada izin guru pembuka gerbang.",
                                'data' => [
                                    'nama' => $siswa->nama,
                                    'nis' => $siswa->nis ?? '-',
                                    'foto' => $siswa->foto_url ?? null,
                                    'role' => 'Siswa',
                                    'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                    'status' => 'Belum Jam Pulang',
                                    'jam' => $now->format('H:i:s'),
                                    'jam_masuk' => Carbon::parse($att->jam_masuk)->format('H:i'),
                                    'jam_pulang' => '--:--',
                                    'keterangan' => "Waktu pulang dibuka pukul {$jamPulangStr}",
                                ]
                            ]);
                        }
                    }

                    $masuk = Carbon::parse($att->tanggal . ' ' . $att->jam_masuk);
                    $totalSeconds = (int) round(abs($masuk->diffInSeconds($now, false)));
                    $newStatus = $att->status;
                    $newKeterangan = $att->keterangan;

                    if ($att->status === 'B') {
                        $newStatus = ($batasTelat && $masuk->gt($batasTelat)) ? 'T' : 'H';
                        if ($newKeterangan) {
                            $newKeterangan = trim(str_replace('[Auto: Tidak Absen Pulang]', '', $newKeterangan));
                            if (empty($newKeterangan)) $newKeterangan = null;
                        }
                    }

                    $att->update([
                        'jam_pulang' => $now->toTimeString(),
                        'total_seconds' => $totalSeconds,
                        'status' => $newStatus,
                        'keterangan' => $newKeterangan,
                        'is_face_matched' => true,
                        'face_similarity_score' => $similarity,
                        'updated_at' => now(),
                    ]);

                    $this->recordActiveKegiatans($siswaSchoolId, $siswa, $now, $today, 'Auto Face Pulang');
                    DB::commit();

                    $hours = floor($totalSeconds / 3600);
                    $mins = floor(($totalSeconds % 3600) / 60);

                    // Kirim WhatsApp Pulang
                    try {
                        $this->wa->sendCheckOut($siswa->nama, $siswa->no_wa, $now->format('H:i'), $hours, $mins, 'Face Kiosk Scanner', $siswaSchoolId, $masuk->format('H:i'), $siswa->wa_ortu, $now->format('d/m/Y'));
                    } catch (\Throwable $e) {
                        Log::error("WA Face Checkout Error: " . $e->getMessage());
                    }

                    // Kirim Push Notification FCM
                    try {
                        FcmNotificationService::sendToSiswa($siswa, "Presensi Pulang Tercatat", "Halo {$siswa->nama}, absensi pulang Anda via Face Recognition berhasil dicatat pukul {$now->format('H:i')}.", [
                            'type' => 'checkout_siswa',
                            'jam' => $now->format('H:i'),
                        ]);
                    } catch (\Throwable $e) {
                        Log::error("FCM Face Checkout Error: " . $e->getMessage());
                    }

                    return response()->json([
                        'ok' => true,
                        'status' => 'success',
                        'sound' => 'ok',
                        'type' => 'absen_pulang',
                        'message' => "Absen Pulang Berhasil: {$siswa->nama}",
                        'data' => [
                            'nama' => $siswa->nama,
                            'nis' => $siswa->nis ?? '-',
                            'foto' => $siswa->foto_url ?? null,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Pulang',
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => $masuk->format('H:i'),
                            'jam_pulang' => $now->format('H:i'),
                            'durasi' => "{$hours}j {$mins}m",
                            'keterangan' => 'Pulang',
                            'similarity' => round($similarity * 100) . '%',
                        ]
                    ]);
                }

                // --- EKSEKUSI MASUK ---
                if ($att && $att->jam_masuk) {
                    DB::rollBack();
                    return response()->json([
                        'ok' => true,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'sudah_absen_masuk',
                        'message' => "Siswa {$siswa->nama} sudah absen masuk pukul " . Carbon::parse($att->jam_masuk)->format('H:i'),
                        'data' => [
                            'nama' => $siswa->nama,
                            'nis' => $siswa->nis ?? '-',
                            'foto' => $siswa->foto_url ?? null,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Sudah Masuk',
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => Carbon::parse($att->jam_masuk)->format('H:i'),
                            'jam_pulang' => '--:--',
                            'keterangan' => 'Sudah absen masuk',
                        ]
                    ]);
                }

                // Cek Awal Masuk & Akhir Masuk
                $isBeforeAwalMasuk = $awalAbsenMasuk && $now->lt($awalAbsenMasuk);
                $isAfterAkhirMasuk = $akhirAbsenMasuk && $now->gt($akhirAbsenMasuk);

                if ($isBeforeAwalMasuk || $isAfterAkhirMasuk) {
                    $recordedKegiatans = $this->recordActiveKegiatans($siswaSchoolId, $siswa, $now, $today, 'Scan Mandiri Kegiatan Face');
                    if (!empty($recordedKegiatans)) {
                        DB::commit();
                        $namaKeg = implode(', ', $recordedKegiatans);
                        return response()->json([
                            'ok' => true,
                            'status' => 'success',
                            'sound' => 'ok',
                            'type' => 'absen_kegiatan',
                            'message' => "Absen Kegiatan Berhasil ({$namaKeg})",
                            'data' => [
                                'nama' => $siswa->nama,
                                'nis' => $siswa->nis ?? '-',
                                'foto' => $siswa->foto_url ?? null,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Kegiatan: ' . $namaKeg,
                                'jam' => $now->format('H:i:s'),
                                'similarity' => round($similarity * 100) . '%',
                            ]
                        ]);
                    }

                    if ($isBeforeAwalMasuk) {
                        DB::rollBack();
                        $awalMasukStr = $awalAbsenMasuk->format('H:i');
                        return response()->json([
                            'ok' => false,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'too_early',
                            'message' => "Waktu absen masuk belum dibuka (dibuka pukul {$awalMasukStr}).",
                            'data' => [
                                'nama' => $siswa->nama,
                                'nis' => $siswa->nis ?? '-',
                                'foto' => $siswa->foto_url ?? null,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Belum Buka',
                                'jam' => $now->format('H:i:s'),
                            ]
                        ]);
                    }

                    if ($isAfterAkhirMasuk) {
                        DB::rollBack();
                        $akhirMasukStr = $akhirAbsenMasuk->format('H:i');
                        return response()->json([
                            'ok' => false,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'checkin_closed',
                            'message' => "Waktu absen masuk telah ditutup (ditutup pukul {$akhirMasukStr}).",
                            'data' => [
                                'nama' => $siswa->nama,
                                'nis' => $siswa->nis ?? '-',
                                'foto' => $siswa->foto_url ?? null,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Masuk Ditutup',
                                'jam' => $now->format('H:i:s'),
                            ]
                        ]);
                    }
                }

                $status = 'H';
                $keterangan = null;
                $statusLabel = 'Hadir Tepat Waktu';

                if ($batasTelat && $now->gt($batasTelat)) {
                    $status = 'T';
                    $diff = $now->timestamp - $batasTelat->timestamp;
                    $jam = floor($diff / 3600);
                    $menit = floor(($diff % 3600) / 60);
                    $keterangan = $jam > 0 ? "Telat {$jam} jam {$menit} menit" : "Telat {$menit} menit";
                    $statusLabel = "Terlambat ({$keterangan})";
                }

                $attData = [
                    'student_id' => $siswa->id,
                    'tanggal' => $today,
                    'jam_masuk' => $now->toTimeString(),
                    'status' => $status,
                    'keterangan' => $keterangan,
                    'is_face_matched' => true,
                    'face_similarity_score' => $similarity,
                    'photo_checkin_path' => $savedSnapshotPath,
                    'updated_at' => now(),
                ];

                if ($att) {
                    $att->update($attData);
                } else {
                    $attData['created_at'] = now();
                    Attendance::create($attData);
                }

                $this->recordActiveKegiatans($siswaSchoolId, $siswa, $now, $today, 'Auto Face Masuk');
                DB::commit();

                // Kirim WhatsApp Masuk
                try {
                    $this->wa->sendCheckIn($siswa->nama, $siswa->no_wa, $now->format('H:i'), $status, $siswaSchoolId, $keterangan, $siswa->wa_ortu, $siswa->kelas->nama_kelas ?? '-');
                } catch (\Throwable $e) {
                    Log::error("WA Face Checkin Error: " . $e->getMessage());
                }

                // Kirim Push Notification FCM
                try {
                    $readableStatus = $status === 'T' ? 'Terlambat' : 'Hadir';
                    FcmNotificationService::sendToSiswa($siswa, "Presensi Masuk Tercatat", "Halo {$siswa->nama}, absensi masuk Anda via Face Recognition berhasil dicatat pukul {$now->format('H:i')} (Status: {$readableStatus}).", [
                        'type' => 'checkin_siswa',
                        'status' => $readableStatus,
                        'jam' => $now->format('H:i'),
                    ]);
                } catch (\Throwable $e) {
                    Log::error("FCM Face Checkin Error: " . $e->getMessage());
                }

                return response()->json([
                    'ok' => true,
                    'status' => 'success',
                    'sound' => ($status === 'T' ? 'warning' : 'ok'),
                    'type' => 'absen_masuk',
                    'message' => "Absen Masuk Berhasil: {$siswa->nama} ({$statusLabel})",
                    'data' => [
                        'nama' => $siswa->nama,
                        'nis' => $siswa->nis ?? '-',
                        'foto' => $siswa->foto_url ?? null,
                        'role' => 'Siswa',
                        'kelas' => $siswa->kelas->nama_kelas ?? '-',
                        'status' => $status === 'T' ? 'Terlambat' : 'Hadir',
                        'jam' => $now->format('H:i:s'),
                        'jam_masuk' => $now->format('H:i'),
                        'jam_pulang' => '--:--',
                        'keterangan' => $keterangan ?: 'Tepat Waktu',
                        'similarity' => round($similarity * 100) . '%',
                    ]
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("Face Attendance Exception: " . $e->getMessage());
                return response()->json([
                    'ok' => false,
                    'status' => 'error',
                    'sound' => 'error',
                    'message' => 'Terjadi kesalahan sistem saat memproses absensi: ' . $e->getMessage()
                ], 500);
            }
        }

        // --- 2. PROSES ABSENSI GURU ---
        if ($type === 'guru') {
            $guru = Guru::where('id', $personId)->first();
            if (!$guru) {
                return response()->json(['ok' => false, 'status' => 'error', 'message' => 'Guru tidak ditemukan.'], 404);
            }

            $guruSchoolId = $guru->school_id ?: $schoolId;

            try {
                DB::beginTransaction();

                $shift = $guru->getShiftForDate($now);
                if (!$shift) {
                    DB::rollBack();
                    return response()->json([
                        'ok' => false,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'no_shift',
                        'message' => 'Guru tidak memiliki jadwal shift aktif hari ini.',
                        'data' => [
                            'nama' => $guru->nama,
                            'role' => 'Guru / Staf',
                            'kelas' => $guru->jabatan ?? 'Dewan Guru',
                            'status' => 'Tanpa Shift',
                            'jam' => $now->format('H:i:s'),
                        ]
                    ]);
                }

                $absensiGuru = AbsensiGuru::where('guru_id', $guru->id)
                    ->where('tanggal', $today)
                    ->where('school_id', $guruSchoolId)
                    ->whereNull('jadwal_pelajaran_id')
                    ->lockForUpdate()
                    ->first();

                // Cek jika sudah lengkap masuk dan pulang
                if ($absensiGuru && $absensiGuru->jam_pulang) {
                    DB::rollBack();
                    return response()->json([
                        'ok' => true,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'sudah_lengkap',
                        'message' => "Guru {$guru->nama} sudah lengkap absen Masuk & Pulang hari ini.",
                        'data' => [
                            'nama' => $guru->nama,
                            'nis' => $guru->nip ?? '-',
                            'foto' => $guru->foto_url ?? null,
                            'role' => 'Guru / Staf',
                            'kelas' => $shift->nama_shift,
                            'status' => 'Lengkap',
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => Carbon::parse($absensiGuru->jam_masuk)->format('H:i'),
                            'jam_pulang' => Carbon::parse($absensiGuru->jam_pulang)->format('H:i'),
                        ]
                    ]);
                }

                $isPulang = false;
                if ($reqMode === 'pulang') {
                    $isPulang = true;
                } elseif ($reqMode === 'masuk') {
                    $isPulang = false;
                } else {
                    $isPulang = ($absensiGuru && $absensiGuru->jam_masuk !== null);
                }

                if ($isPulang) {
                    if (!$absensiGuru || !$absensiGuru->jam_masuk) {
                        DB::rollBack();
                        return response()->json([
                            'ok' => false,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'belum_absen_masuk',
                            'message' => "Guru {$guru->nama} belum melakukan absen masuk hari ini.",
                            'data' => [
                                'nama' => $guru->nama,
                                'role' => 'Guru / Staf',
                                'kelas' => $shift->nama_shift,
                                'status' => 'Belum Masuk',
                                'jam' => $now->format('H:i:s'),
                            ]
                        ]);
                    }

                    $checkoutEnabled = Setting::where('school_id', $guruSchoolId)
                        ->where('setting_key', 'enable_checkout_teacher')
                        ->value('setting_value') ?? 'false';

                    if ($checkoutEnabled === 'false') {
                        DB::commit();
                        return response()->json([
                            'ok' => true,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'absen_sudah_masuk_guru',
                            'message' => "Guru {$guru->nama} sudah absen masuk sebelumnya (Kepulangan guru dinonaktifkan).",
                            'data' => [
                                'nama' => $guru->nama,
                                'role' => 'Guru / Staf',
                                'kelas' => $shift->nama_shift,
                                'status' => 'Sudah Masuk',
                                'jam' => $now->format('H:i:s'),
                            ]
                        ]);
                    }

                    $inCheckoutWindow = $shift->isInCheckOutWindow($now->format('H:i:s'));
                    $schoolGateCardUids = GateCard::where('school_id', $guruSchoolId)->pluck('uid_rfid')->filter()->toArray();
                    $gateSession = TeacherCheckoutSession::where('expires_at', '>', $now)
                        ->where('status', 'open')
                        ->where(function ($q) use ($guruSchoolId, $schoolGateCardUids) {
                            $q->whereIn('uid_rfid', $schoolGateCardUids)
                              ->orWhereHas('teacher', fn($t) => $t->where('school_id', $guruSchoolId));
                        })
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if (!$inCheckoutWindow && !$gateSession) {
                        if ($shift->isInCheckInWindow($now->format('H:i:s'))) {
                            DB::commit();
                            return response()->json([
                                'ok' => true,
                                'status' => 'warning',
                                'sound' => 'warning',
                                'type' => 'absen_sudah_masuk_guru',
                                'message' => "Guru {$guru->nama} sudah absen masuk (Waktu pulang belum dimulai).",
                                'data' => [
                                    'nama' => $guru->nama,
                                    'role' => 'Guru / Staf',
                                    'kelas' => $shift->nama_shift,
                                    'status' => 'Sudah Masuk',
                                    'jam' => $now->format('H:i:s'),
                                ]
                            ]);
                        }

                        DB::rollBack();
                        $timeNow = $now->format('H:i:s');
                        if ($shift->awal_absen_pulang && $timeNow < Carbon::parse($shift->awal_absen_pulang)->format('H:i:s')) {
                            $awalPulangStr = Carbon::parse($shift->awal_absen_pulang)->format('H:i');
                            $msg = "Belum waktu kepulangan guru (dibuka pukul {$awalPulangStr}) dan tidak ada izin pembuka gerbang.";
                        } elseif ($shift->akhir_absen_pulang && $timeNow > Carbon::parse($shift->akhir_absen_pulang)->format('H:i:s')) {
                            $akhirPulangStr = Carbon::parse($shift->akhir_absen_pulang)->format('H:i');
                            $msg = "Waktu absen pulang guru telah ditutup (ditutup pukul {$akhirPulangStr}).";
                        } else {
                            $windowStr = ($shift->awal_absen_pulang && $shift->akhir_absen_pulang)
                                ? Carbon::parse($shift->awal_absen_pulang)->format('H:i') . '-' . Carbon::parse($shift->akhir_absen_pulang)->format('H:i')
                                : '';
                            $msg = "Di luar jam absen pulang guru ({$windowStr})";
                        }

                        return response()->json([
                            'ok' => false,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'outside_checkout_window',
                            'message' => $msg,
                            'data' => [
                                'nama' => $guru->nama,
                                'role' => 'Guru / Staf',
                                'kelas' => $shift->nama_shift,
                                'status' => 'Di Luar Jam Pulang',
                                'jam' => $now->format('H:i:s'),
                            ]
                        ]);
                    }

                    $masuk = Carbon::parse($absensiGuru->tanggal . ' ' . $absensiGuru->jam_masuk);
                    $totalSeconds = (int) round(abs($masuk->diffInSeconds($now, false)));

                    $absensiGuru->update([
                        'jam_pulang' => $now->toTimeString(),
                        'is_face_matched' => true,
                        'face_similarity_score' => $similarity,
                        'updated_at' => now(),
                    ]);
                    DB::commit();

                    $hours = floor($totalSeconds / 3600);
                    $mins = floor(($totalSeconds % 3600) / 60);
                    $authorizedBy = $gateSession ? $gateSession->teacher_name : 'Face Kiosk Scanner';

                    try {
                        $this->wa->sendCheckOut($guru->nama, $guru->no_wa, $now->format('H:i'), $hours, $mins, $authorizedBy, $guruSchoolId, $masuk->format('H:i'), null, $now->format('d/m/Y'));
                    } catch (\Throwable $e) {
                        Log::error("WA Guru Face Checkout Error: " . $e->getMessage());
                    }

                    return response()->json([
                        'ok' => true,
                        'status' => 'success',
                        'sound' => 'ok',
                        'type' => 'absen_pulang_guru',
                        'message' => "Absen Pulang Berhasil: {$guru->nama}",
                        'data' => [
                            'nama' => $guru->nama,
                            'nis' => $guru->nip ?? '-',
                            'foto' => $guru->foto_url ?? null,
                            'role' => 'Guru / Staf',
                            'kelas' => $shift->nama_shift,
                            'status' => 'Pulang',
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => $masuk->format('H:i'),
                            'jam_pulang' => $now->format('H:i'),
                            'keterangan' => "Total durasi: {$hours}j {$mins}m",
                            'similarity' => round($similarity * 100) . '%',
                        ]
                    ]);
                } else {
                    // Masuk Guru
                    if ($absensiGuru && $absensiGuru->jam_masuk) {
                        DB::rollBack();
                        return response()->json([
                            'ok' => true,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'absen_sudah_masuk_guru',
                            'message' => "Guru {$guru->nama} sudah absen masuk pukul " . Carbon::parse($absensiGuru->jam_masuk)->format('H:i'),
                            'data' => [
                                'nama' => $guru->nama,
                                'nis' => $guru->nip ?? '-',
                                'foto' => $guru->foto_url ?? null,
                                'role' => 'Guru / Staf',
                                'kelas' => $shift->nama_shift,
                                'status' => 'Sudah Masuk',
                                'jam' => $now->format('H:i:s'),
                                'jam_masuk' => Carbon::parse($absensiGuru->jam_masuk)->format('H:i'),
                                'jam_pulang' => '--:--',
                            ]
                        ]);
                    }

                    if (!$shift->isInCheckInWindow($now->format('H:i:s'))) {
                        DB::rollBack();
                        $timeNow = $now->format('H:i:s');
                        if ($shift->awal_absen_masuk && $timeNow < Carbon::parse($shift->awal_absen_masuk)->format('H:i:s')) {
                            $awalMasukStr = Carbon::parse($shift->awal_absen_masuk)->format('H:i');
                            $msg = "Waktu absen masuk guru belum dibuka (dibuka pukul {$awalMasukStr}).";
                        } elseif ($shift->akhir_absen_masuk && $timeNow > Carbon::parse($shift->akhir_absen_masuk)->format('H:i:s')) {
                            $akhirMasukStr = Carbon::parse($shift->akhir_absen_masuk)->format('H:i');
                            $msg = "Waktu absen masuk guru telah ditutup (ditutup pukul {$akhirMasukStr}).";
                        } else {
                            $windowStr = ($shift->awal_absen_masuk && $shift->akhir_absen_masuk)
                                ? Carbon::parse($shift->awal_absen_masuk)->format('H:i') . '-' . Carbon::parse($shift->akhir_absen_masuk)->format('H:i')
                                : '';
                            $msg = "Di luar jam absen masuk guru ({$windowStr})";
                        }

                        return response()->json([
                            'ok' => false,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'outside_checkin_window',
                            'message' => $msg,
                            'data' => [
                                'nama' => $guru->nama,
                                'role' => 'Guru / Staf',
                                'kelas' => $shift->nama_shift,
                                'status' => 'Di Luar Jam',
                                'jam' => $now->format('H:i:s'),
                            ]
                        ]);
                    }

                    $status = 'Hadir';
                    $statusKehadiran = 'tepat_waktu';
                    $menitTerlambat = 0;
                    $keterangan = null;

                    if ($shift->isLate($now->format('H:i:s'))) {
                        $status = 'Terlambat';
                        $statusKehadiran = 'terlambat';
                        $menitTerlambat = $shift->calculateLateMinutes($now->format('H:i:s'));
                        $keterangan = "Terlambat {$menitTerlambat} m ({$shift->nama_shift})";
                    } else {
                        $status = 'Hadir';
                        $statusKehadiran = 'tepat_waktu';
                        $keterangan = "Tepat Waktu ({$shift->nama_shift})";
                    }

                    $guruData = [
                        'guru_id' => $guru->id,
                        'school_id' => $guruSchoolId,
                        'jadwal_pelajaran_id' => null,
                        'shift_id' => $shift->id,
                        'tanggal' => $today,
                        'jam_masuk' => $now->toTimeString(),
                        'waktu_hadir' => $now,
                        'menit_terlambat' => $menitTerlambat,
                        'status' => $status,
                        'status_kehadiran' => $statusKehadiran,
                        'keterangan' => $keterangan,
                        'is_face_matched' => true,
                        'face_similarity_score' => $similarity,
                        'photo_checkin_path' => $savedSnapshotPath,
                        'updated_at' => now(),
                    ];

                    if ($absensiGuru) {
                        $absensiGuru->update($guruData);
                    } else {
                        $guruData['created_at'] = now();
                        AbsensiGuru::create($guruData);
                    }

                    DB::commit();

                    try {
                        $this->wa->sendCheckIn($guru->nama, $guru->no_wa, $now->format('H:i'), $status, $guruSchoolId, $keterangan, null, '-');
                    } catch (\Throwable $e) {
                        Log::error("WA Guru Face Checkin Error: " . $e->getMessage());
                    }

                    $respMsg = $status === 'Terlambat'
                        ? "Masuk ({$status}): {$guru->nama} (+{$menitTerlambat}m)"
                        : "Selamat Datang, {$guru->nama}.";

                    return response()->json([
                        'ok' => true,
                        'status' => 'success',
                        'sound' => ($status === 'Terlambat' ? 'warning' : 'ok'),
                        'type' => 'absen_masuk_guru',
                        'message' => $respMsg,
                        'data' => [
                            'nama' => $guru->nama,
                            'nis' => $guru->nip ?? '-',
                            'foto' => $guru->foto_url ?? null,
                            'role' => 'Guru / Staf',
                            'kelas' => $shift->nama_shift,
                            'status' => $status,
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => $now->format('H:i'),
                            'jam_pulang' => '--:--',
                            'keterangan' => $keterangan,
                            'similarity' => round($similarity * 100) . '%',
                        ]
                    ]);
                }
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("Guru Face Attendance Exception: " . $e->getMessage());
                return response()->json([
                    'ok' => false,
                    'status' => 'error',
                    'sound' => 'error',
                    'message' => 'Gagal memproses absensi guru: ' . $e->getMessage()
                ], 500);
            }
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
            'person_id' => 'required',
            'type' => 'required|in:siswa,guru',
            'embedding' => 'required|array',
            'photo' => 'required|string', // base64
        ]);

        $personId = $request->person_id;
        $type = $request->type;
        $embedding = $request->embedding;
        $photoBase64 = $request->photo;

        // Decode & store photo
        $savedPath = 'photos/' . $type . '/' . $type . '_' . $personId . '_' . time() . '.jpg';
        try {
            $imageParts = explode(';base64,', $photoBase64);
            $imageData = base64_decode($imageParts[1] ?? $photoBase64);
            Storage::disk('public')->put($savedPath, $imageData);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Gagal menyimpan file foto: ' . $e->getMessage()], 500);
        }

        if ($type === 'siswa') {
            $siswa = Siswa::find($personId);
            if (!$siswa) return response()->json(['ok' => false, 'message' => 'Siswa tidak ditemukan'], 404);

            $targetUser = $siswa->user;
            if (!$targetUser) {
                $targetUser = User::where('username', 'siswa_' . $siswa->nis)->orWhere('username', $siswa->nis)->first();
            }
            if (!$targetUser) {
                $targetUser = User::create([
                    'full_name' => $siswa->nama,
                    'username' => 'siswa_' . ($siswa->nis ?: $siswa->id),
                    'email' => ($siswa->nis ?: $siswa->id) . '@siswa.local',
                    'password_hash' => Hash::make('123456'),
                    'role' => 'student',
                    'school_id' => $siswa->school_id,
                ]);
            }

            if ($siswa->user_id !== $targetUser->id) {
                $siswa->user_id = $targetUser->id;
            }
            if (empty($siswa->foto)) {
                $siswa->foto = $savedPath;
            }
            $siswa->save();

            UserFaceData::updateOrCreate(
                ['siswa_id' => $siswa->id],
                [
                    'user_id' => $targetUser->id,
                    'face_embedding' => json_encode($embedding),
                    'sample_photo_path' => $savedPath,
                    'is_verified' => true,
                ]
            );

            return response()->json([
                'ok' => true,
                'message' => "Face ID siswa {$siswa->nama} berhasil didaftarkan!",
                'descriptor' => [
                    'id' => $siswa->id,
                    'type' => 'siswa',
                    'nama' => $siswa->nama,
                    'nis' => $siswa->nis ?? '-',
                    'kelas' => $siswa->kelas->nama_kelas ?? '-',
                    'foto' => $siswa->foto_url ?? null,
                    'embedding' => $embedding,
                ]
            ]);
        } else {
            $guru = Guru::find($personId);
            if (!$guru) return response()->json(['ok' => false, 'message' => 'Guru tidak ditemukan'], 404);

            $targetUser = $guru->user;
            if (!$targetUser) {
                $targetUser = User::where('username', $guru->nip)->first();
            }
            if (!$targetUser) {
                $targetUser = User::create([
                    'full_name' => $guru->nama,
                    'username' => $guru->nip ?: 'guru_' . $guru->id,
                    'email' => ($guru->nip ?: 'guru_' . $guru->id) . '@guru.local',
                    'password_hash' => Hash::make('123456'),
                    'role' => 'teacher',
                    'school_id' => $guru->school_id,
                ]);
            }

            if ($guru->user_id !== $targetUser->id) {
                $guru->user_id = $targetUser->id;
            }
            if (empty($guru->foto)) {
                $guru->foto = $savedPath;
            }
            $guru->save();

            UserFaceData::updateOrCreate(
                ['guru_id' => $guru->id],
                [
                    'user_id' => $targetUser->id,
                    'face_embedding' => json_encode($embedding),
                    'sample_photo_path' => $savedPath,
                    'is_verified' => true,
                ]
            );

            return response()->json([
                'ok' => true,
                'message' => "Face ID guru {$guru->nama} berhasil didaftarkan!",
                'descriptor' => [
                    'id' => $guru->id,
                    'type' => 'guru',
                    'nama' => $guru->nama,
                    'nis' => $guru->nip ?? '-',
                    'kelas' => 'Dewan Guru',
                    'foto' => $guru->foto_url ?? null,
                    'embedding' => $embedding,
                ]
            ]);
        }
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

        // Apply Wali Kelas logic
        if (auth()->user() && auth()->user()->role === 'wali_kelas') {
            $guru = auth()->user()->guru;
            if ($guru) {
                // Get all kelas managed by this guru
                $managedKelasIds = \App\Models\Kelas::where(function($q) use ($guru) {
                    $q->where('wali_kelas_id', $guru->id)
                      ->orWhere('wali_kelas_2_id', $guru->id);
                })->pluck('id');
                $siswaQuery->whereIn('kelas_id', $managedKelasIds);
                // Also restrict the class filter dropdown list
                $kelasQuery = Kelas::whereIn('id', $managedKelasIds)->orderBy('nama_kelas');
            } else {
                // If no guru associated, return nothing
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
                        ['type' => 'attendance_alpha', 'status' => 'Alpha', 'tanggal' => $date]
                    );
                } elseif ($status === 'B') {
                    FcmNotificationService::sendToSiswa(
                        $student,
                        "Peringatan Bolos",
                        "Halo {$student->nama}, status absensi Anda untuk tanggal {$date} telah dicatat sebagai Bolos.",
                        ['type' => 'attendance_bolos', 'status' => 'Bolos', 'tanggal' => $date]
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
        $uid = strtoupper(trim($rawUid));
        $reqMode = $request->input('mode', 'auto');

        // Get school_id from authenticated user
        $user = auth()->user();
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

        // Apply school timezone
        $schoolTz = Setting::where('school_id', $schoolId)
            ->where('setting_key', 'timezone')
            ->value('setting_value');
        if ($schoolTz) {
            date_default_timezone_set($schoolTz);
            config(['app.timezone' => $schoolTz]);
        }

        $now = now();
        $today = $now->format('Y-m-d');
        $indexHari = (int) $now->format('N'); // 1 (Mon) - 7 (Sun)

        // 1. Check Gate Card
        $gateCard = GateCard::with('guru')
            ->whereRaw('UPPER(uid_rfid) = ?', [$uid])
            ->where('school_id', $schoolId)
            ->first();

        if ($gateCard) {
            try {
                DB::beginTransaction();
                $gateName = $gateCard->guru_id ? ($gateCard->guru->nama ?? $gateCard->name) : $gateCard->name;
                TeacherCheckoutSession::where('expires_at', '<', $now)->delete();

                $schoolGateCardUids = GateCard::where('school_id', $schoolId)
                    ->pluck('uid_rfid')
                    ->filter()
                    ->toArray();

                $activeSession = TeacherCheckoutSession::where(function ($q) use ($uid, $schoolGateCardUids) {
                    $q->where('uid_rfid', $uid)->orWhereIn('uid_rfid', $schoolGateCardUids);
                })
                ->where('expires_at', '>=', $now)
                ->first();

                if ($activeSession) {
                    $activeSession->delete();
                    DB::commit();
                    return response()->json([
                        'ok' => true,
                        'status' => 'success',
                        'sound' => 'ok',
                        'type' => 'gate_closed',
                        'message' => 'Sesi Gerbang Kepulangan DITUTUP (' . $gateName . ')',
                        'data' => [
                            'nama' => $gateName,
                            'role' => 'Kartu Gerbang',
                            'kelas' => 'Akses Gerbang',
                            'status' => 'Gerbang Ditutup',
                            'jam' => $now->format('H:i:s'),
                            'uid' => $uid,
                        ]
                    ]);
                }

                TeacherCheckoutSession::create([
                    'teacher_id' => $gateCard->guru_id,
                    'teacher_name' => $gateName,
                    'uid_rfid' => $uid,
                    'status' => 'open',
                    'expires_at' => $now->copy()->addMinutes(30),
                    'created_at' => $now
                ]);
                DB::commit();

                return response()->json([
                    'ok' => true,
                    'status' => 'success',
                    'sound' => 'ok',
                    'type' => 'gate_opened',
                    'message' => 'Sesi Gerbang Kepulangan DIBUKA 30 Menit (' . $gateName . ')',
                    'data' => [
                        'nama' => $gateName,
                        'role' => 'Kartu Gerbang',
                        'kelas' => 'Akses Gerbang',
                        'status' => 'Gerbang Dibuka',
                        'jam' => $now->format('H:i:s'),
                        'uid' => $uid,
                    ]
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'ok' => false,
                    'status' => 'error',
                    'sound' => 'error',
                    'message' => 'Gagal memproses kartu gerbang: ' . $e->getMessage()
                ], 500);
            }
        }

        // 2. Check Teacher Card
        $teacher = Guru::whereRaw('UPPER(uid_rfid) = ?', [$uid])
            ->where('school_id', $schoolId)
            ->first();

        if ($teacher) {
            try {
                DB::beginTransaction();
                $shift = $teacher->getShiftForDate($now);
                if (!$shift) {
                    DB::rollBack();
                    return response()->json([
                        'ok' => false,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'no_shift',
                        'message' => 'Guru tidak memiliki jadwal shift aktif hari ini.',
                        'data' => [
                            'nama' => $teacher->nama,
                            'role' => 'Guru / Staf',
                            'kelas' => $teacher->jabatan ?? 'Guru',
                            'status' => 'Tanpa Shift',
                            'jam' => $now->format('H:i:s'),
                            'uid' => $uid,
                        ]
                    ]);
                }

                $absensiGuru = AbsensiGuru::where('guru_id', $teacher->id)
                    ->where('tanggal', $today)
                    ->where('school_id', $schoolId)
                    ->whereNull('jadwal_pelajaran_id')
                    ->lockForUpdate()
                    ->first();

                // Cek jika sudah lengkap masuk dan pulang
                if ($absensiGuru && $absensiGuru->jam_pulang) {
                    DB::rollBack();
                    return response()->json([
                        'ok' => true,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'sudah_lengkap',
                        'message' => "Guru {$teacher->nama} sudah lengkap absen Masuk & Pulang hari ini.",
                        'data' => [
                            'nama' => $teacher->nama,
                            'role' => 'Guru / Staf',
                            'kelas' => $shift->nama_shift,
                            'status' => 'Lengkap',
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => Carbon::parse($absensiGuru->jam_masuk)->format('H:i'),
                            'jam_pulang' => Carbon::parse($absensiGuru->jam_pulang)->format('H:i'),
                            'uid' => $uid,
                        ]
                    ]);
                }

                $isPulang = false;
                if ($reqMode === 'pulang') {
                    $isPulang = true;
                } elseif ($reqMode === 'masuk') {
                    $isPulang = false;
                } else {
                    $isPulang = ($absensiGuru && $absensiGuru->jam_masuk !== null);
                }

                if ($isPulang) {
                    // Check-out Guru
                    if (!$absensiGuru || !$absensiGuru->jam_masuk) {
                        DB::rollBack();
                        return response()->json([
                            'ok' => false,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'belum_absen_masuk',
                            'message' => "Guru {$teacher->nama} belum absen masuk hari ini.",
                            'data' => [
                                'nama' => $teacher->nama,
                                'role' => 'Guru / Staf',
                                'kelas' => $shift->nama_shift,
                                'status' => 'Belum Masuk',
                                'jam' => $now->format('H:i:s'),
                                'uid' => $uid,
                            ]
                        ]);
                    }

                    $checkoutEnabled = Setting::where('school_id', $schoolId)
                        ->where('setting_key', 'enable_checkout_teacher')
                        ->value('setting_value') ?? 'false';

                    if ($checkoutEnabled === 'false') {
                        DB::commit();
                        return response()->json([
                            'ok' => true,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'absen_sudah_masuk_guru',
                            'message' => "Guru {$teacher->nama} sudah absen masuk sebelumnya (Kepulangan guru dinonaktifkan).",
                            'data' => [
                                'nama' => $teacher->nama,
                                'role' => 'Guru / Staf',
                                'kelas' => $shift->nama_shift,
                                'status' => 'Sudah Masuk',
                                'jam' => $now->format('H:i:s'),
                                'uid' => $uid,
                            ]
                        ]);
                    }

                    $inCheckoutWindow = $shift->isInCheckOutWindow($now->format('H:i:s'));
                    $schoolGateCardUids = GateCard::where('school_id', $schoolId)->pluck('uid_rfid')->filter()->toArray();
                    $gateSession = TeacherCheckoutSession::where('expires_at', '>', $now)
                        ->where('status', 'open')
                        ->where(function ($q) use ($schoolId, $schoolGateCardUids) {
                            $q->whereIn('uid_rfid', $schoolGateCardUids)
                              ->orWhereHas('teacher', fn($t) => $t->where('school_id', $schoolId));
                        })
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if (!$inCheckoutWindow && !$gateSession) {
                        if ($shift->isInCheckInWindow($now->format('H:i:s'))) {
                            DB::commit();
                            return response()->json([
                                'ok' => true,
                                'status' => 'warning',
                                'sound' => 'warning',
                                'type' => 'absen_sudah_masuk_guru',
                                'message' => "Guru {$teacher->nama} sudah absen masuk (Waktu pulang belum dimulai).",
                                'data' => [
                                    'nama' => $teacher->nama,
                                    'role' => 'Guru / Staf',
                                    'kelas' => $shift->nama_shift,
                                    'status' => 'Sudah Masuk',
                                    'jam' => $now->format('H:i:s'),
                                    'uid' => $uid,
                                ]
                            ]);
                        }

                        DB::rollBack();
                        $timeNow = $now->format('H:i:s');
                        if ($shift->awal_absen_pulang && $timeNow < Carbon::parse($shift->awal_absen_pulang)->format('H:i:s')) {
                            $awalPulangStr = Carbon::parse($shift->awal_absen_pulang)->format('H:i');
                            $msg = "Belum waktu kepulangan guru (dibuka pukul {$awalPulangStr}) dan tidak ada izin pembuka gerbang.";
                        } elseif ($shift->akhir_absen_pulang && $timeNow > Carbon::parse($shift->akhir_absen_pulang)->format('H:i:s')) {
                            $akhirPulangStr = Carbon::parse($shift->akhir_absen_pulang)->format('H:i');
                            $msg = "Waktu absen pulang guru telah ditutup (ditutup pukul {$akhirPulangStr}).";
                        } else {
                            $windowStr = ($shift->awal_absen_pulang && $shift->akhir_absen_pulang)
                                ? Carbon::parse($shift->awal_absen_pulang)->format('H:i') . '-' . Carbon::parse($shift->akhir_absen_pulang)->format('H:i')
                                : '';
                            $msg = "Di luar jam absen pulang guru ({$windowStr})";
                        }

                        return response()->json([
                            'ok' => false,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'outside_checkout_window',
                            'message' => $msg,
                            'data' => [
                                'nama' => $teacher->nama,
                                'role' => 'Guru / Staf',
                                'kelas' => $shift->nama_shift,
                                'status' => 'Di Luar Jam Pulang',
                                'jam' => $now->format('H:i:s'),
                                'uid' => $uid,
                            ]
                        ]);
                    }

                    $masuk = Carbon::parse($absensiGuru->tanggal . ' ' . $absensiGuru->jam_masuk);
                    $totalSeconds = (int) round(abs($masuk->diffInSeconds($now, false)));

                    $absensiGuru->update([
                        'jam_pulang' => $now->toTimeString(),
                        'updated_at' => now(),
                    ]);
                    DB::commit();

                    $authorizedBy = $gateSession ? $gateSession->teacher_name : 'Sistem';
                    $hours = floor($totalSeconds / 3600);
                    $mins = floor(($totalSeconds % 3600) / 60);

                    try {
                        $this->wa->sendCheckOut($teacher->nama, $teacher->no_wa, $now->format('H:i'), $hours, $mins, $authorizedBy, $schoolId, $masuk->format('H:i'), null, $now->format('d/m/Y'));
                    } catch (\Exception $e) {
                        Log::error("WA Guru Checkout Error: " . $e->getMessage());
                    }

                    return response()->json([
                        'ok' => true,
                        'status' => 'success',
                        'sound' => 'ok',
                        'type' => 'absen_pulang_guru',
                        'message' => "Absen Pulang Berhasil: {$teacher->nama}",
                        'data' => [
                            'nama' => $teacher->nama,
                            'role' => 'Guru / Staf',
                            'kelas' => $shift->nama_shift,
                            'status' => 'Pulang',
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => $masuk->format('H:i'),
                            'jam_pulang' => $now->format('H:i'),
                            'keterangan' => "Total durasi: {$hours}j {$mins}m",
                            'uid' => $uid,
                        ]
                    ]);
                } else {
                    // Check-in Guru
                    if ($absensiGuru && $absensiGuru->jam_masuk) {
                        DB::rollBack();
                        return response()->json([
                            'ok' => true,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'absen_sudah_masuk_guru',
                            'message' => "Guru {$teacher->nama} sudah absen masuk pukul " . Carbon::parse($absensiGuru->jam_masuk)->format('H:i'),
                            'data' => [
                                'nama' => $teacher->nama,
                                'role' => 'Guru / Staf',
                                'kelas' => $shift->nama_shift,
                                'status' => 'Sudah Masuk',
                                'jam' => $now->format('H:i:s'),
                                'jam_masuk' => Carbon::parse($absensiGuru->jam_masuk)->format('H:i'),
                                'jam_pulang' => '--:--',
                                'uid' => $uid,
                            ]
                        ]);
                    }

                    if (!$shift->isInCheckInWindow($now->format('H:i:s'))) {
                        DB::rollBack();
                        $timeNow = $now->format('H:i:s');
                        if ($shift->awal_absen_masuk && $timeNow < Carbon::parse($shift->awal_absen_masuk)->format('H:i:s')) {
                            $awalMasukStr = Carbon::parse($shift->awal_absen_masuk)->format('H:i');
                            $msg = "Waktu absen masuk guru belum dibuka (dibuka pukul {$awalMasukStr}).";
                        } elseif ($shift->akhir_absen_masuk && $timeNow > Carbon::parse($shift->akhir_absen_masuk)->format('H:i:s')) {
                            $akhirMasukStr = Carbon::parse($shift->akhir_absen_masuk)->format('H:i');
                            $msg = "Waktu absen masuk guru telah ditutup (ditutup pukul {$akhirMasukStr}).";
                        } else {
                            $windowStr = ($shift->awal_absen_masuk && $shift->akhir_absen_masuk)
                                ? Carbon::parse($shift->awal_absen_masuk)->format('H:i') . '-' . Carbon::parse($shift->akhir_absen_masuk)->format('H:i')
                                : '';
                            $msg = "Di luar jam absen masuk ({$windowStr})";
                        }

                        return response()->json([
                            'ok' => false,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'outside_checkin_window',
                            'message' => $msg,
                            'data' => [
                                'nama' => $teacher->nama,
                                'role' => 'Guru / Staf',
                                'kelas' => $shift->nama_shift,
                                'status' => 'Di Luar Jam',
                                'jam' => $now->format('H:i:s'),
                                'uid' => $uid,
                            ]
                        ]);
                    }

                    $status = 'Hadir';
                    $statusKehadiran = 'tepat_waktu';
                    $menitTerlambat = 0;
                    $keterangan = null;

                    if ($shift->isLate($now->format('H:i:s'))) {
                        $status = 'Terlambat';
                        $statusKehadiran = 'terlambat';
                        $menitTerlambat = $shift->calculateLateMinutes($now->format('H:i:s'));
                        $keterangan = "Terlambat {$menitTerlambat} m ({$shift->nama_shift})";
                    } else {
                        $status = 'Hadir';
                        $statusKehadiran = 'tepat_waktu';
                        $keterangan = "Tepat Waktu ({$shift->nama_shift})";
                    }

                    $guruData = [
                        'guru_id' => $teacher->id,
                        'school_id' => $schoolId,
                        'jadwal_pelajaran_id' => null,
                        'shift_id' => $shift->id,
                        'tanggal' => $today,
                        'jam_masuk' => $now->toTimeString(),
                        'waktu_hadir' => $now,
                        'menit_terlambat' => $menitTerlambat,
                        'status' => $status,
                        'status_kehadiran' => $statusKehadiran,
                        'keterangan' => $keterangan,
                        'created_at' => $now
                    ];

                    if ($absensiGuru) {
                        $absensiGuru->update($guruData);
                    } else {
                        AbsensiGuru::create($guruData);
                    }

                    DB::commit();

                    try {
                        $this->wa->sendCheckIn($teacher->nama, $teacher->no_wa, $now->format('H:i'), $status, $schoolId, $keterangan, null, '-');
                    } catch (\Exception $e) {
                        Log::error("WA Guru Checkin Error: " . $e->getMessage());
                    }

                    $respMsg = $status === 'Terlambat'
                        ? "Masuk ({$status}): {$teacher->nama} (+{$menitTerlambat}m)"
                        : "Selamat Pagi, {$teacher->nama}.";

                    return response()->json([
                        'ok' => true,
                        'status' => 'success',
                        'sound' => ($status === 'Terlambat' ? 'warning' : 'ok'),
                        'type' => 'absen_masuk_guru',
                        'message' => $respMsg,
                        'data' => [
                            'nama' => $teacher->nama,
                            'role' => 'Guru / Staf',
                            'kelas' => $shift->nama_shift,
                            'status' => $status,
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => $now->format('H:i'),
                            'jam_pulang' => '--:--',
                            'keterangan' => $keterangan,
                            'uid' => $uid,
                        ]
                    ]);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("USB RFID Teacher Scan Error: " . $e->getMessage());
                return response()->json([
                    'ok' => false,
                    'status' => 'error',
                    'sound' => 'error',
                    'message' => 'Gagal memproses absen guru: ' . $e->getMessage()
                ], 500);
            }
        }

        // 3. Check Student (Siswa)
        $siswa = Siswa::with('kelas')
            ->where('school_id', $schoolId)
            ->where(function ($q) use ($uid) {
                $q->whereRaw('UPPER(uid_rfid) = ?', [$uid])
                  ->orWhere('uid_rfid', $uid);
            })
            ->first();

        if (!$siswa) {
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'sound' => 'error',
                'type' => 'unknown_card',
                'message' => "Kartu RFID ({$uid}) tidak terdaftar di sistem!",
                'data' => [
                    'nama' => 'Tidak Dikenal',
                    'role' => 'Tidak Terdaftar',
                    'kelas' => '-',
                    'status' => 'Kartu Belum Terdaftar',
                    'jam' => $now->format('H:i:s'),
                    'uid' => $uid,
                ]
            ]);
        }

        if ($siswa->kelas && !$siswa->kelas->is_active_attendance) {
            return response()->json([
                'ok' => false,
                'status' => 'warning',
                'sound' => 'warning',
                'type' => 'class_disabled',
                'message' => "Absensi dimatikan untuk kelas {$siswa->kelas->nama_kelas}.",
                'data' => [
                    'nama' => $siswa->nama,
                    'role' => 'Siswa',
                    'kelas' => $siswa->kelas->nama_kelas,
                    'status' => 'Kelas Nonaktif',
                    'jam' => $now->format('H:i:s'),
                    'uid' => $uid,
                ]
            ]);
        }

        $jadwal = Jadwal::where('index_hari', $indexHari)
            ->where('is_active', 1)
            ->where('school_id', $schoolId)
            ->first();

        if (!$jadwal) {
            return response()->json([
                'ok' => false,
                'status' => 'warning',
                'sound' => 'warning',
                'type' => 'schedule_empty',
                'message' => 'Tidak ada jadwal sekolah / Hari libur.',
                'data' => [
                    'nama' => $siswa->nama,
                    'role' => 'Siswa',
                    'kelas' => $siswa->kelas->nama_kelas ?? '-',
                    'status' => 'Hari Libur',
                    'jam' => $now->format('H:i:s'),
                    'uid' => $uid,
                ]
            ]);
        }

        $jamMasuk = !empty($jadwal->jam_masuk) ? Carbon::parse($today . ' ' . $jadwal->jam_masuk) : null;
        $jamPulang = !empty($jadwal->jam_pulang) ? Carbon::parse($today . ' ' . $jadwal->jam_pulang) : null;
        $awalAbsenMasuk = !empty($jadwal->awal_absen_masuk) ? Carbon::parse($today . ' ' . $jadwal->awal_absen_masuk) : null;
        $akhirAbsenMasuk = !empty($jadwal->akhir_absen_masuk) ? Carbon::parse($today . ' ' . $jadwal->akhir_absen_masuk) : null;
        $akhirAbsenPulang = !empty($jadwal->akhir_absen_pulang) ? Carbon::parse($today . ' ' . $jadwal->akhir_absen_pulang) : null;
        $batasTelat = $jamMasuk;

        $schoolGateCardUids = GateCard::where('school_id', $schoolId)->pluck('uid_rfid')->filter()->toArray();
        $teacherSession = TeacherCheckoutSession::where('expires_at', '>', $now)
            ->where('status', 'open')
            ->where(function ($q) use ($schoolId, $schoolGateCardUids) {
                $q->whereIn('uid_rfid', $schoolGateCardUids)
                  ->orWhereHas('teacher', fn($t) => $t->where('school_id', $schoolId));
            })
            ->orderBy('created_at', 'desc')
            ->first();

        $checkoutEnabled = Setting::where('school_id', $schoolId)
            ->where('setting_key', 'enable_checkout_attendance')
            ->value('setting_value') ?? 'true';

        try {
            DB::beginTransaction();

            $att = Attendance::where('student_id', $siswa->id)
                ->where('tanggal', $today)
                ->lockForUpdate()
                ->first();

            // Override system Alpha/Izin/Sakit if scanning in person
            $isSystemAlpha = $att && $att->jam_masuk === null && ($att->is_auto_alpha || in_array($att->status, ['S', 'I', 'A', 'B']));
            if ($isSystemAlpha) {
                $att->delete();
                $att = null;
            }

            // Case 1: Sudah Lengkap (Masuk & Pulang)
            if ($att && $att->jam_pulang) {
                $recordedKegiatans = $this->recordActiveKegiatans($schoolId, $siswa, $now, $today, 'Scan Mandiri Kegiatan USB');
                if (!empty($recordedKegiatans)) {
                    DB::commit();
                    $namaKeg = implode(', ', $recordedKegiatans);
                    return response()->json([
                        'ok' => true,
                        'status' => 'success',
                        'sound' => 'ok',
                        'type' => 'absen_kegiatan',
                        'message' => "Absen Kegiatan Berhasil ({$namaKeg})",
                        'data' => [
                            'nama' => $siswa->nama,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Kegiatan: ' . $namaKeg,
                            'jam' => $now->format('H:i:s'),
                            'uid' => $uid,
                        ]
                    ]);
                }
                DB::rollBack();
                return response()->json([
                    'ok' => true,
                    'status' => 'warning',
                    'sound' => 'warning',
                    'type' => 'sudah_lengkap',
                    'message' => "Siswa {$siswa->nama} sudah lengkap absen Masuk & Pulang hari ini.",
                    'data' => [
                        'nama' => $siswa->nama,
                        'nis' => $siswa->nis ?? '-',
                        'foto' => $siswa->foto_url ?? null,
                        'role' => 'Siswa',
                        'kelas' => $siswa->kelas->nama_kelas ?? '-',
                        'status' => 'Sudah Lengkap',
                        'jam' => $now->format('H:i:s'),
                        'jam_masuk' => $att->jam_masuk ? Carbon::parse($att->jam_masuk)->format('H:i') : '--:--',
                        'jam_pulang' => $att->jam_pulang ? Carbon::parse($att->jam_pulang)->format('H:i') : '--:--',
                        'keterangan' => 'Sudah lengkap Masuk & Pulang',
                        'uid' => $uid,
                    ]
                ]);
            }

            // Tentukan apakah aksi ini Pulang atau Masuk
            $isPulangAction = false;
            if ($reqMode === 'pulang') {
                $isPulangAction = true;
            } elseif ($reqMode === 'masuk') {
                $isPulangAction = false;
            } else {
                // Auto mode
                $isPulangAction = ($att && $att->jam_masuk !== null);
            }

            // Case 2: Proses Pulang
            if ($isPulangAction) {
                if (!$att || !$att->jam_masuk) {
                    DB::rollBack();
                    return response()->json([
                        'ok' => false,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'belum_absen_masuk',
                        'message' => "Siswa {$siswa->nama} belum melakukan absen masuk hari ini.",
                        'data' => [
                            'nama' => $siswa->nama,
                            'nis' => $siswa->nis ?? '-',
                            'foto' => $siswa->foto_url ?? null,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Belum Masuk',
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => '--:--',
                            'jam_pulang' => '--:--',
                            'uid' => $uid,
                        ]
                    ]);
                }

                if ($checkoutEnabled === 'false') {
                    $this->recordActiveKegiatans($schoolId, $siswa, $now, $today, 'Auto dari Tap Kartu USB');
                    DB::rollBack();
                    return response()->json([
                        'ok' => true,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'sudah_absen_masuk',
                        'message' => "Siswa {$siswa->nama} sudah absen masuk (Kepulangan dinonaktifkan).",
                        'data' => [
                            'nama' => $siswa->nama,
                            'nis' => $siswa->nis ?? '-',
                            'foto' => $siswa->foto_url ?? null,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Sudah Masuk',
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => $att->jam_masuk ? Carbon::parse($att->jam_masuk)->format('H:i') : '--:--',
                            'jam_pulang' => '--:--',
                            'keterangan' => 'Sudah absen masuk',
                            'uid' => $uid,
                        ]
                    ]);
                }

                // Cek batas akhir absen pulang
                if ($akhirAbsenPulang && $now->gt($akhirAbsenPulang) && !$teacherSession) {
                    $recordedKegiatans = $this->recordActiveKegiatans($schoolId, $siswa, $now, $today, 'Auto dari Tap Kartu USB');
                    if (!empty($recordedKegiatans)) {
                        DB::commit();
                        $namaKeg = implode(', ', $recordedKegiatans);
                        return response()->json([
                            'ok' => true,
                            'status' => 'success',
                            'sound' => 'ok',
                            'type' => 'absen_kegiatan',
                            'message' => "Absen Kegiatan Berhasil ({$namaKeg})",
                            'data' => [
                                'nama' => $siswa->nama,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Kegiatan: ' . $namaKeg,
                                'jam' => $now->format('H:i:s'),
                                'uid' => $uid,
                            ]
                        ]);
                    }
                    DB::rollBack();
                    $akhirPulangStr = $akhirAbsenPulang->format('H:i');
                    return response()->json([
                        'ok' => false,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'checkout_closed',
                        'message' => "Waktu absen pulang telah ditutup (ditutup pukul {$akhirPulangStr}).",
                        'data' => [
                            'nama' => $siswa->nama,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Pulang Ditutup',
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => $att->jam_masuk ? Carbon::parse($att->jam_masuk)->format('H:i') : '--:--',
                            'jam_pulang' => '--:--',
                            'uid' => $uid,
                        ]
                    ]);
                }

                // Cek awal absen pulang (belum waktu pulang)
                if ($jamPulang && $now->lt($jamPulang) && !$teacherSession) {
                    $recordedKegiatans = $this->recordActiveKegiatans($schoolId, $siswa, $now, $today, 'Auto dari Tap Kartu USB');
                    if (!empty($recordedKegiatans)) {
                        DB::commit();
                        $namaKeg = implode(', ', $recordedKegiatans);
                        return response()->json([
                            'ok' => true,
                            'status' => 'success',
                            'sound' => 'ok',
                            'type' => 'absen_kegiatan',
                            'message' => "Absen Kegiatan Berhasil ({$namaKeg})",
                            'data' => [
                                'nama' => $siswa->nama,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Kegiatan: ' . $namaKeg,
                                'jam' => $now->format('H:i:s'),
                                'uid' => $uid,
                            ]
                        ]);
                    }

                    $jamPulangStr = $jamPulang->format('H:i');
                    $isStillInCheckinWindow = ($awalAbsenMasuk && $now->between($awalAbsenMasuk, $akhirAbsenMasuk ?? $jamPulang));
                    if ($isStillInCheckinWindow) {
                        DB::rollBack();
                        return response()->json([
                            'ok' => true,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'sudah_absen_masuk',
                            'message' => "Siswa {$siswa->nama} sudah absen masuk (Waktu pulang belum dimulai, dibuka pukul {$jamPulangStr}).",
                            'data' => [
                                'nama' => $siswa->nama,
                                'nis' => $siswa->nis ?? '-',
                                'foto' => $siswa->foto_url ?? null,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Sudah Masuk',
                                'jam' => $now->format('H:i:s'),
                                'jam_masuk' => $att->jam_masuk ? Carbon::parse($att->jam_masuk)->format('H:i') : '--:--',
                                'jam_pulang' => '--:--',
                                'keterangan' => "Waktu pulang dibuka pukul {$jamPulangStr}",
                                'uid' => $uid,
                            ]
                        ]);
                    }

                    DB::rollBack();
                    return response()->json([
                        'ok' => false,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'no_authorization',
                        'message' => "Belum waktu kepulangan (dibuka pukul {$jamPulangStr}) dan tidak ada izin guru pembuka gerbang.",
                        'data' => [
                            'nama' => $siswa->nama,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Belum Jam Pulang',
                            'jam' => $now->format('H:i:s'),
                            'jam_masuk' => $att->jam_masuk ? Carbon::parse($att->jam_masuk)->format('H:i') : '--:--',
                            'jam_pulang' => '--:--',
                            'keterangan' => "Waktu pulang dibuka pukul {$jamPulangStr}",
                            'uid' => $uid,
                        ]
                    ]);
                }

                // Process Pulang Siswa
                $masuk = Carbon::parse($att->tanggal . ' ' . $att->jam_masuk);
                $totalSeconds = (int) round(abs($masuk->diffInSeconds($now, false)));

                $newStatus = $att->status;
                $newKeterangan = $att->keterangan;

                if ($att->status === 'B') {
                    $newStatus = ($batasTelat && $masuk->gt($batasTelat)) ? 'T' : 'H';
                    if ($newKeterangan) {
                        $newKeterangan = trim(str_replace('[Auto: Tidak Absen Pulang]', '', $newKeterangan));
                        if (empty($newKeterangan)) $newKeterangan = null;
                    }
                }

                $att->update([
                    'jam_pulang' => $now->toTimeString(),
                    'total_seconds' => $totalSeconds,
                    'status' => $newStatus,
                    'keterangan' => $newKeterangan,
                    'updated_at' => now(),
                ]);

                $this->recordActiveKegiatans($schoolId, $siswa, $now, $today, 'Auto dari Absen Pulang USB');
                DB::commit();

                $hours = floor($totalSeconds / 3600);
                $mins = floor(($totalSeconds % 3600) / 60);
                $authorizedBy = $teacherSession ? $teacherSession->teacher_name : 'Sistem Otomatis';

                try {
                    $this->wa->sendCheckOut($siswa->nama, $siswa->no_wa, $now->format('H:i'), $hours, $mins, $authorizedBy, $schoolId, $masuk->format('H:i'), $siswa->wa_ortu, $now->format('d/m/Y'));
                } catch (\Exception $e) {
                    Log::error("WA Student USB Checkout Error: " . $e->getMessage());
                }

                // Send FCM Push Notification
                try {
                    FcmNotificationService::sendToSiswa($siswa, "Presensi Pulang Tercatat", "Halo {$siswa->nama}, absensi pulang Anda berhasil dicatat pukul {$now->format('H:i')}.", [
                        'type' => 'checkout_siswa',
                        'jam' => $now->format('H:i'),
                    ]);
                } catch (\Throwable $e) {
                    Log::error("FCM Student USB Checkout Error: " . $e->getMessage());
                }

                return response()->json([
                    'ok' => true,
                    'status' => 'success',
                    'sound' => 'ok',
                    'type' => 'absen_pulang',
                    'message' => "Absen Pulang Berhasil: {$siswa->nama}",
                    'data' => [
                        'nama' => $siswa->nama,
                        'nis' => $siswa->nis ?? '-',
                        'foto' => $siswa->foto_url ?? null,
                        'role' => 'Siswa',
                        'kelas' => $siswa->kelas->nama_kelas ?? '-',
                        'status' => 'Pulang',
                        'jam' => $now->format('H:i:s'),
                        'jam_masuk' => $att->jam_masuk ? Carbon::parse($att->jam_masuk)->format('H:i') : '--:--',
                        'jam_pulang' => $now->format('H:i'),
                        'keterangan' => "Total Belajar: {$hours}j {$mins}m",
                        'uid' => $uid,
                    ]
                ]);
            }

            // Case 3: Absen Masuk Siswa
            if (!$att || !$att->jam_masuk) {
                // Cek Awal Masuk & Akhir Masuk
                $isBeforeAwalMasuk = $awalAbsenMasuk && $now->lt($awalAbsenMasuk);
                $isAfterAkhirMasuk = $akhirAbsenMasuk && $now->gt($akhirAbsenMasuk);

                if ($isBeforeAwalMasuk || $isAfterAkhirMasuk) {
                    $recordedKegiatans = $this->recordActiveKegiatans($schoolId, $siswa, $now, $today, 'Scan Mandiri Kegiatan USB');
                    if (!empty($recordedKegiatans)) {
                        DB::commit();
                        $namaKeg = implode(', ', $recordedKegiatans);
                        return response()->json([
                            'ok' => true,
                            'status' => 'success',
                            'sound' => 'ok',
                            'type' => 'absen_kegiatan',
                            'message' => "Absen Kegiatan Berhasil ({$namaKeg})",
                            'data' => [
                                'nama' => $siswa->nama,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Kegiatan: ' . $namaKeg,
                                'jam' => $now->format('H:i:s'),
                                'uid' => $uid,
                            ]
                        ]);
                    }

                    if ($isBeforeAwalMasuk) {
                        DB::rollBack();
                        $awalMasukStr = $awalAbsenMasuk->format('H:i');
                        return response()->json([
                            'ok' => false,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'too_early',
                            'message' => "Waktu absen masuk belum dibuka (dibuka pukul {$awalMasukStr}).",
                            'data' => [
                                'nama' => $siswa->nama,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Belum Buka',
                                'jam' => $now->format('H:i:s'),
                                'uid' => $uid,
                            ]
                        ]);
                    }

                    if ($isAfterAkhirMasuk) {
                        DB::rollBack();
                        $akhirMasukStr = $akhirAbsenMasuk->format('H:i');
                        return response()->json([
                            'ok' => false,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'checkin_closed',
                            'message' => "Waktu absen masuk telah ditutup (ditutup pukul {$akhirMasukStr}).",
                            'data' => [
                                'nama' => $siswa->nama,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Masuk Ditutup',
                                'jam' => $now->format('H:i:s'),
                                'uid' => $uid,
                            ]
                        ]);
                    }
                }

                $status = 'H';
                $keterangan = null;
                $statusLabel = 'Hadir Tepat Waktu';

                if ($batasTelat && $now->gt($batasTelat)) {
                    $status = 'T';
                    $diff = $now->timestamp - $batasTelat->timestamp;
                    $jam = floor($diff / 3600);
                    $menit = floor(($diff % 3600) / 60);

                    if ($jam > 0) {
                        $keterangan = "Telat {$jam} jam {$menit} menit";
                    } else {
                        $keterangan = "Telat {$menit} menit";
                    }
                    $statusLabel = "Terlambat ({$keterangan})";
                }

                if ($att) {
                    $att->update([
                        'jam_masuk' => $now->toTimeString(),
                        'status' => $status,
                        'keterangan' => $keterangan,
                        'updated_at' => now(),
                    ]);
                } else {
                    Attendance::create([
                        'student_id' => $siswa->id,
                        'tanggal' => $today,
                        'jam_masuk' => $now->toTimeString(),
                        'status' => $status,
                        'keterangan' => $keterangan,
                        'created_at' => now(),
                    ]);
                }

                $this->recordActiveKegiatans($schoolId, $siswa, $now, $today, 'Auto dari Absen Masuk USB');
                DB::commit();

                try {
                    $this->wa->sendCheckIn($siswa->nama, $siswa->no_wa, $now->format('H:i'), $status, $schoolId, $keterangan, $siswa->wa_ortu, $siswa->kelas->nama_kelas ?? '-');
                } catch (\Exception $e) {
                    Log::error("WA Student USB Checkin Error: " . $e->getMessage());
                }

                // Send FCM Push Notification
                try {
                    $readableStatus = $status === 'T' ? 'Terlambat' : 'Hadir';
                    FcmNotificationService::sendToSiswa($siswa, "Presensi Masuk Tercatat", "Halo {$siswa->nama}, absensi masuk Anda berhasil dicatat pukul {$now->format('H:i')} (Status: {$readableStatus}).", [
                        'type' => 'checkin_siswa',
                        'status' => $readableStatus,
                        'jam' => $now->format('H:i'),
                    ]);
                } catch (\Throwable $e) {
                    Log::error("FCM Student USB Checkin Error: " . $e->getMessage());
                }

                return response()->json([
                    'ok' => true,
                    'status' => 'success',
                    'sound' => ($status === 'T' ? 'warning' : 'ok'),
                    'type' => 'absen_masuk',
                    'message' => "Absen Masuk Berhasil: {$siswa->nama} ({$statusLabel})",
                    'data' => [
                        'nama' => $siswa->nama,
                        'nis' => $siswa->nis ?? '-',
                        'foto' => $siswa->foto_url ?? null,
                        'role' => 'Siswa',
                        'kelas' => $siswa->kelas->nama_kelas ?? '-',
                        'status' => $status === 'T' ? 'Terlambat' : 'Hadir',
                        'jam' => $now->format('H:i:s'),
                        'jam_masuk' => $now->format('H:i'),
                        'jam_pulang' => '--:--',
                        'keterangan' => $keterangan ?: 'Tepat Waktu',
                        'uid' => $uid,
                    ]
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("USB RFID Scan Error: " . $e->getMessage());
            return response()->json([
                'ok' => false,
                'status' => 'error',
                'sound' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to auto-record attendance for active kegiatan scheduled now.
     */
    private function recordActiveKegiatans($schoolId, $siswa, $now, $today, $keterangan = 'Auto dari Scan Mesin'): array
    {
        $activeKegiatans = \App\Models\Kegiatan::where('school_id', $schoolId)
            ->where('is_active', 1)
            ->get()
            ->filter(function ($keg) use ($now) {
                return $keg->isScheduledNow($now);
            });

        $recordedKegiatans = [];
        foreach ($activeKegiatans as $keg) {
            if (!$keg->isStudentEligible($siswa)) {
                continue;
            }

            $alreadyKeg = \App\Models\KegiatanAttendance::where('kegiatan_id', $keg->id)
                ->where('student_id', $siswa->id)
                ->where('tanggal', $today)
                ->exists();

            if (!$alreadyKeg) {
                \App\Models\KegiatanAttendance::create([
                    'school_id'   => $schoolId,
                    'kegiatan_id' => $keg->id,
                    'student_id'  => $siswa->id,
                    'tanggal'     => $today,
                    'jam_masuk'   => $now->toTimeString(),
                    'status'      => 'H',
                    'keterangan'  => $keterangan,
                ]);
                $recordedKegiatans[] = $keg->nama_kegiatan;

                try {
                    $telegramService = app(\App\Services\TelegramService::class);
                    $telegramService->sendKegiatanCheckIn(
                        namaSiswa: $siswa->nama,
                        namaKegiatan: $keg->nama_kegiatan,
                        jam: $now->format('H:i'),
                        tanggal: $now->translatedFormat('l, d F Y'),
                        schoolId: $schoolId,
                        chatIdSiswa: $siswa->telegram_chat_id ?: null,
                        chatIdOrtu: $siswa->telegram_ortu_chat_id ?: null
                    );
                } catch (\Throwable $e) {
                    Log::error("Failed to send Telegram for kegiatan attendance: " . $e->getMessage());
                }
            }
        }
        return $recordedKegiatans;
    }
}
