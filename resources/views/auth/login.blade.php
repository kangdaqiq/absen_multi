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

    {{-- Google Fonts: Inter for clean readable text --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

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

    <style>
        body { font-family: 'Inter', 'Outfit', sans-serif; }
        .school-name-text { font-family: 'Inter', sans-serif; }
    </style>
</head>

<body class="min-h-screen bg-gray-50 dark:bg-gray-900" x-data="{ showPass: false }">

    {{-- ─── LAYOUT: split screen ─── --}}
    <div class="min-h-screen lg:grid lg:grid-cols-2">

        {{-- ── LEFT PANEL — Branding ── --}}
        <div class="hidden lg:flex flex-col bg-brand-600 dark:bg-gray-dark relative overflow-hidden
            @if(isset($school)) items-center justify-center @else justify-between p-12 @endif">

            {{-- Decorative blobs --}}
            <div class="pointer-events-none absolute -top-40 -left-40 w-[28rem] h-[28rem] rounded-full bg-white/10 blur-[80px]"></div>
            <div class="pointer-events-none absolute -bottom-40 -right-40 w-[28rem] h-[28rem] rounded-full bg-white/10 blur-[80px]"></div>
            <div class="pointer-events-none absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] rounded-full bg-white/5 blur-[100px]"></div>

            @if(isset($school))
                {{-- ── SCHOOL BRANDING: Simple centered column ── --}}
                <div class="relative z-10 flex flex-col items-center text-center gap-6 px-12 w-full max-w-md">

                    {{-- Logo --}}
                    @if($school->logo)
                        <div class="w-32 h-32 rounded-2xl bg-white shadow-2xl flex items-center justify-center flex-shrink-0 p-3">
                            <img src="{{ asset('storage/' . $school->logo) }}"
                                 alt="{{ $school->name }}"
                                 class="w-full h-full object-contain">
                        </div>
                    @else
                        <div class="w-24 h-24 rounded-2xl bg-white/20 flex items-center justify-center">
                            <i class="fas fa-school text-4xl text-white"></i>
                        </div>
                    @endif

                    {{-- School name --}}
                    <div class="space-y-2">
                        <h2 class="school-name-text text-xl font-bold text-white leading-tight tracking-normal">
                            {{ $school->name }}
                        </h2>
                        <p class="text-xs font-medium text-white/50 tracking-widest uppercase">
                            Sistem Absensi Digital
                        </p>
                    </div>

                    {{-- Thin separator --}}
                    <div class="w-8 h-0.5 bg-white/20 rounded-full"></div>

                    {{-- Short tagline --}}
                    <p class="text-sm text-white/60 leading-relaxed">
                        Silakan masuk menggunakan akun Anda untuk mengakses sistem absensi.
                    </p>
                </div>

            @else
                {{-- ── DEFAULT BRANDING ── --}}
                <div class="relative z-10">
                    <img src="/images/logo/logo-dark.svg" alt="Jagat Tech" class="h-10 w-auto">
                </div>

                <div class="relative z-10 text-center">
                    <div class="mb-6 inline-flex h-24 w-24 items-center justify-center rounded-3xl bg-white/20 backdrop-blur-sm">
                        <i class="fas fa-fingerprint text-5xl text-white"></i>
                    </div>
                    <h2 class="mb-4 text-4xl font-bold text-white leading-tight">
                        Pantau Kehadiran<br>dengan Mudah
                    </h2>
                    <p class="text-lg text-white/70 max-w-sm mx-auto">
                        Sistem absensi digital berbasis RFID &amp; fingerprint untuk mencatat kehadiran secara otomatis dan real-time.
                    </p>
                </div>

                <div class="relative z-10 grid grid-cols-3 gap-4">
                    <div class="rounded-2xl bg-white/10 backdrop-blur-sm p-4 text-center">
                        <p class="text-2xl font-bold text-white">Aman</p>
                        <p class="text-xs text-white/60 mt-1">Anti-Pemalsuan</p>
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
                    Koneksi aman &amp; terenkripsi &mdash; Sistem Absensi v2.0
                </p>
            </div>
        </div>
    </div>

</body>

</html>