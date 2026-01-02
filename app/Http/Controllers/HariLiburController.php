<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use Illuminate\Http\Request;

class HariLiburController extends Controller
{
    public function index()
    {
        $libur = HariLibur::orderBy('tanggal', 'desc')->get();
        return view('hari_libur.index', compact('libur'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date|unique:hari_libur,tanggal',
            'keterangan' => 'required|string|max:255'
        ]);

        HariLibur::create($request->all());

        return redirect()->route('hari-libur.index')->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function destroy($id)
    {
        HariLibur::findOrFail($id)->delete();
        return redirect()->route('hari-libur.index')->with('success', 'Hari libur berhasil dihapus.');
    }
}
