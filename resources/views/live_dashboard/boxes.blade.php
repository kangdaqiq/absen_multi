@extends('layouts.app')

@section('title', 'Live Monitoring Absensi Siswa')

@push('styles')
<style>
    /* Fullscreen Kiosk Mode Styling for Smart TV / Monitor */
    body.is-fullscreen {
        overflow-y: auto !important;
    }

    body.is-fullscreen .kiosk-header {
        position: sticky;
        top: 0;
        z-index: 50;
    }

    body.is-fullscreen #live-monitoring-container {
        max-width: 100% !important;
    }

    /* Pulse Glow Animation for New Tap Row */
    @keyframes arrival-highlight {
        0% {
            background-color: rgba(16, 185, 129, 0.25);
            transform: scale(1.005);
        }
        50% {
            background-color: rgba(16, 185, 129, 0.15);
        }
        100% {
            background-color: transparent;
            transform: scale(1);
        }
    }

    .animate-arrival {
        animation: arrival-highlight 1.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }

    @keyframes pulse-ring {
        0% { transform: scale(0.95); opacity: 0.8; }
        50% { transform: scale(1.15); opacity: 0.3; }
        100% { transform: scale(0.95); opacity: 0.8; }
    }

    .pulse-ring {
        animation: pulse-ring 2.5s infinite ease-in-out;
    }

    /* Custom Vertical Scrollbar */
    .custom-scroll-box::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scroll-box::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .dark .custom-scroll-box::-webkit-scrollbar-track {
        background: #1e293b;
    }
    .custom-scroll-box::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .custom-scroll-box::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .dark .custom-scroll-box::-webkit-scrollbar-thumb {
        background: #475569;
    }

    /* Fixed Scroll Container Height & Sticky Header */
    #subview-table-container {
        max-height: 520px !important;
        overflow-y: auto !important;
        overflow-x: auto !important;
        display: block !important;
    }
    #subview-grid-container {
        max-height: 520px !important;
        overflow-y: auto !important;
        overflow-x: hidden !important;
    }
    #subview-table-container thead th {
        position: sticky !important;
        top: 0 !important;
        z-index: 20 !important;
        background-color: #f9fafb !important;
    }
    .dark #subview-table-container thead th {
        background-color: #1f2937 !important;
    }
</style>
@endpush

