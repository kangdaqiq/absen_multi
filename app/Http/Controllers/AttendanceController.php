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
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $totalPulang = (clone $attendanceQuery)->whereNotNull('jam_pulang')->count();
        $totalBelumAbsen = max(0, $totalSiswa - $totalMasuk);

        $recentAttendances = $attendanceQuery->orderBy('updated_at', 'desc')->limit(15)->get();

        $indexHari = (int) now()->format('N');
        $jadwal = Jadwal::where('index_hari', $indexHari)
            ->where('is_active', 1)
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->first();

        return view('absensi.scanner-usb', compact(
            'today', 'totalSiswa', 'totalMasuk', 'totalTepatWaktu', 'totalTerlambat', 'totalPulang', 'totalBelumAbsen', 'recentAttendances', 'jadwal'
        ));
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

    /**
     * Process Scan from USB RFID Reader connected directly to PC/Browser.
     */
    public function scanUsbRfid(Request $request)
    {
        $request->validate([
            'uid' => 'required|string',
        ]);

        $rawUid = trim($request->input('uid'));
        $uid = strtoupper(trim($rawUid));

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

                if (!$absensiGuru) {
                    // Check-in
                    if (!$shift->isInCheckInWindow($now->format('H:i:s'))) {
                        DB::rollBack();
                        $windowStr = ($shift->awal_absen_masuk && $shift->akhir_absen_masuk)
                            ? Carbon::parse($shift->awal_absen_masuk)->format('H:i') . '-' . Carbon::parse($shift->akhir_absen_masuk)->format('H:i')
                            : '';
                        return response()->json([
                            'ok' => false,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'outside_checkin_window',
                            'message' => "Di luar jam absen masuk ({$windowStr})",
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

                    AbsensiGuru::create([
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
                    ]);

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
                        'sound' => 'ok',
                        'type' => 'absen_masuk_guru',
                        'message' => $respMsg,
                        'data' => [
                            'nama' => $teacher->nama,
                            'role' => 'Guru / Staf',
                            'kelas' => $shift->nama_shift,
                            'status' => $status,
                            'jam' => $now->format('H:i:s'),
                            'keterangan' => $keterangan,
                            'uid' => $uid,
                        ]
                    ]);
                } else {
                    // Check-out
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
                            'message' => "Guru {$teacher->nama} sudah absen masuk sebelumnya.",
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
                                'message' => "Guru {$teacher->nama} sudah absen masuk.",
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
                        $windowStr = ($shift->awal_absen_pulang && $shift->akhir_absen_pulang)
                            ? Carbon::parse($shift->awal_absen_pulang)->format('H:i') . '-' . Carbon::parse($shift->akhir_absen_pulang)->format('H:i')
                            : '';
                        return response()->json([
                            'ok' => false,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'outside_checkout_window',
                            'message' => "Di luar jam absen pulang ({$windowStr})",
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
                    $totalSeconds = $masuk->diffInSeconds($now, false);
                    if ($totalSeconds < 0) $totalSeconds = abs($totalSeconds);

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
                            'keterangan' => "Total durasi: {$hours}j {$mins}m",
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

        $jamMasuk = Carbon::parse($now->format('Y-m-d') . ' ' . $jadwal->jam_masuk);
        $jamPulang = Carbon::parse($now->format('Y-m-d') . ' ' . $jadwal->jam_pulang);
        $awalAbsenMasuk = Carbon::parse($now->format('Y-m-d') . ' ' . $jadwal->awal_absen_masuk);
        $akhirAbsenMasuk = Carbon::parse($now->format('Y-m-d') . ' ' . $jadwal->akhir_absen_masuk);
        $akhirAbsenPulang = Carbon::parse($now->format('Y-m-d') . ' ' . $jadwal->akhir_absen_pulang);
        $batasTelat = $jamMasuk;

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
                        'role' => 'Siswa',
                        'kelas' => $siswa->kelas->nama_kelas ?? '-',
                        'status' => 'Sudah Lengkap',
                        'jam' => $now->format('H:i:s'),
                        'uid' => $uid,
                    ]
                ]);
            }

            // Case 2: Sudah Masuk, Belum Pulang -> Proses Pulang
            if ($att && $att->jam_masuk && !$att->jam_pulang) {
                $checkoutEnabled = Setting::where('school_id', $schoolId)
                    ->where('setting_key', 'enable_checkout_attendance')
                    ->value('setting_value') ?? 'true';

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
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Sudah Masuk',
                            'jam' => $now->format('H:i:s'),
                            'uid' => $uid,
                        ]
                    ]);
                }

                $teacherSession = TeacherCheckoutSession::select('teacher_checkout_sessions.*')
                    ->join('guru', 'teacher_checkout_sessions.teacher_id', '=', 'guru.id')
                    ->where('guru.school_id', $schoolId)
                    ->where('teacher_checkout_sessions.expires_at', '>', now())
                    ->where('teacher_checkout_sessions.status', 'open')
                    ->orderBy('teacher_checkout_sessions.created_at', 'desc')
                    ->first();

                $isAutoCheckoutTime = $now->between($jamPulang, $akhirAbsenPulang);

                if ($now->gt($akhirAbsenPulang) && !$teacherSession) {
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
                    return response()->json([
                        'ok' => false,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'checkout_closed',
                        'message' => 'Waktu absen pulang telah ditutup.',
                        'data' => [
                            'nama' => $siswa->nama,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Pulang Ditutup',
                            'jam' => $now->format('H:i:s'),
                            'uid' => $uid,
                        ]
                    ]);
                }

                if (!$isAutoCheckoutTime && !$teacherSession) {
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

                    if ($now->between($awalAbsenMasuk, $akhirAbsenMasuk)) {
                        DB::rollBack();
                        return response()->json([
                            'ok' => true,
                            'status' => 'warning',
                            'sound' => 'warning',
                            'type' => 'sudah_absen_masuk',
                            'message' => "Siswa {$siswa->nama} sudah absen masuk (Jam Pulang belum dimulai).",
                            'data' => [
                                'nama' => $siswa->nama,
                                'role' => 'Siswa',
                                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                                'status' => 'Sudah Masuk',
                                'jam' => $now->format('H:i:s'),
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
                        'message' => 'Belum waktu kepulangan dan tidak ada izin guru pembuka gerbang.',
                        'data' => [
                            'nama' => $siswa->nama,
                            'role' => 'Siswa',
                            'kelas' => $siswa->kelas->nama_kelas ?? '-',
                            'status' => 'Belum Jam Pulang',
                            'jam' => $now->format('H:i:s'),
                            'uid' => $uid,
                        ]
                    ]);
                }

                // Process Pulang
                $masuk = Carbon::parse($att->tanggal . ' ' . $att->jam_masuk);
                $totalSeconds = $masuk->diffInSeconds($now, false);
                if ($totalSeconds < 0) $totalSeconds = abs($totalSeconds);

                $newStatus = $att->status;
                $newKeterangan = $att->keterangan;

                if ($att->status === 'B') {
                    $waktuMasuk = Carbon::parse($att->tanggal . ' ' . $att->jam_masuk);
                    $newStatus = $waktuMasuk->gt($batasTelat) ? 'T' : 'H';
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

                return response()->json([
                    'ok' => true,
                    'status' => 'success',
                    'sound' => 'ok',
                    'type' => 'absen_pulang',
                    'message' => "Absen Pulang Berhasil: {$siswa->nama}",
                    'data' => [
                        'nama' => $siswa->nama,
                        'role' => 'Siswa',
                        'kelas' => $siswa->kelas->nama_kelas ?? '-',
                        'status' => 'Pulang',
                        'jam' => $now->format('H:i:s'),
                        'keterangan' => "Total Belajar: {$hours}j {$mins}m",
                        'uid' => $uid,
                    ]
                ]);
            }

            // Case 3: Absen Masuk
            if (!$att || !$att->jam_masuk) {
                if ($now->lt($awalAbsenMasuk) || $now->gt($akhirAbsenMasuk)) {
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
                }

                if ($now->lt($awalAbsenMasuk)) {
                    DB::rollBack();
                    return response()->json([
                        'ok' => false,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'too_early',
                        'message' => 'Waktu absen masuk belum dibuka.',
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

                if ($now->gt($akhirAbsenMasuk)) {
                    DB::rollBack();
                    return response()->json([
                        'ok' => false,
                        'status' => 'warning',
                        'sound' => 'warning',
                        'type' => 'checkin_closed',
                        'message' => 'Waktu absen masuk telah ditutup.',
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

                $status = 'H';
                $keterangan = null;
                $statusLabel = 'Hadir Tepat Waktu';

                if ($now->gt($batasTelat)) {
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

                return response()->json([
                    'ok' => true,
                    'status' => 'success',
                    'sound' => 'ok',
                    'type' => 'absen_masuk',
                    'message' => "Absen Masuk Berhasil: {$siswa->nama} ({$statusLabel})",
                    'data' => [
                        'nama' => $siswa->nama,
                        'role' => 'Siswa',
                        'kelas' => $siswa->kelas->nama_kelas ?? '-',
                        'status' => $status === 'T' ? 'Terlambat' : 'Hadir',
                        'jam' => $now->format('H:i:s'),
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
