<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\GuruShiftAssignment;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GuruShiftMappingController extends Controller
{
    public function index(Request $request)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        // 1. Ambil seluruh master shift beserta guru-guru yang terdaftar di shift ini
        $shiftsQuery = Shift::where('is_active', true);
        if ($schoolId) {
            $shiftsQuery->where('school_id', $schoolId);
        }
        $shifts = $shiftsQuery->with(['assignedGurus' => function ($q) {
            $q->orderBy('nama', 'asc');
        }])->orderBy('jam_masuk', 'asc')->get();

        // 2. Ambil seluruh guru
        $allGurusQuery = Guru::select('id', 'nama', 'nip', 'no_wa');
        if ($schoolId) {
            $allGurusQuery->where('school_id', $schoolId);
        }
        $allGurus = $allGurusQuery->orderBy('nama', 'asc')->get();

        // 3. Guru yang belum memiliki shift sama sekali
        $assignedGuruIds = GuruShiftAssignment::pluck('guru_id')->unique()->toArray();
        $unassignedGurus = $allGurus->whereNotIn('id', $assignedGuruIds)->values();

        $totalGurus = $allGurus->count();
        $totalPlotted = count($assignedGuruIds);
        $totalUnplotted = $unassignedGurus->count();

        return view('shifts.mapping', compact(
            'shifts',
            'allGurus',
            'unassignedGurus',
            'totalGurus',
            'totalPlotted',
            'totalUnplotted'
        ));
    }

    /**
     * Plotting Guru ke Shift (Simpan Seluruh Anggota Terpilih Sekaligus)
     */
    public function assignGurusToShift(Request $request)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'guru_ids' => 'nullable|array',
            'guru_ids.*' => 'exists:guru,id',
        ]);

        $shiftId = $request->shift_id;
        $guruIds = $request->input('guru_ids', []);

        DB::beginTransaction();
        try {
            // Hapus penugasan lama untuk shift ini
            GuruShiftAssignment::where('shift_id', $shiftId)->delete();

            // Masukkan penugasan baru
            foreach ($guruIds as $gId) {
                GuruShiftAssignment::create([
                    'school_id' => $schoolId,
                    'guru_id' => $gId,
                    'shift_id' => $shiftId,
                ]);

                // Update default shift jika belum ada
                Guru::where('id', $gId)->update(['default_shift_id' => $shiftId]);
            }

            DB::commit();

            $shift = Shift::find($shiftId);
            $count = count($guruIds);
            return back()->with('success', "Berhasil menyimpan {$count} guru ke shift '{$shift->nama_shift}'.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyimpan plotting: ' . $e->getMessage());
        }
    }

    /**
     * Tambah Satu Guru ke Shift Tertentu (dari Dropdown / Search)
     */
    public function addGuruToShift(Request $request)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'guru_id' => 'required|exists:guru,id',
        ]);

        $shiftId = $request->shift_id;
        $guruId = $request->guru_id;

        GuruShiftAssignment::firstOrCreate(
            ['guru_id' => $guruId, 'shift_id' => $shiftId],
            ['school_id' => $schoolId]
        );

        Guru::where('id', $guruId)->update(['default_shift_id' => $shiftId]);

        $guru = Guru::find($guruId);
        $shift = Shift::find($shiftId);

        return back()->with('success', "{$guru->nama} berhasil ditambahkan ke shift '{$shift->nama_shift}'.");
    }

    /**
     * Tambahkan Semua Guru Sekaligus ke Shift Ini
     */
    public function addAllGurusToShift(Request $request)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
        ]);

        $shiftId = $request->shift_id;

        $guruQuery = Guru::query();
        if ($schoolId) {
            $guruQuery->where('school_id', $schoolId);
        }
        $guruIds = $guruQuery->pluck('id');

        DB::beginTransaction();
        try {
            foreach ($guruIds as $gId) {
                GuruShiftAssignment::firstOrCreate(
                    ['guru_id' => $gId, 'shift_id' => $shiftId],
                    ['school_id' => $schoolId]
                );
                Guru::where('id', $gId)->update(['default_shift_id' => $shiftId]);
            }
            DB::commit();

            $shift = Shift::find($shiftId);
            return back()->with('success', "Seluruh guru ({$guruIds->count()}) berhasil dimasukkan ke shift '{$shift->nama_shift}'.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menambahkan guru: ' . $e->getMessage());
        }
    }

    /**
     * Hapus Guru dari Shift Tertentu
     */
    public function removeGuruFromShift(Request $request)
    {
        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'guru_id' => 'required|exists:guru,id',
        ]);

        $guruId = $request->guru_id;
        $shiftId = $request->shift_id;

        GuruShiftAssignment::where('shift_id', $shiftId)
            ->where('guru_id', $guruId)
            ->delete();

        Guru::where('id', $guruId)
            ->where('default_shift_id', $shiftId)
            ->update(['default_shift_id' => null]);

        return back()->with('success', 'Guru berhasil dikeluarkan dari shift.');
    }

    /**
     * Assign Cepat untuk Guru yang Belum Memiliki Shift
     */
    public function bulkAssignUnassigned(Request $request)
    {
        $schoolId = (auth()->user() && !auth()->user()->isSuperAdmin())
            ? auth()->user()->school_id
            : null;

        $request->validate([
            'guru_ids' => 'required|array|min:1',
            'guru_ids.*' => 'exists:guru,id',
            'shift_id' => 'required|exists:shifts,id',
        ]);

        $guruIds = $request->input('guru_ids', []);
        $shiftId = $request->input('shift_id');

        DB::beginTransaction();
        try {
            foreach ($guruIds as $gId) {
                GuruShiftAssignment::updateOrCreate(
                    ['guru_id' => $gId, 'shift_id' => $shiftId],
                    ['school_id' => $schoolId]
                );
                Guru::where('id', $gId)->update(['default_shift_id' => $shiftId]);
            }

            DB::commit();
            $shift = Shift::find($shiftId);
            return back()->with('success', count($guruIds) . " guru berhasil dimasukkan ke shift '{$shift->nama_shift}'.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }
}