@section('content')
<div class="space-y-6 pb-12" id="live-monitoring-container">

    {{-- 1. UNIFIED HEADER BAR (Rapi, Bersih, Seragam dengan Desain Admin / Live Log) --}}
    <div class="kiosk-header rounded-2xl border border-gray-200 bg-white p-4 sm:px-6 sm:py-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            
            {{-- Sisi Kiri: Judul, Indikator Real-time, dan Subtitle --}}
            <div class="flex items-center gap-3.5">
                <div class="relative flex h-3.5 w-3.5 shrink-0">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-500"></span>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h2 class="text-xl sm:text-2xl font-black text-gray-800 dark:text-white tracking-tight">
                            LIVE Monitoring Absensi
                        </h2>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span id="live-indicator-text">Real-time</span>
                        </span>
                    </div>
                </div>
            </div>

            {{-- Sisi Kanan: Action Toolbar & Jam Digital --}}
            <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                {{-- Filter Kelas --}}
                <div class="relative">
                    <select id="filter-kelas" onchange="fetchLiveBoxData()"
                        class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 outline-none focus:border-brand-500 transition shadow-2xs">
                        <option value="">Semua Kelas</option>
                        @foreach($kelas as $k)
                            <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Audio Chime Toggle --}}
                <button type="button" id="btn-audio-toggle" onclick="toggleAudio()"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition shadow-2xs">
                    <i class="fas fa-volume-up text-brand-500" id="icon-audio"></i>
                    <span id="label-audio">Suara: Aktif</span>
                </button>

                {{-- Mode Switcher (Tab) --}}
                <div class="inline-flex items-center rounded-xl bg-gray-100 dark:bg-gray-800 p-1 text-xs">
                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-white dark:bg-gray-700 px-2.5 py-1 font-bold text-brand-500 dark:text-white shadow-2xs">
                        <i class="fas fa-th-large"></i> Mode Box
                    </span>
                    <a href="{{ route('live.index') }}"
                        class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 font-semibold text-gray-600 dark:text-gray-400 hover:text-brand-500 dark:hover:text-white transition">
                        <i class="fas fa-table"></i> Mode Tabel
                    </a>
                </div>

                {{-- Fullscreen Toggle --}}
                <button type="button" onclick="toggleKioskFullscreen()" id="btn-kiosk-fs"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-xs font-bold text-white transition-all shadow-sm">
                    <i class="fas fa-expand" id="icon-kiosk-fs"></i> <span id="label-kiosk-fs">Fullscreen</span>
                </button>

                {{-- Divider --}}
                <div class="hidden xl:block h-8 w-px bg-gray-200 dark:bg-gray-800 mx-1"></div>

                {{-- Clock Widget --}}
                <div class="flex items-center gap-3 pl-1 shrink-0">
                    <div class="text-right">
                        <p id="live-clock-date" class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">
                            --
                        </p>
                        <p id="live-clock-time" class="text-xl sm:text-2xl font-black text-brand-500 font-mono tracking-tight leading-none mt-0.5">--:--:--</p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- 2. MAIN SPLIT WORKSPACE: KIRI (FULL POTRET) & KANAN (STATS + LIST TABEL) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- ==================== SISI KIRI: FULL POTRET CARD SISWA ==================== --}}
        <div class="lg:col-span-5 xl:col-span-4 lg:sticky lg:top-20 space-y-4">
            <div id="spotlight-container" class="transition-all duration-500">
                <div class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
                    {{-- Decorative Ambient Glow --}}
                    <div class="absolute -top-16 -right-16 w-40 h-40 bg-brand-500/10 dark:bg-brand-500/20 rounded-full blur-3xl pointer-events-none"></div>

                    {{-- Top Header Row: Label & Tap Time --}}
                    <div class="flex items-center justify-between gap-2 pb-3 mb-3 border-b border-gray-100 dark:border-gray-800">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-500 px-3 py-1 text-[11px] font-black uppercase text-white tracking-wide shadow-sm">
                            <i class="fas fa-bolt text-amber-300"></i> TAP TERAKHIR
                        </span>
                        <span id="spotlight-time-badge" class="inline-flex items-center gap-1.5 font-mono text-xs font-bold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2.5 py-1 rounded-lg">
                            <i class="fas fa-clock text-[10px] text-gray-400"></i>
                            <span id="spotlight-time-text">--:--:--</span>
                        </span>
                    </div>

                    {{-- 3-Second Auto Reset Timer Bar --}}
                    <div id="spotlight-timer-wrapper" class="w-full bg-gray-100 dark:bg-gray-800 h-1 overflow-hidden rounded-full mb-4 opacity-0 transition-opacity">
                        <div id="spotlight-timer-bar" class="h-full bg-brand-500 rounded-full w-full"></div>
                    </div>

                    {{-- Portrait Center: Full Portrait Photo / Avatar Frame (Lebar Dibatasi Seragam) --}}
                    <div class="text-center">
                        <div class="relative mx-auto w-52 sm:w-56 aspect-[3/4] mb-4" style="max-width: 220px; aspect-ratio: 3/4;">
                            <div id="spotlight-photo-wrapper" class="w-full h-full rounded-3xl overflow-hidden border-2 border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40 shadow-inner flex items-center justify-center transition-all">
                                <img id="spotlight-photo" src="" alt="Foto Siswa" class="w-full h-full object-cover object-top hidden"
                                    onerror="this.classList.add('hidden'); document.getElementById('spotlight-initial').classList.remove('hidden');">
                                <span id="spotlight-initial" class="text-5xl font-black text-gray-300 dark:text-gray-600"><i class="fas fa-id-card-alt text-5xl"></i></span>
                            </div>
                            <div id="spotlight-ring" class="hidden pulse-ring absolute -inset-2 rounded-3xl border-2 border-brand-500/40 pointer-events-none"></div>
                        </div>

                        {{-- Status Pill Badge --}}
                        <div class="mb-2">
                            <span id="spotlight-status-badge" class="inline-flex rounded-full bg-gray-100 text-gray-500 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700 px-3.5 py-1 text-xs font-bold shadow-xs">
                                Standby Absensi
                            </span>
                        </div>

                        {{-- Student Name --}}
                        <h3 id="spotlight-name" class="text-xl md:text-2xl font-black text-gray-800 dark:text-white tracking-tight leading-snug">
                            Menunggu Siswa...
                        </h3>

                        {{-- Class & NIS --}}
                        <div class="flex items-center justify-center gap-2 mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-bold text-gray-700 dark:text-gray-300">Kelas <span id="spotlight-kelas">-</span></span>
                            <span>&bull;</span>
                            <span class="font-mono">NIS: <span id="spotlight-nis">-</span></span>
                        </div>
                    </div>

                    {{-- Bottom Metric Box: Jam Masuk & Jam Pulang in 2-Columns --}}
                    <div class="mt-5 grid grid-cols-2 gap-3 p-3 rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                        <div class="text-center p-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 shadow-2xs">
                            <span class="block text-[10px] uppercase font-bold text-gray-400 tracking-wider">Jam Masuk</span>
                            <span id="spotlight-jam-masuk" class="block font-mono text-base font-black text-emerald-600 dark:text-emerald-400 mt-0.5">--:--</span>
                        </div>
                        <div class="text-center p-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 shadow-2xs">
                            <span class="block text-[10px] uppercase font-bold text-gray-400 tracking-wider">Jam Pulang</span>
                            <span id="spotlight-jam-pulang" class="block font-mono text-base font-black text-sky-600 dark:text-sky-400 mt-0.5">--:--</span>
                        </div>
                    </div>

                    {{-- Footer Note / Keterangan --}}
                    <div class="mt-3.5 pt-3 border-t border-gray-100 dark:border-gray-800 text-center">
                        <p id="spotlight-keterangan" class="text-xs text-gray-500 dark:text-gray-400 italic">
                            Menunggu aktivitas tapping absensi...
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== SISI KANAN: STATS CARDS + LIST SISWA ==================== --}}
        <div class="lg:col-span-7 xl:col-span-8 space-y-6">

            {{-- 1. STATS OVERVIEW CARDS (Grid 3 Kolom x 2 Baris) --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 md:gap-4">
                {{-- Total Siswa --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark flex items-center justify-between">
                    <div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Siswa</span>
                        <h4 id="stat-total" class="text-xl md:text-2xl font-black text-gray-800 dark:text-white mt-1">--</h4>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-brand-50 dark:bg-brand-500/15 flex items-center justify-center text-brand-500">
                        <i class="fas fa-users"></i>
                    </div>
                </div>

                {{-- Sudah Absen --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark flex items-center justify-between">
                    <div>
                        <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 font-semibold">Sudah Absen</span>
                        <h4 id="stat-absen" class="text-xl md:text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">--</h4>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/15 flex items-center justify-center text-emerald-500">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                </div>

                {{-- Hadir Tepat Waktu --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark flex items-center justify-between">
                    <div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Tepat Waktu</span>
                        <h4 id="stat-hadir" class="text-xl md:text-2xl font-black text-gray-800 dark:text-white mt-1">--</h4>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-500/15 flex items-center justify-center text-emerald-500">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>

                {{-- Terlambat --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark flex items-center justify-between">
                    <div>
                        <span class="text-xs font-medium text-amber-600 dark:text-amber-400 font-semibold">Terlambat</span>
                        <h4 id="stat-terlambat" class="text-xl md:text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">--</h4>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-500/15 flex items-center justify-center text-amber-500">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>

                {{-- Izin & Sakit --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark flex items-center justify-between">
                    <div>
                        <span class="text-xs font-medium text-sky-600 dark:text-sky-400">Izin / Sakit</span>
                        <h4 id="stat-izin-sakit" class="text-xl md:text-2xl font-black text-sky-600 dark:text-sky-400 mt-1">--</h4>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-500/15 flex items-center justify-center text-sky-500">
                        <i class="fas fa-file-medical"></i>
                    </div>
                </div>

                {{-- Belum Hadir --}}
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark flex items-center justify-between">
                    <div>
                        <span class="text-xs font-medium text-rose-500 dark:text-rose-400 font-semibold">Belum Absen</span>
                        <h4 id="stat-belum" class="text-xl md:text-2xl font-black text-rose-500 dark:text-rose-400 mt-1">--</h4>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-500/15 flex items-center justify-center text-rose-500">
                        <i class="fas fa-user-times"></i>
                    </div>
                </div>
            </div>

            {{-- 2. DAFTAR ALIRAN ABSENSI HARI INI (TABEL REAL-TIME) --}}
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark overflow-hidden">
                {{-- Card Header --}}
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <h4 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                            <i class="fas fa-list-ul text-brand-500"></i>
                            <span>Daftar Absensi Hari Ini</span>
                        </h4>
                        <span id="boxes-count-pill" class="rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 px-2.5 py-0.5 text-xs font-bold">
                            0 Siswa
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        {{-- Toggle View Between Table & Box Card --}}
                        <div class="inline-flex items-center rounded-xl bg-gray-100 p-0.5 dark:bg-gray-800 text-xs">
                            <button type="button" onclick="setSubView('table')" id="btn-subview-table"
                                class="px-3 py-1 rounded-lg font-bold text-brand-500 bg-white dark:bg-gray-700 shadow-sm transition">
                                <i class="fas fa-list"></i> Model List / Tabel
                            </button>
                            <button type="button" onclick="setSubView('grid')" id="btn-subview-grid"
                                class="px-3 py-1 rounded-lg font-semibold text-gray-600 dark:text-gray-400 hover:text-brand-500 transition">
                                <i class="fas fa-th-large"></i> Model Box
                            </button>
                        </div>

                        <span class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1.5">
                            <i class="fas fa-sync-alt animate-spin text-[10px]" id="icon-sync"></i>
                            <span id="last-sync-time">Sinkronisasi aktif</span>
                        </span>
                    </div>
                </div>

                {{-- Subview 1: Table List View (Default & Primary: Tanpa Foto) --}}
                <div id="subview-table-container" class="max-w-full custom-scroll-box border-b border-gray-100 dark:border-gray-800" style="max-height: 520px; overflow-y: auto; overflow-x: auto; display: block;">
                    <table class="w-full table-auto">
                        <thead class="sticky top-0 z-20 bg-gray-50/95 dark:bg-gray-800/95 backdrop-blur-sm shadow-xs border-b border-gray-200 dark:border-gray-700">
                            <tr class="text-left text-gray-800 dark:text-white/90 font-medium text-xs uppercase tracking-wider">
                                <th class="px-3.5 py-3.5 xl:pl-5 text-center w-10">#</th>
                                <th class="px-3.5 py-3.5 min-w-[80px]">Waktu</th>
                                <th class="px-3.5 py-3.5 min-w-[150px]">Nama Siswa</th>
                                <th class="px-3.5 py-3.5 min-w-[80px]">NIS</th>
                                <th class="px-3.5 py-3.5 min-w-[70px]">Kelas</th>
                                <th class="px-3.5 py-3.5 min-w-[80px]">Masuk</th>
                                <th class="px-3.5 py-3.5 min-w-[80px]">Pulang</th>
                                <th class="px-3.5 py-3.5 min-w-[110px] text-center">Status</th>
                                <th class="px-3.5 py-3.5 min-w-[120px]">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody id="student-table-body" class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                            {{-- Initial Loading Placeholder --}}
                            <tr>
                                <td colspan="9" class="px-4 py-12 text-center text-gray-400 italic">
                                    <i class="fas fa-circle-notch fa-spin mr-2"></i> Memuat data absensi real-time...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Subview 2: Box Grid View (Alternative toggle) --}}
                <div id="subview-grid-container" class="hidden p-5 custom-scroll-box" style="max-height: 520px; overflow-y: auto;">
                    <div id="boxes-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        {{-- Injected via JS when grid mode active --}}
                    </div>
                </div>

                {{-- Empty State --}}
                <div id="boxes-empty-state" class="hidden p-12 text-center bg-gray-50/50 dark:bg-gray-900/30">
                    <div class="mx-auto w-14 h-14 rounded-2xl bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center text-brand-500 text-2xl mb-3">
                        <i class="fas fa-fingerprint"></i>
                    </div>
                    <h5 class="text-base font-bold text-gray-800 dark:text-white">Belum Ada Absensi Masuk Hari Ini</h5>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                        Daftar kehadiran siswa yang melakukan tap pada mesin scanner gerbang atau fingerprint akan langsung tampil di sini secara real-time.
                    </p>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    // ── Global State ───────────────────────────────────────────────────────────
    let currentSubView = 'table'; // 'table' or 'grid'
    let isAudioEnabled = true;
    let audioCtx = null;
    let lastKnownTimestamp = 0;
    let firstLoadDone = false;
    let pollingInterval = null;
    let cachedBoxes = [];

    // ── Switch SubView (Table vs Grid) ─────────────────────────────────────────
    function setSubView(mode) {
        currentSubView = mode;
        const btnTable = document.getElementById('btn-subview-table');
        const btnGrid = document.getElementById('btn-subview-grid');
        const tableContainer = document.getElementById('subview-table-container');
        const gridContainer = document.getElementById('subview-grid-container');

        if (mode === 'table') {
            btnTable.className = 'px-3 py-1 rounded-lg font-bold text-brand-500 bg-white dark:bg-gray-700 shadow-sm transition';
            btnGrid.className = 'px-3 py-1 rounded-lg font-semibold text-gray-600 dark:text-gray-400 hover:text-brand-500 transition';
            tableContainer.classList.remove('hidden');
            gridContainer.classList.add('hidden');
        } else {
            btnGrid.className = 'px-3 py-1 rounded-lg font-bold text-brand-500 bg-white dark:bg-gray-700 shadow-sm transition';
            btnTable.className = 'px-3 py-1 rounded-lg font-semibold text-gray-600 dark:text-gray-400 hover:text-brand-500 transition';
            gridContainer.classList.remove('hidden');
            tableContainer.classList.add('hidden');
        }

        renderDisplay(cachedBoxes);
    }

    // ── Web Audio Chime Generator (Compliant with Browser Autoplay Policy) ────
    function initAudioOnInteraction() {
        try {
            if (!audioCtx) {
                const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
                if (AudioCtxClass) {
                    audioCtx = new AudioCtxClass();
                }
            }
            if (audioCtx && audioCtx.state === 'suspended') {
                audioCtx.resume().catch(() => {});
            }
        } catch (e) {}
    }

    // Dengarkan interaksi user pertama kali di halaman untuk mengaktifkan Web Audio
    ['click', 'touchstart', 'keydown'].forEach(evt => {
        document.addEventListener(evt, initAudioOnInteraction, { once: true, passive: true });
    });

    function playChime(isLate = false) {
        if (!isAudioEnabled) return;
        // Jangan jalankan AudioContext sebelum ada interaksi user untuk menghindari peringatan browser
        if (!audioCtx || audioCtx.state !== 'running') return;

        try {
            const now = audioCtx.currentTime;

            if (isLate) {
                // Two-tone warning chime for late students (E4 -> C4)
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(329.63, now);
                osc.frequency.setValueAtTime(261.63, now + 0.15);
                gain.gain.setValueAtTime(0.15, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start(now);
                osc.stop(now + 0.5);
            } else {
                // Joyful bell chime for on-time students (C5 -> G5)
                const osc1 = audioCtx.createOscillator();
                const osc2 = audioCtx.createOscillator();
                const gain = audioCtx.createGain();

                osc1.type = 'sine';
                osc2.type = 'sine';
                osc1.frequency.setValueAtTime(523.25, now);
                osc2.frequency.setValueAtTime(783.99, now + 0.12);

                gain.gain.setValueAtTime(0.12, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.6);

                osc1.connect(gain);
                osc2.connect(gain);
                gain.connect(audioCtx.destination);

                osc1.start(now);
                osc1.stop(now + 0.25);
                osc2.start(now + 0.12);
                osc2.stop(now + 0.6);
            }
        } catch (e) {
            // Abaikan kesalahan audio minor
        }
    }

    function toggleAudio() {
        initAudioOnInteraction();
        isAudioEnabled = !isAudioEnabled;
        const icon = document.getElementById('icon-audio');
        const label = document.getElementById('label-audio');

        if (isAudioEnabled) {
            icon.className = 'fas fa-volume-up text-brand-500';
            label.textContent = 'Suara: Aktif';
            if (audioCtx && audioCtx.state === 'running') {
                playChime(false);
            }
        } else {
            icon.className = 'fas fa-volume-mute text-gray-400';
            label.textContent = 'Suara: Senyap';
        }
    }

    // ── Live Digital Clock (Format sama dengan Live Log) ──────────────────────
    function updateDigitalClock() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const timeEl = document.getElementById('live-clock-time');
        const dateEl = document.getElementById('live-clock-date');

        if (timeEl) {
            timeEl.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
        }
        if (dateEl) {
            dateEl.textContent = now.toLocaleDateString('id-ID', options);
        }
    }

    // ── Kiosk Fullscreen Mode ──────────────────────────────────────────────────
    function toggleKioskFullscreen() {
        const isFs = !!(document.fullscreenElement || document.webkitFullscreenElement);
        if (!isFs) {
            const el = document.documentElement;
            if (el.requestFullscreen) {
                el.requestFullscreen();
            } else if (el.webkitRequestFullscreen) {
                el.webkitRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            }
        }
    }

    document.addEventListener('fullscreenchange', () => {
        const isFs = !!document.fullscreenElement;
        const btn = document.getElementById('btn-kiosk-fs');
        const label = document.getElementById('label-kiosk-fs');
        const icon = document.getElementById('icon-kiosk-fs');

        if (isFs) {
            if (label) label.textContent = 'Keluar Fullscreen';
            if (icon) icon.className = 'fas fa-compress';
            if (btn) btn.classList.add('bg-brand-500', 'text-white');
            if (window.Alpine && Alpine.store('sidebar')) {
                Alpine.store('sidebar').setFullScreen(true);
            }
        } else {
            if (label) label.textContent = 'Fullscreen';
            if (icon) icon.className = 'fas fa-expand';
            if (btn) btn.classList.remove('bg-brand-500', 'text-white');
            if (window.Alpine && Alpine.store('sidebar')) {
                Alpine.store('sidebar').setFullScreen(false);
            }
        }
    });

    // ── Fetch Real-time Box / List Data ───────────────────────────────────────
    async function fetchLiveBoxData() {
        const kelasId = document.getElementById('filter-kelas')?.value || '';
        const syncIcon = document.getElementById('icon-sync');

        try {
            if (syncIcon) syncIcon.classList.add('animate-spin');

            const response = await fetch(`{{ route('live.box-data') }}?kelas_id=${encodeURIComponent(kelasId)}`, {
                headers: { 
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();

            // Reset indicator status
            const indText = document.getElementById('live-indicator-text');
            if (indText) {
                indText.textContent = 'Real-time';
                indText.className = '';
            }

            // 1. Update Stats Bar
            document.getElementById('stat-total').textContent = data.stats.total ?? 0;
            document.getElementById('stat-absen').textContent = data.stats.absen ?? 0;
            document.getElementById('stat-hadir').textContent = data.stats.hadir ?? 0;
            document.getElementById('stat-terlambat').textContent = data.stats.terlambat ?? 0;
            document.getElementById('stat-izin-sakit').textContent = (data.stats.izin ?? 0) + (data.stats.sakit ?? 0);
            document.getElementById('stat-belum').textContent = data.stats.belum ?? 0;

            // Filter hanya siswa yang hadir/tap (jangan masukkan Alpha, Sakit, Izin, Bolos)
            const allBoxes = data.boxes || [];
            cachedBoxes = allBoxes.filter(box => {
                const statusType = (box.status_type || '').toLowerCase();
                const statusRaw = (box.status_raw || '').toUpperCase();
                return !['alpha', 'sakit', 'izin', 'bolos'].includes(statusType) 
                    && !['A', 'S', 'I', 'B'].includes(statusRaw);
            });

            const boxesCountPill = document.getElementById('boxes-count-pill');
            if (boxesCountPill) {
                boxesCountPill.textContent = cachedBoxes.length + ' Siswa';
            }

            // 2. Check for New Student Tap
            if (data.latest && data.latest.timestamp > lastKnownTimestamp) {
                const isLate = data.latest.status_type === 'terlambat';

                if (firstLoadDone) {
                    playChime(isLate);
                }

                lastKnownTimestamp = data.latest.timestamp;
                updateSpotlightCard(data.latest);
            } else if (data.latest && !firstLoadDone) {
                lastKnownTimestamp = data.latest.timestamp;
                updateSpotlightCard(data.latest);
            }

            // Render display dengan list yang sudah disaring
            renderDisplay(cachedBoxes);

            const syncText = document.getElementById('last-sync-time');
            if (syncText) {
                syncText.textContent = 'Update: ' + new Date().toLocaleTimeString('id-ID', { hour12: false });
            }

            firstLoadDone = true;
        } catch (err) {
            console.warn('Live box data notice:', err.message);
            const indText = document.getElementById('live-indicator-text');
            if (indText) {
                indText.textContent = 'Menghubungkan kembali...';
                indText.className = 'text-amber-500 font-semibold';
            }
        } finally {
            if (syncIcon) syncIcon.classList.remove('animate-spin');
        }
    }

    let spotlightResetTimer = null;

    // ── Reset Spotlight Card to Standby State (Setelah 3 Detik) ───────────────
    function resetSpotlightCard() {
        const photoImg = document.getElementById('spotlight-photo');
        const initialSpan = document.getElementById('spotlight-initial');
        const photoWrapper = document.getElementById('spotlight-photo-wrapper');
        const ring = document.getElementById('spotlight-ring');

        const name = document.getElementById('spotlight-name');
        const kelas = document.getElementById('spotlight-kelas');
        const nis = document.getElementById('spotlight-nis');
        const statusBadge = document.getElementById('spotlight-status-badge');
        const timeText = document.getElementById('spotlight-time-text');
        const keterangan = document.getElementById('spotlight-keterangan');
        const inTime = document.getElementById('spotlight-jam-masuk');
        const outTime = document.getElementById('spotlight-jam-pulang');
        const timerWrapper = document.getElementById('spotlight-timer-wrapper');

        if (timerWrapper) timerWrapper.classList.add('opacity-0');

        if (name) name.textContent = 'Menunggu Siswa...';
        if (kelas) kelas.textContent = '-';
        if (nis) nis.textContent = '-';
        if (timeText) timeText.textContent = '--:--:--';
        if (inTime) inTime.textContent = '--:--';
        if (outTime) outTime.textContent = '--:--';

        if (keterangan) {
            keterangan.textContent = 'Silakan tap kartu RFID ...';
        }

        // Hide photo, show standby icon
        if (photoImg) photoImg.classList.add('hidden');
        if (initialSpan) {
            initialSpan.innerHTML = '<i class="fas fa-id-card-alt text-5xl"></i>';
            initialSpan.classList.remove('hidden');
        }

        // Reset photo wrapper & ring
        if (photoWrapper) {
            photoWrapper.className = 'w-full h-full rounded-3xl overflow-hidden border-2 border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40 shadow-inner flex items-center justify-center transition-all';
        }
        if (ring) {
            ring.className = 'hidden';
        }

        // Reset status badge
        if (statusBadge) {
            statusBadge.textContent = 'Standby Absensi';
            statusBadge.className = 'inline-flex rounded-full bg-gray-100 text-gray-500 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700 px-3.5 py-1 text-xs font-bold shadow-xs';
        }
    }

    // ── Update Spotlight Card ─────────────────────────────────────────────────
    function updateSpotlightCard(student) {
        if (!student) return;

        // Clear existing reset countdown if any
        if (spotlightResetTimer) {
            clearTimeout(spotlightResetTimer);
            spotlightResetTimer = null;
        }

        const photoImg = document.getElementById('spotlight-photo');
        const initialSpan = document.getElementById('spotlight-initial');
        const photoWrapper = document.getElementById('spotlight-photo-wrapper');
        const ring = document.getElementById('spotlight-ring');

        const name = document.getElementById('spotlight-name');
        const kelas = document.getElementById('spotlight-kelas');
        const nis = document.getElementById('spotlight-nis');
        const statusBadge = document.getElementById('spotlight-status-badge');
        const timeText = document.getElementById('spotlight-time-text');
        const keterangan = document.getElementById('spotlight-keterangan');
        const inTime = document.getElementById('spotlight-jam-masuk');
        const outTime = document.getElementById('spotlight-jam-pulang');

        if (name) name.textContent = student.nama;
        if (kelas) kelas.textContent = student.kelas;
        if (nis) nis.textContent = student.nis;
        if (timeText) timeText.textContent = student.time;
        if (inTime) inTime.textContent = student.jam_masuk;
        if (outTime) outTime.textContent = student.jam_pulang;

        if (keterangan) {
            keterangan.textContent = student.keterangan || (student.status_type === 'pulang' ? 'Siswa telah pulang' : 'Tepat Waktu');
        }

        // Photo or Initial Fallback
        if (student.foto && !student.foto.includes('ui-avatars.com')) {
            if (photoImg) {
                photoImg.src = student.foto;
                photoImg.classList.remove('hidden');
            }
            if (initialSpan) initialSpan.classList.add('hidden');
        } else {
            if (photoImg) photoImg.classList.add('hidden');
            if (initialSpan) {
                initialSpan.textContent = (student.nama || 'S').trim().charAt(0).toUpperCase();
                initialSpan.classList.remove('hidden');
            }
        }

        // Status Badge & Border Accents
        if (statusBadge) {
            statusBadge.textContent = student.status_text;
            if (student.status_type === 'terlambat') {
                statusBadge.className = 'inline-flex rounded-full bg-warning-50 text-warning-600 border border-warning-200 dark:bg-warning-500/20 dark:text-warning-500 px-3 py-1 text-xs font-bold shadow-xs';
                if (photoWrapper) photoWrapper.className = 'w-full h-full rounded-3xl overflow-hidden border-2 border-warning-500/40 bg-warning-50 dark:bg-warning-500/10 shadow-md flex items-center justify-center transition-all';
                if (ring) ring.className = 'pulse-ring absolute -inset-1.5 rounded-3xl border-2 border-warning-500/40 pointer-events-none';
            } else if (student.status_type === 'pulang') {
                statusBadge.className = 'inline-flex rounded-full bg-info-50 text-info-600 border border-info-200 dark:bg-info-500/20 dark:text-info-500 px-3 py-1 text-xs font-bold shadow-xs';
                if (photoWrapper) photoWrapper.className = 'w-full h-full rounded-3xl overflow-hidden border-2 border-info-500/40 bg-info-50 dark:bg-info-500/10 shadow-md flex items-center justify-center transition-all';
                if (ring) ring.className = 'pulse-ring absolute -inset-1.5 rounded-3xl border-2 border-info-500/40 pointer-events-none';
            } else {
                statusBadge.className = 'inline-flex rounded-full bg-success-50 text-success-600 border border-success-200 dark:bg-success-500/20 dark:text-success-500 px-3 py-1 text-xs font-bold shadow-xs';
                if (photoWrapper) photoWrapper.className = 'w-full h-full rounded-3xl overflow-hidden border-2 border-success-500/40 bg-success-50 dark:bg-success-500/10 shadow-md flex items-center justify-center transition-all';
                if (ring) ring.className = 'pulse-ring absolute -inset-1.5 rounded-3xl border-2 border-success-500/40 pointer-events-none';
            }
        }

        const spotlight = document.getElementById('spotlight-container');
        if (spotlight) {
            spotlight.classList.add('scale-[1.015]');
            setTimeout(() => spotlight.classList.remove('scale-[1.015]'), 400);
        }

        // Trigger 3-second animated progress bar
        const timerBar = document.getElementById('spotlight-timer-bar');
        const timerWrapper = document.getElementById('spotlight-timer-wrapper');
        if (timerBar && timerWrapper) {
            timerWrapper.classList.remove('opacity-0');
            timerBar.style.transition = 'none';
            timerBar.style.width = '100%';
            void timerBar.offsetWidth; // Force CSS reflow
            timerBar.style.transition = 'width 3s linear';
            timerBar.style.width = '0%';
        }

        // Otomatis reset kembali ke standby setelah 3 detik
        spotlightResetTimer = setTimeout(() => {
            resetSpotlightCard();
        }, 3000);
    }

    // ── Render Display (Route to Table or Grid) ───────────────────────────────
    function renderDisplay(boxes) {
        const emptyState = document.getElementById('boxes-empty-state');
        const tableContainer = document.getElementById('subview-table-container');
        const gridContainer = document.getElementById('subview-grid-container');

        if (boxes.length === 0) {
            if (emptyState) emptyState.classList.remove('hidden');
            if (tableContainer) tableContainer.classList.add('hidden');
            if (gridContainer) gridContainer.classList.add('hidden');
            return;
        }

        if (emptyState) emptyState.classList.add('hidden');

        if (currentSubView === 'table') {
            if (tableContainer) tableContainer.classList.remove('hidden');
            if (gridContainer) gridContainer.classList.add('hidden');
            renderTableRows(boxes);
        } else {
            if (gridContainer) gridContainer.classList.remove('hidden');
            if (tableContainer) tableContainer.classList.add('hidden');
            renderGridCards(boxes);
        }
    }

    // ── Render Table List (Tanpa Foto - Model Table) ──────────────────────────
    function renderTableRows(boxes) {
        const tbody = document.getElementById('student-table-body');
        if (!tbody) return;

        let html = '';
        boxes.forEach((box, index) => {
            const isFirst = index === 0;

            let badgeHtml = '';
            if (box.status_type === 'terlambat') {
                badgeHtml = `<span class="inline-flex rounded-full bg-warning-50 px-2.5 py-1 text-xs font-semibold text-warning-600 dark:bg-warning-500/15 dark:text-warning-500 border border-warning-200 dark:border-warning-500/20">Terlambat</span>`;
            } else if (box.status_type === 'pulang') {
                badgeHtml = `<span class="inline-flex rounded-full bg-info-50 px-2.5 py-1 text-xs font-semibold text-info-600 dark:bg-info-500/15 dark:text-info-500 border border-info-200 dark:border-info-500/20">Pulang</span>`;
            } else if (box.status_type === 'izin') {
                badgeHtml = `<span class="inline-flex rounded-full bg-info-50 px-2.5 py-1 text-xs font-semibold text-info-600 dark:bg-info-500/15 dark:text-info-500 border border-info-200 dark:border-info-500/20">Izin</span>`;
            } else if (box.status_type === 'sakit') {
                badgeHtml = `<span class="inline-flex rounded-full bg-warning-50 px-2.5 py-1 text-xs font-semibold text-warning-600 dark:bg-warning-500/15 dark:text-warning-500 border border-warning-200 dark:border-warning-500/20">Sakit</span>`;
            } else if (box.status_type === 'alpha') {
                badgeHtml = `<span class="inline-flex rounded-full bg-error-50 px-2.5 py-1 text-xs font-semibold text-error-600 dark:bg-error-500/15 dark:text-error-500 border border-error-200 dark:border-error-500/20">Alpha</span>`;
            } else {
                badgeHtml = `<span class="inline-flex rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-500 border border-success-200 dark:border-success-500/20">Hadir</span>`;
            }

            html += `
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors ${isFirst ? 'animate-arrival' : ''}">
                    <td class="px-3.5 py-3 xl:pl-5 text-center text-xs font-semibold text-gray-400">
                        ${index + 1}
                    </td>
                    <td class="px-3.5 py-3 font-mono text-xs font-bold text-gray-800 dark:text-white">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                            <i class="fas fa-clock text-[10px] text-gray-400"></i>
                            ${box.time_short}
                        </span>
                    </td>
                    <td class="px-3.5 py-3 font-bold text-gray-800 dark:text-white">
                        ${box.nama}
                    </td>
                    <td class="px-3.5 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                        ${box.nis}
                    </td>
                    <td class="px-3.5 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-bold bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                            ${box.kelas}
                        </span>
                    </td>
                    <td class="px-3.5 py-3 font-mono text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        ${box.jam_masuk}
                    </td>
                    <td class="px-3.5 py-3 font-mono text-xs font-semibold text-sky-600 dark:text-sky-400">
                        ${box.jam_pulang}
                    </td>
                    <td class="px-3.5 py-3 text-center">
                        ${badgeHtml}
                    </td>
                    <td class="px-3.5 py-3 text-xs text-gray-500 dark:text-gray-400">
                        ${box.keterangan || '-'}
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    }

    // ── Render Grid Cards (Model Box) ─────────────────────────────────────────
    function renderGridCards(boxes) {
        const grid = document.getElementById('boxes-grid');
        if (!grid) return;

        let html = '';
        boxes.forEach((box, index) => {
            const isFirst = index === 0;

            let badgeBg = 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500 border-success-200';
            let dotColor = 'bg-success-500';

            if (box.status_type === 'terlambat') {
                badgeBg = 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500 border-warning-200';
                dotColor = 'bg-warning-500';
            } else if (box.status_type === 'pulang') {
                badgeBg = 'bg-info-50 text-info-600 dark:bg-info-500/15 dark:text-info-500 border-info-200';
                dotColor = 'bg-info-500';
            }

            const initial = (box.nama || 'S').trim().charAt(0).toUpperCase();

            html += `
                <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-dark p-4 shadow-theme-sm transition-all duration-300 ${isFirst ? 'ring-2 ring-brand-500/30' : ''}">
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-flex items-center gap-1.5 font-mono text-xs font-bold text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-lg">
                            <i class="fas fa-clock text-[10px] text-gray-400"></i>
                            ${box.time_short}
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full ${badgeBg} border px-2 py-0.5 text-[10px] font-bold">
                            <span class="w-1.5 h-1.5 rounded-full ${dotColor}"></span>
                            ${box.status_text}
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-brand-50 dark:bg-brand-500/15 text-brand-600 dark:text-brand-400 font-black flex items-center justify-center text-base shrink-0 overflow-hidden border border-brand-500/20">
                            ${box.foto && !box.foto.includes('ui-avatars.com') 
                                ? `<img src="${box.foto}" alt="${box.nama}" class="w-full h-full object-cover object-top">` 
                                : initial}
                        </div>
                        <div class="min-w-0 flex-1">
                            <h5 class="text-sm font-bold text-gray-800 dark:text-white truncate" title="${box.nama}">
                                ${box.nama}
                            </h5>
                            <p class="text-xs font-semibold text-brand-600 dark:text-brand-400 truncate mt-0.5">
                                ${box.kelas}
                            </p>
                            <p class="text-[11px] font-mono text-gray-400 dark:text-gray-500 truncate">
                                NIS: ${box.nis}
                            </p>
                        </div>
                    </div>

                    <div class="mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-[11px] font-mono">
                        <span class="text-gray-500 dark:text-gray-400">
                            IN: <strong class="text-emerald-600 dark:text-emerald-400">${box.jam_masuk}</strong>
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">
                            OUT: <strong class="text-sky-600 dark:text-sky-400">${box.jam_pulang}</strong>
                        </span>
                    </div>
                </div>
            `;
        });

        grid.innerHTML = html;
    }

    // ── Simulate Tap Request ───────────────────────────────────────────────────
    async function triggerSimulation() {
        const btn = document.getElementById('btn-simulate');
        const origText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Mengetuk...</span>';

        try {
            const res = await fetch('{{ route('live.simulate-tap') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const result = await res.json();
            if (result.success) {
                await fetchLiveBoxData();
            } else {
                alert(result.message || 'Gagal simulasi');
            }
        } catch (e) {
            console.error('Simulation error:', e);
        } finally {
            btn.disabled = false;
            btn.innerHTML = origText;
        }
    }

    // ── Lifecycle Initialization ──────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        updateDigitalClock();
        setInterval(updateDigitalClock, 1000);

        fetchLiveBoxData();
        pollingInterval = setInterval(fetchLiveBoxData, 3500);
    });
</script>
@endpush
