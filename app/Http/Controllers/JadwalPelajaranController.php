<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelajaran;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\JamPelajaran;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JadwalPelajaranController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = auth()->user()->school_id;

        $query = JadwalPelajaran::where('school_id', $schoolId)
            ->with(['guru', 'kelas', 'mapel', 'jamPelajaran']);

        if ($request->filled('hari')) {
            $query->where('hari', $request->hari);
        }

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        if ($request->filled('guru_id')) {
            $query->where('guru_id', $request->guru_id);
        }

        if ($request->filled('mapel_id')) {
            $query->where('mapel_id', $request->mapel_id);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('mapel', function ($m) use ($search) {
                    $m->where('nama_mapel', 'like', "%{$search}%");
                })->orWhereHas('guru', function ($g) use ($search) {
                    $g->where('nama', 'like', "%{$search}%");
                })->orWhereHas('kelas', function ($k) use ($search) {
                    $k->where('nama_kelas', 'like', "%{$search}%");
                })->orWhere('jam_ke', 'like', "%{$search}%")
                  ->orWhere('ruangan', 'like', "%{$search}%");
            });
        }

        // Urutkan berdasarkan hari (Senin - Minggu) lalu jam mulai
        $jadwals = $query->with(['guru', 'kelas', 'mapel', 'jamPelajaran', 'jamPelajaranAkhir'])
            ->orderByRaw("FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu')")
            ->orderBy('jam_mulai')
            ->paginate(25)
            ->withQueryString();

        $kelases = Kelas::where('school_id', $schoolId)->orderBy('nama_kelas')->get();
        $gurus = Guru::where('school_id', $schoolId)->orderBy('nama')->get();
        $mapels = Mapel::where('school_id', $schoolId)->with('kelases:id,nama_kelas')->orderBy('nama_mapel')->get();
        $jamPelajarans = JamPelajaran::where('school_id', $schoolId)->where('is_active', true)->orderBy('urutan')->orderBy('jam_mulai')->get();

        $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

        return view('jadwal-pelajaran.index', compact(
            'jadwals',
            'kelases',
            'gurus',
            'mapels',
            'jamPelajarans',
            'hariList'
        ));
    }

    public function store(Request $request)
    {
        $schoolId = auth()->user()->school_id;

        $request->validate([
            'jam_pelajaran_id'       => 'required|exists:jam_pelajaran,id',
            'jam_pelajaran_id_akhir' => 'nullable|exists:jam_pelajaran,id',
            'guru_id'                => 'required|exists:guru,id',
            'kelas_id'               => 'required|exists:kelas,id',
            'mapel_id'               => 'required|exists:mapel,id',
            'hari'                   => 'required|string|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'ruangan'                => 'nullable|string|max:100',
            'keterangan'             => 'nullable|string|max:255',
        ]);

        $jpAwal = JamPelajaran::findOrFail($request->jam_pelajaran_id);
        $jpAkhir = !empty($request->jam_pelajaran_id_akhir) && $request->jam_pelajaran_id_akhir != $jpAwal->id
            ? JamPelajaran::find($request->jam_pelajaran_id_akhir)
            : null;

        $jamMulai = Carbon::parse($jpAwal->jam_mulai)->format('H:i');
        $jamSelesai = $jpAkhir
            ? Carbon::parse($jpAkhir->jam_selesai)->format('H:i')
            : Carbon::parse($jpAwal->jam_selesai)->format('H:i');

        $jamKeAwal = $jpAwal->jam_ke ?: ($jpAwal->nama_jam ?? '-');
        if ($jpAkhir && $jpAkhir->id !== $jpAwal->id) {
            $jamKeAkhir = $jpAkhir->jam_ke ?: ($jpAkhir->nama_jam ?? '-');
            $jamKe = "{$jamKeAwal} - {$jamKeAkhir}";
        } else {
            $jamKe = $jamKeAwal;
        }

        JadwalPelajaran::create([
            'school_id'               => $schoolId,
            'jam_pelajaran_id'        => $jpAwal->id,
            'jam_pelajaran_id_akhir'  => $jpAkhir?->id,
            'guru_id'                 => $request->guru_id,
            'kelas_id'                => $request->kelas_id,
            'mapel_id'                => $request->mapel_id,
            'hari'                    => $request->hari,
            'jam_ke'                  => $jamKe,
            'jam_mulai'               => $jamMulai,
            'jam_selesai'             => $jamSelesai,
            'ruangan'                 => $request->ruangan,
            'keterangan'              => $request->keterangan,
        ]);

        return redirect()->back()->with('success', 'Jadwal pembelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $schoolId = auth()->user()->school_id;
        $jadwal = JadwalPelajaran::where('school_id', $schoolId)->findOrFail($id);

        $request->validate([
            'jam_pelajaran_id'       => 'required|exists:jam_pelajaran,id',
            'jam_pelajaran_id_akhir' => 'nullable|exists:jam_pelajaran,id',
            'guru_id'                => 'required|exists:guru,id',
            'kelas_id'               => 'required|exists:kelas,id',
            'mapel_id'               => 'required|exists:mapel,id',
            'hari'                   => 'required|string|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'ruangan'                => 'nullable|string|max:100',
            'keterangan'             => 'nullable|string|max:255',
        ]);

        $jpAwal = JamPelajaran::findOrFail($request->jam_pelajaran_id);
        $jpAkhir = !empty($request->jam_pelajaran_id_akhir) && $request->jam_pelajaran_id_akhir != $jpAwal->id
            ? JamPelajaran::find($request->jam_pelajaran_id_akhir)
            : null;

        $jamMulai = Carbon::parse($jpAwal->jam_mulai)->format('H:i');
        $jamSelesai = $jpAkhir
            ? Carbon::parse($jpAkhir->jam_selesai)->format('H:i')
            : Carbon::parse($jpAwal->jam_selesai)->format('H:i');

        $jamKeAwal = $jpAwal->jam_ke ?: ($jpAwal->nama_jam ?? '-');
        if ($jpAkhir && $jpAkhir->id !== $jpAwal->id) {
            $jamKeAkhir = $jpAkhir->jam_ke ?: ($jpAkhir->nama_jam ?? '-');
            $jamKe = "{$jamKeAwal} - {$jamKeAkhir}";
        } else {
            $jamKe = $jamKeAwal;
        }

        $jadwal->update([
            'jam_pelajaran_id'        => $jpAwal->id,
            'jam_pelajaran_id_akhir'  => $jpAkhir?->id,
            'guru_id'                 => $request->guru_id,
            'kelas_id'                => $request->kelas_id,
            'mapel_id'                => $request->mapel_id,
            'hari'                    => $request->hari,
            'jam_ke'                  => $jamKe,
            'jam_mulai'               => $jamMulai,
            'jam_selesai'             => $jamSelesai,
            'ruangan'                 => $request->ruangan,
            'keterangan'              => $request->keterangan,
        ]);

        return redirect()->back()->with('success', 'Jadwal pembelajaran berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $schoolId = auth()->user()->school_id;
        $jadwal = JadwalPelajaran::where('school_id', $schoolId)->findOrFail($id);

        $jadwal->delete();

        return redirect()->back()->with('success', 'Jadwal pembelajaran berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $schoolId = auth()->user()->school_id;
        $ids = $request->input('selected_ids', []);

        if (!empty($ids)) {
            JadwalPelajaran::where('school_id', $schoolId)
                ->whereIn('id', $ids)
                ->delete();
            return redirect()->back()->with('success', count($ids) . ' Jadwal berhasil dihapus.');
        }

        return redirect()->back()->with('error', 'Tidak ada jadwal yang dipilih.');
    }
}
