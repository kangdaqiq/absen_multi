<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\KategoriPelanggaran;
use App\Models\Kelas;
use App\Models\Pelanggaran;
use App\Models\Siswa;
use App\Models\User;
use App\Services\FcmNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MobilePelanggaranController extends Controller
{
    /**
     * Daftar catatan pelanggaran milik siswa yang sedang login.
     */
    public function myViolations(Request $request)
    {
        $user = $request->user();
        $siswa = $this->resolveStudent($user);

        if (!$siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terhubung dengan data siswa.',
            ], 403);
        }

        $baseQuery = Pelanggaran::with(['student.kelas', 'kategoriPelanggaran', 'recorder'])
            ->where('school_id', $siswa->school_id)
            ->where('student_id', $siswa->id);

        $summary = [
            'total_kasus' => (clone $baseQuery)->count(),
            'total_poin'  => (int) (clone $baseQuery)->sum('poin'),
            'ringan'      => (clone $baseQuery)->where('kategori', 'ringan')->count(),
            'sedang'      => (clone $baseQuery)->where('kategori', 'sedang')->count(),
            'berat'       => (clone $baseQuery)->where('kategori', 'berat')->count(),
            'tercatat'    => (clone $baseQuery)->where('status', 'tercatat')->count(),
            'diproses'    => (clone $baseQuery)->where('status', 'diproses')->count(),
            'selesai'     => (clone $baseQuery)->where('status', 'selesai')->count(),
        ];

        $query = clone $baseQuery;

        if ($request->filled('kategori') && in_array($request->kategori, ['ringan', 'sedang', 'berat'])) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('status') && in_array($request->status, ['tercatat', 'diproses', 'selesai'])) {
            $query->where('status', $request->status);
        }

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $items = $query->latest('tanggal')->latest('id')->paginate($perPage);

        return response()->json([
            'success'    => true,
            'message'    => 'Daftar catatan pelanggaran siswa berhasil diambil.',
            'summary'    => $summary,
            'data'       => $items->getCollection()->map(fn($p) => $this->formatPelanggaran($p))->values(),
            'pagination' => $this->formatPagination($items),
        ]);
    }

    /**
     * Detail satu catatan pelanggaran.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $pelanggaran = Pelanggaran::with(['student.kelas', 'kategoriPelanggaran', 'recorder'])->find($id);

        if (!$pelanggaran) {
            return response()->json([
                'success' => false,
                'message' => 'Data catatan pelanggaran tidak ditemukan.',
            ], 404);
        }

        if (!$this->canViewPelanggaran($user, $pelanggaran)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melihat catatan pelanggaran ini.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail catatan pelanggaran berhasil diambil.',
            'data'    => $this->formatPelanggaran($pelanggaran),
        ]);
    }

    /**
     * Daftar master kategori pelanggaran aktif di sekolah.
     */
    public function kategoriList(Request $request)
    {
        $user = $request->user();
        $schoolId = $user->school_id;

        $items = KategoriPelanggaran::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('kategori')
            ->orderBy('nama')
            ->get()
            ->map(fn($k) => [
                'id'             => $k->id,
                'kode'           => $k->kode,
                'nama'           => $k->nama,
                'kategori'       => $k->kategori,
                'kategori_label' => $k->kategori_label,
                'poin'           => $k->poin,
                'sanksi_default' => $k->sanksi_default,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $items->values(),
        ]);
    }

    /**
     * Daftar catatan pelanggaran siswa untuk akun guru / wali kelas / admin.
     */
    public function teacherIndex(Request $request)
    {
        $user = $request->user();
        if (!$this->isStaffRole($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Endpoint ini khusus untuk akun guru/wali kelas/admin.',
            ], 403);
        }

        $schoolId = $user->school_id;
        $isFullSchool = $user->isAdmin() || $user->isWakaKurikulum();
        $managedKelasIds = $isFullSchool ? [] : $user->getManagedKelasIds();

        $baseQuery = Pelanggaran::with(['student.kelas', 'kategoriPelanggaran', 'recorder'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId));

        if (!$isFullSchool) {
            $baseQuery->where(function ($q) use ($user, $managedKelasIds) {
                $q->where('recorded_by', $user->id);
                if (!empty($managedKelasIds)) {
                    $q->orWhereHas('student', fn($sq) => $sq->whereIn('kelas_id', $managedKelasIds));
                }
            });
        }

        $summary = [
            'total_kasus' => (clone $baseQuery)->count(),
            'total_poin'  => (int) (clone $baseQuery)->sum('poin'),
            'total_today' => (clone $baseQuery)->whereDate('tanggal', today())->count(),
            'ringan'      => (clone $baseQuery)->where('kategori', 'ringan')->count(),
            'sedang'      => (clone $baseQuery)->where('kategori', 'sedang')->count(),
            'berat'       => (clone $baseQuery)->where('kategori', 'berat')->count(),
            'tercatat'    => (clone $baseQuery)->where('status', 'tercatat')->count(),
            'diproses'    => (clone $baseQuery)->where('status', 'diproses')->count(),
            'selesai'     => (clone $baseQuery)->where('status', 'selesai')->count(),
        ];

        $query = clone $baseQuery;

        if ($request->filled('kategori') && in_array($request->kategori, ['ringan', 'sedang', 'berat'])) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('status') && in_array($request->status, ['tercatat', 'diproses', 'selesai'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kelas_id')) {
            $query->whereHas('student', fn($q) => $q->where('kelas_id', (int) $request->kelas_id));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', (int) $request->student_id);
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

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $items = $query->latest('tanggal')->latest('id')->paginate($perPage);

        $kelases = Kelas::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when(!$isFullSchool && !empty($managedKelasIds), fn($q) => $q->whereIn('id', $managedKelasIds))
            ->where('is_active_attendance', true)
            ->orderBy('nama_kelas')
            ->get(['id', 'nama_kelas', 'tingkat']);

        return response()->json([
            'success'    => true,
            'message'    => 'Daftar catatan pelanggaran berhasil diambil.',
            'summary'    => $summary,
            'kelases'    => $kelases,
            'data'       => $items->getCollection()->map(fn($p) => $this->formatPelanggaran($p))->values(),
            'pagination' => $this->formatPagination($items),
        ]);
    }

    /**
     * Daftar siswa di sekolah/kelas untuk dipilih guru saat mencatat pelanggaran.
     */
    public function teacherStudents(Request $request)
    {
        $user = $request->user();
        if (!$this->isStaffRole($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Endpoint ini khusus untuk akun guru/wali kelas/admin.',
            ], 403);
        }

        $query = Siswa::with('kelas:id,nama_kelas')
            ->when($user->school_id, fn($q) => $q->where('school_id', $user->school_id));

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

        $students = $query->orderBy('nama')
            ->when($request->filled('limit'), fn($q) => $q->limit(max(1, (int) $request->limit)))
            ->get(['id', 'nama', 'nis', 'kelas_id']);

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
     * Guru mencatat pelanggaran siswa baru via mobile app.
     */
    public function teacherStore(Request $request)
    {
        $user = $request->user();
        if (!$this->isStaffRole($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Endpoint ini khusus untuk akun guru/wali kelas/admin.',
            ], 403);
        }

        $validated = $request->validate([
            'student_id'              => 'required|exists:siswa,id',
            'kategori_pelanggaran_id' => 'nullable|exists:kategori_pelanggarans,id',
            'tanggal'                 => 'nullable|date',
            'waktu'                   => 'nullable|string|max:10',
            'judul_pelanggaran'       => 'required_without:kategori_pelanggaran_id|nullable|string|max:200',
            'kategori'                => 'required_without:kategori_pelanggaran_id|nullable|in:ringan,sedang,berat',
            'poin'                    => 'nullable|integer|min:0|max:1000',
            'keterangan'              => 'nullable|string|max:2000',
            'tindak_lanjut'           => 'nullable|string|max:1000',
            'status'                  => 'nullable|in:tercatat,diproses,selesai',
            'bukti_foto'              => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'foto'                    => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        $student = Siswa::with('kelas')->findOrFail($validated['student_id']);
        if ($user->school_id && (int) $student->school_id !== (int) $user->school_id) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa bukan berasal dari sekolah Anda.',
            ], 403);
        }

        $master = !empty($validated['kategori_pelanggaran_id'])
            ? KategoriPelanggaran::find($validated['kategori_pelanggaran_id'])
            : null;

        if ($master && $user->school_id && (int) $master->school_id !== (int) $user->school_id) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori pelanggaran bukan milik sekolah Anda.',
            ], 403);
        }

        $file = $request->file('bukti_foto') ?? $request->file('foto');
        $filePath = $file ? $file->store('pelanggaran', 'public') : null;

        $pelanggaran = Pelanggaran::create([
            'school_id'               => $student->school_id,
            'student_id'              => $student->id,
            'kategori_pelanggaran_id' => $master?->id,
            'recorded_by'             => $user->id,
            'code'                    => Pelanggaran::generateCode(),
            'tanggal'                 => !empty($validated['tanggal'])
                ? Carbon::parse($validated['tanggal'])->format('Y-m-d')
                : now()->format('Y-m-d'),
            'waktu'                   => $validated['waktu'] ?? now()->format('H:i'),
            'judul_pelanggaran'       => ($validated['judul_pelanggaran'] ?? null) ?: ($master?->nama ?? 'Pelanggaran Tata Tertib'),
            'kategori'                => ($validated['kategori'] ?? null) ?: ($master?->kategori ?? 'ringan'),
            'poin'                    => $validated['poin'] ?? ($master?->poin ?? 5),
            'keterangan'              => $validated['keterangan'] ?? null,
            'tindak_lanjut'           => $validated['tindak_lanjut'] ?? $master?->sanksi_default,
            'bukti_foto'              => $filePath,
            'status'                  => $validated['status'] ?? 'tercatat',
        ]);

        $pelanggaran->load(['student.kelas', 'kategoriPelanggaran', 'recorder']);

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
            Log::warning("FCM violation notification failed (mobile): " . $e->getMessage());
        }

        $this->logMobile(
            $pelanggaran->school_id,
            'mobile_pelanggaran_store',
            $student->nis,
            true,
            "Pelanggaran {$pelanggaran->code} dicatat oleh {$user->full_name} untuk {$student->nama}",
            $request
        );

        return response()->json([
            'success' => true,
            'message' => "Catatan pelanggaran {$pelanggaran->code} berhasil disimpan.",
            'data'    => $this->formatPelanggaran($pelanggaran),
        ], 201);
    }

    /**
     * Guru memperbarui status atau tindak lanjut catatan pelanggaran.
     */
    public function teacherUpdate(Request $request, $id)
    {
        $user = $request->user();
        $pelanggaran = Pelanggaran::with(['student.kelas', 'kategoriPelanggaran', 'recorder'])->find($id);

        if (!$pelanggaran) {
            return response()->json([
                'success' => false,
                'message' => 'Data catatan pelanggaran tidak ditemukan.',
            ], 404);
        }

        if (!$this->canManagePelanggaran($user, $pelanggaran)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk mengubah catatan pelanggaran ini.',
            ], 403);
        }

        $validated = $request->validate([
            'status'        => 'required|in:tercatat,diproses,selesai',
            'tindak_lanjut' => 'nullable|string|max:1000',
            'keterangan'    => 'nullable|string|max:2000',
        ]);

        $pelanggaran->update([
            'status'        => $validated['status'],
            'tindak_lanjut' => array_key_exists('tindak_lanjut', $validated) ? $validated['tindak_lanjut'] : $pelanggaran->tindak_lanjut,
            'keterangan'    => array_key_exists('keterangan', $validated) ? $validated['keterangan'] : $pelanggaran->keterangan,
        ]);

        $pelanggaran->refresh()->load(['student.kelas', 'kategoriPelanggaran', 'recorder']);

        $this->logMobile(
            $pelanggaran->school_id,
            'mobile_pelanggaran_update',
            $pelanggaran->student?->nis ?? $pelanggaran->code,
            true,
            "Status pelanggaran {$pelanggaran->code} diubah ke {$pelanggaran->status} oleh {$user->full_name}",
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Catatan pelanggaran berhasil diperbarui.',
            'data'    => $this->formatPelanggaran($pelanggaran),
        ]);
    }

    /**
     * Guru / admin menghapus catatan pelanggaran.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $pelanggaran = Pelanggaran::with('student')->find($id);

        if (!$pelanggaran) {
            return response()->json([
                'success' => false,
                'message' => 'Data catatan pelanggaran tidak ditemukan.',
            ], 404);
        }

        if (!$this->canManagePelanggaran($user, $pelanggaran)) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menghapus catatan pelanggaran ini.',
            ], 403);
        }

        if ($pelanggaran->bukti_foto && !str_starts_with($pelanggaran->bukti_foto, 'http')) {
            if (Storage::disk('public')->exists($pelanggaran->bukti_foto)) {
                Storage::disk('public')->delete($pelanggaran->bukti_foto);
            }
        }

        $code = $pelanggaran->code;
        $schoolId = $pelanggaran->school_id;
        $pelanggaran->delete();

        $this->logMobile(
            $schoolId,
            'mobile_pelanggaran_delete',
            $user->username ?? (string) $user->id,
            true,
            "Catatan pelanggaran {$code} dihapus",
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Catatan pelanggaran berhasil dihapus.',
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

    private function canViewPelanggaran(User $user, Pelanggaran $pelanggaran): bool
    {
        $siswa = $this->resolveStudent($user);
        if ($siswa && (int) $pelanggaran->student_id === (int) $siswa->id) {
            return true;
        }

        return $this->canManagePelanggaran($user, $pelanggaran);
    }

    private function canManagePelanggaran(User $user, Pelanggaran $pelanggaran): bool
    {
        if (!$this->isStaffRole($user)) {
            return false;
        }

        if ($user->school_id && (int) $pelanggaran->school_id !== (int) $user->school_id) {
            return false;
        }

        if ($user->isAdmin() || $user->isWakaKurikulum()) {
            return true;
        }

        if ((int) $pelanggaran->recorded_by === (int) $user->id) {
            return true;
        }

        $managedKelasIds = $user->getManagedKelasIds();
        $kelasId = $pelanggaran->student?->kelas_id;
        return $kelasId && in_array($kelasId, $managedKelasIds);
    }

    private function formatPelanggaran(Pelanggaran $p): array
    {
        return [
            'id'                      => $p->id,
            'code'                    => $p->code,
            'school_id'               => $p->school_id,
            'student_id'              => $p->student_id,
            'student'                 => $p->student ? [
                'id'         => $p->student->id,
                'nama'       => $p->student->nama,
                'nis'        => $p->student->nis,
                'kelas_id'   => $p->student->kelas_id,
                'nama_kelas' => $p->student->kelas?->nama_kelas,
            ] : null,
            'kategori_pelanggaran_id' => $p->kategori_pelanggaran_id,
            'judul_pelanggaran'       => $p->judul_pelanggaran,
            'kategori'                => $p->kategori,
            'kategori_label'          => $p->kategori_label,
            'poin'                    => $p->poin,
            'tanggal'                 => $p->tanggal?->format('Y-m-d'),
            'waktu'                   => $p->waktu,
            'keterangan'              => $p->keterangan,
            'tindak_lanjut'           => $p->tindak_lanjut,
            'bukti_foto'              => $p->bukti_foto,
            'bukti_foto_url'          => $p->bukti_foto_url,
            'status'                  => $p->status,
            'status_label'            => $p->status_label,
            'recorded_by'             => $p->recorded_by,
            'recorder_name'           => $p->recorder?->full_name,
            'created_at'              => $p->created_at?->toIso8601String(),
            'updated_at'              => $p->updated_at?->toIso8601String(),
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

