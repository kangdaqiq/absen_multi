<?php

namespace App\Http\Controllers;

use App\Models\Mapel;
use Illuminate\Http\Request;

class MapelController extends Controller
{
    public function index()
    {
        $query = Mapel::orderBy('nama_mapel', 'asc');

        // Filter by school_id for non-super admin users
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $query->where('school_id', auth()->user()->school_id);
        }

        $mapel = $query->get();
        return view('mapel.index', compact('mapel'));
    }

    public function store(Request $request)
    {
        $schoolId = auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id;

        $request->validate([
            'nama_mapel' => [
                'required',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('mapel')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })
            ],
        ]);

        $data = ['nama_mapel' => $request->nama_mapel];
        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        Mapel::create($data);

        return redirect()->route('mapel.index')->with('success', 'Mata Pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $mapel = Mapel::findOrFail($id);
        $schoolId = auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id;

        $request->validate([
            'nama_mapel' => [
                'required',
                'string',
                'max:100',
                \Illuminate\Validation\Rule::unique('mapel')->ignore($mapel->id)->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })
            ],
        ]);

        $mapel->update([
            'nama_mapel' => $request->nama_mapel,
        ]);

        return redirect()->route('mapel.index')->with('success', 'Mata Pelajaran berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $mapel = Mapel::findOrFail($id);

        // Security check for school_id
        if (auth()->user() && !auth()->user()->isSuperAdmin() && $mapel->school_id != auth()->user()->school_id) {
            abort(403);
        }

        // Count usage?
        // if ($mapel->jadwals()->count() > 0) return error... (optional, usually good practice)
        if ($mapel->jadwals()->count() > 0) {
            return redirect()->route('mapel.index')->with('error', 'Gagal dihapus: Mata Pelajaran ini masih digunakan di Jadwal.');
        }

        $mapel->delete();

        return redirect()->route('mapel.index')->with('success', 'Mata Pelajaran berhasil dihapus.');
    }
}
