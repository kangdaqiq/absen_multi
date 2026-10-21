<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\Request;

class LicenseController extends Controller
{
    public function index()
    {
        $licenses = License::latest()->get();
        return view('super-admin.licenses.index', compact('licenses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_name'          => 'required|string|max:255',
            'max_schools'          => 'required|integer|min:0',
            'max_students'         => 'required|integer|min:0',
            'max_teachers'         => 'required|integer|min:0',
            'max_bot_users'        => 'required|integer|min:0',
            'history_quota_months' => 'nullable|integer|min:0',
            'storage_quota_mb'     => 'nullable|integer|min:0',
            'expired_at'           => 'nullable|date|after:today',
            'allowed_hostname'     => 'nullable|string|max:255',
            'notes'                => 'nullable|string',
        ]);

        $validated['license_key']          = License::generateKey();
        $validated['is_active']            = $request->has('is_active');
        $validated['photo_enabled']        = $request->has('photo_enabled');
        $validated['face_enabled']         = $request->has('face_enabled');
        $validated['dok_kbm_enabled']      = $request->has('dok_kbm_enabled');
        $validated['history_quota_months'] = empty($request->history_quota_months) ? null : (int) $request->history_quota_months;
        $validated['storage_quota_mb']     = $request->filled('storage_quota_mb') ? (int) $request->storage_quota_mb : 1000;
        $validated['storage_api_key']      = 'sk_lic_' . strtolower(\Illuminate\Support\Str::random(24));

        $license = License::create($validated);

        try {
            app(\App\Services\StorageServerService::class)->syncLicenseUser($license);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("[LicenseController] Gagal auto-sync storage server: " . $e->getMessage());
        }

        return redirect()->route('super-admin.licenses.index')
            ->with('success', 'Lisensi berhasil dibuat untuk ' . $validated['client_name']);
    }

    public function update(Request $request, License $license)
    {
        $validated = $request->validate([
            'client_name'          => 'required|string|max:255',
            'max_schools'          => 'required|integer|min:0',
            'max_students'         => 'required|integer|min:0',
            'max_teachers'         => 'required|integer|min:0',
            'max_bot_users'        => 'required|integer|min:0',
            'history_quota_months' => 'nullable|integer|min:0',
            'storage_quota_mb'     => 'nullable|integer|min:0',
            'expired_at'           => 'nullable|date',
            'allowed_hostname'     => 'nullable|string|max:255',
            'notes'                => 'nullable|string',
        ]);

        $validated['is_active']            = $request->has('is_active');
        $validated['photo_enabled']        = $request->has('photo_enabled');
        $validated['face_enabled']         = $request->has('face_enabled');
        $validated['dok_kbm_enabled']      = $request->has('dok_kbm_enabled');
        $validated['history_quota_months'] = empty($request->history_quota_months) ? null : (int) $request->history_quota_months;
        $validated['storage_quota_mb']     = $request->filled('storage_quota_mb') ? (int) $request->storage_quota_mb : ($license->storage_quota_mb ?? 1000);

        if (empty($license->storage_api_key)) {
            $validated['storage_api_key'] = 'sk_lic_' . strtolower(\Illuminate\Support\Str::random(24));
        }

        $license->update($validated);

        try {
            app(\App\Services\StorageServerService::class)->syncLicenseUser($license);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("[LicenseController] Gagal auto-sync storage server saat update: " . $e->getMessage());
        }

        return redirect()->route('super-admin.licenses.index')
            ->with('success', 'Lisensi ' . $license->client_name . ' berhasil diperbarui.');
    }

    /**
     * Toggle photo_enabled for a license (AJAX, superadmin only)
     */
    public function togglePhoto(License $license)
    {
        $license->photo_enabled = !$license->photo_enabled;
        $license->save();

        $status = $license->photo_enabled ? 'aktif' : 'nonaktif';

        return response()->json([
            'success'       => true,
            'photo_enabled' => $license->photo_enabled,
            'message'       => "Fitur Foto Siswa untuk lisensi {$license->client_name} berhasil di{$status}kan.",
        ]);
    }

    /**
     * Toggle face_enabled for a license (AJAX, superadmin only)
     */
    public function toggleFace(License $license)
    {
        $license->face_enabled = !$license->face_enabled;
        $license->save();

        $status = $license->face_enabled ? 'aktif' : 'nonaktif';

        return response()->json([
            'success'      => true,
            'face_enabled' => $license->face_enabled,
            'message'      => "Fitur Scanner Face ID untuk lisensi {$license->client_name} berhasil di{$status}kan.",
        ]);
    }

    /**
     * Toggle dok_kbm_enabled for a license (AJAX, superadmin only)
     */
    public function toggleDokKbm(License $license)
    {
        $license->dok_kbm_enabled = !$license->dok_kbm_enabled;
        $license->save();

        $status = $license->dok_kbm_enabled ? 'aktif' : 'nonaktif';

        return response()->json([
            'success'         => true,
            'dok_kbm_enabled' => $license->dok_kbm_enabled,
            'message'         => "Fitur Dokumentasi KBM untuk lisensi {$license->client_name} berhasil di{$status}kan.",
        ]);
    }

    public function destroy(License $license)
    {
        $name = $license->client_name;
        $storageUserId = $license->storage_user_id;
        $license->delete();

        if (!empty($storageUserId)) {
            try {
                app(\App\Services\StorageServerService::class)->deleteUser($storageUserId);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("[LicenseController] Gagal hapus user storage: " . $e->getMessage());
            }
        }

        return redirect()->route('super-admin.licenses.index')
            ->with('success', 'Lisensi ' . $name . ' berhasil dihapus.');
    }

    /**
     * Regenerate a new license key for existing license
     */
    public function regenerate(License $license)
    {
        $license->update(['license_key' => License::generateKey()]);

        return redirect()->route('super-admin.licenses.index')
            ->with('success', 'License key untuk ' . $license->client_name . ' berhasil diperbarui.');
    }

    /**
     * Download the latest backup for the license
     */
    public function downloadBackup(License $license)
    {
        if (empty($license->backup_file)) {
            return redirect()->route('super-admin.licenses.index')
                ->with('error', 'File backup untuk lisensi ' . $license->client_name . ' belum tersedia di server.');
        }

        $fullPath = null;
        if (file_exists(storage_path('app/' . $license->backup_file))) {
            $fullPath = storage_path('app/' . $license->backup_file);
        } elseif (\Illuminate\Support\Facades\Storage::disk('local')->exists($license->backup_file)) {
            $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($license->backup_file);
        } elseif (file_exists($license->backup_file)) {
            $fullPath = $license->backup_file;
        }

        if (!$fullPath || !file_exists($fullPath)) {
            return redirect()->route('super-admin.licenses.index')
                ->with('error', 'File backup untuk lisensi ' . $license->client_name . ' tidak ditemukan di server.');
        }

        $downloadName = basename($fullPath);

        return response()->download($fullPath, $downloadName);
    }
}
