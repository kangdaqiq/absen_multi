<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index()
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $query = Shift::withCount(['gurus', 'shiftAssignments']);
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }

        $shifts = $query->orderBy('jam_masuk', 'asc')->get();

        return view('shifts.index', compact('shifts'));
    }

    public function store(Request $request)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $request->validate([
            'nama_shift' => 'required|string|max:100',
            'kode_shift' => 'nullable|string|max:50',
            'jam_masuk' => 'required',
            'jam_pulang' => 'required',
            'jam_terlambat' => 'nullable',
            'awal_absen_masuk' => 'required',
            'akhir_absen_masuk' => 'required',
            'awal_absen_pulang' => 'required',
            'akhir_absen_pulang' => 'required',
        ]);

        $data = $request->all();
        $data['school_id'] = $schoolId;
        $data['is_active'] = $request->has('is_active') ? true : ($request->input('is_active', true));
        $data['hari_kerja'] = $request->input('hari_kerja', [1, 2, 3, 4, 5]);

        Shift::create($data);

        return redirect()->route('shifts.index')->with('success', 'Shift kerja baru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $query = Shift::where('id', $id);
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        $shift = $query->firstOrFail();

        $request->validate([
            'nama_shift' => 'required|string|max:100',
            'kode_shift' => 'nullable|string|max:50',
            'jam_masuk' => 'required',
            'jam_pulang' => 'required',
            'jam_terlambat' => 'nullable',
            'awal_absen_masuk' => 'required',
            'akhir_absen_masuk' => 'required',
            'awal_absen_pulang' => 'required',
            'akhir_absen_pulang' => 'required',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');
        $data['hari_kerja'] = $request->input('hari_kerja', [1, 2, 3, 4, 5]);

        $shift->update($data);

        return redirect()->route('shifts.index')->with('success', 'Data shift berhasil diperbarui.');
    }

    public function toggleActive($id)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $query = Shift::where('id', $id);
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        $shift = $query->firstOrFail();

        $shift->update(['is_active' => !$shift->is_active]);

        $statusText = $shift->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Shift '{$shift->nama_shift}' berhasil {$statusText}.");
    }

    public function destroy($id)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $query = Shift::where('id', $id);
        if ($schoolId) {
            $query->where('school_id', $schoolId);
        }
        $shift = $query->firstOrFail();

        $shift->delete();

        return redirect()->route('shifts.index')->with('success', 'Shift berhasil dihapus.');
    }
}
