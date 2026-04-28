<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KelasController extends Controller
{
    public function index(Request $request)
    {
        $kelasQuery = Kelas::with('waliKelas')->orderBy('nama_kelas');
        $gurusQuery = \App\Models\Guru::orderBy('nama');

        // Filter by school_id for non-super admin users
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $kelasQuery->where('school_id', auth()->user()->school_id);
            $gurusQuery->where('school_id', auth()->user()->school_id);
        }

        // Search functionality
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $kelasQuery->where('nama_kelas', 'like', "%{$search}%");
        }

        $kelas = $kelasQuery->paginate(20)->withQueryString();
        $gurus = $gurusQuery->get();
        return view('kelas.index', compact('kelas', 'gurus'));
    }

    public function store(Request $request)
    {
        $schoolId = auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id;

        $request->validate([
            'nama_kelas' => [
                'required',
                'string',
                'max:50',
                Rule::unique('kelas')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })
            ],
            'wali_kelas_id' => 'nullable|exists:guru,id',
            'wa_group_id' => 'nullable|string|max:100',
        ]);

        $data = $request->all();

        // Add school_id from authenticated user
        if ($schoolId) {
            $data['school_id'] = $schoolId;
        }

        Kelas::create($data);

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $kelas = Kelas::findOrFail($id);
        $schoolId = auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id;

        $request->validate([
            'nama_kelas' => [
                'required',
                'string',
                'max:50',
                Rule::unique('kelas')->ignore($kelas->id)->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })
            ],
            'wali_kelas_id' => 'nullable|exists:guru,id',
            'wa_group_id' => 'nullable|string|max:100',
        ]);

        $kelas->update($request->all());

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $kelas = Kelas::findOrFail($id);
        $kelas->delete();

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil dihapus.');
    }

    public function toggleStatus($id)
    {
        $kelas = Kelas::findOrFail($id);
        $kelas->is_active_attendance = !$kelas->is_active_attendance;
        $kelas->save();

        $status = $kelas->is_active_attendance ? 'aktif' : 'nonaktif';
        return redirect()->route('kelas.index')->with('success', "Status absensi kelas berhasil diubah menjadi $status.");
    }

    public function toggleReport($id)
    {
        $kelas = Kelas::findOrFail($id);

        // Only allow toggle if attendance is active
        if (!$kelas->is_active_attendance) {
            return redirect()->route('kelas.index')->with('error', 'Tidak dapat mengaktifkan report jika absensi nonaktif.');
        }

        $kelas->is_active_report = !$kelas->is_active_report;
        $kelas->save();

        $status = $kelas->is_active_report ? 'aktif' : 'nonaktif';
        return redirect()->route('kelas.index')->with('success', "Status report WA kelas berhasil diubah menjadi $status.");
    }
}
