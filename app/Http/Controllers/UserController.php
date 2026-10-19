<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Guru;
use App\Models\Setting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $allowedRoles = ['admin', 'super_admin', 'wali_kelas', 'waka_kurikulum', 'guru', 'teacher'];
        $query = User::with('guru')->whereIn('role', $allowedRoles)->orderBy('full_name');

        // Filter by school_id for non-super admin users
        $schoolId = auth()->user()?->school_id;
        $isSuperAdmin = auth()->user()?->isSuperAdmin();

        if (auth()->user() && !$isSuperAdmin && $schoolId) {
            $query->where('school_id', $schoolId)
                  ->whereIn('role', ['admin', 'wali_kelas', 'waka_kurikulum', 'guru', 'teacher']);
        }

        // Filter by role if requested
        if ($request->filled('role')) {
            if ($request->role === 'guru') {
                $query->whereIn('role', ['guru', 'teacher']);
            } else {
                $query->where('role', $request->role);
            }
        }

        // Search by keyword
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('username', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $users = $query->get();

        // Ambil data guru yang belum punya akun untuk fitur Generate Akun Guru
        $unregisteredGurusQuery = Guru::whereNull('user_id');
        if (auth()->user() && !$isSuperAdmin && $schoolId) {
            $unregisteredGurusQuery->where('school_id', $schoolId);
        }
        $unregisteredGurus = $unregisteredGurusQuery->orderBy('nama')->get();

        // Ambil semua guru untuk pilihan generate spesifik / reset
        $allGurusQuery = Guru::query();
        if (auth()->user() && !$isSuperAdmin && $schoolId) {
            $allGurusQuery->where('school_id', $schoolId);
        }
        $allGurus = $allGurusQuery->orderBy('nama')->get();

        return view('users.index', compact('users', 'unregisteredGurus', 'allGurus'));
    }

    public function create()
    {
        $gurusQuery = Guru::orderBy('nama');
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $gurusQuery->where('school_id', auth()->user()->school_id);
        }
        $gurus = $gurusQuery->get();
        return view('users.create', compact('gurus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,super_admin,wali_kelas,waka_kurikulum,guru,teacher',
            'guru_id' => 'nullable|exists:guru,id'
        ]);

        $data = [
            'full_name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password_hash' => Hash::make($request->password),
            'plain_password' => $request->password,
            'role' => $request->role,
        ];

        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $data['school_id'] = auth()->user()->school_id;
        }

        $user = User::create($data);

        if (in_array($request->role, ['wali_kelas', 'waka_kurikulum', 'guru', 'teacher']) && $request->filled('guru_id')) {
            Guru::where('id', $request->guru_id)->update(['user_id' => $user->id]);
        }

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Export daftar akun pengguna ke PDF (Nama, Username, Password, Role)
     */
    public function exportPdf(Request $request)
    {
        $allowedRoles = ['admin', 'super_admin', 'wali_kelas', 'waka_kurikulum', 'guru', 'teacher'];
        $query = User::with('guru')->whereIn('role', $allowedRoles)->orderBy('role')->orderBy('full_name');

        $user = auth()->user();
        $schoolId = $user?->school_id;
        $isSuperAdmin = $user?->isSuperAdmin();

        if ($user && !$isSuperAdmin && $schoolId) {
            $query->where('school_id', $schoolId)
                  ->whereIn('role', ['admin', 'wali_kelas', 'waka_kurikulum', 'guru', 'teacher']);
        }

        // Filter role jika diminta
        if ($request->filled('role')) {
            if ($request->role === 'guru') {
                $query->whereIn('role', ['guru', 'teacher']);
            } else {
                $query->where('role', $request->role);
            }
        }

        // Filter kata kunci pencarian jika diminta
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('username', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $users = $query->get();

        $school = $user?->school;
        $schoolName = Setting::getVal('nama_sekolah', null, $schoolId) ?? ($school?->name ?? 'Sistem Absensi Jagat');
        $schoolAddress = Setting::getVal('alamat_sekolah', null, $schoolId) ?? ($school?->address ?? '');
        $kopSurat = Setting::getVal('kop_surat', null, $schoolId);

        $activeRole = $request->query('role');
        $roleTitle = match ($activeRole) {
            'guru' => 'Guru Pengajar',
            'wali_kelas' => 'Wali Kelas',
            'waka_kurikulum' => 'Waka Kurikulum',
            'admin' => 'Administrator',
            default => 'Semua Pengguna'
        };

        $pdf = Pdf::loadView('users.pdf', compact('users', 'schoolName', 'schoolAddress', 'kopSurat', 'activeRole', 'roleTitle', 'school'));
        $pdf->setPaper('a4', 'portrait');

        $filename = 'daftar-akun-' . Str::slug($roleTitle) . '-' . date('Ymd-His') . '.pdf';

        if ($request->boolean('stream')) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    public function edit(User $user)
    {
        $gurusQuery = Guru::orderBy('nama');
        if (auth()->user() && !auth()->user()->isSuperAdmin()) {
            $gurusQuery->where('school_id', auth()->user()->school_id);
        }
        $gurus = $gurusQuery->get();
        return view('users.edit', compact('user', 'gurus'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'email' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:4|confirmed',
            'role' => 'required|in:admin,super_admin,wali_kelas,waka_kurikulum,guru,teacher',
            'guru_id' => 'nullable|exists:guru,id'
        ]);

        $user->full_name = $request->name;
        $user->username = $request->username;
        $user->email = $request->email;
        $user->role = $request->role;

        if ($request->filled('password')) {
            $user->password_hash = Hash::make($request->password);
            $user->plain_password = $request->password;
        }

        $user->save();

        if (in_array($request->role, ['wali_kelas', 'waka_kurikulum', 'guru', 'teacher'])) {
            // Unlink previous guru if any
            Guru::where('user_id', $user->id)->update(['user_id' => null]);
            if ($request->filled('guru_id')) {
                Guru::where('id', $request->guru_id)->update(['user_id' => $user->id]);
            }
        }

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        Guru::where('user_id', $user->id)->update(['user_id' => null]);

        $user->delete();
        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $userIds = $request->user_ids;
        $currentUserId = auth()->id();

        // Remove current user from the list if present
        $userIds = array_filter($userIds, function ($id) use ($currentUserId) {
            return $id != $currentUserId;
        });

        if (empty($userIds)) {
            return back()->with('error', 'Tidak ada user yang dapat dihapus.');
        }

        Guru::whereIn('user_id', $userIds)->update(['user_id' => null]);

        $deletedCount = User::whereIn('id', $userIds)->delete();

        return redirect()->route('users.index')->with('success', "Berhasil menghapus {$deletedCount} user.");
    }

    /**
     * Generate username dari nama guru:
     * - Ambil nama sebelum tanda koma (karena setelah koma adalah gelar akademik).
     * - Batasi maks 2 kata dari nama yang digabung (identifikasi spasi).
     */
    public static function generateUsernameFromTeacherName(string $fullName): string
    {
        // 1. Ambil nama sebelum tanda koma
        $nameBeforeComma = explode(',', $fullName)[0];

        // 2. Hilangkan gelar kehormatan depan umum (Drs., Dra., Dr., Prof., Ir., H., Hj., K.H., Ust., dll) beruntun
        $cleanPrefix = preg_replace('/^((dr\.|drs\.|dra\.|prof\.|ir\.|h\.|hj\.|kh\.|k\.h\.|ust\.|ustadz\.)\s*)+/i', '', trim($nameBeforeComma));

        // 3. Hanya izinkan karakter alfanumerik dan spasi
        $onlyAlpha = preg_replace('/[^a-zA-Z0-9\s]/', '', $cleanPrefix);

        // 4. Split berdasarkan spasi dan filter elemen kosong
        $words = array_values(array_filter(explode(' ', trim($onlyAlpha)), fn($w) => strlen(trim($w)) > 0));

        // Fallback jika kosong
        if (empty($words)) {
            $fallbackAlpha = preg_replace('/[^a-zA-Z0-9]/', '', $nameBeforeComma);
            $words = !empty($fallbackAlpha) ? [$fallbackAlpha] : ['guru'];
        }

        // 5. Batasi hanya maks 2 kata pertama
        $selectedWords = array_slice($words, 0, 2);

        // 6. Gabungkan kata tanpa spasi, lowercase
        $username = strtolower(implode('', $selectedWords));

        return $username ?: 'guru';
    }

    /**
     * Fitur Generate Akun Guru Massal / Satuan
     */
    public function generateGuruAccounts(Request $request)
    {
        $request->validate([
            'mode' => 'required|in:all,selected',
            'guru_ids' => 'nullable|array',
            'guru_ids.*' => 'exists:guru,id',
            'reset_existing' => 'nullable|boolean',
        ]);

        $schoolId = auth()->user()?->school_id;
        $isSuperAdmin = auth()->user()?->isSuperAdmin();

        $query = Guru::query();
        if (!$isSuperAdmin && $schoolId) {
            $query->where('school_id', $schoolId);
        }

        $resetExisting = $request->boolean('reset_existing');

        if ($request->mode === 'selected') {
            if (empty($request->guru_ids)) {
                return back()->with('error', 'Silakan pilih minimal 1 guru untuk digenerate akun.');
            }
            $query->whereIn('id', $request->guru_ids);
        } else {
            // Mode all: default guru yang belum punya akun
            if (!$resetExisting) {
                $query->whereNull('user_id');
            }
        }

        $gurus = $query->orderBy('nama')->get();

        if ($gurus->isEmpty()) {
            return back()->with('error', 'Tidak ada data guru yang perlu diproses.');
        }

        $generatedAccounts = [];
        $countCreated = 0;
        $countReset = 0;

        foreach ($gurus as $guru) {
            // Password auto generate 8 karakter (mudah dibaca & aman)
            $plainPassword = strtolower(Str::random(4)) . rand(1000, 9999);

            // Jika guru sudah punya akun dan mode reset_existing aktif
            if ($guru->user_id && $existingUser = User::find($guru->user_id)) {
                if ($resetExisting) {
                    $existingUser->password_hash = Hash::make($plainPassword);
                    $existingUser->plain_password = $plainPassword;
                    // Pastikan rolenya minimal guru jika sebelumnya bukan admin
                    if (!in_array($existingUser->role, ['admin', 'super_admin', 'wali_kelas', 'waka_kurikulum'])) {
                        $existingUser->role = 'guru';
                    }
                    $existingUser->save();

                    $generatedAccounts[] = [
                        'nama' => $guru->nama,
                        'nip' => $guru->nip ?: '-',
                        'username' => $existingUser->username,
                        'email' => $existingUser->email,
                        'password' => $plainPassword,
                        'role' => $existingUser->role,
                        'status' => 'Password Direset',
                    ];
                    $countReset++;
                }
                continue;
            }

            // Buat username unik dengan aturan: nama sebelum koma, maks 2 kata digabung
            $baseUsername = self::generateUsernameFromTeacherName($guru->nama);
            $username = $baseUsername;
            $suffix = 1;
            while (User::where('username', $username)->exists()) {
                $username = $baseUsername . $suffix;
                $suffix++;
            }

            // Buat email unik
            $baseEmail = $username . '@guru.jagattech.my.id';
            $email = $baseEmail;
            $emailSuffix = 1;
            while (User::where('email', $email)->exists()) {
                $email = $username . $emailSuffix . '@guru.jagattech.my.id';
                $emailSuffix++;
            }

            // Buat User dengan role 'guru'
            $newUser = User::create([
                'full_name' => $guru->nama,
                'username' => $username,
                'email' => $email,
                'password_hash' => Hash::make($plainPassword),
                'plain_password' => $plainPassword,
                'role' => 'guru',
                'school_id' => $guru->school_id ?: $schoolId,
            ]);

            // Kaitkan guru ke user_id
            $guru->update(['user_id' => $newUser->id]);

            $generatedAccounts[] = [
                'nama' => $guru->nama,
                'nip' => $guru->nip ?: '-',
                'username' => $username,
                'email' => $email,
                'password' => $plainPassword,
                'role' => 'guru',
                'status' => 'Akun Baru',
            ];
            $countCreated++;
        }

        if (empty($generatedAccounts)) {
            return back()->with('info', 'Semua guru yang dipilih sudah memiliki akun user.');
        }

        $summaryMsg = "Berhasil memproses akun guru: {$countCreated} akun baru dibuat";
        if ($countReset > 0) {
            $summaryMsg .= ", {$countReset} password direset";
        }
        $summaryMsg .= ". Harap simpan / salin data login di bawah ini.";

        return redirect()->route('users.index')
            ->with('success', $summaryMsg)
            ->with('generated_accounts', $generatedAccounts);
    }
}
