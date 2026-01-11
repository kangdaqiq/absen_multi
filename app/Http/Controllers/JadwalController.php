<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use Illuminate\Http\Request;

class JadwalController extends Controller
{
    public function index()
    {
        $query = Jadwal::query();

        // Filter by school_id for non-super admin users
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $query->where('school_id', auth()->user()->school_id);
        }

        $jadwal = $query->get();
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

        // Add school_id for non-super admin users
        $schoolId = null;
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $schoolId = auth()->user()->school_id;
            $data['school_id'] = $schoolId;
        }

        // Check if day already exists within the same school
        $existsQuery = Jadwal::where('index_hari', $request->index_hari);
        if ($schoolId) {
            $existsQuery->where('school_id', $schoolId);
        }
        $exists = $existsQuery->exists();

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

    public function updateAll(Request $request)
    {
        $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        $schedules = $request->input('schedules', []);

        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : 0;

        foreach ($days as $index => $dayName) {
            $data = $schedules[$index] ?? [];

            // Check validation
            if (!isset($data['jam_masuk']) || !isset($data['jam_pulang'])) {
                continue;
            }

            $isActive = isset($data['is_active']) ? 1 : 0;

            Jadwal::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'index_hari' => $index
                ],
                [
                    'hari' => $dayName,
                    'jam_masuk' => $data['jam_masuk'],
                    'jam_pulang' => $data['jam_pulang'],
                    'toleransi' => $data['toleransi'] ?? 15,
                    'is_active' => $isActive
                ]
            );
        }

        return redirect()->route('jadwal.index')->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy($id)
    {
        Jadwal::findOrFail($id)->delete();
        return back()->with('success', 'Jadwal berhasil dihapus.');
    }
}
