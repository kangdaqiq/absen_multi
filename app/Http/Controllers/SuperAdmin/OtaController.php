<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class OtaController extends Controller
{
    public function index()
    {
        $otaPath = public_path('ota');
        $files = [];
        
        if (File::exists($otaPath)) {
            $files = File::files($otaPath);
        }

        return view('super-admin.ota.index', compact('files'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'firmware' => 'required|file',
        ]);

        $otaPath = public_path('ota');
        if (!File::exists($otaPath)) {
            File::makeDirectory($otaPath, 0755, true);
        }

        $file = $request->file('firmware');
        // We force the name to RFIDV2.bin as expected by the ESP8266 code
        $file->move($otaPath, 'RFIDV2.bin');

        return redirect()->back()->with('success', 'Firmware RFIDV2.bin berhasil diupload!');
    }
}
