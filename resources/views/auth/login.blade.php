<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($school) ? $school->name . ' — Sistem Absensi' : 'Login — Sistem Absensi' }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo/logo-icon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">

    {{-- Prevent dark mode flash --}}
    <script>
        (function () {
            const t = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            if (t === 'dark') {
                document.documentElement.classList.add('dark');
            }

            document.addEventListener('DOMContentLoaded', () => {
                if (t === 'dark') {
                    document.body.classList.add('dark', 'bg-gray-900');
                }
            });
        })();
    </script>
</head>

<body class="min-h-screen bg-gray-50 dark:bg-gray-900 font-outfit" x-data="{ showPass: false }">

    {{-- ─── LAYOUT: split screen ─── --}}
    <div class="min-h-screen lg:grid lg:grid-cols-2">

        {{-- ── LEFT PANEL — Branding ── --}}
        <div
            class="hidden lg:flex flex-col {{ isset($school) ? 'justify-center items-center' : 'justify-between' }} bg-brand-600 dark:bg-gray-dark p-12 relative overflow-hidden">
            {{-- Decorative blobs --}}
            <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-white/10 blur-3xl"></div>
            <div class="absolute -bottom-24 -right-24 w-80 h-80 rounded-full bg-white/10 blur-3xl"></div>
            <div
                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] rounded-full bg-white/5 blur-3xl">
            </div>

            {{-- Logo --}}
            @if(!isset($school))
                <div class="relative z-10">
                    <img src="/images/logo/logo-dark.svg" alt="Jagat Tech" class="h-10 w-auto">
                </div>
            @endif

            {{-- Center content / Premium Card --}}
            <div class="relative z-10 w-full flex flex-col items-center justify-center">
                @if(isset($school))
                    {{-- Premium School Portal Card --}}
                    <div class="max-w-md w-full bg-white/10 backdrop-blur-xl rounded-[2rem] p-8 border border-white/20 shadow-2xl flex flex-col items-center text-center mt-12 relative hover:scale-[1.02] transition-all duration-500 ease-out">
                        
                        {{-- Floating Logo Badge --}}
                        <div class="w-32 h-32 flex items-center justify-center rounded-3xl bg-white shadow-xl p-4 absolute -top-16 border border-white/30 transform hover:rotate-3 transition-transform duration-300">
                            @if($school->logo)
                                <img src="{{ asset('storage/' . $school->logo) }}" alt="{{ $school->name }}" class="max-h-full max-w-full object-contain">
                            @else
                                <div class="w-full h-full rounded-2xl bg-brand-500/10 flex items-center justify-center">
                                    <i class="fas fa-school text-4xl text-brand-500"></i>
                                </div>
                            @endif
                        </div>

                        {{-- Card Header & Badge --}}
                        <div class="pt-16 space-y-4">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 border border-white/20 text-white text-xs font-medium uppercase tracking-widest">
                                <span class="w-1.5 h-1.5 rounded-full bg-success-400 animate-pulse"></span>
                                Portal Resmi Sekolah
                            </span>
                            
                            <h2 class="text-xl sm:text-2xl font-black text-white tracking-wide uppercase leading-tight font-outfit">
                                {{ $school->name }}
                            </h2>
                            
                            <p class="text-xs font-semibold text-brand-200 tracking-wider uppercase font-outfit">
                                Sistem Absensi Terintegrasi
                            </p>
                        </div>

                        {{-- Divider --}}
                        <div class="w-16 h-0.5 bg-gradient-to-r from-transparent via-white/30 to-transparent my-6"></div>

                        {{-- Welcome message --}}
                        <p class="text-sm text-white/80 leading-relaxed font-outfit">
                            Selamat datang di aplikasi absensi digital terpadu. Silakan gunakan kredensial Anda pada panel login di sebelah kanan untuk mengakses dasbor.
                        </p>

                        {{-- Features Grid inside Card --}}
                        <div class="w-full grid grid-cols-3 gap-3 pt-6 mt-6 border-t border-white/10">
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center mb-1 text-white">
                                    <i class="fas fa-shield-alt text-sm"></i>
                                </div>
                                <span class="text-xs font-bold text-white">Aman</span>
                                <span class="text-[9px] text-white/50 mt-0.5 leading-none">Anti-Fraud</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center mb-1 text-white">
                                    <i class="fas fa-bolt text-sm"></i>
                                </div>
                                <span class="text-xs font-bold text-white">Cepat</span>
                                <span class="text-[9px] text-white/50 mt-0.5 leading-none">Real-time</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <div class="w-8 h-8 rounded-lg bg-white/5 flex items-center justify-center mb-1 text-white">
                                    <i class="fas fa-bell text-sm"></i>
                                </div>
                                <span class="text-xs font-bold text-white">Notifikasi</span>
                                <span class="text-[9px] text-white/50 mt-0.5 leading-none">WhatsApp</span>
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Default Branding Mode --}}
                    <div class="mb-6 inline-flex h-28 w-28 items-center justify-center rounded-3xl bg-white/20 backdrop-blur-sm p-4">
                        <i class="fas fa-fingerprint text-5xl text-white"></i>
                    </div>
                    <h2 class="mb-4 text-4xl font-bold text-white leading-tight">
                        Pantau Kehadiran<br>dengan Mudah
                    </h2>
                    <p class="text-lg text-white/70 max-w-sm mx-auto">
                        Sistem absensi digital berbasis RFID & fingerprint untuk mencatat kehadiran secara otomatis dan real-time.
                    </p>
                @endif
            </div>

            {{-- Stats (Only for Default Branding) --}}
            @if(!isset($school))
                <div class="relative z-10 grid grid-cols-3 gap-4">
                    <div class="rounded-2xl bg-white/10 backdrop-blur-sm p-4 text-center">
                        <p class="text-2xl font-bold text-white">Aman</p>
                        <p class="text-xs text-white/60 mt-1">Tidak Bisa Dipalusukan</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 backdrop-blur-sm p-4 text-center">
                        <p class="text-2xl font-bold text-white">Tepat</p>
                        <p class="text-xs text-white/60 mt-1">Akurasi Tinggi</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 backdrop-blur-sm p-4 text-center">
                        <p class="text-2xl font-bold text-white">Auto</p>
                        <p class="text-xs text-white/60 mt-1">Notifikasi WA</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- ── RIGHT PANEL — Login Form ── --}}
        <div class="flex flex-col items-center justify-center px-6 py-12 sm:px-12">

            {{-- Mobile logo --}}
            <div class="mb-8 lg:hidden flex flex-col items-center gap-3">
                @if(isset($school) && $school->logo)
                    <img src="{{ asset('storage/' . $school->logo) }}" alt="{{ $school->name }}" class="h-16 w-auto object-contain bg-white dark:bg-gray-800 p-2 rounded-xl shadow-md border border-gray-100 dark:border-gray-700">
                    <span class="text-base font-bold text-gray-800 dark:text-white">{{ $school->name }}</span>
                @else
                    <img src="/images/logo/logo.svg" alt="Jagat Tech" class="h-12 w-auto">
                @endif
            </div>

            <div class="w-full max-w-md">
                {{-- Heading --}}
                <div class="mb-6 sm:mb-8 text-center sm:text-left">
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-white/90">
                        Selamat Datang 👋
                    </h1>
                    <p class="mt-1.5 sm:mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ isset($school) ? 'Silakan masuk menggunakan akun Anda' : 'Masukkan kredensial Anda untuk mengakses dashboard' }}
                    </p>
                </div>

                {{-- Error alert --}}
                @if ($errors->any())
                    <div
                        class="mb-6 flex items-start gap-3 rounded-xl border border-error-200 bg-error-50 px-4 py-3 dark:border-error-500/20 dark:bg-error-500/10">
                        <div
                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-error-500 text-white">
                            <i class="fas fa-times text-xs"></i>
                        </div>
                        <div>
                            <p class="font-medium text-error-700 dark:text-error-400">Login gagal</p>
                            @foreach ($errors->all() as $error)
                                <p class="mt-0.5 text-sm text-error-600 dark:text-error-400">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Form --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    {{-- Email / Username --}}
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Email / Username
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                <i class="fas fa-user text-sm"></i>
                            </span>
                            <input type="text" id="email" name="email" value="{{ old('email') }}"
                                placeholder="email@sekolah.com atau username" required autofocus
                                class="w-full rounded-xl border border-gray-200 bg-white py-3 pl-11 pr-4 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-800 dark:bg-gray-900 dark:text-white dark:placeholder-gray-600 dark:focus:border-brand-500 @error('email') border-error-300 focus:border-error-500 dark:border-error-500/50 @enderror">
                        </div>
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Password
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                <i class="fas fa-lock text-sm"></i>
                            </span>
                            <input :type="showPass ? 'text' : 'password'" id="password" name="password"
                                placeholder="Masukkan password" required
                                class="w-full rounded-xl border border-gray-200 bg-white py-3 pl-11 pr-12 text-sm text-gray-800 placeholder-gray-400 outline-none transition focus:border-brand-500 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-800 dark:bg-gray-900 dark:text-white dark:placeholder-gray-600 dark:focus:border-brand-500">
                            <button type="button" @click="showPass = !showPass"
                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition"
                                tabindex="-1">
                                <i :class="showPass ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-sm"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-600 focus:outline-none focus:ring-3 focus:ring-brand-500/30 active:scale-[0.98]">
                        <i class="fas fa-sign-in-alt"></i>
                        Masuk ke Dashboard
                    </button>
                </form>

                {{-- Footer --}}
                <p class="mt-8 text-center text-xs text-gray-400 dark:text-gray-600">
                    <i class="fas fa-shield-alt mr-1 text-brand-400"></i>
                    Koneksi aman & terenkripsi &mdash; Sistem Absensi v2.0
                </p>
            </div>
        </div>
    </div>

</body>

</html>