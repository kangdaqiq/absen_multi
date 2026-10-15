@extends('layouts.app')

@section('title', 'Update Sistem & Web Console')

@section('content')
<div class="space-y-6">
    <!-- Header Page -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-white/90 flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-brand-500/10 text-brand-500">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </span>
                Update Sistem & Web Console
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Pembaruan source code via Git, database migration, build paket bot, dan kontrol service server.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold {{ $isWindows ? 'bg-sky-50 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300' }}">
                <span>{{ $isWindows ? '🪟 Windows Native' : '🐧 Linux / Armbian' }}</span>
            </span>
            <a href="{{ url('/update.php') }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-750 transition-colors shadow-sm">
                <span>Tab Penuh</span>
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        </div>
    </div>

    <!-- Server Meta Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="p-4 bg-white dark:bg-gray-800/80 rounded-xl border border-gray-200 dark:border-gray-700/60 shadow-sm">
            <div class="text-xs font-medium text-gray-400">Hostname Server</div>
            <div class="text-sm font-bold text-gray-800 dark:text-white mt-1 truncate">{{ $serverHost }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800/80 rounded-xl border border-gray-200 dark:border-gray-700/60 shadow-sm">
            <div class="text-xs font-medium text-gray-400">Versi PHP Server</div>
            <div class="text-sm font-bold text-gray-800 dark:text-white mt-1">PHP {{ $phpVer }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800/80 rounded-xl border border-gray-200 dark:border-gray-700/60 shadow-sm">
            <div class="text-xs font-medium text-gray-400">Sistem Operasi</div>
            <div class="text-sm font-bold text-gray-800 dark:text-white mt-1 truncate">{{ $serverOS }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800/80 rounded-xl border border-gray-200 dark:border-gray-700/60 shadow-sm">
            <div class="text-xs font-medium text-gray-400">Engine Eksekusi</div>
            <div class="text-sm font-bold text-brand-500 mt-1">JAGAT CLI `absen`</div>
        </div>
    </div>

    <!-- Main Controller Card -->
    <div class="bg-white dark:bg-gray-800/90 rounded-2xl border border-gray-200 dark:border-gray-700/70 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 mb-5 border-b border-gray-100 dark:border-gray-700/60 gap-3">
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-white text-base">Tombol Perintah Sistem</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Pilih salah satu tombol untuk mengeksekusi perintah di server secara realtime.</p>
            </div>
            <div id="liveStatus" class="inline-flex items-center gap-2 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                </span>
                <span>Sistem Siap</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
            <button type="button" onclick="executeSysCommand('update')" class="btn-sys flex items-center justify-center gap-2.5 px-4 py-3 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-500 hover:to-blue-500 shadow-md shadow-cyan-500/20 hover:shadow-cyan-500/30 transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Update Sistem Lengkap</span>
            </button>

            <button type="button" onclick="executeSysCommand('status')" class="btn-sys flex items-center justify-center gap-2.5 px-4 py-3 rounded-xl font-semibold text-sm text-gray-700 bg-gray-100 hover:bg-gray-200 dark:text-gray-200 dark:bg-gray-700 dark:hover:bg-gray-650 transition-all cursor-pointer">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <span>Cek Status Layanan</span>
            </button>

            <button type="button" onclick="executeSysCommand('restart')" class="btn-sys flex items-center justify-center gap-2.5 px-4 py-3 rounded-xl font-semibold text-sm text-amber-700 bg-amber-50 hover:bg-amber-100 dark:text-amber-300 dark:bg-amber-950/40 dark:hover:bg-amber-900/50 border border-amber-200 dark:border-amber-800/50 transition-all cursor-pointer">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <span>Restart Semua Service</span>
            </button>

            <button type="button" onclick="executeSysCommand('backup')" class="btn-sys flex items-center justify-center gap-2.5 px-4 py-3 rounded-xl font-semibold text-sm text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:text-emerald-300 dark:bg-emerald-950/40 dark:hover:bg-emerald-900/50 border border-emerald-200 dark:border-emerald-800/50 transition-all cursor-pointer">
                <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
                <span>Backup Database & Storage</span>
            </button>
        </div>

        <!-- Terminal Output Screen -->
        <div class="rounded-xl overflow-hidden border border-gray-900 bg-gray-950 shadow-inner">
            <div class="flex items-center justify-between px-4 py-2.5 bg-gray-900/90 border-b border-gray-800 text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                    <span id="termHeader" class="ml-2 font-mono text-gray-400">console@jagat-tech:~$ siap menerima perintah</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="copyConsoleLog()" class="px-2 py-1 text-[11px] font-medium text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 rounded transition-colors">Salin Log</button>
                    <button type="button" onclick="clearConsoleLog()" class="px-2 py-1 text-[11px] font-medium text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 rounded transition-colors">Bersihkan</button>
                </div>
            </div>
            <pre id="consoleOutput" class="p-4 font-mono text-xs leading-relaxed text-emerald-400 h-96 overflow-y-auto whitespace-pre-wrap break-all select-text">Selamat datang di Konsol Update Sistem JAGAT TECH.
Tekan salah satu tombol perintah di atas untuk memulai eksekusi di server.
Log pembaruan akan mengalir di sini secara realtime...</pre>
        </div>
    </div>
</div>

<script>
let streamSource = null;
let commandRunning = false;

function executeSysCommand(cmdKey) {
    if (commandRunning) {
        alert('Ada perintah yang masih berjalan. Harap tunggu hingga proses selesai.');
        return;
    }

    if (cmdKey === 'update' && !confirm('Jalankan update sistem sekarang? Proses ini akan menarik Git terbaru, mengupdate dependensi, migrasi database, dan restart bot.')) {
        return;
    }

    if (cmdKey === 'restart' && !confirm('Restart seluruh service server (Web, WhatsApp, Bot)?')) {
        return;
    }

    const consoleBox = document.getElementById('consoleOutput');
    const termHeader = document.getElementById('termHeader');
    const liveStatus = document.getElementById('liveStatus');
    const buttons = document.querySelectorAll('.btn-sys');

    commandRunning = true;
    buttons.forEach(b => {
        b.disabled = true;
        b.classList.add('opacity-50', 'cursor-not-allowed');
    });

    liveStatus.innerHTML = `
        <span class="relative flex h-2.5 w-2.5">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-cyan-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-cyan-500"></span>
        </span>
        <span class="text-cyan-600 dark:text-cyan-400">Menjalankan ${cmdKey.toUpperCase()}...</span>
    `;

    termHeader.innerText = `console@jagat-tech:~$ absen ${cmdKey} (Running)`;
    consoleBox.innerText = `=== MEMULAI EKSEKUSI PERINTAH: ${cmdKey.toUpperCase()} ===\n`;

    if (streamSource) {
        streamSource.close();
    }

    const streamUrl = "{{ route('system.update.stream') }}?cmd=" + encodeURIComponent(cmdKey);
    streamSource = new EventSource(streamUrl);

    streamSource.onmessage = function(e) {
        consoleBox.innerText += e.data + '\n';
        consoleBox.scrollTop = consoleBox.scrollHeight;

        if (e.data.includes('[SELESAI]') || e.data.includes('[ERROR]')) {
            finishSysCommand(e.data.includes('[SELESAI]') ? 'Selesai' : 'Gagal');
        }
    };

    streamSource.onerror = function() {
        finishSysCommand('Koneksi Ditutup');
    };
}

function finishSysCommand(statusText) {
    if (streamSource) {
        streamSource.close();
        streamSource = null;
    }
    commandRunning = false;
    document.querySelectorAll('.btn-sys').forEach(b => {
        b.disabled = false;
        b.classList.remove('opacity-50', 'cursor-not-allowed');
    });

    const liveStatus = document.getElementById('liveStatus');
    const termHeader = document.getElementById('termHeader');

    liveStatus.innerHTML = `
        <span class="relative flex h-2.5 w-2.5">
            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
        </span>
        <span class="text-emerald-600 dark:text-emerald-400">Sistem Siap (${statusText})</span>
    `;
    termHeader.innerText = 'console@jagat-tech:~$ siap menerima perintah';
}

function clearConsoleLog() {
    if (!commandRunning) {
        document.getElementById('consoleOutput').innerText = 'Konsol telah dibersihkan.\n';
    }
}

function copyConsoleLog() {
    const text = document.getElementById('consoleOutput').innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('Log konsol berhasil disalin ke clipboard!');
    }).catch(() => {
        alert('Gagal menyalin log.');
    });
}
</script>
@endsection