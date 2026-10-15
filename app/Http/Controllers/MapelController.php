<?php

namespace App\Http\Controllers;

use App\Models\Mapel;
use App\Models\Kelas;
use Illuminate\Http\Request;

class MapelController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = auth()->user()->school_id;

        $query = Mapel::where('school_id', $schoolId)
            ->with(['kelases:id,nama_kelas'])
            ->withCount('jadwals')
            ->orderBy('nama_mapel');

        if ($request->filled('search')) {
            $query->where('nama_mapel', 'like', '%' . $request->search . '%');
        }

        $mapels = $query->paginate(20)->withQueryString();
        $kelases = Kelas::where('school_id', $schoolId)->orderBy('nama_kelas')->get();

        return view('mapel.index', compact('mapels', 'kelases'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_mapel'  => 'required|string|max:150',
            'kelas_ids'   => 'nullable|array',
            'kelas_ids.*' => 'integer|exists:kelas,id',
        ]);

        $schoolId = auth()->user()->school_id;

        $mapel = Mapel::create([
            'nama_mapel' => trim($request->nama_mapel),
            'school_id'  => $schoolId,
        ]);

        if ($request->has('kelas_ids')) {
            $mapel->kelases()->sync($request->kelas_ids);
        }

        return redirect()->back()->with('success', 'Mata Pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $schoolId = auth()->user()->school_id;
        $mapel = Mapel::where('school_id', $schoolId)->findOrFail($id);

        $request->validate([
            'nama_mapel'  => 'required|string|max:150',
            'kelas_ids'   => 'nullable|array',
            'kelas_ids.*' => 'integer|exists:kelas,id',
        ]);

        $mapel->update([
            'nama_mapel' => trim($request->nama_mapel),
        ]);

        $mapel->kelases()->sync($request->input('kelas_ids', []));

        return redirect()->back()->with('success', 'Mata Pelajaran berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $schoolId = auth()->user()->school_id;
        $mapel = Mapel::where('school_id', $schoolId)->findOrFail($id);

        $mapel->kelases()->detach();
        $mapel->delete();

        return redirect()->back()->with('success', 'Mata Pelajaran berhasil dihapus.');
    }
}
