<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use App\Models\KategoriPelanggaran;
use App\Models\Kelas;
use App\Models\Pelanggaran;
use App\Models\PenangananPelanggaran;
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

        $baseQuery = Pelanggaran::with(['student.kelas', 'kategoriPelanggaran', 'recorder', 'penanganans.handler'])
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
        $pelanggaran = Pelanggaran::with(['student.kelas', 'kategoriPelanggaran', 'recorder', 'penanganans.handler'])->find($id);

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

        $baseQuery = Pelanggaran::with(['student.kelas', 'kategoriPelanggaran', 'recorder', 'penanganans.handler'])
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
            'bukti_foto'              => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf,mp4,mov,avi,webm,mkv,3gp|max:51200',
            'foto'                    => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf,mp4,mov,avi,webm,mkv,3gp|max:51200',
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
        $filePath = $file
            ? app(\App\Services\R2StorageService::class)->uploadWithFallback($file, $student->school_id, 'pelanggaran', null, $user->id)
            : null;

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
        $pelanggaran = Pelanggaran::with(['student.kelas', 'kategoriPelanggaran', 'recorder', 'penanganans.handler'])->find($id);

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
     * Waka Kesiswaan / Admin menghapus catatan pelanggaran (guru biasa tidak bisa).
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$this->canDeletePelanggaran($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Waka Kesiswaan atau Admin yang dapat menghapus catatan pelanggaran.',
            ], 403);
        }

        $pelanggaran = Pelanggaran::with('student')->find($id);

        if (!$pelanggaran) {
            return response()->json([
                'success' => false,
                'message' => 'Data catatan pelanggaran tidak ditemukan.',
            ], 404);
        }

        if ($user->school_id && (int) $pelanggaran->school_id !== (int) $user->school_id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke catatan pelanggaran sekolah ini.',
            ], 403);
        }

        if ($pelanggaran->bukti_foto) {
            app(\App\Services\R2StorageService::class)->safeDelete($pelanggaran->bukti_foto);
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

    /**
     * Rekapitulasi poin & kasus pelanggaran siswa untuk akun Guru / Wali Kelas / Admin.
     */
    public function rekapStudents(Request $request)
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

        $filterPoin = $request->input('filter_poin', 'with_points'); // 'with_points' (default) or 'all'
        $startDate  = $request->input('start_date');
        $endDate    = $request->input('end_date');

        $query = Siswa::with(['kelas', 'latestPelanggaran', 'latestPenanganan'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when(!$isFullSchool, fn($q) => $q->whereIn('kelas_id', empty($managedKelasIds) ? [-1] : $managedKelasIds));

        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->where('kelas_id', (int) $request->kelas_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                    ->orWhere('nis', 'LIKE', "%{$search}%");
            });
        }

        if ($startDate && $endDate) {
            $query->withSum(['pelanggarans as total_poin' => fn($q) => $q->whereBetween('tanggal', [$startDate, $endDate])], 'poin')
                  ->withCount(['pelanggarans as total_kasus' => fn($q) => $q->whereBetween('tanggal', [$startDate, $endDate])])
                  ->withCount(['penanganans as total_penanganan' => fn($q) => $q->whereBetween('tanggal', [$startDate, $endDate])]);
        } else {
            $query->withSum('pelanggarans as total_poin', 'poin')
                  ->withCount('pelanggarans as total_kasus')
                  ->withCount('penanganans as total_penanganan');
        }

        if ($filterPoin === 'with_points') {
            $query->whereHas('pelanggarans', function ($q) use ($startDate, $endDate) {
                $q->where('poin', '>', 0);
                if ($startDate && $endDate) {
                    $q->whereBetween('tanggal', [$startDate, $endDate]);
                }
            });
        }

        // Summary Stats
        $baseStudent = Siswa::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when(!$isFullSchool, fn($q) => $q->whereIn('kelas_id', empty($managedKelasIds) ? [-1] : $managedKelasIds));

        $violationBase = Pelanggaran::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when(!$isFullSchool, fn($q) => $q->whereHas('student', fn($sq) => $sq->whereIn('kelas_id', empty($managedKelasIds) ? [-1] : $managedKelasIds)));

        $summary = [
            'total_siswa'           => (clone $baseStudent)->count(),
            'total_siswa_pelanggar' => (clone $baseStudent)->whereHas('pelanggarans', fn($q) => $q->where('poin', '>', 0))->count(),
            'total_kasus'           => (clone $violationBase)->count(),
            'total_poin'            => (int) (clone $violationBase)->sum('poin'),
            'total_penanganan'      => PenangananPelanggaran::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->when(!$isFullSchool, fn($q) => $q->whereHas('student', fn($sq) => $sq->whereIn('kelas_id', empty($managedKelasIds) ? [-1] : $managedKelasIds)))
                ->count(),
        ];

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $paginated = $query->orderByDesc('total_poin')->orderBy('nama')->paginate($perPage);

        return response()->json([
            'success'    => true,
            'message'    => 'Rekapitulasi poin pelanggaran siswa berhasil diambil.',
            'summary'    => $summary,
            'data'       => $paginated->getCollection()->map(fn($s) => $this->formatRekapStudent($s))->values(),
            'pagination' => $this->formatPagination($paginated),
        ]);
    }

    /**
     * Daftar siswa yang memiliki poin pelanggaran di rekap (siswa tanpa poin tidak ditampilkan).
     * Digunakan untuk dropdown saat guru/BK mencatat penanganan.
     */
    public function penangananStudents(Request $request)
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

        $query = Siswa::with(['kelas', 'pelanggarans' => fn($q) => $q->orderBy('tanggal', 'desc')])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when(!$isFullSchool, fn($q) => $q->whereIn('kelas_id', empty($managedKelasIds) ? [-1] : $managedKelasIds))
            ->whereHas('pelanggarans', fn($q) => $q->where('poin', '>', 0))
            ->withSum('pelanggarans as total_poin', 'poin')
            ->withCount('pelanggarans as total_kasus');

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

        $students = $query->orderByDesc('total_poin')->orderBy('nama')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar siswa berpoin untuk penanganan BK berhasil diambil.',
            'data'    => $students->map(function ($s) {
                $totalPoin = (int) ($s->total_poin ?? 0);
                $kelasName = $s->kelas?->nama_kelas ?? '-';
                return [
                    'id'           => $s->id,
                    'nama'         => $s->nama,
                    'nis'          => $s->nis,
                    'kelas_id'     => $s->kelas_id,
                    'nama_kelas'   => $kelasName,
                    'total_poin'   => $totalPoin,
                    'total_kasus'  => (int) ($s->total_kasus ?? 0),
                    'display_text' => "{$s->nama} ({$totalPoin} Poin) - {$kelasName}",
                    'violations'   => $s->pelanggarans->map(fn($v) => [
                        'id'                => $v->id,
                        'code'              => $v->code,
                        'tanggal'           => $v->tanggal?->format('Y-m-d'),
                        'judul_pelanggaran' => $v->judul_pelanggaran,
                        'poin'              => $v->poin,
                        'status'            => $v->status,
                        'status_label'      => $v->status_label,
                    ])->values(),
                ];
            })->values(),
        ]);
    }

    /**
     * Riwayat penanganan & konseling BK (SP 1 - 4, Link GDrive, Pembinaan).
     */
    public function penangananIndex(Request $request)
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

        $query = PenangananPelanggaran::with(['student.kelas', 'pelanggaran', 'handler'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when(!$isFullSchool, fn($q) => $q->whereHas('student', fn($sq) => $sq->whereIn('kelas_id', empty($managedKelasIds) ? [-1] : $managedKelasIds)));

        if ($request->filled('student_id')) {
            $query->where('student_id', (int) $request->student_id);
        }

        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->whereHas('student', fn($q) => $q->where('kelas_id', (int) $request->kelas_id));
        }

        if ($request->filled('tindakan_sp') && $request->tindakan_sp !== 'all') {
            $query->where('tindakan_sp', $request->tindakan_sp);
        }

        if ($request->filled('status_penanganan') && $request->status_penanganan !== 'all') {
            $query->where('status_penanganan', $request->status_penanganan);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('catatan_penanganan', 'LIKE', "%{$search}%")
                    ->orWhere('hasil_kesepakatan', 'LIKE', "%{$search}%")
                    ->orWhere('link_gdrive', 'LIKE', "%{$search}%")
                    ->orWhereHas('student', function ($sq) use ($search) {
                        $sq->where('nama', 'LIKE', "%{$search}%")
                            ->orWhere('nis', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('pelanggaran', function ($pq) use ($search) {
                        $pq->where('code', 'LIKE', "%{$search}%")
                            ->orWhere('judul_pelanggaran', 'LIKE', "%{$search}%");
                    });
            });
        }

        $summary = [
            'total'          => (clone $query)->count(),
            'sp_1'           => (clone $query)->where('tindakan_sp', 'sp_1')->count(),
            'sp_2'           => (clone $query)->where('tindakan_sp', 'sp_2')->count(),
            'sp_3'           => (clone $query)->where('tindakan_sp', 'sp_3')->count(),
            'sp_4'           => (clone $query)->where('tindakan_sp', 'sp_4')->count(),
            'pembinaan'      => (clone $query)->whereIn('tindakan_sp', ['pembinaan', 'teguran_lisan', 'teguran_tertulis'])->count(),
            'selesai'        => (clone $query)->where('status_penanganan', 'selesai')->count(),
            'dalam_proses'   => (clone $query)->where('status_penanganan', 'dalam_proses')->count(),
        ];

        $perPage = min(max((int) $request->input('per_page', 15), 1), 50);
        $items = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success'    => true,
            'message'    => 'Daftar riwayat penanganan BK berhasil diambil.',
            'summary'    => $summary,
            'data'       => $items->getCollection()->map(fn($pn) => $this->formatPenanganan($pn))->values(),
            'pagination' => $this->formatPagination($items),
        ]);
    }

    /**
     * Guru / Guru BK mencatat penanganan & sanksi SP via mobile app.
     */
    public function storePenanganan(Request $request)
    {
        $user = $request->user();
        if (!$this->isStaffRole($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Endpoint ini khusus untuk akun guru/wali kelas/admin.',
            ], 403);
        }

        $validated = $request->validate([
            'student_id'         => 'required|exists:siswa,id',
            'pelanggaran_id'     => 'nullable|exists:pelanggarans,id',
            'tanggal'            => 'nullable|date',
            'tindakan_sp'        => 'required|in:pembinaan,teguran_lisan,teguran_tertulis,sp_1,sp_2,sp_3,sp_4,skorsing,dikembalikan_ortu',
            'catatan_penanganan' => 'required|string|max:3000',
            'hasil_kesepakatan'  => 'nullable|string|max:2000',
            'link_gdrive'        => 'nullable|url|max:1000',
            'status_penanganan'  => 'nullable|in:dalam_proses,selesai,evaluasi_lanjutan',
            'lampiran_file'      => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:10240',
            'foto'               => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:10240',
            'update_status'      => 'nullable|in:tercatat,diproses,selesai',
        ]);

        $student = Siswa::with('kelas')->findOrFail($validated['student_id']);
        if ($user->school_id && (int) $student->school_id !== (int) $user->school_id) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa bukan berasal dari sekolah Anda.',
            ], 403);
        }

        $pelanggaran = null;
        if (!empty($validated['pelanggaran_id'])) {
            $pelanggaran = Pelanggaran::find($validated['pelanggaran_id']);
        } else {
            $pelanggaran = Pelanggaran::where('student_id', $student->id)
                ->where('status', '!=', 'selesai')
                ->latest('tanggal')
                ->first()
                ?? Pelanggaran::where('student_id', $student->id)->latest('tanggal')->first();
        }

        $file = $request->file('lampiran_file') ?? $request->file('foto');
        $lampiranPath = $file
            ? app(\App\Services\R2StorageService::class)->uploadWithFallback($file, $student->school_id ?? $user->school_id, 'penanganan_bk', null, $user->id)
            : null;

        $statusPenanganan = $validated['status_penanganan'] ?? 'dalam_proses';

        $penanganan = PenangananPelanggaran::create([
            'school_id'          => $student->school_id ?? $user->school_id,
            'pelanggaran_id'     => $pelanggaran?->id,
            'student_id'         => $student->id,
            'handled_by'         => $user->id,
            'tanggal'            => !empty($validated['tanggal']) ? Carbon::parse($validated['tanggal'])->format('Y-m-d') : now()->format('Y-m-d'),
            'tindakan_sp'        => $validated['tindakan_sp'],
            'catatan_penanganan' => $validated['catatan_penanganan'],
            'hasil_kesepakatan'  => $validated['hasil_kesepakatan'] ?? null,
            'link_gdrive'        => $validated['link_gdrive'] ?? null,
            'lampiran_file'      => $lampiranPath,
            'status_penanganan'  => $statusPenanganan,
        ]);

        $penanganan->load(['student.kelas', 'pelanggaran', 'handler']);

        $labelTindakan = $penanganan->tindakan_sp_label;

        if ($pelanggaran) {
            $newStatus = $validated['update_status'] ?? ($statusPenanganan === 'selesai' ? 'selesai' : 'diproses');
            $tindakLanjutRingkas = "[BK: {$labelTindakan}] " . \Illuminate\Support\Str::limit($validated['catatan_penanganan'], 150);
            $pelanggaran->update([
                'status'        => $newStatus,
                'tindak_lanjut' => $tindakLanjutRingkas,
            ]);
        }

        try {
            FcmNotificationService::sendToSiswa(
                $student,
                "Tindakan BK: {$labelTindakan}",
                "Telah dicatat penanganan kedisiplinan oleh Guru BK/Kesiswaan pada " . $penanganan->tanggal->format('d/m/Y'),
                [
                    'type'           => 'student_penanganan_recorded',
                    'pelanggaran_id' => (string) ($pelanggaran?->id ?? ''),
                    'tindakan_sp'    => $penanganan->tindakan_sp,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning("FCM penanganan notification failed (mobile): " . $e->getMessage());
        }

        $this->logMobile(
            $penanganan->school_id,
            'mobile_penanganan_store',
            $student->nis,
            true,
            "Penanganan BK {$labelTindakan} dicatat untuk {$student->nama}",
            $request
        );

        return response()->json([
            'success' => true,
            'message' => "Catatan penanganan BK ({$labelTindakan}) an. {$student->nama} berhasil disimpan.",
            'data'    => $this->formatPenanganan($penanganan),
        ], 201);
    }

    /**
     * Rekam jejak utuh dossier pelanggaran dan penanganan BK per siswa.
     */
    public function studentHistory(Request $request, $studentId)
    {
        $user = $request->user();
        if (!$this->isStaffRole($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $student = Siswa::with([
            'kelas',
            'pelanggarans' => fn($q) => $q->orderBy('tanggal', 'desc'),
            'penanganans'  => fn($q) => $q->with(['pelanggaran', 'handler'])->orderBy('tanggal', 'desc')
        ])->find($studentId);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan.',
            ], 404);
        }

        if ($user->school_id && (int) $student->school_id !== (int) $user->school_id) {
            return response()->json([
                'success' => false,
                'message' => 'Siswa bukan berasal dari sekolah Anda.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'student' => [
                'id'               => $student->id,
                'nama'             => $student->nama,
                'nis'              => $student->nis ?? '-',
                'kelas_id'         => $student->kelas_id,
                'nama_kelas'       => $student->kelas?->nama_kelas ?? '-',
                'total_poin'       => (int) $student->pelanggarans->sum('poin'),
                'total_kasus'      => $student->pelanggarans->count(),
                'total_penanganan' => $student->penanganans->count(),
                'display_text'     => "{$student->nama} (" . (int)$student->pelanggarans->sum('poin') . " Poin) - " . ($student->kelas?->nama_kelas ?? '-'),
            ],
            'violations'  => $student->pelanggarans->map(fn($v) => [
                'id'                => $v->id,
                'code'              => $v->code,
                'tanggal'           => $v->tanggal?->format('Y-m-d'),
                'judul_pelanggaran' => $v->judul_pelanggaran,
                'poin'              => $v->poin,
                'kategori'          => $v->kategori,
                'status'            => $v->status,
                'status_label'      => $v->status_label,
            ])->values(),
            'penanganans' => $student->penanganans->map(fn($pn) => $this->formatPenanganan($pn))->values(),
        ]);
    }

    /**
     * Hapus satu catatan penanganan BK.
     */
    public function destroyPenanganan(Request $request, $id)
    {
        $user = $request->user();
        if (!$this->isStaffRole($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $penanganan = PenangananPelanggaran::with('student')->find($id);
        if (!$penanganan) {
            return response()->json([
                'success' => false,
                'message' => 'Data penanganan BK tidak ditemukan.',
            ], 404);
        }

        if ($user->school_id && (int) $penanganan->school_id !== (int) $user->school_id) {
            return response()->json([
                'success' => false,
                'message' => 'Akses tidak diizinkan.',
            ], 403);
        }

        if ($penanganan->lampiran_file) {
            app(\App\Services\R2StorageService::class)->safeDelete($penanganan->lampiran_file);
        }

        $penanganan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Catatan penanganan BK berhasil dihapus.',
        ]);
    }

    /**
     * Siswa melihat catatan penanganan/SP milik diri sendiri.
     */
    public function myPenanganans(Request $request)
    {
        $user = $request->user();
        $siswa = $this->resolveStudent($user);

        if (!$siswa) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda tidak terhubung dengan data siswa.',
            ], 403);
        }

        $items = PenangananPelanggaran::with(['pelanggaran', 'handler'])
            ->where('school_id', $siswa->school_id)
            ->where('student_id', $siswa->id)
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat penanganan BK siswa berhasil diambil.',
            'summary' => [
                'total_penanganan' => $items->count(),
                'sp_aktif'         => $items->firstWhere('status_penanganan', 'dalam_proses')?->tindakan_sp_label ?? 'Tidak Ada',
            ],
            'data'    => $items->map(fn($pn) => $this->formatPenanganan($pn))->values(),
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

    private function canDeletePelanggaran(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $role = strtolower((string) ($user->role ?? ''));
        if (str_contains($role, 'kesiswaan') || str_contains($role, 'kurikulum') || str_contains($role, 'waka')) {
            return true;
        }

        return false;
    }

    private function formatPelanggaran(Pelanggaran $p): array
    {
        $latestPen = $p->latestPenanganan ?? ($p->relationLoaded('penanganans') ? $p->penanganans->first() : null);

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
            'is_video'                => (bool) $p->is_video,
            'status'                  => $p->status,
            'status_label'            => $p->status_label,
            'recorded_by'             => $p->recorded_by,
            'recorder_name'           => $p->recorder?->full_name,
            'penanganans'             => $p->relationLoaded('penanganans') ? $p->penanganans->map(fn($pn) => $this->formatPenanganan($pn))->values() : [],
            'latest_penanganan'       => $latestPen ? [
                'id'                      => $latestPen->id,
                'tanggal'                 => $latestPen->tanggal?->format('Y-m-d'),
                'tindakan_sp'             => $latestPen->tindakan_sp,
                'tindakan_sp_label'       => $latestPen->tindakan_sp_label,
                'catatan_penanganan'      => $latestPen->catatan_penanganan,
                'hasil_kesepakatan'       => $latestPen->hasil_kesepakatan,
                'link_gdrive'             => $latestPen->link_gdrive,
                'lampiran_file_url'       => $latestPen->lampiran_file_url,
                'status_penanganan'       => $latestPen->status_penanganan,
                'status_penanganan_label' => $latestPen->status_penanganan_label,
                'handler_name'            => $latestPen->handler?->full_name,
            ] : null,
            'created_at'              => $p->created_at?->toIso8601String(),
            'updated_at'              => $p->updated_at?->toIso8601String(),
        ];
    }

    private function formatPenanganan(PenangananPelanggaran $pn): array
    {
        return [
            'id'                      => $pn->id,
            'school_id'               => $pn->school_id,
            'student_id'              => $pn->student_id,
            'student'                 => $pn->student ? [
                'id'         => $pn->student->id,
                'nama'       => $pn->student->nama,
                'nis'        => $pn->student->nis,
                'kelas_id'   => $pn->student->kelas_id,
                'nama_kelas' => $pn->student->kelas?->nama_kelas,
            ] : null,
            'pelanggaran_id'          => $pn->pelanggaran_id,
            'kasus'                   => $pn->pelanggaran ? [
                'id'                => $pn->pelanggaran->id,
                'code'              => $pn->pelanggaran->code,
                'judul_pelanggaran' => $pn->pelanggaran->judul_pelanggaran,
                'poin'              => $pn->pelanggaran->poin,
            ] : null,
            'tanggal'                 => $pn->tanggal?->format('Y-m-d'),
            'tindakan_sp'             => $pn->tindakan_sp,
            'tindakan_sp_label'       => $pn->tindakan_sp_label,
            'catatan_penanganan'      => $pn->catatan_penanganan,
            'hasil_kesepakatan'       => $pn->hasil_kesepakatan,
            'link_gdrive'             => $pn->link_gdrive,
            'lampiran_file'           => $pn->lampiran_file,
            'lampiran_file_url'       => $pn->lampiran_file_url,
            'status_penanganan'       => $pn->status_penanganan,
            'status_penanganan_label' => $pn->status_penanganan_label,
            'handled_by'              => $pn->handled_by,
            'handler_name'            => $pn->handler?->full_name ?? $pn->handler?->username ?? 'Guru BK',
            'created_at'              => $pn->created_at?->toIso8601String(),
        ];
    }

    private function formatRekapStudent(Siswa $s): array
    {
        $totalPoin = (int) ($s->total_poin ?? 0);
        $kelasName = $s->kelas?->nama_kelas ?? '-';
        return [
            'id'                 => $s->id,
            'nama'               => $s->nama,
            'nis'                => $s->nis,
            'kelas_id'           => $s->kelas_id,
            'nama_kelas'         => $kelasName,
            'foto_url'           => $s->foto_url,
            'total_poin'         => $totalPoin,
            'total_kasus'        => (int) ($s->total_kasus ?? 0),
            'total_penanganan'   => (int) ($s->total_penanganan ?? 0),
            'display_text'       => "{$s->nama} ({$totalPoin} Poin) - {$kelasName}",
            'latest_pelanggaran' => $s->latestPelanggaran ? [
                'id'                => $s->latestPelanggaran->id,
                'code'              => $s->latestPelanggaran->code,
                'tanggal'           => $s->latestPelanggaran->tanggal?->format('Y-m-d'),
                'judul_pelanggaran' => $s->latestPelanggaran->judul_pelanggaran,
                'poin'              => $s->latestPelanggaran->poin,
                'kategori'          => $s->latestPelanggaran->kategori,
                'kategori_label'    => $s->latestPelanggaran->kategori_label,
            ] : null,
            'latest_penanganan'  => $s->latestPenanganan ? [
                'id'                => $s->latestPenanganan->id,
                'tanggal'           => $s->latestPenanganan->tanggal?->format('Y-m-d'),
                'tindakan_sp'       => $s->latestPenanganan->tindakan_sp,
                'tindakan_sp_label' => $s->latestPenanganan->tindakan_sp_label,
                'link_gdrive'       => $s->latestPenanganan->link_gdrive,
                'status_penanganan' => $s->latestPenanganan->status_penanganan,
            ] : null,
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

