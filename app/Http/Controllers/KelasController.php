<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Http\Request;

class KelasController extends Controller
{
    public function index()
    {
        $kelas = Kelas::with('waliKelas')->orderBy('nama_kelas')->get();
        $gurus = \App\Models\Guru::orderBy('nama')->get();
        return view('kelas.index', compact('kelas', 'gurus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_kelas' => 'required|string|max:50|unique:kelas,nama_kelas',
            'wali_kelas_id' => 'nullable|exists:guru,id',
        ]);

        Kelas::create($request->all());

        return redirect()->route('kelas.index')->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $kelas = Kelas::findOrFail($id);

        $request->validate([
            'nama_kelas' => 'required|string|max:50|unique:kelas,nama_kelas,' . $kelas->id,
            'wali_kelas_id' => 'nullable|exists:guru,id',
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
}
