<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use Illuminate\Http\Request;

class JadwalController extends Controller
{
    public function index()
    {
        $jadwal = Jadwal::all();
        // Since days are stored as index 1-7, we can map them for display
        $days = [
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            7 => 'Minggu',
        ];
        return view('jadwal.index', compact('jadwal', 'days'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'index_hari' => 'required|integer|min:1|max:7',
            'jam_masuk' => 'required',
            'jam_pulang' => 'required',
            'toleransi' => 'required|integer',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;

        // Check if day already exists
        $exists = Jadwal::where('index_hari', $request->index_hari)->exists();
        if ($exists) {
            return back()->with('error', 'Jadwal untuk hari tersebut sudah ada.');
        }

        // Auto-fill 'hari' name based on index
        $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        $data['hari'] = $days[$request->index_hari] ?? 'Unknown';

        Jadwal::create($data);
        return back()->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'jam_masuk' => 'required',
            'jam_pulang' => 'required',
            'toleransi' => 'required|integer',
        ]);

        $jadwal = Jadwal::findOrFail($id);
        $data = $request->all();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        
        $jadwal->update($data);
        return back()->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Jadwal::findOrFail($id)->delete();
        return back()->with('success', 'Jadwal berhasil dihapus.');
    }
}
