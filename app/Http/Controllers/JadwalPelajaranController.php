<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelajaran;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Mapel;
use Illuminate\Http\Request;

class JadwalPelajaranController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : 0;

        $query = JadwalPelajaran::with(['guru', 'kelas', 'mapel']);

        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        if ($request->has('guru_id') && $request->guru_id != '') {
            $query->where('guru_id', $request->guru_id);
        }

        if ($request->has('kelas_id') && $request->kelas_id != '') {
            $query->where('kelas_id', $request->kelas_id);
        }

        if ($request->has('hari') && $request->hari != '') {
            $query->where('hari', $request->hari);
        }

        $jadwals = $query->orderBy('hari') // We will sort properly by day index in view or collection
            ->orderBy('jam_mulai', 'asc')
            ->get();

        // Custom sort for Days
        $daysMap = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7];
        $jadwals = $jadwals->sortBy(function ($j) use ($daysMap) {
            return $daysMap[$j->hari] ?? 8;
        });

        // Filter dropdowns by school_id
        if ($schoolId) {
            $gurus = Guru::where('school_id', $schoolId)->orderBy('nama')->get();
            $kelas = Kelas::where('school_id', $schoolId)->orderBy('nama_kelas')->get();
            $mapels = Mapel::where('school_id', $schoolId)->orderBy('nama_mapel')->get();
        } else {
            // Super admin sees all, or handle differently
            $gurus = Guru::orderBy('nama')->get();
            $kelas = Kelas::orderBy('nama_kelas')->get();
            $mapels = Mapel::orderBy('nama_mapel')->get();
        }

        // If class-centric view is requested or just default to grouped by class?
        // Let's pass data for the new UI structure

        return view('jadwal-pelajaran.index', compact('jadwals', 'gurus', 'kelas', 'mapels', 'request'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'guru_id' => 'required|exists:guru,id',
            'kelas_id' => 'required|exists:kelas,id',
            'mapel_id' => 'required|exists:mapel,id',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'jam_mulai' => 'required', // Allow H:i
            'jam_selesai' => 'required|after:jam_mulai',
        ]);

        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : 0;

        // Check overlap
        $overlap = JadwalPelajaran::where('guru_id', $request->guru_id)
            ->where('hari', $request->hari)
            ->where(function ($q) use ($request) {
                $q->whereBetween('jam_mulai', [$request->jam_mulai, $request->jam_selesai])
                    ->orWhereBetween('jam_selesai', [$request->jam_mulai, $request->jam_selesai])
                    ->orWhere(function ($sq) use ($request) {
                        $sq->where('jam_mulai', '<=', $request->jam_mulai)
                            ->where('jam_selesai', '>=', $request->jam_selesai);
                    });
            });

        if ($schoolId) {
            $overlap->where('school_id', $schoolId);
        }

        if ($overlap->exists()) {
            return redirect()->back()->with('error', 'Guru tersebut sudah ada jadwal di jam yang sama (Bentrok).');
        }

        $data = $request->all();
        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        JadwalPelajaran::create($data);

        return redirect()->back()->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'guru_id' => 'required|exists:guru,id',
            'kelas_id' => 'required|exists:kelas,id',
            'mapel_id' => 'required|exists:mapel,id',
            'hari' => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'jam_mulai' => 'required',
            'jam_selesai' => 'required|after:jam_mulai',
        ]);

        $jadwal = JadwalPelajaran::findOrFail($id);

        // Security check for school_id
        if (auth()->user() && !auth()->user()->isSuperAdmin() && $jadwal->school_id != auth()->user()->school_id) {
            abort(403);
        }

        $jadwal->update($request->all());

        return redirect()->back()->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $jadwal = JadwalPelajaran::findOrFail($id);

        // Security check for school_id
        if (auth()->user() && !auth()->user()->isSuperAdmin() && $jadwal->school_id != auth()->user()->school_id) {
            abort(403);
        }

        $jadwal->delete();

        return redirect()->back()->with('success', 'Jadwal berhasil dihapus.');
    }
}
