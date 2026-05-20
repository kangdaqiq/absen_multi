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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Prevent dark mode flash --}}
    <script>
        (function () {
            const t = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
            if (t === 'dark') document.documentElement.classList.add('dark');
            document.addEventListener('DOMContentLoaded', () => {
                if (t === 'dark') document.body.classList.add('dark', 'bg-gray-900');
            });
        })();
    </script>

    <style>
        * { font-family: 'Plus Jakarta Sans', 'Outfit', sans-serif; }

        /* Left panel gradient */
        .left-panel {
            background: linear-gradient(145deg, #111827 0%, #1e3a8a 40%, #3730a3 70%, #4f46e5 100%);
        }

        /* Dot grid overlay */
        .dot-grid {
            background-image: radial-gradient(circle, rgba(255,255,255,0.07) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        /* Logo glow */
        .logo-glow {
            box-shadow: 0 0 0 1px rgba(255,255,255,0.15), 0 25px 60px rgba(0,0,0,0.4), 0 0 80px rgba(99,102,241,0.3);
        }

        /* Pill tag */
        .feature-pill {
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.12);
            backdrop-filter: blur(8px);
        }

        /* Shimmer on logo */
        .logo-ring {
            background: conic-gradient(from 180deg, rgba(255,255,255,0.15), rgba(255,255,255,0.03), rgba(255,255,255,0.15));
        }

        /* Form panel */
        .right-panel { background: #f9fafb; }
        .dark .right-panel { background: #111827; }

        /* Input */
        .form-input {
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.12);
        }

        /* Submit button */
        .btn-submit {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
            transition: all 0.2s;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #4338ca, #6d28d9);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.5);
            transform: translateY(-1px);
        }
        .btn-submit:active { transform: translateY(0); }

        /* Animate blobs */
        @keyframes floatBlob {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-20px) scale(1.05); }
        }
        .blob-animate { animation: floatBlob 8s ease-in-out infinite; }
        .blob-animate-2 { animation: floatBlob 10s ease-in-out infinite reverse; }
    </style>
</head>

<body class="min-h-screen bg-gray-50 dark:bg-gray-900" x-data="{ showPass: false }">

    <div class="min-h-screen lg:grid lg:grid-cols-2">

        {{-- ══════════════════════════════════════════
             LEFT PANEL — Branding
        ══════════════════════════════════════════ --}}
        <div class="hidden lg:flex flex-col items-center justify-center relative overflow-hidden left-panel">

            {{-- Dot grid --}}
            <div class="dot-grid absolute inset-0 pointer-events-none"></div>

            {{-- Ambient blobs --}}
            <div class="blob-animate absolute top-[-80px] right-[-80px] w-[420px] h-[420px] rounded-full pointer-events-none"
                 style="background: radial-gradient(circle, rgba(139,92,246,0.25) 0%, transparent 70%);"></div>
            <div class="blob-animate-2 absolute bottom-[-100px] left-[-100px] w-[400px] h-[400px] rounded-full pointer-events-none"
                 style="background: radial-gradient(circle, rgba(59,130,246,0.2) 0%, transparent 70%);"></div>
            <div class="absolute top-1/2 right-[-50px] w-64 h-64 rounded-full pointer-events-none"
                 style="background: radial-gradient(circle, rgba(16,185,129,0.1) 0%, transparent 70%); transform: translateY(-50%);"></div>

            @if(isset($school))
                {{-- ── SCHOOL MODE ── --}}
                <div class="relative z-10 flex flex-col items-center text-center gap-8 px-14 w-full max-w-md">

                    {{-- Logo --}}
                    <div class="relative flex-shrink-0">
                        {{-- Outer glow ring --}}
                        <div class="absolute inset-0 rounded-[2rem] blur-2xl opacity-60 scale-110 logo-ring"
                             style="background: rgba(255,255,255,0.15);"></div>
                        {{-- White container --}}
                        <div class="relative w-40 h-40 bg-white rounded-[1.75rem] logo-glow flex items-center justify-center p-4">
                            @if($school->logo)
                                <img src="{{ asset('storage/' . $school->logo) }}"
                                     alt="{{ $school->name }}"
                                     class="w-full h-full object-contain">
                            @else
                                <div class="w-full h-full rounded-2xl flex items-center justify-center"
                                     style="background: linear-gradient(135deg, #ede9fe, #ddd6fe);">
                                    <i class="fas fa-school text-5xl" style="color: #6d28d9;"></i>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- School name --}}
                    <div class="space-y-3">
                        <h2 class="text-[1.65rem] font-extrabold text-white leading-tight tracking-tight">
                            {{ $school->name }}
                        </h2>
                        <div class="flex items-center justify-center gap-3">
                            <span class="block h-px w-10 rounded-full" style="background: rgba(255,255,255,0.2);"></span>
                            <span class="text-[10px] font-semibold tracking-[0.2em] uppercase text-white/40">
                                Portal Resmi
                            </span>
                            <span class="block h-px w-10 rounded-full" style="background: rgba(255,255,255,0.2);"></span>
                        </div>
                    </div>

                    {{-- Feature pills --}}
                    <div class="flex flex-wrap justify-center gap-2">
                        <span class="feature-pill flex items-center gap-1.5 px-3.5 py-2 rounded-full text-white/70 text-xs font-medium">
                            <i class="fas fa-fingerprint text-violet-300 text-xs"></i>
                            Fingerprint
                        </span>
                        <span class="feature-pill flex items-center gap-1.5 px-3.5 py-2 rounded-full text-white/70 text-xs font-medium">
                            <i class="fas fa-id-card text-blue-300 text-xs"></i>
                            RFID
                        </span>
                        <span class="feature-pill flex items-center gap-1.5 px-3.5 py-2 rounded-full text-white/70 text-xs font-medium">
                            <i class="fab fa-whatsapp text-green-300 text-xs"></i>
                            WhatsApp
                        </span>
                        <span class="feature-pill flex items-center gap-1.5 px-3.5 py-2 rounded-full text-white/70 text-xs font-medium">
                            <i class="fas fa-chart-bar text-sky-300 text-xs"></i>
                            Real-time
                        </span>
                    </div>

                    {{-- Bottom tagline --}}
                    <p class="text-xs text-white/35 leading-relaxed max-w-[260px]">
                        Sistem pencatatan kehadiran otomatis berbasis biometrik & RFID
                    </p>
                </div>

            @else
                {{-- ── DEFAULT BRANDING MODE ── --}}
                <div class="relative z-10 flex flex-col justify-between h-full w-full px-12 py-14">
                    {{-- Logo top --}}
                    <div>
                        <img src="/images/logo/logo-dark.svg" alt="Jagat Tech" class="h-9 w-auto">
                    </div>

                    {{-- Center --}}
                    <div class="text-center">
                        <div class="mb-8 inline-flex h-24 w-24 items-center justify-center rounded-3xl"
                             style="background: rgba(255,255,255,0.12); backdrop-filter: blur(12px);">
                            <i class="fas fa-fingerprint text-5xl text-white"></i>
                        </div>
                        <h2 class="mb-4 text-4xl font-bold text-white leading-tight tracking-tight">
                            Pantau Kehadiran<br>dengan Mudah
                        </h2>
                        <p class="text-base text-white/60 max-w-xs mx-auto leading-relaxed">
                            Sistem absensi digital berbasis RFID &amp; fingerprint — otomatis, akurat, real-time.
                        </p>
                    </div>

                    {{-- Stats bottom --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-2xl p-4 text-center" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1);">
                            <p class="text-xl font-bold text-white">Aman</p>
                            <p class="text-[11px] text-white/50 mt-1">Anti-Pemalsuan</p>
                        </div>
                        <div class="rounded-2xl p-4 text-center" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1);">
                            <p class="text-xl font-bold text-white">Tepat</p>
                            <p class="text-[11px] text-white/50 mt-1">Akurasi Tinggi</p>
                        </div>
                        <div class="rounded-2xl p-4 text-center" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.1);">
                            <p class="text-xl font-bold text-white">Auto</p>
                            <p class="text-[11px] text-white/50 mt-1">Notifikasi WA</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ══════════════════════════════════════════
             RIGHT PANEL — Login Form
        ══════════════════════════════════════════ --}}
        <div class="right-panel flex flex-col items-center justify-center px-6 py-12 sm:px-12">

            {{-- Mobile logo --}}
            <div class="mb-8 lg:hidden flex flex-col items-center gap-3">
                @if(isset($school) && $school->logo)
                    <img src="{{ asset('storage/' . $school->logo) }}" alt="{{ $school->name }}"
                         class="h-16 w-auto object-contain bg-white dark:bg-gray-800 p-2 rounded-xl shadow-md border border-gray-100 dark:border-gray-700">
                    <span class="text-base font-bold text-gray-800 dark:text-white">{{ $school->name }}</span>
                @else
                    <img src="/images/logo/logo.svg" alt="Jagat Tech" class="h-12 w-auto">
                @endif
            </div>

            <div class="w-full max-w-md">

                {{-- Heading --}}
                <div class="mb-8">
                    <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                        Selamat Datang 👋
                    </h1>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        {{ isset($school) ? 'Silakan masuk menggunakan akun Anda.' : 'Masukkan kredensial Anda untuk mengakses dashboard.' }}
                    </p>
                </div>

                {{-- Error alert --}}
                @if ($errors->any())
                    <div class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 dark:border-red-500/20 dark:bg-red-500/10">
                        <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-500 text-white">
                            <i class="fas fa-times text-xs"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-red-700 dark:text-red-400">Login gagal</p>
                            @foreach ($errors->all() as $error)
                                <p class="mt-0.5 text-sm text-red-600 dark:text-red-400">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Form --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Email / Username
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                <i class="fas fa-user text-sm"></i>
                            </span>
                            <input type="text" id="email" name="email" value="{{ old('email') }}"
                                placeholder="Masukkan email atau username" required autofocus
                                class="form-input w-full rounded-xl border border-gray-200 bg-white py-3 pl-11 pr-4 text-sm text-gray-800 placeholder-gray-400 outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500 @error('email') border-red-300 @enderror">
                        </div>
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                            Password
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                                <i class="fas fa-lock text-sm"></i>
                            </span>
                            <input :type="showPass ? 'text' : 'password'" id="password" name="password"
                                placeholder="Masukkan password" required
                                class="form-input w-full rounded-xl border border-gray-200 bg-white py-3 pl-11 pr-12 text-sm text-gray-800 placeholder-gray-400 outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500">
                            <button type="button" @click="showPass = !showPass"
                                class="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition"
                                tabindex="-1">
                                <i :class="showPass ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-sm"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button type="submit" class="btn-submit flex w-full items-center justify-center gap-2 rounded-xl px-6 py-3.5 text-sm font-bold text-white">
                        <i class="fas fa-sign-in-alt"></i>
                        Masuk ke Dashboard
                    </button>
                </form>

                {{-- Footer --}}
                <p class="mt-8 text-center text-xs text-gray-400 dark:text-gray-600">
                    <i class="fas fa-shield-alt mr-1 text-indigo-400"></i>
                    Koneksi aman &amp; terenkripsi — Sistem Absensi v2.0
                </p>
            </div>
        </div>

    </div>

</body>
</html>