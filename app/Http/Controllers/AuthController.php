<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        $host = strtolower($request->getHost());
        $school = null;

        // Cari sekolah yang aktif dengan domain/subdomain ini (mengabaikan www.)
        $normalizedHost = str_replace('www.', '', $host);
        
        $school = \App\Models\School::where('is_active', true)
            ->where(function($query) use ($normalizedHost) {
                $query->where('domain', $normalizedHost);
            })
            ->first();

        // Fallback untuk self-hosted mode: gunakan sekolah pertama yang aktif
        if (!$school && config('app.mode') === 'self_hosted') {
            $school = \App\Models\School::where('is_active', true)->first();
        }

        return view('auth.login', compact('school'));
    }

    public function login(Request $request)
    {
        $input = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $loginField = $input['email'];
        $password = $input['password'];

        $throttleKey = Str::transliterate(Str::lower($loginField).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$seconds} detik.",
            ])->onlyInput('email');
        }

        // 1. Try to login with email
        if (Auth::attempt(['email' => $loginField, 'password' => $password])) {
            return $this->handleSuccessfulLogin($request, $throttleKey);
        }

        // 2. Try with exact username
        if (Auth::attempt(['username' => $loginField, 'password' => $password])) {
            return $this->handleSuccessfulLogin($request, $throttleKey);
        }

        // 3. Try with prefix 'siswa_' in username (e.g. user typed NIS as username)
        if (Auth::attempt(['username' => 'siswa_' . $loginField, 'password' => $password])) {
            return $this->handleSuccessfulLogin($request, $throttleKey);
        }

        // 4. Cek apakah login sebagai Siswa menggunakan NIS / NISN dan Tanggal Lahir (sama seperti Mobile App)
        try {
            $siswa = \App\Models\Siswa::with(['kelas', 'school'])
                ->where(function ($q) use ($loginField) {
                    $q->where('nis', $loginField);
                    if (\Illuminate\Support\Facades\Schema::hasColumn('siswa', 'nisn')) {
                        $q->orWhere('nisn', $loginField);
                    }
                })
                ->first();

            if ($siswa && !empty($siswa->tgl_lahir)) {
                $birthDate = \Carbon\Carbon::parse($siswa->tgl_lahir);
                $cleanInputPass = preg_replace('/[^0-9]/', '', $password);

                $validFormats = [
                    $birthDate->format('Y-m-d'),   // 2008-05-15
                    $birthDate->format('d-m-Y'),   // 15-05-2008
                    $birthDate->format('Y/m/d'),   // 2008/05/15
                    $birthDate->format('d/m/Y'),   // 15/05/2008
                    $birthDate->format('Ymd'),     // 20080515
                    $birthDate->format('dmY'),     // 15052008
                ];

                $isBirthDateValid = in_array($password, $validFormats) ||
                                    in_array($cleanInputPass, [$birthDate->format('Ymd'), $birthDate->format('dmY')]);

                if ($isBirthDateValid) {
                    // Cari atau buat User untuk siswa
                    $user = null;
                    if (\Illuminate\Support\Facades\Schema::hasColumn('siswa', 'user_id') && $siswa->user_id) {
                        $user = \App\Models\User::find($siswa->user_id);
                    }

                    if (!$user) {
                        $user = \App\Models\User::where('username', 'siswa_' . $siswa->nis)
                            ->orWhere('username', $siswa->nis)
                            ->first();
                    }

                    if (!$user) {
                        $user = \App\Models\User::create([
                            'full_name' => $siswa->nama,
                            'username' => 'siswa_' . $siswa->nis,
                            'email' => $siswa->nis . '@siswa.local',
                            'password_hash' => \Illuminate\Support\Facades\Hash::make($password),
                            'role' => 'student',
                            'school_id' => $siswa->school_id,
                        ]);
                    } else {
                        $user->role = 'student';
                        $user->school_id = $siswa->school_id;
                        $user->password_hash = \Illuminate\Support\Facades\Hash::make($password);
                        $user->save();
                    }

                    if (\Illuminate\Support\Facades\Schema::hasColumn('siswa', 'user_id') && $siswa->user_id !== $user->id) {
                        $siswa->user_id = $user->id;
                        $siswa->save();
                    }

                    Auth::login($user);
                    return $this->handleSuccessfulLogin($request, $throttleKey);
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Web student login error: " . $e->getMessage());
        }

        RateLimiter::hit($throttleKey);

        return back()->withErrors([
            'email' => 'Email/Username/NIS atau Password salah.',
        ])->onlyInput('email');
    }

    /**
     * Helper handling common post-login actions (school active check & redirection)
     */
    protected function handleSuccessfulLogin(Request $request, string $throttleKey)
    {
        $user = Auth::user();
        $request->session()->regenerate();

        // Check if user's school is active (skip for super admin)
        if ($user->school_id && $user->school) {
            if (!$user->school->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors([
                    'email' => 'Sekolah Anda sedang nonaktif. Hubungi Administrator untuk informasi lebih lanjut.',
                ])->onlyInput('email');
            }
        }

        // Jika user adalah siswa, pastikan relasi siswa.user_id tersinkronisasi
        if ($user->role === 'student') {
            $siswa = $user->student;
            if (!$siswa) {
                $cleanNis = str_replace('siswa_', '', $user->username);
                $siswa = \App\Models\Siswa::where('user_id', $user->id)
                    ->orWhere('nis', $cleanNis)
                    ->first();
                if ($siswa && \Illuminate\Support\Facades\Schema::hasColumn('siswa', 'user_id')) {
                    $siswa->user_id = $user->id;
                    $siswa->save();
                }
            }
        }

        RateLimiter::clear($throttleKey);

        // Redirect based on role
        if ($user->role === 'super_admin') {
            return redirect()->intended(route('super-admin.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
