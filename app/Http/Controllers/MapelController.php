<?php

namespace App\Http\Controllers;

use App\Models\Mapel;
use Illuminate\Http\Request;

class MapelController extends Controller
{
    public function index()
    {
        $mapel = Mapel::orderBy('nama_mapel', 'asc')->get();
        return view('mapel.index', compact('mapel'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_mapel' => 'required|string|max:100|unique:mapel,nama_mapel',
        ]);

        Mapel::create([
            'nama_mapel' => $request->nama_mapel,
        ]);

        return redirect()->route('mapel.index')->with('success', 'Mata Pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama_mapel' => 'required|string|max:100|unique:mapel,nama_mapel,'.$id,
        ]);

        $mapel = Mapel::findOrFail($id);
        $mapel->update([
            'nama_mapel' => $request->nama_mapel,
        ]);

        return redirect()->route('mapel.index')->with('success', 'Mata Pelajaran berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        $mapel = Mapel::findOrFail($id);
        
        // Count usage?
        // if ($mapel->jadwals()->count() > 0) return error... (optional, usually good practice)
        if ($mapel->jadwals()->count() > 0) {
            return redirect()->route('mapel.index')->with('error', 'Gagal dihapus: Mata Pelajaran ini masih digunakan di Jadwal.');
        }

        $mapel->delete();

        return redirect()->route('mapel.index')->with('success', 'Mata Pelajaran berhasil dihapus.');
    }
}
