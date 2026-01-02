<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index()
    {
        $path = storage_path("app/backups");
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        $files = glob("$path/*.sql");
        $backups = [];

        foreach ($files as $file) {
            $backups[] = [
                'name' => basename($file),
                'size' => $this->human_filesize(filesize($file)),
                'date' => date('Y-m-d H:i:s', filemtime($file)),
                'path' => $file
            ];
        }

        // Sort by date desc
        usort($backups, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return view('backups.index', compact('backups'));
    }

    public function create()
    {
        Artisan::call('db:backup');
        return back()->with('success', 'Backup database berhasil dijalankan.');
    }

    public function download($filename)
    {
        $path = storage_path("app/backups/$filename");
        
        if (!file_exists($path)) {
            return back()->with('error', 'File tidak ditemukan.');
        }

        return response()->download($path);
    }

    public function delete($filename)
    {
        $path = storage_path("app/backups/$filename");
        
        if (file_exists($path)) {
            unlink($path);
            return back()->with('success', 'Backup berhasil dihapus.');
        }

        return back()->with('error', 'File tidak ditemukan.');
    }

    private function human_filesize($bytes, $decimals = 2)
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }
}
