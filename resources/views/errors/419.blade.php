<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="3;url={{ route('login') }}">
    <title>419 | Page Expired — Sistem Absensi</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo/logo-icon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

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
        @keyframes shrink {
            from { width: 100%; }
            to { width: 0%; }
        }
        .progress-bar {
            animation: shrink 3s linear forwards;
        }
    </style>
</head>
<body class="h-full bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl border border-slate-200/80 dark:border-slate-800/80 rounded-3xl p-8 shadow-2xl text-center space-y-6 relative overflow-hidden">
        
        {{-- Progress Bar top edge --}}
        <div class="absolute top-0 left-0 right-0 h-1.5 bg-slate-100 dark:bg-slate-800">
            <div class="h-full bg-gradient-to-r from-amber-500 to-indigo-600 progress-bar"></div>
        </div>

        {{-- Icon --}}
        <div class="mx-auto w-20 h-20 rounded-3xl bg-amber-500/10 dark:bg-amber-500/20 text-amber-500 dark:text-amber-400 flex items-center justify-center text-3xl shadow-inner">
            <i class="fas fa-clock"></i>
        </div>

        {{-- Error Info --}}
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/10 text-amber-600 dark:text-amber-400 text-xs font-bold tracking-widest uppercase">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                Error 419
            </div>
            <h1 class="text-2xl font-extrabold text-slate-800 dark:text-white tracking-tight">
                Sesi Halaman Berakhir
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 font-medium leading-relaxed">
                Halaman telah kedaluwarsa karena tidak ada aktivitas. Anda akan dialihkan secara otomatis.
            </p>
        </div>

        {{-- Countdown badge --}}
        <div class="p-4 rounded-2xl bg-slate-100/70 dark:bg-slate-800/50 border border-slate-200/50 dark:border-slate-700/50 flex items-center justify-center gap-3 text-xs font-semibold text-slate-600 dark:text-slate-300">
            <i class="fas fa-spinner fa-spin text-indigo-500"></i>
            Mengalihkan ke halaman Login dalam <span id="countdown" class="font-extrabold text-indigo-600 dark:text-indigo-400">3</span> detik...
        </div>

        {{-- Action Button --}}
        <div>
            <a href="{{ route('login') }}" 
               class="w-full inline-flex items-center justify-center gap-2 py-3.5 px-6 rounded-2xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 text-white font-bold text-sm shadow-lg shadow-indigo-600/25 transition-all duration-200 active:scale-95">
                <span>Kembali ke Login Sekarang</span>
                <i class="fas fa-arrow-right text-xs"></i>
            </a>
        </div>
    </div>

    <script>
        let seconds = 3;
        const countdownEl = document.getElementById('countdown');
        const timer = setInterval(() => {
            seconds--;
            if (countdownEl) countdownEl.innerText = seconds;
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = "{{ route('login') }}";
            }
        }, 1000);
    </script>
</body>
</html>
