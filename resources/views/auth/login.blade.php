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
                if (t === 'dark') document.body.classList.add('dark', 'bg-slate-950');
            });
        })();
    </script>

    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        /* Modern background grid */
        .glass-bg {
            background-color: #f8fafc;
            transition: background-color 0.5s ease;
        }
        .dark .glass-bg {
            background-color: #080c14;
        }

        .mesh-grid {
            background-image: radial-gradient(rgba(79, 70, 229, 0.04) 1.5px, transparent 1.5px), radial-gradient(rgba(79, 70, 229, 0.04) 1.5px, transparent 1.5px);
            background-size: 32px 32px;
            background-position: 0 0, 16px 16px;
        }
        .dark .mesh-grid {
            background-image: radial-gradient(rgba(129, 140, 248, 0.035) 1.5px, transparent 1.5px), radial-gradient(rgba(129, 140, 248, 0.035) 1.5px, transparent 1.5px);
        }

        /* Animated organic background blobs */
        @keyframes floatBlob1 {
            0%, 100% { transform: translate(0px, 0px) scale(1); }
            33% { transform: translate(60px, -80px) scale(1.15); }
            66% { transform: translate(-40px, 40px) scale(0.9); }
        }
        @keyframes floatBlob2 {
            0%, 100% { transform: translate(0px, 0px) scale(1); }
            50% { transform: translate(-70px, 90px) scale(1.2); }
        }
        @keyframes floatBlob3 {
            0%, 100% { transform: translate(0px, 0px) scale(1); }
            40% { transform: translate(50px, 60px) scale(0.85); }
        }
        
        .blob-1 { animation: floatBlob1 25s ease-in-out infinite; }
        .blob-2 { animation: floatBlob2 30s ease-in-out infinite alternate; }
        .blob-3 { animation: floatBlob3 22s ease-in-out infinite alternate-reverse; }

        /* Frosted Glassmorphism Panels */
        .glass-panel-light {
            background: rgba(255, 255, 255, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.03), inset 0 1px 0 rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .dark .glass-panel-light {
            background: rgba(15, 23, 42, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .premium-card {
            transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .premium-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 30px 70px rgba(79, 70, 229, 0.12);
            border-color: rgba(255, 255, 255, 0.55);
        }
        .dark .premium-card:hover {
            box-shadow: 0 30px 70px rgba(99, 102, 241, 0.18);
            border-color: rgba(255, 255, 255, 0.12);
        }

        /* Squircle frame for logo */
        .squircle-frame {
            position: relative;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.75), rgba(255, 255, 255, 0.45));
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: inset 0 2px 4px rgba(255, 255, 255, 0.4), 0 12px 30px rgba(0, 0, 0, 0.04);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .dark .squircle-frame {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.4));
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: inset 0 1.5px 2px rgba(255, 255, 255, 0.05), 0 12px 35px rgba(0, 0, 0, 0.25);
        }
        .squircle-frame:hover {
            transform: translateY(-3px) scale(1.05);
            border-color: rgba(79, 70, 229, 0.45);
            box-shadow: 0 20px 40px rgba(79, 70, 229, 0.2);
        }
        .dark .squircle-frame:hover {
            border-color: rgba(129, 140, 248, 0.45);
            box-shadow: 0 20px 40px rgba(129, 140, 248, 0.25);
        }

        /* Glass badging */
        .badge-glass {
            background: rgba(255, 255, 255, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        .dark .badge-glass {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.06);
        }
        .badge-glass:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.7);
            border-color: rgba(79, 70, 229, 0.35);
            box-shadow: 0 6px 15px rgba(79, 70, 229, 0.08);
        }
        .dark .badge-glass:hover {
            background: rgba(15, 23, 42, 0.6);
            border-color: rgba(129, 140, 248, 0.35);
            box-shadow: 0 6px 15px rgba(129, 140, 248, 0.15);
        }

        /* Input highlight */
        .input-box {
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .input-box:focus-within {
            border-color: #4f46e5;
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12), 0 4px 10px rgba(0, 0, 0, 0.01);
        }
        .dark .input-box:focus-within {
            border-color: #818cf8;
            background: rgba(15, 23, 42, 0.75);
            box-shadow: 0 0 0 4px rgba(129, 140, 248, 0.2), 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Premium gradient submit button */
        .btn-submit-premium {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 55%, #ec4899 100%);
            background-size: 200% auto;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 25px -4px rgba(79, 70, 229, 0.35);
        }
        .btn-submit-premium:hover {
            background-position: right center;
            box-shadow: 0 18px 35px -4px rgba(124, 58, 237, 0.45);
            transform: translateY(-2px);
        }
        .btn-submit-premium:active {
            transform: translateY(0px);
            box-shadow: 0 8px 20px -4px rgba(124, 58, 237, 0.35);
        }

        /* Pulse Beacon */
        .pulse-beacon {
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.5);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }
    </style>
