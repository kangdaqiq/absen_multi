<?php

namespace App\Http\Controllers;

use App\Models\KategoriPelanggaran;
use App\Models\Kelas;
use App\Models\Pelanggaran;
use App\Models\Siswa;
use App\Services\FcmNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PelanggaranController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $schoolId = $user->school_id;

        $query = Pelanggaran::with(['student.kelas', 'kategoriPelanggaran', 'recorder'])
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
            'bukti_foto'              => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
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
            ? $request->file('bukti_foto')->store('pelanggaran', 'public')
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

        if ($pelanggaran->bukti_foto && Storage::disk('public')->exists($pelanggaran->bukti_foto)) {
            Storage::disk('public')->delete($pelanggaran->bukti_foto);
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
}

