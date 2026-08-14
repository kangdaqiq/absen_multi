<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class OtaController extends Controller
{
    public const PRESET_TYPES = [
        'RFIDV2' => 'RFID Versi 2 (Dengan Baterai)',
        'RFIDV2_NoBat' => 'RFID Versi 2 (Tanpa Baterai)',
        'FingerprintV2' => 'Fingerprint Versi 2',
        'FingerprintV3' => 'Fingerprint Versi 3',
        'Fingerprint608' => 'Fingerprint Sensor AS608',
    ];

    public function index()
    {
        $otaPath = public_path('ota');
        $firmwares = [];

        if (File::exists($otaPath)) {
            $files = File::files($otaPath);
            foreach ($files as $file) {
                if (strtolower($file->getExtension()) === 'bin') {
                    $filename = $file->getFilename();
                    $deviceType = pathinfo($filename, PATHINFO_FILENAME);
                    
                    $firmwares[] = [
                        'filename' => $filename,
                        'device_type' => $deviceType,
                        'label' => self::PRESET_TYPES[$deviceType] ?? $deviceType,
                        'size' => $file->getSize(),
                        'size_formatted' => round($file->getSize() / 1024, 2) . ' KB',
                        'mtime' => $file->getMTime(),
                        'date_formatted' => date('d M Y, H:i', $file->getMTime()),
                        'url' => url('ota/' . $filename),
                    ];
                }
            }
        }

        // Sort by device type name
        usort($firmwares, function ($a, $b) {
            return strcmp($a['device_type'], $b['device_type']);
        });

        $presetTypes = self::PRESET_TYPES;

        return view('super-admin.ota.index', compact('firmwares', 'presetTypes'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'firmware' => 'required|file',
            'device_type_preset' => 'required|string',
            'device_type_custom' => 'nullable|required_if:device_type_preset,custom|string|max:50',
        ], [
            'firmware.required' => 'Pilih file firmware (.bin) terlebih dahulu.',
            'device_type_preset.required' => 'Pilih tipe device.',
            'device_type_custom.required_if' => 'Masukkan nama tipe device kustom.',
        ]);

        $deviceType = $request->input('device_type_preset');
        if ($deviceType === 'custom') {
            $rawType = trim($request->input('device_type_custom'));
            // Clean type to safe characters (letters, numbers, underscore, hyphen)
            $deviceType = preg_replace('/[^A-Za-z0-9_\-]/', '_', $rawType);
        }

        if (empty($deviceType)) {
            return redirect()->back()->with('error', 'Tipe device tidak valid.');
        }

        $otaPath = public_path('ota');
        if (!File::exists($otaPath)) {
            File::makeDirectory($otaPath, 0755, true);
        }

        $filename = $deviceType . '.bin';
        $file = $request->file('firmware');
        $file->move($otaPath, $filename);

        return redirect()->back()->with('success', "Firmware untuk device type '{$deviceType}' ({$filename}) berhasil dipublikasikan!");
    }

    public function destroy($filename)
    {
        $safeFilename = basename($filename);
        $filePath = public_path('ota/' . $safeFilename);

        if (File::exists($filePath) && strtolower(pathinfo($safeFilename, PATHINFO_EXTENSION)) === 'bin') {
            File::delete($filePath);
            return redirect()->back()->with('success', "Firmware {$safeFilename} berhasil dihapus.");
        }

        return redirect()->back()->with('error', 'File firmware tidak ditemukan.');
    }
}