</head>

<body class="min-h-screen relative overflow-x-hidden glass-bg mesh-grid flex items-center justify-center py-10 px-4 sm:px-6 lg:px-8 z-0" x-data="{ showPass: false }">

    {{-- Ambient glowing mesh blobs in the background --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-[-1] select-none">
        <div class="blob-1 absolute top-[-10%] left-[-15%] w-[50vw] h-[50vw] max-w-[650px] max-h-[650px] rounded-full blur-[110px]"
             style="background: radial-gradient(circle, rgba(99,102,241,0.18) 0%, rgba(236,72,153,0.06) 60%, transparent 100%);"></div>
        <div class="blob-2 absolute bottom-[-10%] right-[-10%] w-[45vw] h-[45vw] max-w-[550px] max-h-[550px] rounded-full blur-[100px]"
             style="background: radial-gradient(circle, rgba(59,130,246,0.16) 0%, rgba(139,92,246,0.08) 60%, transparent 100%);"></div>
        <div class="blob-3 absolute top-[30%] right-[-15%] w-[35vw] h-[35vw] max-w-[450px] max-h-[450px] rounded-full blur-[90px]"
             style="background: radial-gradient(circle, rgba(236,72,153,0.1) 0%, rgba(79,70,229,0.06) 70%, transparent 100%);"></div>
    </div>

    {{-- Main Container Card --}}
    <div class="w-full max-w-6xl glass-panel-light rounded-[2.5rem] shadow-[0_30px_80px_rgba(0,0,0,0.04)] dark:shadow-[0_30px_80px_rgba(0,0,0,0.4)] overflow-hidden lg:grid lg:grid-cols-12 min-h-[680px] z-10 border border-white/20 dark:border-white/5">

        {{-- ══════════════════════════════════════════
             LEFT PANEL — Branding & Graphics (5 Cols)
        ══════════════════════════════════════════ --}}
        <div class="hidden lg:flex lg:col-span-5 flex-col justify-between p-12 relative overflow-hidden border-r border-white/20 dark:border-white/5 bg-slate-900/5 dark:bg-slate-950/20">
            
            {{-- Extra subtle overlay glow inside branding --}}
            <div class="absolute inset-0 pointer-events-none select-none">
                <div class="absolute top-[20%] left-[20%] w-56 h-56 rounded-full bg-indigo-500/10 blur-[80px]"></div>
            </div>

            @if(isset($school))
                {{-- ── TENANT BRANDING (SCHOOL / OFFICE) ── --}}
                <div class="relative z-10 flex flex-col items-center justify-center text-center gap-8 my-auto w-full">

                    {{-- Premium Squircle Logo Container --}}
                    <div class="relative flex-shrink-0 group">
                        <div class="absolute inset-0 rounded-[2.25rem] blur-2xl opacity-50 bg-indigo-500/30 scale-110 group-hover:scale-125 transition duration-500"></div>
                        <div class="w-36 h-36 rounded-[2rem] squircle-frame flex items-center justify-center p-5">
                            @if($school->logo)
                                <img src="{{ asset('storage/' . $school->logo) }}"
                                     alt="{{ $school->name }}"
                                     class="w-full h-full object-contain filter drop-shadow-[0_4px_10px_rgba(0,0,0,0.08)]">
                            @else
                                <div class="w-full h-full rounded-2xl flex items-center justify-center"
                                     style="background: linear-gradient(135deg, rgba(79,70,229,0.1), rgba(124,58,237,0.1));">
                                    <i class="fas {{ $school->isOffice() ? 'fa-building' : 'fa-graduation-cap' }} text-4xl text-indigo-600 dark:text-indigo-400"></i>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- School/Office Details --}}
                    <div class="space-y-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold tracking-[0.2em] uppercase bg-indigo-500/10 dark:bg-indigo-400/10 text-indigo-700 dark:text-indigo-300">
                            <i class="fas fa-shield-alt text-[9px]"></i>
                            {{ $school->isOffice() ? 'Portal Karyawan' : 'Portal Resmi Sekolah' }}
                        </span>
                        <h2 class="text-2xl font-extrabold tracking-tight bg-clip-text text-transparent bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 dark:from-white dark:via-indigo-100 dark:to-slate-300 leading-tight">
                            {{ $school->name }}
                        </h2>
                    </div>

                    {{-- Beautiful Translucent Pills --}}
                    <div class="flex flex-wrap justify-center gap-2 max-w-sm mt-2">
                        <span class="badge-glass flex items-center gap-2 px-3.5 py-2 rounded-xl text-slate-700 dark:text-slate-300 text-xs font-semibold">
                            <i class="fas fa-fingerprint text-indigo-500 dark:text-indigo-400 text-xs"></i>
                            Biometrik
                        </span>
                        <span class="badge-glass flex items-center gap-2 px-3.5 py-2 rounded-xl text-slate-700 dark:text-slate-300 text-xs font-semibold">
                            <i class="fas fa-id-card text-blue-500 dark:text-blue-400 text-xs"></i>
                            RFID Access
                        </span>
                        <span class="badge-glass flex items-center gap-2 px-3.5 py-2 rounded-xl text-slate-700 dark:text-slate-300 text-xs font-semibold">
                            <i class="fab fa-whatsapp text-emerald-500 dark:text-emerald-400 text-xs"></i>
                            WhatsApp Bot
                        </span>
                        <span class="badge-glass flex items-center gap-2 px-3.5 py-2 rounded-xl text-slate-700 dark:text-slate-300 text-xs font-semibold">
                            <i class="fas fa-chart-line text-pink-500 dark:text-pink-400 text-xs"></i>
                            Real-time
                        </span>
                    </div>

                    <p class="text-[11px] text-slate-400 dark:text-slate-500 max-w-[280px] mt-4 leading-relaxed font-medium">
                        {{ $school->isOffice() ? 'Sistem manajemen & pencatatan kehadiran karyawan cerdas.' : 'Sistem pencatatan absensi otomatis berbasis identitas digital & biometrik.' }}
                    </p>
                </div>
            @else
                {{-- ── DEFAULT BRANDING MODE ── --}}
                <div class="relative z-10 flex flex-col justify-between h-full w-full">
                    {{-- Logo Top --}}
                    <div>
                        <img src="{{ asset('images/logo/logo.svg') }}" alt="Jagat Tech" class="h-8 w-auto dark:hidden">
                        <img src="{{ asset('images/logo/logo-dark.svg') }}" alt="Jagat Tech" class="h-8 w-auto hidden dark:block">
                    </div>

                    {{-- Feature Showcase --}}
                    <div class="my-auto py-10 text-center">
                        <div class="mb-6 inline-flex h-20 w-20 items-center justify-center rounded-[1.75rem] squircle-frame"
                             style="background: linear-gradient(135deg, rgba(79,70,229,0.1), rgba(236,72,153,0.1));">
                            <i class="fas fa-fingerprint text-3xl text-indigo-600 dark:text-indigo-400"></i>
                        </div>
                        <h3 class="text-xl font-extrabold text-slate-800 dark:text-white leading-tight tracking-tight mb-3">
                            Kehadiran Digital Lebih Cerdas
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400 max-w-xs mx-auto leading-relaxed font-medium">
                            Sistem terintegrasi RFID, Biometrik & Notifikasi instan WhatsApp untuk efisiensi instansi Anda.
                        </p>

                        {{-- Stats counters grid --}}
                        <div class="grid grid-cols-3 gap-2.5 mt-8 max-w-sm mx-auto">
                            <div class="badge-glass p-3 rounded-2xl text-center">
                                <span class="block text-sm font-extrabold text-indigo-600 dark:text-indigo-400">Anti-Cheat</span>
                                <span class="text-[9px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider">Keamanan</span>
                            </div>
                            <div class="badge-glass p-3 rounded-2xl text-center">
                                <span class="block text-sm font-extrabold text-blue-600 dark:text-blue-400">Akurat</span>
                                <span class="text-[9px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider">Data</span>
                            </div>
                            <div class="badge-glass p-3 rounded-2xl text-center">
                                <span class="block text-sm font-extrabold text-emerald-600 dark:text-emerald-400">Instan</span>
                                <span class="text-[9px] text-slate-400 dark:text-slate-500 font-semibold uppercase tracking-wider">WA Notif</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Unified Core System Footer Info --}}
            <div class="relative z-10 border-t border-slate-100 dark:border-slate-800/40 pt-4 flex items-center justify-between text-[10px] font-semibold text-slate-400 dark:text-slate-500">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 pulse-beacon"></span>
                    <span>System Core: Online</span>
                </div>
                <span>Server v2.4</span>
            </div>
        </div>

        {{-- ══════════════════════════════════════════
             RIGHT PANEL — Auth Form (7 Cols)
        ══════════════════════════════════════════ --}}
        <div class="lg:col-span-7 flex flex-col justify-center px-6 py-12 sm:px-16 lg:px-20 bg-white/10 dark:bg-slate-900/10 backdrop-blur-md">
            
            {{-- Mobile and Tablet Logo Section --}}
            <div class="mb-10 lg:hidden flex flex-col items-center gap-4 text-center">
                @if(isset($school))
                    <div class="relative group">
                        <div class="absolute inset-0 rounded-[1.5rem] blur-xl opacity-40 bg-indigo-500/30 scale-110"></div>
                        <div class="w-20 h-20 rounded-[1.5rem] squircle-frame flex items-center justify-center p-3">
                            @if($school->logo)
                                <img src="{{ asset('storage/' . $school->logo) }}" alt="{{ $school->name }}"
                                     class="w-full h-full object-contain">
                            @else
                                <i class="fas {{ $school->isOffice() ? 'fa-building' : 'fa-graduation-cap' }} text-2xl text-indigo-600 dark:text-indigo-400"></i>
                            @endif
                        </div>
                    </div>
                    <div class="space-y-1">
                        <span class="inline-block text-[9px] font-extrabold tracking-[0.25em] uppercase text-indigo-600 dark:text-indigo-400 bg-indigo-500/10 dark:bg-indigo-400/10 px-2.5 py-0.5 rounded-full">
                            {{ $school->isOffice() ? 'Portal Karyawan' : 'Portal Resmi Sekolah' }}
                        </span>
                        <h2 class="text-xl font-black text-slate-800 dark:text-white leading-tight">
                            {{ $school->name }}
                        </h2>
                    </div>
                @else
                    <img src="{{ asset('images/logo/logo.svg') }}" alt="Jagat Tech" class="h-7 w-auto dark:hidden">
                    <img src="{{ asset('images/logo/logo-dark.svg') }}" alt="Jagat Tech" class="h-7 w-auto hidden dark:block">
                @endif
            </div>

            {{-- Main Form Container --}}
            <div class="w-full max-w-md mx-auto">
                
                {{-- Form Welcome Text --}}
                <div class="mb-8 text-center lg:text-left">
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight">
                        Selamat Datang 👋
                    </h1>
                    <p class="mt-2 text-xs sm:text-sm text-slate-400 dark:text-slate-400 font-medium leading-relaxed">
                        {{ isset($school) ? 'Silakan masuk menggunakan kredensial akun Anda.' : 'Masukkan email/username dan password untuk mengakses dashboard.' }}
                    </p>
                </div>

                {{-- Interactive Redesigned Error Alert --}}
                @if ($errors->any())
                    <div class="mb-6 flex items-start gap-3.5 rounded-2xl border border-rose-200/60 dark:border-rose-500/20 bg-rose-50/50 dark:bg-rose-500/5 p-4 backdrop-blur-md shadow-[0_10px_20px_rgba(244,63,94,0.04)] dark:shadow-none animate-shake">
                        <div class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-rose-500 text-white shadow-[0_4px_10px_rgba(244,63,94,0.3)]">
                            <i class="fas fa-times text-xs"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-extrabold text-xs text-rose-800 dark:text-rose-400 tracking-wide uppercase">Login gagal</h4>
                            @foreach ($errors->all() as $error)
                                <p class="mt-1 text-xs text-rose-600 dark:text-rose-400 font-semibold leading-relaxed">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Login Form --}}
                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf

                    {{-- Username / Email Field --}}
                    <div class="space-y-2">
                        <label for="email" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">
                            Email / Username
                        </label>
                        <div class="relative rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white/70 dark:bg-slate-900/60 input-box overflow-hidden flex items-center">
                            <span class="pl-4.5 pr-2.5 text-slate-400 dark:text-slate-500 flex items-center justify-center transition-colors">
                                <i class="fas fa-user text-sm"></i>
                            </span>
                            <input type="text" id="email" name="email" value="{{ old('email') }}"
                                   placeholder="nama@email.com atau username" required autofocus
                                   class="w-full py-3.5 pr-4 bg-transparent border-none outline-none text-sm text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 font-semibold">
                        </div>
                    </div>

                    {{-- Password Field --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-widest">
                                Password
                            </label>
                        </div>
                        <div class="relative rounded-2xl border border-slate-200/80 dark:border-slate-800/80 bg-white/70 dark:bg-slate-900/60 input-box overflow-hidden flex items-center">
                            <span class="pl-4.5 pr-2.5 text-slate-400 dark:text-slate-500 flex items-center justify-center transition-colors">
                                <i class="fas fa-lock text-sm"></i>
                            </span>
                            <input :type="showPass ? 'text' : 'password'" id="password" name="password"
                                   placeholder="Masukkan kata sandi Anda" required
                                   class="w-full py-3.5 pr-12 bg-transparent border-none outline-none text-sm text-slate-800 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 font-semibold">
                            <button type="button" @click="showPass = !showPass"
                                    class="absolute inset-y-0 right-0 pr-4.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
                                    tabindex="-1">
                                <i :class="showPass ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-sm"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit" class="btn-submit-premium w-full flex items-center justify-center gap-3.5 rounded-2xl py-4 text-sm font-bold text-white tracking-wider uppercase cursor-pointer">
                        <span>Masuk Ke Dashboard</span>
                        <i class="fas fa-arrow-right text-xs"></i>
                    </button>
                </form>

                {{-- Elegant Footer --}}
                <div class="mt-10 pt-6 border-t border-slate-100 dark:border-slate-800/40 text-center flex flex-col sm:flex-row items-center justify-between gap-3 text-[10px] font-semibold text-slate-400 dark:text-slate-500">
                    <span class="flex items-center gap-1.5 justify-center">
                        <i class="fas fa-shield-alt text-indigo-500 dark:text-indigo-400"></i>
                        <span>Koneksi Aman &amp; Terenkripsi</span>
                    </span>
                    <span class="opacity-75">Sistem Absensi v2.0</span>
                </div>
            </div>
        </div>

    </div>

</body>
</html>