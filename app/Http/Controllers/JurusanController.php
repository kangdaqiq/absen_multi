<?php

namespace App\Http\Controllers;

use App\Models\Jurusan;
use Illuminate\Http\Request;

class JurusanController extends Controller
{
    public function index(Request $request)
    {
        $query = Jurusan::withCount('kelas')->orderBy('nama_jurusan');
        
        // Filter by school_id
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $query->where('school_id', auth()->user()->school_id);
        }

        if ($request->has('search') && !empty($request->search)) {
            $query->where('nama_jurusan', 'like', '%' . $request->search . '%');
        }

        $jurusans = $query->paginate(20)->withQueryString();

        return view('jurusan.index', compact('jurusans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_jurusan' => 'required|string|max:255',
        ]);

        $data = $request->only('nama_jurusan');
        
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $data['school_id'] = auth()->user()->school_id;
        }

        Jurusan::create($data);

        return redirect()->back()->with('success', 'Jurusan berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $jurusan = Jurusan::findOrFail($id);
        
        // Ensure user can only update their school's data
        if (auth()->user() && !auth()->user()->isSuperAdmin() && $jurusan->school_id != auth()->user()->school_id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'nama_jurusan' => 'required|string|max:255',
        ]);

        $jurusan->update($request->only('nama_jurusan'));

        return redirect()->back()->with('success', 'Jurusan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $jurusan = Jurusan::findOrFail($id);
        
        // Ensure user can only delete their school's data
        if (auth()->user() && !auth()->user()->isSuperAdmin() && $jurusan->school_id != auth()->user()->school_id) {
            abort(403, 'Unauthorized action.');
        }

        $jurusan->delete();

        return redirect()->back()->with('success', 'Jurusan berhasil dihapus.');
    }
}
