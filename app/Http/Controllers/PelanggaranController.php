<?php

namespace App\Http\Controllers;

use App\Models\KategoriPelanggaran;
use App\Models\Kelas;
use App\Models\Pelanggaran;
use App\Models\PenangananPelanggaran;
use App\Models\Siswa;
use App\Models\School;
use App\Services\FcmNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Barryvdh\DomPDF\Facade\Pdf;

class PelanggaranController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        $query = Pelanggaran::with(['student.kelas', 'kategoriPelanggaran', 'recorder', 'penanganans.handler'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId));

        $isWaliKelas = $user->isWaliKelas() && $user->guru;
        $waliKelasIds = $isWaliKelas ? $user->getManagedKelasIds() : [];

        if ($isWaliKelas) {
            $query->whereHas('student', function ($q) use ($waliKelasIds) {
                $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds);
            });
        }

        $statsQuery = clone $query;
        $totalAll    = (clone $statsQuery)->count();
        $totalRingan = (clone $statsQuery)->where('kategori', 'ringan')->count();
        $totalSedang = (clone $statsQuery)->where('kategori', 'sedang')->count();
        $totalBerat  = (clone $statsQuery)->where('kategori', 'berat')->count();
        $totalToday  = (clone $statsQuery)->whereDate('tanggal', today())->count();
        $totalPoin   = (int) (clone $statsQuery)->sum('poin');

        if ($request->filled('kategori') && in_array($request->kategori, ['ringan', 'sedang', 'berat'])) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('status') && in_array($request->status, ['tercatat', 'diproses', 'selesai'])) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kelas_id')) {
            $query->whereHas('student', fn($q) => $q->where('kelas_id', $request->kelas_id));
        }

        $selectedStudent = null;
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
            $selectedStudent = Siswa::with(['kelas', 'pelanggarans'])->find($request->student_id);
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

        $pelanggarans = $query->latest('tanggal')->latest('id')->paginate(15)->withQueryString();

        $kelasList = Kelas::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
            ->where('is_active_attendance', true)
            ->orderBy('nama_kelas')
            ->get();

        $siswaList = Siswa::with('kelas:id,nama_kelas')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
            ->orderBy('nama')
            ->get(['id', 'nama', 'nis', 'kelas_id']);

        $kategoriList = KategoriPelanggaran::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('kategori')
            ->orderBy('nama')
            ->get();

        return view('pelanggaran.index', compact(
            'pelanggarans',
            'kelasList',
            'siswaList',
            'kategoriList',
            'selectedStudent',
            'totalAll',
            'totalRingan',
            'totalSedang',
            'totalBerat',
            'totalToday',
            'totalPoin'
        ));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        $validated = $request->validate([
            'student_id'              => 'required|exists:siswa,id',
            'kategori_pelanggaran_id' => 'nullable|exists:kategori_pelanggarans,id',
            'tanggal'                 => 'required|date',
            'waktu'                   => 'nullable|string|max:10',
            'judul_pelanggaran'       => 'required_without:kategori_pelanggaran_id|nullable|string|max:200',
            'kategori'                => 'required_without:kategori_pelanggaran_id|nullable|in:ringan,sedang,berat',
            'poin'                    => 'nullable|integer|min:0|max:1000',
            'keterangan'              => 'nullable|string|max:2000',
            'tindak_lanjut'           => 'nullable|string|max:1000',
            'status'                  => 'nullable|in:tercatat,diproses,selesai',
            'bukti_foto'              => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf,mp4,mov,avi,webm,mkv,3gp|max:51200',
        ]);

        $student = Siswa::findOrFail($validated['student_id']);
        if ($schoolId && (int) $student->school_id !== (int) $schoolId) {
            abort(403, 'Siswa bukan berasal dari sekolah Anda.');
        }

        $master = !empty($validated['kategori_pelanggaran_id'])
            ? KategoriPelanggaran::find($validated['kategori_pelanggaran_id'])
            : null;

        $judul = ($validated['judul_pelanggaran'] ?? null) ?: ($master?->nama ?? 'Pelanggaran Tata Tertib');
        $kategori = ($validated['kategori'] ?? null) ?: ($master?->kategori ?? 'ringan');
        $poin = $validated['poin'] ?? ($master?->poin ?? 5);
        $tindakLanjut = $validated['tindak_lanjut'] ?? $master?->sanksi_default;

        $filePath = $request->hasFile('bukti_foto')
            ? app(\App\Services\R2StorageService::class)->uploadWithFallback($request->file('bukti_foto'), $student->school_id, 'pelanggaran', null, $user->id)
            : null;

        $pelanggaran = Pelanggaran::create([
            'school_id'               => $student->school_id,
            'student_id'              => $student->id,
            'kategori_pelanggaran_id' => $master?->id,
            'recorded_by'             => $user->id,
            'code'                    => Pelanggaran::generateCode(),
            'tanggal'                 => Carbon::parse($validated['tanggal'])->format('Y-m-d'),
            'waktu'                   => $validated['waktu'] ?? now()->format('H:i'),
            'judul_pelanggaran'       => $judul,
            'kategori'                => $kategori,
            'poin'                    => $poin,
            'keterangan'              => $validated['keterangan'] ?? null,
            'tindak_lanjut'           => $tindakLanjut,
            'bukti_foto'              => $filePath,
            'status'                  => $validated['status'] ?? 'tercatat',
        ]);

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
            Log::warning("FCM violation notification failed: " . $e->getMessage());
        }

        return back()->with('success', "Catatan pelanggaran {$pelanggaran->code} an. {$student->nama} berhasil disimpan.");
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $pelanggaran = Pelanggaran::findOrFail($id);

        if ($user->school_id && (int) $pelanggaran->school_id !== (int) $user->school_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $validated = $request->validate([
            'status'        => 'required|in:tercatat,diproses,selesai',
            'tindak_lanjut' => 'nullable|string|max:1000',
            'keterangan'    => 'nullable|string|max:2000',
        ]);

        $pelanggaran->update([
            'status'        => $validated['status'],
            'tindak_lanjut' => $validated['tindak_lanjut'] ?? $pelanggaran->tindak_lanjut,
            'keterangan'    => array_key_exists('keterangan', $validated) ? $validated['keterangan'] : $pelanggaran->keterangan,
        ]);

        return back()->with('success', "Status pelanggaran {$pelanggaran->code} berhasil diperbarui.");
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $pelanggaran = Pelanggaran::findOrFail($id);

        if ($user->school_id && (int) $pelanggaran->school_id !== (int) $user->school_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if ($pelanggaran->bukti_foto) {
            app(\App\Services\R2StorageService::class)->safeDelete($pelanggaran->bukti_foto);
        }

        $pelanggaran->delete();

        return back()->with('success', 'Catatan pelanggaran berhasil dihapus.');
    }

    public function storeKategori(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id ?? 1;

        $validated = $request->validate([
            'kode'           => 'nullable|string|max:30',
            'nama'           => 'required|string|max:150',
            'kategori'       => 'required|in:ringan,sedang,berat',
            'poin'           => 'required|integer|min:1|max:1000',
            'sanksi_default' => 'nullable|string|max:500',
        ]);

        KategoriPelanggaran::create([
            'school_id'      => $schoolId,
            'kode'           => $validated['kode'] ?? null,
            'nama'           => $validated['nama'],
            'kategori'       => $validated['kategori'],
            'poin'           => $validated['poin'],
            'sanksi_default' => $validated['sanksi_default'] ?? null,
            'is_active'      => true,
        ]);

        return back()->with('success', 'Master kategori pelanggaran berhasil ditambahkan.');
    }

    public function destroyKategori($id)
    {
        $user = auth()->user();
        $kategori = KategoriPelanggaran::findOrFail($id);

        if ($user->school_id && (int) $kategori->school_id !== (int) $user->school_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $kategori->delete();

        return back()->with('success', 'Kategori pelanggaran berhasil dihapus.');
    }

    /**
     * Catat Tindakan / Penanganan BK (SP 1, 2, 3, 4, pembinaan, link gdrive, dll)
     */
    public function storePenanganan(Request $request, $id)
    {
        $user = auth()->user();
        $pelanggaran = Pelanggaran::with('student')->findOrFail($id);

        if ($user->school_id && (int) $pelanggaran->school_id !== (int) $user->school_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        $validated = $request->validate([
            'tanggal'            => 'required|date',
            'tindakan_sp'        => 'required|in:pembinaan,teguran_lisan,teguran_tertulis,sp_1,sp_2,sp_3,sp_4,skorsing,dikembalikan_ortu',
            'catatan_penanganan' => 'required|string|max:3000',
            'hasil_kesepakatan'  => 'nullable|string|max:2000',
            'link_gdrive'        => 'nullable|url|max:1000',
            'status_penanganan'  => 'required|in:dalam_proses,selesai,evaluasi_lanjutan',
            'lampiran_file'      => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:10240',
            'update_status'      => 'nullable|in:tercatat,diproses,selesai',
        ]);

        $lampiranPath = $request->hasFile('lampiran_file')
            ? app(\App\Services\R2StorageService::class)->uploadWithFallback($request->file('lampiran_file'), $pelanggaran->school_id, 'penanganan_bk', null, $user->id)
            : null;

        $penanganan = PenangananPelanggaran::create([
            'school_id'          => $pelanggaran->school_id,
            'pelanggaran_id'     => $pelanggaran->id,
            'student_id'         => $pelanggaran->student_id,
            'handled_by'         => $user->id,
            'tanggal'            => Carbon::parse($validated['tanggal'])->format('Y-m-d'),
            'tindakan_sp'        => $validated['tindakan_sp'],
            'catatan_penanganan' => $validated['catatan_penanganan'],
            'hasil_kesepakatan'  => $validated['hasil_kesepakatan'] ?? null,
            'link_gdrive'        => $validated['link_gdrive'] ?? null,
            'lampiran_file'      => $lampiranPath,
            'status_penanganan'  => $validated['status_penanganan'],
        ]);

        // Sinkronkan status kasus pelanggaran & ringkasan tindak lanjut
        $newStatus = $validated['update_status'] ?? ($validated['status_penanganan'] === 'selesai' ? 'selesai' : 'diproses');
        $labelTindakan = $penanganan->tindakan_sp_label;
        $tindakLanjutRingkas = "[BK: {$labelTindakan}] " . \Illuminate\Support\Str::limit($validated['catatan_penanganan'], 150);

        $pelanggaran->update([
            'status'        => $newStatus,
            'tindak_lanjut' => $tindakLanjutRingkas,
        ]);

        // Kirim notifikasi via FCM jika akun siswa terhubung
        try {
            if ($pelanggaran->student) {
                FcmNotificationService::sendToSiswa(
                    $pelanggaran->student,
                    "Tindakan BK: {$labelTindakan}",
                    "Telah dicatat penanganan kasus ({$pelanggaran->judul_pelanggaran}) oleh Guru BK/Kesiswaan pada " . $penanganan->tanggal->format('d/m/Y'),
                    [
                        'type'           => 'student_penanganan_recorded',
                        'pelanggaran_id' => (string) $pelanggaran->id,
                        'tindakan_sp'    => $penanganan->tindakan_sp,
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::warning("FCM penanganan notification failed: " . $e->getMessage());
        }

        return back()->with('success', "Catatan penanganan BK ({$labelTindakan}) untuk kasus {$pelanggaran->code} an. {$pelanggaran->student?->nama} berhasil disimpan.");
    }

    /**
     * Catat Tindakan / Penanganan BK langsung memilih Siswa (dari rekap poin)
     */
    public function storePenangananDirect(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        $validated = $request->validate([
            'student_id'         => 'required|exists:siswa,id',
            'pelanggaran_id'     => 'nullable|exists:pelanggarans,id',
            'tanggal'            => 'required|date',
            'tindakan_sp'        => 'required|in:pembinaan,teguran_lisan,teguran_tertulis,sp_1,sp_2,sp_3,sp_4,skorsing,dikembalikan_ortu',
            'catatan_penanganan' => 'required|string|max:3000',
            'hasil_kesepakatan'  => 'nullable|string|max:2000',
            'link_gdrive'        => 'nullable|url|max:1000',
            'status_penanganan'  => 'required|in:dalam_proses,selesai,evaluasi_lanjutan',
            'lampiran_file'      => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx|max:10240',
            'update_status'      => 'nullable|in:tercatat,diproses,selesai',
        ]);

        $student = Siswa::with('kelas')->findOrFail($validated['student_id']);
        if ($schoolId && (int) $student->school_id !== (int) $schoolId) {
            abort(403, 'Siswa bukan berasal dari sekolah Anda.');
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

        $lampiranPath = $request->hasFile('lampiran_file')
            ? app(\App\Services\R2StorageService::class)->uploadWithFallback($request->file('lampiran_file'), $pelanggaran->school_id, 'penanganan_bk', null, $user->id)
            : null;

        $penanganan = PenangananPelanggaran::create([
            'school_id'          => $student->school_id ?? $schoolId,
            'pelanggaran_id'     => $pelanggaran?->id,
            'student_id'         => $student->id,
            'handled_by'         => $user->id,
            'tanggal'            => Carbon::parse($validated['tanggal'])->format('Y-m-d'),
            'tindakan_sp'        => $validated['tindakan_sp'],
            'catatan_penanganan' => $validated['catatan_penanganan'],
            'hasil_kesepakatan'  => $validated['hasil_kesepakatan'] ?? null,
            'link_gdrive'        => $validated['link_gdrive'] ?? null,
            'lampiran_file'      => $lampiranPath,
            'status_penanganan'  => $validated['status_penanganan'],
        ]);

        $labelTindakan = $penanganan->tindakan_sp_label;

        if ($pelanggaran) {
            $newStatus = $validated['update_status'] ?? ($validated['status_penanganan'] === 'selesai' ? 'selesai' : 'diproses');
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
            Log::warning("FCM penanganan notification failed: " . $e->getMessage());
        }

        return back()->with('success', "Catatan penanganan BK ({$labelTindakan}) an. {$student->nama} berhasil disimpan.");
    }

    /**
     * Hapus satu riwayat penanganan BK
     */
    public function destroyPenanganan($id)
    {
        $user = auth()->user();
        $penanganan = PenangananPelanggaran::with('pelanggaran')->findOrFail($id);

        if ($user->school_id && (int) $penanganan->school_id !== (int) $user->school_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        if ($penanganan->lampiran_file) {
            app(\App\Services\R2StorageService::class)->safeDelete($penanganan->lampiran_file);
        }

        $penanganan->delete();

        return back()->with('success', 'Catatan penanganan BK berhasil dihapus.');
    }

    /**
     * Halaman Khusus Riwayat Penanganan & Konseling BK (SP 1, 2, 3, 4, GDrive, Pembinaan)
     */
    public function penangananIndex(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');

        $isWaliKelas = $user->isWaliKelas() && $user->guru;
        $waliKelasIds = $isWaliKelas ? $user->getManagedKelasIds() : [];

        $query = PenangananPelanggaran::with(['student.kelas', 'pelanggaran', 'handler'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId));

        if ($startDate && $endDate) {
            $query->whereBetween('tanggal', [$startDate, $endDate]);
        }

        if ($isWaliKelas) {
            $query->whereHas('student', function ($q) use ($waliKelasIds) {
                $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds);
            });
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->whereHas('student', fn($q) => $q->where('kelas_id', $request->kelas_id));
        }

        if ($request->filled('tindakan_sp') && $request->tindakan_sp !== 'all') {
            $query->where('tindakan_sp', $request->tindakan_sp);
        }

        if ($request->filled('status_penanganan') && $request->status_penanganan !== 'all') {
            $query->where('status_penanganan', $request->status_penanganan);
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
                    })
                    ->orWhereHas('handler', function ($hq) use ($search) {
                        $hq->where('full_name', 'LIKE', "%{$search}%")
                            ->orWhere('username', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Summary Stats
        $totalPenanganan  = (clone $query)->count();
        $totalSp1         = (clone $query)->where('tindakan_sp', 'sp_1')->count();
        $totalSp2         = (clone $query)->where('tindakan_sp', 'sp_2')->count();
        $totalSp3         = (clone $query)->where('tindakan_sp', 'sp_3')->count();
        $totalSp4         = (clone $query)->where('tindakan_sp', 'sp_4')->count();
        $totalPembinaan   = (clone $query)->whereIn('tindakan_sp', ['pembinaan', 'teguran_lisan', 'teguran_tertulis'])->count();
        $totalSelesai     = (clone $query)->where('status_penanganan', 'selesai')->count();
        $totalDalamProses = (clone $query)->where('status_penanganan', 'dalam_proses')->count();

        $items = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();

        // Kelas list for filter
        $classesQuery = Kelas::when($schoolId, fn($q) => $q->where('school_id', $schoolId))->orderBy('nama_kelas');
        if ($isWaliKelas) {
            $classesQuery->whereIn('id', empty($waliKelasIds) ? [-1] : $waliKelasIds);
        }
        $classes = $classesQuery->get();

        // Siswa dari rekap yang memiliki poin pelanggaran (yang tidak punya poin tidak disertakan)
        $studentsWithPoints = Siswa::with(['kelas', 'pelanggarans' => fn($q) => $q->orderBy('tanggal', 'desc')])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
            ->whereHas('pelanggarans', fn($q) => $q->where('poin', '>', 0))
            ->withSum('pelanggarans as total_poin', 'poin')
            ->withCount('pelanggarans as total_kasus')
            ->orderByDesc('total_poin')
            ->orderBy('nama')
            ->get();

        $studentsMap = $studentsWithPoints->mapWithKeys(fn($s) => [
            $s->id => [
                'id' => $s->id,
                'nama' => $s->nama,
                'total_poin' => (int) $s->total_poin,
                'kelas' => $s->kelas?->nama_kelas ?? '-',
                'violations' => $s->pelanggarans->map(fn($v) => [
                    'id' => $v->id,
                    'code' => $v->code,
                    'tanggal' => $v->tanggal?->format('d/m/Y') ?? '-',
                    'judul_pelanggaran' => $v->judul_pelanggaran,
                    'poin' => $v->poin,
                    'status' => ucfirst($v->status),
                ])->values()->all(),
            ],
        ]);

        // Pelanggaran list that can be handled (for create handling modal directly from this page)
        $unhandledViolations = Pelanggaran::with('student.kelas')
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->whereIn('status', ['tercatat', 'diproses'])
            ->orderBy('tanggal', 'desc')
            ->limit(100)
            ->get();

        // Selected student if filtered
        $selectedStudent = null;
        if ($request->filled('student_id')) {
            $selectedStudent = Siswa::with('kelas')->find($request->student_id);
        }

        return view('pelanggaran.penanganan', compact(
            'items',
            'classes',
            'studentsWithPoints',
            'studentsMap',
            'unhandledViolations',
            'selectedStudent',
            'startDate',
            'endDate',
            'totalPenanganan',
            'totalSp1',
            'totalSp2',
            'totalSp3',
            'totalSp4',
            'totalPembinaan',
            'totalSelesai',
            'totalDalamProses'
        ));
    }

    /**
     * AJAX endpoint detail histori penanganan per siswa
     */
    public function studentHistory($studentId)
    {
        $user = auth()->user();
        $student = Siswa::with([
            'kelas',
            'pelanggarans' => fn($q) => $q->orderBy('tanggal', 'desc'),
            'penanganans'  => fn($q) => $q->with(['pelanggaran', 'handler'])->orderBy('tanggal', 'desc')
        ])->findOrFail($studentId);

        if ($user->school_id && (int) $student->school_id !== (int) $user->school_id) {
            abort(403, 'Akses tidak diizinkan.');
        }

        return response()->json([
            'success' => true,
            'student' => [
                'id'               => $student->id,
                'nama'             => $student->nama,
                'nis'              => $student->nis ?? '-',
                'kelas'            => $student->kelas?->nama_kelas ?? '-',
                'total_poin'       => (int) $student->pelanggarans->sum('poin'),
                'total_kasus'      => $student->pelanggarans->count(),
                'total_penanganan' => $student->penanganans->count(),
            ],
            'penanganans' => $student->penanganans->map(fn($p) => [
                'id'                      => $p->id,
                'tanggal'                 => $p->tanggal?->format('d/m/Y') ?? '-',
                'tindakan_sp'             => $p->tindakan_sp,
                'tindakan_sp_label'       => $p->tindakan_sp_label,
                'catatan_penanganan'      => $p->catatan_penanganan,
                'hasil_kesepakatan'       => $p->hasil_kesepakatan,
                'link_gdrive'             => $p->link_gdrive,
                'lampiran_file_url'       => $p->lampiran_file_url,
                'status_penanganan'       => $p->status_penanganan,
                'status_penanganan_label' => $p->status_penanganan_label,
                'kasus_judul'             => $p->pelanggaran?->judul_pelanggaran ?? '-',
                'kasus_kode'              => $p->pelanggaran?->code ?? '-',
                'handler_name'            => $p->handler?->full_name ?? $p->handler?->username ?? 'Guru BK',
            ]),
        ]);
    }

    /**
     * Export Excel Riwayat Penanganan & SP BK
     */
    public function exportPenangananExcel(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        $isWaliKelas = $user->isWaliKelas() && $user->guru;
        $waliKelasIds = $isWaliKelas ? $user->getManagedKelasIds() : [];

        $query = PenangananPelanggaran::with(['student.kelas', 'pelanggaran', 'handler'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId));

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
        }

        if ($isWaliKelas) {
            $query->whereHas('student', function ($q) use ($waliKelasIds) {
                $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds);
            });
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->whereHas('student', fn($q) => $q->where('kelas_id', $request->kelas_id));
        }

        if ($request->filled('tindakan_sp') && $request->tindakan_sp !== 'all') {
            $query->where('tindakan_sp', $request->tindakan_sp);
        }

        if ($request->filled('status_penanganan') && $request->status_penanganan !== 'all') {
            $query->where('status_penanganan', $request->status_penanganan);
        }

        $items = $query->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->get();
        $school = $user->school ?? School::find($schoolId);
        $schoolName = $school?->name ?? 'Sekolah';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Riwayat Penanganan BK');

        // Header Title
        $sheet->setCellValue('A1', 'LAPORAN RIWAYAT PENANGANAN & KONSELING BK');
        $sheet->setCellValue('A2', strtoupper($schoolName));
        $sheet->setCellValue('A3', 'Dicetak: ' . now()->translatedFormat('d F Y H:i'));

        $sheet->mergeCells('A1:L1');
        $sheet->mergeCells('A2:L2');
        $sheet->mergeCells('A3:L3');

        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);
        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['No', 'Tanggal', 'NIS', 'Nama Siswa', 'Kelas', 'Kasus Terkait', 'Tindakan / SP', 'Catatan Penanganan BK', 'Hasil Kesepakatan', 'Link Google Drive', 'Guru BK / Petugas', 'Status'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '5', $h);
            $col++;
        }

        $sheet->getStyle('A5:L5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4F46E5']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $row = 6;
        $no = 1;
        foreach ($items as $item) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $item->tanggal?->format('d/m/Y') ?? '-');
            $sheet->setCellValueExplicit('C' . $row, $item->student?->nis ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('D' . $row, $item->student?->nama ?? '-');
            $sheet->setCellValue('E' . $row, $item->student?->kelas?->nama_kelas ?? '-');
            $sheet->setCellValue('F' . $row, ($item->pelanggaran ? "[{$item->pelanggaran->code}] " . $item->pelanggaran->judul_pelanggaran : '-'));
            $sheet->setCellValue('G' . $row, $item->tindakan_sp_label);
            $sheet->setCellValue('H' . $row, $item->catatan_penanganan);
            $sheet->setCellValue('I' . $row, $item->hasil_kesepakatan ?? '-');
            $sheet->setCellValue('J' . $row, $item->link_gdrive ?? '-');
            $sheet->setCellValue('K' . $row, $item->handler?->full_name ?? $item->handler?->username ?? '-');
            $sheet->setCellValue('L' . $row, $item->status_penanganan_label);

            $row++;
        }

        if ($row > 6) {
            $sheet->getStyle('A5:L' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        foreach (range('A', 'L') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = 'riwayat_penanganan_bk_' . date('Ymd_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Rekapitulasi Pelanggaran Siswa & Akumulasi Poin per Siswa.
     */
    public function rekap(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        $isWaliKelas = $user->isWaliKelas() && $user->guru;
        $waliKelasIds = $isWaliKelas ? $user->getManagedKelasIds() : [];

        $filterPoin = $request->input('filter_poin', 'with_points'); // 'with_points' (siswa yang punya poin) or 'all' (semua siswa)
        $startDate  = $request->input('start_date');
        $endDate    = $request->input('end_date');

        $query = Siswa::with(['kelas', 'latestPelanggaran', 'latestPenanganan'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds));

        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->where('kelas_id', $request->kelas_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'LIKE', "%{$search}%")
                    ->orWhere('nis', 'LIKE', "%{$search}%");
            });
        }

        // Hitung total poin & kasus (opsional filter periode tanggal jika diisi)
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
        $baseStudentQuery = Siswa::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds));
        
        $totalSiswa = (clone $baseStudentQuery)->count();
        $totalSiswaPelanggar = (clone $baseStudentQuery)->whereHas('pelanggarans', fn($q) => $q->where('poin', '>', 0))->count();

        $violationBase = Pelanggaran::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, function ($q) use ($waliKelasIds) {
                $q->whereHas('student', fn($sq) => $sq->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds));
            });
        
        $totalKasus = (clone $violationBase)->count();
        $totalPoin = (int) (clone $violationBase)->sum('poin');
        $totalPenanganan = PenangananPelanggaran::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, function ($q) use ($waliKelasIds) {
                $q->whereHas('student', fn($sq) => $sq->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds));
            })->count();

        // Top 5 Siswa dengan akumulasi poin tertinggi
        $topSiswa = (clone $baseStudentQuery)
            ->whereHas('pelanggarans', fn($q) => $q->where('poin', '>', 0))
            ->with('kelas')
            ->withSum('pelanggarans as total_poin', 'poin')
            ->withCount('pelanggarans as total_kasus')
            ->orderByDesc('total_poin')
            ->limit(5)
            ->get();

        $students = $query->orderByDesc('total_poin')->orderBy('nama')->paginate(20)->withQueryString();

        $kelasList = Kelas::when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('id', empty($waliKelasIds) ? [-1] : $waliKelasIds))
            ->where('is_active_attendance', true)
            ->orderBy('nama_kelas')
            ->get();

        return view('pelanggaran.rekap', compact(
            'students',
            'kelasList',
            'filterPoin',
            'startDate',
            'endDate',
            'totalSiswa',
            'totalSiswaPelanggar',
            'totalKasus',
            'totalPoin',
            'totalPenanganan',
            'topSiswa'
        ));
    }

    /**
     * Export Rekap Pelanggaran ke Excel (.xlsx)
     */
    public function exportExcel(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id ?? 0;

        $isWaliKelas = $user->isWaliKelas() && $user->guru;
        $waliKelasIds = $isWaliKelas ? $user->getManagedKelasIds() : [];

        $filterPoin = $request->input('filter_poin', 'with_points');
        $startDate  = $request->input('start_date');
        $endDate    = $request->input('end_date');

        $query = Siswa::with(['kelas', 'latestPelanggaran', 'latestPenanganan'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds));

        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->where('kelas_id', $request->kelas_id);
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

        $items = $query->orderByDesc('total_poin')->orderBy('nama')->get();
        $school = $user->school ?? School::find($schoolId);
        $schoolName = $school?->name ?? 'Sekolah';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Poin Siswa');

        // Header Title
        $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI AKUMULASI POIN PELANGGARAN SISWA');
        $sheet->setCellValue('A2', strtoupper($schoolName));
        $periodLabel = ($startDate && $endDate) ? "Periode: {$startDate} s.d. {$endDate}" : 'Seluruh Riwayat (Kumulatif)';
        $sheet->setCellValue('A3', "{$periodLabel} • Dicetak: " . now()->translatedFormat('d F Y H:i'));

        $sheet->mergeCells('A1:I1');
        $sheet->mergeCells('A2:I2');
        $sheet->mergeCells('A3:I3');

        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);
        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Table Columns
        $headers = ['No', 'NIS', 'Nama Siswa', 'Kelas', 'Total Kasus', 'Akumulasi Poin', 'Pelanggaran Terakhir', 'Tindakan / SP Terakhir', 'Total Penanganan BK'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '5', $h);
            $col++;
        }

        $sheet->getStyle('A5:I5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DC2626']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $row = 6;
        $no = 1;
        $totalPoinSum = 0;
        $totalKasusSum = 0;

        foreach ($items as $s) {
            $lastViolation = $s->latestPelanggaran ? "({$s->latestPelanggaran->tanggal?->format('d/m/Y')}) {$s->latestPelanggaran->judul_pelanggaran}" : '-';
            $lastSp = $s->latestPenanganan ? "{$s->latestPenanganan->tindakan_sp_label} ({$s->latestPenanganan->tanggal?->format('d/m/Y')})" : '-';

            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValueExplicit('B' . $row, $s->nis ?? '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C' . $row, $s->nama);
            $sheet->setCellValue('D' . $row, $s->kelas?->nama_kelas ?? '-');
            $sheet->setCellValue('E' . $row, (int) $s->total_kasus);
            $sheet->setCellValue('F' . $row, (int) $s->total_poin);
            $sheet->setCellValue('G' . $row, $lastViolation);
            $sheet->setCellValue('H' . $row, $lastSp);
            $sheet->setCellValue('I' . $row, (int) $s->total_penanganan);

            $totalPoinSum += (int) $s->total_poin;
            $totalKasusSum += (int) $s->total_kasus;
            $row++;
        }

        if ($row > 6) {
            $sheet->setCellValue('A' . $row, 'TOTAL');
            $sheet->mergeCells('A' . $row . ':D' . $row);
            $sheet->setCellValue('E' . $row, $totalKasusSum);
            $sheet->setCellValue('F' . $row, $totalPoinSum);
            $sheet->getStyle('A' . $row . ':I' . $row)->getFont()->setBold(true);
            $sheet->getStyle('A' . $row . ':D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('A5:I' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }

        foreach (range('A', 'I') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = 'rekap_poin_siswa_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Rekap Pelanggaran ke PDF
     */
    public function exportPdf(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id ?? 0;

        $isWaliKelas = $user->isWaliKelas() && $user->guru;
        $waliKelasIds = $isWaliKelas ? $user->getManagedKelasIds() : [];

        $filterPoin = $request->input('filter_poin', 'with_points');
        $startDate  = $request->input('start_date');
        $endDate    = $request->input('end_date');

        $query = Siswa::with(['kelas', 'latestPelanggaran', 'latestPenanganan'])
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->when($isWaliKelas, fn($q) => $q->whereIn('kelas_id', empty($waliKelasIds) ? [-1] : $waliKelasIds));

        if ($request->filled('kelas_id') && $request->kelas_id !== 'all') {
            $query->where('kelas_id', $request->kelas_id);
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

        $items = $query->orderByDesc('total_poin')->orderBy('nama')->get();
        $school = $user->school ?? School::find($schoolId);

        $schoolName = $school?->name ?? 'Sekolah';
        $schoolAddress = $school?->address ?? '';
        $kopSurat = $school?->kop_surat ?? null;

        $totalKasus = (int) $items->sum('total_kasus');
        $totalPoin  = (int) $items->sum('total_poin');
        $totalSiswa = $items->count();

        $pdf = Pdf::loadView('pelanggaran.export-pdf', compact(
            'items',
            'startDate',
            'endDate',
            'filterPoin',
            'schoolName',
            'schoolAddress',
            'kopSurat',
            'totalKasus',
            'totalPoin',
            'totalSiswa'
        ));

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream('rekap_poin_siswa_' . date('Ymd_His') . '.pdf');
    }
}

