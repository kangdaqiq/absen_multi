<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use Illuminate\Http\Request;

class HariLiburController extends Controller
{
    public function index()
    {
        $query = HariLibur::orderBy('tanggal', 'desc');

        // Filter by school_id for non-super admin users
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $query->where('school_id', auth()->user()->school_id);
        }

        $libur = $query->get();
        return view('hari_libur.index', compact('libur'));
    }

    public function store(Request $request)
    {
        $schoolId = auth()->user()->isSuperAdmin() ? null : auth()->user()->school_id;

        $request->validate([
            'tanggal' => [
                'required',
                'date',
                \Illuminate\Validation\Rule::unique('hari_libur')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                }),
            ],
            'keterangan' => 'required|string|max:255'
        ]);

        $data = $request->all();

        // Add school_id for non-super admin users
        if (!auth()->user()->isSuperAdmin()) {
            $data['school_id'] = $schoolId;
        }

        HariLibur::create($data);

        return redirect()->route('hari-libur.index')->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        HariLibur::findOrFail($id)->delete();
        return redirect()->route('hari-libur.index')->with('success', 'Hari libur berhasil dihapus.');
    }
}
