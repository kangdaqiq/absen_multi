<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\Attendance;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\StudentLeave;
use App\Models\User;
use App\Services\FcmNotificationService;
use App\Services\WhatsAppService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MobileStudentLeaveController extends Controller
{
    protected WhatsAppService $wa;

    public function __construct(WhatsAppService $wa)
    {
        $this->wa = $wa;
    }

    /**
     * Daftar pengajuan izin milik siswa yang sedang login.
     */
    public function myLeaves(Request $request)
    {
        $user = $request->user();
        $siswa = $this->resolveStudent($user);

        if (!$siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terhubung dengan data siswa.',
            ], 403);
        }

        $baseQuery = StudentLeave::with(['student.kelas', 'approver'])
            ->where('school_id', $siswa->school_id)
            ->where('student_id', $siswa->id);

        $summary = [
            'pending'  => (clone $baseQuery)->where('status', 'pending')->count(),
            'approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'total'    => (clone $baseQuery)->count(),
        ];

        $query = clone $baseQuery;

        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('jenis') && in_array($request->jenis, ['sakit', 'izin', 'dispensasi'])) {
            $query->where('jenis', $request->jenis);
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $leaves = $query->latest('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengajuan izin siswa berhasil diambil.',
            'summary' => $summary,
            'data'    => $leaves->getCollection()->map(fn($leave) => $this->formatLeave($leave))->values(),
            'pagination' => $this->formatPagination($leaves),
        ]);
    }

    /**
     * Siswa mengajukan izin/sakit baru via mobile app.
     */
    public function submitStudentLeave(Request $request)
    {
        $user = $request->user();
        $siswa = $this->resolveStudent($user);

        if (!$siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terhubung dengan data siswa.',
            ], 403);
        }

        $validated = $request->validate([
            'jenis'           => 'required|in:sakit,izin,dispensasi',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan'      => 'required|string|max:1000',
            'bukti_foto'      => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'foto'            => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'pengaju'         => 'nullable|in:siswa,ortu',
            'nama_pengaju'    => 'nullable|string|max:100',
            'no_wa_pengaju'   => 'nullable|string|max:25',
        ]);

        $tglMulai = Carbon::parse($validated['tanggal_mulai'])->format('Y-m-d');
        $tglSelesai = Carbon::parse($validated['tanggal_selesai'])->format('Y-m-d');

        $overlap = StudentLeave::where('student_id', $siswa->id)
            ->whereIn('status', ['pending', 'approved'])
            ->whereDate('tanggal_mulai', '<=', $tglSelesai)
            ->whereDate('tanggal_selesai', '>=', $tglMulai)
            ->exists();

        if ($overlap) {
            return response()->json([
                'success' => false,
                'message' => 'Sudah ada pengajuan izin yang sedang diproses atau disetujui pada rentang tanggal tersebut.',
            ], 422);
        }

        $file = $request->file('bukti_foto') ?? $request->file('foto');
        $filePath = $file ? $file->store('surat_izin', 'public') : null;

        $leave = StudentLeave::create([
            'school_id'       => $siswa->school_id,
            'student_id'      => $siswa->id,
            'code'            => StudentLeave::generateCode(),
            'jenis'           => $validated['jenis'],
            'tanggal_mulai'   => $tglMulai,
            'tanggal_selesai' => $tglSelesai,
            'keterangan'      => $validated['keterangan'],
            'bukti_foto'      => $filePath,
            'pengaju'         => $validated['pengaju'] ?? 'siswa',
            'nama_pengaju'    => $validated['nama_pengaju'] ?? $siswa->nama,
            'no_wa_pengaju'   => $validated['no_wa_pengaju'] ?? ($siswa->wa_ortu ?: $siswa->no_wa),
            'status'          => 'pending',
        ]);

        $leave->load(['student.kelas.waliKelas', 'student.kelas.waliKelas2', 'approver']);

        $this->notifyTeachersOnSubmission($leave);

        $this->logMobile(
            $siswa->school_id,
            'mobile_izin_submit',
            $siswa->nis,
            true,
            "Pengajuan {$leave->jenis} ({$leave->code}) oleh siswa {$siswa->nama}",
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan ' . $leave->jenis_label . ' berhasil dikirim dan menunggu persetujuan wali kelas.',
            'data'    => $this->formatLeave($leave),
        ], 201);
    }

    /**
     * Detail satu pengajuan izin (akses siswa pemilik atau guru/admin yang berwenang).
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $leave = StudentLeave::with(['student.kelas', 'approver'])->find($id);

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengajuan izin tidak ditemukan.',
            ], 404);
        }

        if (!$this->canViewLeave($user, $leave)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melihat pengajuan izin ini.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pengajuan izin berhasil diambil.',
            'data'    => $this->formatLeave($leave),
        ]);
    }

    /**
     * Batalkan / hapus pengajuan izin (siswa hanya saat pending; guru/admin yang berwenang dapat menghapus).
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $leave = StudentLeave::with('student')->find($id);

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengajuan izin tidak ditemukan.',
            ], 404);
        }

        $siswa = $this->resolveStudent($user);
        if ($siswa && !$this->isStaffRole($user)) {
            if ((int) $leave->student_id !== (int) $siswa->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak berhak membatalkan pengajuan izin milik siswa lain.',
                ], 403);
            }

            if ($leave->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengajuan yang sudah diproses tidak dapat dibatalkan.',
                ], 422);
            }
        } elseif (!$this->canManageLeave($user, $leave)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus pengajuan izin ini.',
            ], 403);
        }

        $this->deleteAttachmentIfExists($leave);
        $code = $leave->code;
        $schoolId = $leave->school_id;
        $leave->delete();

        $this->logMobile(
            $schoolId,
            'mobile_izin_delete',
            $user->username ?? (string) $user->id,
            true,
            "Pengajuan izin {$code} dihapus/dibatalkan",
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan izin berhasil dihapus.',
        ]);
    }

    /**
     * Daftar pengajuan izin siswa untuk dikelola guru / wali kelas / admin.
     */
    public function teacherIndex(Request $request)
    {
        $user = $request->user();
        $access = $this->resolveTeacherAccess($user);

        if (!$access['allowed']) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Endpoint ini khusus untuk akun guru/wali kelas/admin.',
            ], 403);
        }

        $schoolId = $user->school_id;
        $isFullSchool = $access['full_school'];
        $managedKelasIds = $access['managed_kelas_ids'];

        $baseQuery = StudentLeave::with(['student.kelas', 'approver'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId));

        if (!$isFullSchool) {
            $baseQuery->whereHas('student', function ($q) use ($managedKelasIds) {
                $q->whereIn('kelas_id', empty($managedKelasIds) ? [-1] : $managedKelasIds);
            });
        }

        $summary = [
            'total_pending'  => (clone $baseQuery)->where('status', 'pending')->count(),
            'total_approved' => (clone $baseQuery)->where('status', 'approved')->count(),
            'total_rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
            'total_today'    => (clone $baseQuery)->whereDate('created_at', today())->count(),
        ];

        $query = clone $baseQuery;

        if ($request->filled('status') && in_array($request->status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kelas_id')) {
            $kelasId = (int) $request->kelas_id;
            $query->whereHas('student', fn($q) => $q->where('kelas_id', $kelasId));
        }

        if ($request->filled('jenis') && in_array($request->jenis, ['sakit', 'izin', 'dispensasi'])) {
            $query->where('jenis', $request->jenis);
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_mulai', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_selesai', '<=', $request->tanggal_selesai);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('nama_pengaju', 'LIKE', "%{$search}%")
                    ->orWhere('no_wa_pengaju', 'LIKE', "%{$search}%")
                    ->orWhere('keterangan', 'LIKE', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('nama', 'LIKE', "%{$search}%")
                            ->orWhere('nis', 'LIKE', "%{$search}%");
                    });
            });
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $leaves = $query->latest('id')->paginate($perPage);

        $managedKelases = Kelas::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when(!$isFullSchool, fn($q) => $q->whereIn('id', empty($managedKelasIds) ? [-1] : $managedKelasIds))
            ->where('is_active_attendance', true)
            ->orderBy('nama_kelas')
            ->get(['id', 'nama_kelas', 'tingkat']);

        return response()->json([
            'success'         => true,
            'message'         => 'Daftar pengajuan izin siswa berhasil diambil.',
            'can_manage'      => $isFullSchool || !empty($managedKelasIds),
            'is_full_school'  => $isFullSchool,
            'summary'         => $summary,
            'managed_kelases' => $managedKelases,
            'data'            => $leaves->getCollection()->map(fn($leave) => $this->formatLeave($leave))->values(),
            'pagination'      => $this->formatPagination($leaves),
        ]);
    }

    /**
     * Daftar siswa di kelas yang dikelola guru (untuk form input izin manual oleh guru).
     */
    public function teacherStudents(Request $request)
    {
        $user = $request->user();
        $access = $this->resolveTeacherAccess($user);

        if (!$access['allowed']) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Endpoint ini khusus untuk akun guru/wali kelas/admin.',
            ], 403);
        }

        $schoolId = $user->school_id;
        $isFullSchool = $access['full_school'];
        $managedKelasIds = $access['managed_kelas_ids'];

        $query = Siswa::with('kelas:id,nama_kelas')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId));

        if (!$isFullSchool) {
            $query->whereIn('kelas_id', empty($managedKelasIds) ? [-1] : $managedKelasIds);
        }

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', (int) $request->kelas_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                    ->orWhere('nis', 'LIKE', "%{$search}%");
            });
        }

        $students = $query->orderBy('nama')->limit(100)->get(['id', 'nama', 'nis', 'kelas_id']);

        return response()->json([
            'success' => true,
            'data'    => $students->map(fn($s) => [
                'id'         => $s->id,
                'nama'       => $s->nama,
                'nis'        => $s->nis,
                'kelas_id'   => $s->kelas_id,
                'nama_kelas' => $s->kelas?->nama_kelas,
            ])->values(),
        ]);
    }

    /**
     * Guru / wali kelas menginput izin siswa secara langsung (otomatis disetujui & sinkron ke absensi).
     */
    public function teacherStore(Request $request)
    {
        $user = $request->user();
        $access = $this->resolveTeacherAccess($user);

        if (!$access['allowed']) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Endpoint ini khusus untuk akun guru/wali kelas/admin.',
            ], 403);
        }

        $validated = $request->validate([
            'student_id'      => 'required|exists:siswa,id',
            'jenis'           => 'required|in:sakit,izin,dispensasi',
            'tanggal_mulai'   => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'keterangan'      => 'required|string|max:1000',
            'bukti_foto'      => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'foto'            => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        $student = Siswa::with('kelas')->findOrFail($validated['student_id']);

        if ($user->school_id && (int) $student->school_id !== (int) $user->school_id) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa bukan berasal dari sekolah Anda.',
            ], 403);
        }

        if (!$access['full_school'] && !in_array($student->kelas_id, $access['managed_kelas_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan wali kelas dari siswa ini.',
            ], 403);
        }

        $file = $request->file('bukti_foto') ?? $request->file('foto');
        $filePath = $file ? $file->store('surat_izin', 'public') : null;

        DB::beginTransaction();
        try {
            $leave = StudentLeave::create([
                'school_id'       => $student->school_id,
                'student_id'      => $student->id,
                'code'            => StudentLeave::generateCode(),
                'jenis'           => $validated['jenis'],
                'tanggal_mulai'   => Carbon::parse($validated['tanggal_mulai'])->format('Y-m-d'),
                'tanggal_selesai' => Carbon::parse($validated['tanggal_selesai'])->format('Y-m-d'),
                'keterangan'      => $validated['keterangan'],
                'bukti_foto'      => $filePath,
                'pengaju'         => 'guru',
                'nama_pengaju'    => $user->full_name,
                'no_wa_pengaju'   => $student->wa_ortu ?: $student->no_wa,
                'status'          => 'approved',
                'approved_by'     => $user->id,
                'approved_at'     => now(),
            ]);

            $this->syncLeaveToAttendance($leave, "[Input Guru: {$user->full_name}] {$leave->keterangan}");

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($filePath && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            Log::error("Mobile teacherStore leave error: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal mencatat izin siswa: ' . $e->getMessage(),
            ], 500);
        }

        $leave->load(['student.kelas', 'approver']);
        $this->notifyStudentOnDecision($leave, true);

        $this->logMobile(
            $leave->school_id,
            'mobile_izin_teacher_store',
            $student->nis,
            true,
            "Guru {$user->full_name} mencatat izin {$leave->code} untuk {$student->nama}",
            $request
        );

        return response()->json([
            'success' => true,
            'message' => "Izin siswa {$student->nama} berhasil dicatat dan disinkronkan ke absensi.",
            'data'    => $this->formatLeave($leave),
        ], 201);
    }

    /**
     * Guru / wali kelas menyetujui pengajuan izin siswa & sinkronkan ke absensi.
     */
    public function approve(Request $request, $id)
    {
        $user = $request->user();
        $leave = StudentLeave::with(['student.kelas', 'approver'])->find($id);

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengajuan izin tidak ditemukan.',
            ], 404);
        }

        if (!$this->canManageLeave($user, $leave)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menyetujui pengajuan izin ini.',
            ], 403);
        }

        if ($leave->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pengajuan berstatus menunggu (pending) yang dapat disetujui.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $leave->update([
                'status'          => 'approved',
                'approved_by'     => $user->id,
                'approved_at'     => now(),
                'rejected_reason' => null,
            ]);

            $this->syncLeaveToAttendance(
                $leave,
                "[Pengajuan " . ucfirst($leave->jenis) . "] " . $leave->keterangan
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Mobile approve leave error: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyetujui pengajuan izin: ' . $e->getMessage(),
            ], 500);
        }

        $leave->refresh()->load(['student.kelas', 'approver']);

        try {
            $this->wa->sendLeaveApprovedNotification($leave, $user->full_name);
        } catch (\Throwable $e) {
            Log::error("Failed to send WA Leave Approved (mobile): " . $e->getMessage());
        }

        $this->notifyStudentOnDecision($leave, true);

        $this->logMobile(
            $leave->school_id,
            'mobile_izin_approve',
            $leave->student?->nis ?? $leave->code,
            true,
            "Pengajuan {$leave->code} disetujui oleh {$user->full_name}",
            $request
        );

        return response()->json([
            'success' => true,
            'message' => "Pengajuan {$leave->code} an. {$leave->student?->nama} berhasil disetujui dan dicatat ke absensi.",
            'data'    => $this->formatLeave($leave),
        ]);
    }

    /**
     * Guru / wali kelas menolak pengajuan izin siswa dengan alasan.
     */
    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'rejected_reason' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $leave = StudentLeave::with(['student.kelas', 'approver'])->find($id);

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengajuan izin tidak ditemukan.',
            ], 404);
        }

        if (!$this->canManageLeave($user, $leave)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menolak pengajuan izin ini.',
            ], 403);
        }

        if ($leave->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pengajuan berstatus menunggu (pending) yang dapat ditolak.',
            ], 422);
        }

        $leave->update([
            'status'          => 'rejected',
            'rejected_reason' => $validated['rejected_reason'],
            'approved_by'     => $user->id,
            'approved_at'     => now(),
        ]);

        $leave->refresh()->load(['student.kelas', 'approver']);

        try {
            $this->wa->sendLeaveRejectedNotification($leave, $validated['rejected_reason']);
        } catch (\Throwable $e) {
            Log::error("Failed to send WA Leave Rejected (mobile): " . $e->getMessage());
        }

        $this->notifyStudentOnDecision($leave, false);

        $this->logMobile(
            $leave->school_id,
            'mobile_izin_reject',
            $leave->student?->nis ?? $leave->code,
            true,
            "Pengajuan {$leave->code} ditolak oleh {$user->full_name}",
            $request
        );

        return response()->json([
            'success' => true,
            'message' => "Pengajuan {$leave->code} an. {$leave->student?->nama} telah ditolak.",
            'data'    => $this->formatLeave($leave),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function resolveStudent(User $user): ?Siswa
    {
        $siswa = $user->student ?? Siswa::with('kelas')->where('user_id', $user->id)->first();
        if (!$siswa && in_array($user->role, ['student', 'siswa'])) {
            $cleanNis = str_replace('siswa_', '', (string) $user->username);
            $siswa = Siswa::with('kelas')->where('nis', $cleanNis)->first();
        }
        return $siswa;
    }

    private function isStaffRole(User $user): bool
    {
        return in_array($user->role, ['guru', 'teacher', 'wali_kelas', 'waka_kurikulum', 'admin', 'super_admin'])
            || !empty($user->guru);
    }

    private function resolveTeacherAccess(User $user): array
    {
        if (!$this->isStaffRole($user)) {
            return ['allowed' => false, 'full_school' => false, 'managed_kelas_ids' => []];
        }

        $fullSchool = $user->isAdmin() || $user->isWakaKurikulum();
        $managedKelasIds = $fullSchool ? [] : $user->getManagedKelasIds();

        return [
            'allowed'           => true,
            'full_school'       => $fullSchool,
            'managed_kelas_ids' => $managedKelasIds,
        ];
    }

    private function canViewLeave(User $user, StudentLeave $leave): bool
    {
        $siswa = $this->resolveStudent($user);
        if ($siswa && (int) $leave->student_id === (int) $siswa->id) {
            return true;
        }

        return $this->canManageLeave($user, $leave);
    }

    private function canManageLeave(User $user, StudentLeave $leave): bool
    {
        $access = $this->resolveTeacherAccess($user);
        if (!$access['allowed']) {
            return false;
        }

        if ($user->school_id && (int) $leave->school_id !== (int) $user->school_id) {
            return false;
        }

        if ($access['full_school']) {
            return true;
        }

        $kelasId = $leave->student?->kelas_id;
        return $kelasId && in_array($kelasId, $access['managed_kelas_ids']);
    }

    private function syncLeaveToAttendance(StudentLeave $leave, string $keterangan): void
    {
        $period = CarbonPeriod::create($leave->tanggal_mulai, $leave->tanggal_selesai);
        $statusCode = ($leave->jenis === 'sakit') ? 'S' : 'I';

        foreach ($period as $date) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $leave->student_id,
                    'tanggal'    => $date->format('Y-m-d'),
                ],
                [
                    'status'        => $statusCode,
                    'keterangan'    => $keterangan,
                    'is_auto_alpha' => false,
                    'updated_at'    => now(),
                ]
            );
        }
    }

    private function notifyTeachersOnSubmission(StudentLeave $leave): void
    {
        try {
            $this->wa->sendLeaveSubmittedNotification($leave);
        } catch (\Throwable $e) {
            Log::error("Failed to send WA Leave Submission (mobile): " . $e->getMessage());
        }

        try {
            $kelas = $leave->student?->kelas;
            if (!$kelas) {
                return;
            }

            $waliGurus = array_filter([$kelas->waliKelas, $kelas->waliKelas2]);
            $siswaNama = $leave->student?->nama ?? 'Siswa';
            $title = "Pengajuan {$leave->jenis_label} Baru";
            $body = "{$siswaNama} ({$kelas->nama_kelas}) mengajukan {$leave->jenis_label} untuk "
                . $leave->tanggal_mulai->format('d/m/Y')
                . ($leave->tanggal_mulai->ne($leave->tanggal_selesai) ? ' - ' . $leave->tanggal_selesai->format('d/m/Y') : '');

            foreach ($waliGurus as $wali) {
                FcmNotificationService::sendToGuru($wali, $title, $body, [
                    'type'     => 'student_leave_submitted',
                    'leave_id' => (string) $leave->id,
                    'code'     => $leave->code,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning("Failed to send FCM Leave Submission: " . $e->getMessage());
        }
    }

    private function notifyStudentOnDecision(StudentLeave $leave, bool $approved): void
    {
        try {
            $siswa = $leave->student;
            if (!$siswa) {
                return;
            }

            $title = $approved
                ? "Pengajuan {$leave->jenis_label} Disetujui"
                : "Pengajuan {$leave->jenis_label} Ditolak";

            $body = $approved
                ? "Pengajuan {$leave->code} Anda telah disetujui dan tercatat pada absensi."
                : "Pengajuan {$leave->code} Anda ditolak. Alasan: " . ($leave->rejected_reason ?? '-');

            FcmNotificationService::sendToSiswa($siswa, $title, $body, [
                'type'     => $approved ? 'student_leave_approved' : 'student_leave_rejected',
                'leave_id' => (string) $leave->id,
                'code'     => $leave->code,
                'status'   => $leave->status,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Failed to send FCM Leave Decision: " . $e->getMessage());
        }
    }

    private function deleteAttachmentIfExists(StudentLeave $leave): void
    {
        if ($leave->bukti_foto && !str_starts_with($leave->bukti_foto, 'http')) {
            if (Storage::disk('public')->exists($leave->bukti_foto)) {
                Storage::disk('public')->delete($leave->bukti_foto);
            }
        }
    }

    private function formatLeave(StudentLeave $leave): array
    {
        $buktiFotoUrl = null;
        if ($leave->bukti_foto) {
            $buktiFotoUrl = str_starts_with($leave->bukti_foto, 'http')
                ? $leave->bukti_foto
                : asset('storage/' . ltrim($leave->bukti_foto, '/'));
        }

        return [
            'id'               => $leave->id,
            'code'             => $leave->code,
            'school_id'        => $leave->school_id,
            'student_id'       => $leave->student_id,
            'student'          => $leave->student ? [
                'id'         => $leave->student->id,
                'nama'       => $leave->student->nama,
                'nis'        => $leave->student->nis,
                'kelas_id'   => $leave->student->kelas_id,
                'nama_kelas' => $leave->student->kelas?->nama_kelas,
            ] : null,
            'jenis'            => $leave->jenis,
            'jenis_label'      => $leave->jenis_label,
            'tanggal_mulai'    => $leave->tanggal_mulai?->format('Y-m-d'),
            'tanggal_selesai'  => $leave->tanggal_selesai?->format('Y-m-d'),
            'durasi_hari'      => $leave->durasi_hari,
            'keterangan'       => $leave->keterangan,
            'bukti_foto'       => $leave->bukti_foto,
            'bukti_foto_url'   => $buktiFotoUrl,
            'pengaju'          => $leave->pengaju,
            'nama_pengaju'     => $leave->nama_pengaju,
            'no_wa_pengaju'    => $leave->no_wa_pengaju,
            'status'           => $leave->status,
            'approved_by'      => $leave->approved_by,
            'approver_name'    => $leave->approver?->full_name,
            'approved_at'      => $leave->approved_at?->toIso8601String(),
            'rejected_reason'  => $leave->rejected_reason,
            'created_at'       => $leave->created_at?->toIso8601String(),
            'updated_at'       => $leave->updated_at?->toIso8601String(),
        ];
    }

    private function formatPagination($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
        ];
    }

    private function logMobile(
        ?int $schoolId,
        string $action,
        ?string $uid,
        bool $success,
        string $message,
        ?Request $request = null
    ): void {
        try {
            $req = $request ?: request();
            ApiLog::create([
                'school_id'  => $schoolId,
                'api_key'    => 'MOBILE_APP',
                'action'     => $action,
                'uid'        => $uid,
                'success'    => $success,
                'message'    => $message,
                'ip_address' => $req?->ip() ?? '127.0.0.1',
                'user_agent' => $req?->userAgent() ?? 'Android Mobile App',
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("ApiLog error: " . $e->getMessage());
        }
    }
}

