@extends('layouts.app')

@section('title', 'Scanner Absensi USB RFID')

@section('content')
<style>
    .custom-scroll-box {
        max-height: 480px;
        overflow-y: auto;
    }
    .custom-scroll-box::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scroll-box::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scroll-box::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9999px;
    }
    .custom-scroll-box::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .dark .custom-scroll-box::-webkit-scrollbar-thumb {
        background: #334155;
    }
    .dark .custom-scroll-box::-webkit-scrollbar-thumb:hover {
        background: #475569;
    }
</style>

<div x-data="usbScannerApp()" x-init="init()" class="space-y-6">

    <!-- HIDDEN INPUT UNTUK SCANNER USB RFID (HID Keyboard Reader) -->
    <!-- Input ini sepenuhnya di-hidden namun selalu fokus dan menangkap input scan -->
    <input type="text"
        x-ref="rfidInput"
        x-model="uidInput"
        @input="handleInput"
        @keydown.enter.prevent="triggerManualScan"
        autocomplete="off"
        tabindex="-1"
        aria-hidden="true"
        style="position: absolute; opacity: 0; pointer-events: none; width: 1px; height: 1px; top: -100px; left: -100px;">

    <!-- TOP HEADER BAR: TITLE, STATUS & CONTROLS -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-500 text-white shadow-md shadow-brand-500/25">
                <i class="fas fa-id-card text-xl"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white/90">
                        Scanner Absensi USB
                    </h2>
                    <!-- Status Scanner Ready Indicator -->
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-800/40">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Siap Scan
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">Tempelkan kartu RFID pada scanner USB</p>
            </div>
        </div>

        <!-- Controls: Jam, Sound, Fullscreen, Table Link -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Digital Clock & Date -->
            <div class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark dark:text-gray-200">
                <i class="fas fa-clock text-brand-500"></i>
                <span x-text="currentTime" class="font-mono text-sm text-gray-800 dark:text-white font-bold">--:--:--</span>
                <span class="text-gray-300 dark:text-gray-700">|</span>
                <span x-text="currentDate" class="text-gray-500 dark:text-gray-400 font-medium">--</span>
            </div>

            <!-- Audio Toggle Button -->
            <button type="button" @click="soundEnabled = !soundEnabled"
                :title="soundEnabled ? 'Suara Aktif (Klik untuk Mute)' : 'Suara Bisu (Klik untuk Bunyi)'"
                class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 hover:border-brand-500 hover:bg-brand-50 hover:text-brand-600 shadow-theme-xs transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-400 dark:hover:bg-gray-800">
                <i class="fas fa-volume-up text-brand-500 text-sm" x-show="soundEnabled"></i>
                <i class="fas fa-volume-mute text-gray-400 text-sm" x-show="!soundEnabled" style="display: none;"></i>
            </button>

            <!-- Fullscreen Kiosk Mode Toggle -->
            <button type="button" @click="toggleFullscreen()"
                title="Layar Penuh (Kiosk Mode)"
                class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 hover:border-brand-500 hover:bg-brand-50 hover:text-brand-600 shadow-theme-xs transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-400 dark:hover:bg-gray-800">
                <i class="fas fa-expand text-gray-600 dark:text-gray-300 text-sm" x-show="!isFullscreen"></i>
                <i class="fas fa-compress text-brand-500 text-sm" x-show="isFullscreen" style="display: none;"></i>
            </button>

            <!-- Link ke Scanner Wajah -->
            <a href="{{ route('absensi.scanner-face') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-purple-200 bg-purple-50 px-3.5 py-2 text-xs font-semibold text-purple-700 hover:bg-purple-100 shadow-theme-xs transition dark:border-purple-800/40 dark:bg-purple-900/20 dark:text-purple-300">
                <i class="fas fa-camera text-purple-500"></i>
                <span>Scanner Wajah</span>
            </a>

            <!-- Link Kembali ke Data Tabel Absensi -->
            <a href="{{ route('absensi.index') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 shadow-theme-xs transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800">
                <i class="fas fa-list text-gray-400"></i>
                <span>Tabel Data</span>
            </a>
        </div>
    </div>

    <!-- MAIN SPLIT WORKSPACE: KIRI (SPOTLIGHT POTRET) & KANAN (3 CARDS + TABEL REALTIME) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- ==================== SISI KIRI: SPOTLIGHT CARD (FOTO + DETAIL SISWA) ==================== -->
        <div class="lg:col-span-5 xl:col-span-4 lg:sticky lg:top-20 space-y-4">
            <div class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark transition-all duration-300">
                <!-- Decorative Ambient Glow -->
                <div class="absolute -top-16 -right-16 w-40 h-40 bg-brand-500/10 dark:bg-brand-500/20 rounded-full blur-3xl pointer-events-none"></div>

                <!-- Top Header Row: Label & Tap Time -->
                <div class="flex items-center justify-between gap-2 pb-3 mb-3 border-b border-gray-100 dark:border-gray-800">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-500 px-3 py-1 text-[11px] font-black uppercase text-white tracking-wide shadow-sm">
                        <i class="fas fa-bolt text-amber-300"></i> TAP TERAKHIR
                    </span>
                    <span class="inline-flex items-center gap-1.5 font-mono text-xs font-bold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2.5 py-1 rounded-lg">
                        <i class="fas fa-clock text-[10px] text-gray-400"></i>
                        <span x-text="spotlight.waktu || '--:--:--'">--:--:--</span>
                    </span>
                </div>

                <!-- Active Progress / Loading Bar -->
                <div class="w-full bg-gray-100 dark:bg-gray-800 h-1 overflow-hidden rounded-full mb-4">
                    <div class="h-full bg-brand-500 rounded-full transition-all duration-300"
                        :style="'width: ' + timerPercent + '%;'"
                        :class="isLoading ? 'w-full animate-pulse' : ''"></div>
                </div>

                <!-- Portrait Center: Full Portrait Photo / Avatar Frame (3:4 aspect ratio) -->
                <div class="text-center">
                    <div class="relative mx-auto w-52 sm:w-56 aspect-[3/4] mb-4" style="max-width: 220px; aspect-ratio: 3/4;">
                        <div class="w-full h-full rounded-3xl overflow-hidden border-2 border-dashed border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40 shadow-inner flex items-center justify-center transition-all"
                            :class="{
                                'border-solid border-emerald-500 ring-4 ring-emerald-500/15': spotlight.statusType === 'success',
                                'border-solid border-amber-500 ring-4 ring-amber-500/15': spotlight.statusType === 'warning',
                                'border-solid border-rose-500 ring-4 ring-rose-500/15': spotlight.statusType === 'error',
                                'border-dashed border-gray-300 dark:border-gray-700': !spotlight.statusType
                            }">
                            <!-- Photo Image -->
                            <img x-show="spotlight.foto"
                                :src="spotlight.foto"
                                alt="Foto Siswa"
                                class="w-full h-full object-cover object-top"
                                x-on:error="spotlight.foto = null"
                                style="display: none;">

                            <!-- Fallback Icon Placeholder (Menunggu Siswa / Foto Kosong) -->
                            <span x-show="!spotlight.foto" class="text-5xl font-black text-gray-300 dark:text-gray-600">
                                <i class="fas fa-id-card-alt text-5xl"></i>
                            </span>

                            <!-- Loading Overlay Spinner -->
                            <div x-show="isLoading" class="absolute inset-0 bg-white/70 dark:bg-gray-900/70 flex items-center justify-center backdrop-blur-xs transition-opacity" style="display: none;">
                                <i class="fas fa-spinner fa-spin text-4xl text-brand-500"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Status Pill Badge -->
                    <div class="mb-2">
                        <span class="inline-flex rounded-full px-3.5 py-1 text-xs font-bold shadow-xs border transition"
                            :class="{
                                'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:border-emerald-800': spotlight.statusType === 'success' || spotlight.status === 'Hadir',
                                'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:border-amber-800': spotlight.statusType === 'warning' || spotlight.status === 'Terlambat',
                                'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-500/15 dark:text-rose-400 dark:border-rose-800': spotlight.statusType === 'error',
                                'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-500/15 dark:text-sky-400 dark:border-sky-800': spotlight.status === 'Pulang',
                                'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700': !spotlight.statusType
                            }"
                            x-text="spotlight.status || 'Standby Absensi'">
                            Standby Absensi
                        </span>
                    </div>

                    <!-- Student Name -->
                    <h3 class="text-xl md:text-2xl font-black text-gray-800 dark:text-white tracking-tight leading-snug"
                        x-text="spotlight.nama || 'Menunggu Siswa...'">
                        Menunggu Siswa...
                    </h3>

                    <!-- Class & NIS -->
                    <div class="flex items-center justify-center gap-2 mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                        <span class="font-bold text-gray-700 dark:text-gray-300">Kelas <span x-text="spotlight.kelas || '-'">-</span></span>
                        <span>&bull;</span>
                        <span class="font-mono">NIS: <span x-text="spotlight.nis || '-'">-</span></span>
                    </div>
                </div>

                <!-- Bottom Metric Box: Jam Masuk & Jam Pulang in 2-Columns -->
                <div class="mt-5 grid grid-cols-2 gap-3 p-3 rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                    <div class="text-center p-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 shadow-2xs">
                        <span class="block text-[10px] uppercase font-bold text-gray-400 tracking-wider">Jam Masuk</span>
                        <span class="block font-mono text-base font-black text-emerald-600 dark:text-emerald-400 mt-0.5"
                            x-text="spotlight.jam_masuk || '--:--'">--:--</span>
                    </div>
                    <div class="text-center p-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 shadow-2xs">
                        <span class="block text-[10px] uppercase font-bold text-gray-400 tracking-wider">Jam Pulang</span>
                        <span class="block font-mono text-base font-black text-sky-600 dark:text-sky-400 mt-0.5"
                            x-text="spotlight.jam_pulang || '--:--'">--:--</span>
                    </div>
                </div>

                <!-- Footer Note / Keterangan -->
                <div class="mt-3.5 pt-3 border-t border-gray-100 dark:border-gray-800 text-center">
                    <p class="text-xs text-gray-500 dark:text-gray-400 italic"
                        x-text="spotlight.keterangan || 'Silakan tap kartu RFID ...'">
                        Silakan tap kartu RFID ...
                    </p>
                </div>
            </div>
        </div>

        <!-- ==================== SISI KANAN: 3 CARDS STATS + TABEL REALTIME ==================== -->
        <div class="lg:col-span-7 xl:col-span-8 space-y-6">

            <!-- 1. STATS OVERVIEW CARDS: Tepat Waktu, Terlambat, Belum Absen (3 CARDS SESUAI REQUEST) -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-4">
                
                <!-- Card 1: Tepat Waktu -->
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark flex items-center justify-between">
                    <div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Tepat Waktu</span>
                        <h4 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white mt-1" x-text="stats.totalTepatWaktu">
                            {{ $totalTepatWaktu }}
                        </h4>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-emerald-50 dark:bg-emerald-500/15 flex items-center justify-center text-emerald-500 shadow-sm">
                        <i class="fas fa-check-circle text-xl"></i>
                    </div>
                </div>

                <!-- Card 2: Terlambat -->
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark flex items-center justify-between">
                    <div>
                        <span class="text-xs font-medium text-amber-600 dark:text-amber-400 font-semibold">Terlambat</span>
                        <h4 class="text-2xl md:text-3xl font-black text-amber-600 dark:text-amber-400 mt-1" x-text="stats.totalTerlambat">
                            {{ $totalTerlambat }}
                        </h4>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-amber-50 dark:bg-amber-500/15 flex items-center justify-center text-amber-500 shadow-sm">
                        <i class="fas fa-clock text-xl"></i>
                    </div>
                </div>

                <!-- Card 3: Belum Absen -->
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark flex items-center justify-between">
                    <div>
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Belum Absen</span>
                        <h4 class="text-2xl md:text-3xl font-black text-gray-800 dark:text-white mt-1" x-text="stats.totalBelumAbsen">
                            {{ $totalBelumAbsen }}
                        </h4>
                    </div>
                    <div class="w-11 h-11 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-300 shadow-sm">
                        <i class="fas fa-user-clock text-xl"></i>
                    </div>
                </div>

            </div>

            <!-- 2. DAFTAR ABSENSI HARI INI (TABEL REAL-TIME / MODEL BOX) -->
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark overflow-hidden">
                
                <!-- Card Header -->
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <h4 class="text-base font-bold text-gray-800 dark:text-white flex items-center gap-2">
                            <i class="fas fa-list-ul text-brand-500"></i>
                            <span>Daftar Absensi Hari Ini</span>
                        </h4>
                        <span class="rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 px-2.5 py-0.5 text-xs font-bold"
                            x-text="attendances.length + ' Siswa'">
                            {{ count($initialAttendances ?? []) }} Siswa
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Update Time Indicator -->
                        <span class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1.5 font-mono">
                            <i class="fas fa-sync-alt text-[10px]"></i>
                            <span x-text="'Update: ' + lastUpdateTime">Update: {{ date('H:i:s') }}</span>
                        </span>
                    </div>
                </div>

                <!-- DAFTAR MODEL LIST / TABEL DENGAN SCROLLBOX -->
                <div class="max-w-full custom-scroll-box border-b border-gray-100 dark:border-gray-800" style="max-height: 480px; overflow-y: auto; overflow-x: auto;">
                    <table class="w-full table-auto">
                        <thead class="sticky top-0 z-20 bg-gray-50/95 dark:bg-gray-800/95 backdrop-blur-sm shadow-xs border-b border-gray-200 dark:border-gray-700">
                            <tr class="text-left text-gray-800 dark:text-white/90 font-medium text-xs uppercase tracking-wider">
                                <th class="px-3.5 py-3 text-center w-10">#</th>
                                <th class="px-3.5 py-3 min-w-[80px]">Waktu</th>
                                <th class="px-3.5 py-3 min-w-[150px]">Nama Siswa</th>
                                <th class="px-3.5 py-3 min-w-[90px]">NIS</th>
                                <th class="px-3.5 py-3 min-w-[70px]">Kelas</th>
                                <th class="px-3.5 py-3 min-w-[80px]">Masuk</th>
                                <th class="px-3.5 py-3 min-w-[80px]">Pulang</th>
                                <th class="px-3.5 py-3 min-w-[100px] text-center">Status</th>
                                <th class="px-3.5 py-3 min-w-[120px]">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                            <template x-if="attendances.length === 0">
                                <tr>
                                    <td colspan="9" class="px-4 py-12 text-center text-gray-400 italic">
                                        <div class="mx-auto w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-400 text-xl mb-2">
                                            <i class="fas fa-id-card"></i>
                                        </div>
                                        Belum ada data absensi hari ini. Silakan tap kartu RFID pada scanner USB.
                                    </td>
                                </tr>
                            </template>

                            <template x-for="(att, index) in attendances" :key="att.id || (att.nis + '-' + index)">
                                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/50 transition">
                                    <td class="px-3.5 py-3 text-center text-xs font-semibold text-gray-400" x-text="index + 1"></td>
                                    <td class="px-3.5 py-3 whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 font-mono text-xs font-semibold text-gray-700 dark:text-gray-300">
                                            <i class="far fa-clock text-gray-400 text-[11px]"></i>
                                            <span x-text="att.waktu"></span>
                                        </span>
                                    </td>
                                    <td class="px-3.5 py-3">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-800 dark:text-white" x-text="att.nama"></span>
                                        </div>
                                    </td>
                                    <td class="px-3.5 py-3 whitespace-nowrap">
                                        <span class="font-mono text-xs text-gray-500 dark:text-gray-400" x-text="att.nis"></span>
                                    </td>
                                    <td class="px-3.5 py-3 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400 text-xs font-semibold"
                                            x-text="att.kelas"></span>
                                    </td>
                                    <td class="px-3.5 py-3 whitespace-nowrap">
                                        <span class="font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400" x-text="att.masuk"></span>
                                    </td>
                                    <td class="px-3.5 py-3 whitespace-nowrap">
                                        <span class="font-mono text-xs font-bold text-gray-700 dark:text-gray-300" x-text="att.pulang"></span>
                                    </td>
                                    <td class="px-3.5 py-3 text-center whitespace-nowrap">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold shadow-2xs"
                                            :class="{
                                                'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300': att.status === 'Pulang',
                                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300': att.status === 'Hadir',
                                                'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300': att.status === 'Terlambat',
                                                'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300': att.status === 'Izin' || att.status === 'Sakit',
                                                'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300': !['Pulang','Hadir','Terlambat','Izin','Sakit'].includes(att.status)
                                            }"
                                            x-text="att.status"></span>
                                    </td>
                                    <td class="px-3.5 py-3 whitespace-nowrap">
                                        <span class="text-xs text-gray-500 dark:text-gray-400" x-text="att.keterangan || '-'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
function usbScannerApp() {
    return {
        uidInput: '',
        inputBuffer: '',
        scanMode: 'auto',
        debounceTimer: null,
        resetTimer: null,
        timerPercent: 0,
        isLoading: false,
        soundEnabled: true,
        isFullscreen: false,
        currentTime: '',
        currentDate: '',
        lastUpdateTime: '{{ date("H.i.s") }}',
        lastScannedUid: '',
        lastScannedTime: 0,
        audioCtx: null,

        // Spotlight State
        spotlight: {
            foto: null,
            nama: 'Menunggu Siswa...',
            nis: '-',
            kelas: '-',
            status: 'Standby Absensi',
            statusType: null, // 'success', 'warning', 'error', 'pulang'
            waktu: '--:--:--',
            jam_masuk: '--:--',
            jam_pulang: '--:--',
            keterangan: 'Silakan tap kartu RFID ...'
        },

        // Counter Cards: Tepat Waktu, Terlambat, Belum Absen
        stats: {
            totalTepatWaktu: {{ $totalTepatWaktu ?? 0 }},
            totalTerlambat: {{ $totalTerlambat ?? 0 }},
            totalBelumAbsen: {{ $totalBelumAbsen ?? 0 }},
            totalSiswa: {{ $totalSiswa ?? 0 }},
            totalMasuk: {{ $totalMasuk ?? 0 }},
            totalPulang: {{ $totalPulang ?? 0 }}
        },

        // Real-time Attendance List
        attendances: @json($initialAttendances ?? []),

        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);

            // Fokuskan input hidden saat pertama kali dimuat
            this.focusInput();

            // Pasang auto re-focus dan unlock AudioContext saat ada interaksi
            document.addEventListener('click', (e) => {
                this.getAudioContext();
                if (!e.target.closest('button, a, select, textarea')) {
                    setTimeout(() => this.focusInput(), 60);
                }
            });

            // Global Keystroke Buffer: fail-safe jika input kehilangan fokus
            window.addEventListener('keydown', (e) => {
                this.getAudioContext();
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                    return; // Normal handling
                }

                if (e.key === 'Enter') {
                    if (this.inputBuffer.trim().length > 0) {
                        const uid = this.inputBuffer.trim();
                        this.inputBuffer = '';
                        this.processCardScan(uid);
                    }
                } else if (e.key && e.key.length === 1) {
                    this.inputBuffer += e.key;
                    clearTimeout(this.debounceTimer);
                    this.debounceTimer = setTimeout(() => {
                        if (this.inputBuffer.trim().length >= 4) {
                            const uid = this.inputBuffer.trim();
                            this.inputBuffer = '';
                            this.processCardScan(uid);
                        }
                    }, 180);
                }
            });

            // Fullscreen change listener
            ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach(evt => {
                document.addEventListener(evt, () => {
                    this.isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
                });
            });

            // Jika ada initial data, spotlight bisa tampilkan yang terbaru
            if (this.attendances && this.attendances.length > 0) {
                const latest = this.attendances[0];
                this.spotlight = {
                    foto: latest.foto || null,
                    nama: latest.nama || 'Menunggu Siswa...',
                    nis: latest.nis || '-',
                    kelas: latest.kelas || '-',
                    status: latest.status || 'Standby Absensi',
                    statusType: latest.statusType || 'success',
                    waktu: latest.waktu || '--:--:--',
                    jam_masuk: latest.masuk || '--:--',
                    jam_pulang: latest.pulang || '--:--',
                    keterangan: latest.keterangan || 'Silakan tap kartu RFID ...'
                };
            }
        },

        focusInput() {
            if (this.$refs && this.$refs.rfidInput) {
                this.$refs.rfidInput.focus();
            }
        },

        updateClock() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('id-ID', { hour12: false });
            this.currentDate = now.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
        },

        toggleFullscreen() {
            const docEl = document.documentElement;
            if (!this.isFullscreen) {
                const req = docEl.requestFullscreen || docEl.webkitRequestFullscreen || docEl.mozRequestFullScreen || docEl.msRequestFullscreen;
                if (req) req.call(docEl).catch(err => console.warn(err));
                this.isFullscreen = true;
            } else {
                const exit = document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen || document.msExitFullscreen;
                if (exit && document.fullscreenElement) exit.call(document).catch(err => console.warn(err));
                this.isFullscreen = false;
            }
        },

        handleInput(e) {
            clearTimeout(this.debounceTimer);
            const val = this.uidInput.trim();
            if (!val) return;

            // Scanner USB mengirim karakter berurutan lalu diakhiri
            this.debounceTimer = setTimeout(() => {
                if (this.uidInput.trim().length >= 4 && !this.isLoading) {
                    const uid = this.uidInput.trim();
                    this.uidInput = '';
                    this.processCardScan(uid);
                }
            }, 180);
        },

        triggerManualScan() {
            clearTimeout(this.debounceTimer);
            const uid = this.uidInput.trim();
            if (uid && !this.isLoading) {
                this.uidInput = '';
                this.processCardScan(uid);
            }
        },

        getAudioContext() {
            if (!this.audioCtx) {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (AudioCtx) this.audioCtx = new AudioCtx();
            }
            if (this.audioCtx && this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }
            return this.audioCtx;
        },

        playBeep(type) {
            if (!this.soundEnabled) return;
            try {
                const ctx = this.getAudioContext();
                if (!ctx) return;

                const now = ctx.currentTime;

                if (type === 'ok' || type === 'success') {
                    // 🔔 SUARA BERHASIL: Chime Melodis Ceria 2-Nada ("Ting-Tung!" Harmoni Tinggi)
                    // Nada 1: 784 Hz (G5) - Jernih dan renyah
                    const osc1 = ctx.createOscillator();
                    const gain1 = ctx.createGain();
                    osc1.type = 'sine';
                    osc1.frequency.setValueAtTime(784, now);
                    gain1.gain.setValueAtTime(0.35, now);
                    gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.12);
                    osc1.connect(gain1);
                    gain1.connect(ctx.destination);
                    osc1.start(now);
                    osc1.stop(now + 0.12);

                    // Nada 2: 1174.66 Hz (D6) - Nada kedua lebih tinggi, ceria & jelas
                    const osc2 = ctx.createOscillator();
                    const gain2 = ctx.createGain();
                    osc2.type = 'sine';
                    osc2.frequency.setValueAtTime(1174.66, now + 0.1);
                    gain2.gain.setValueAtTime(0.4, now + 0.1);
                    gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.38);
                    osc2.connect(gain2);
                    gain2.connect(ctx.destination);
                    osc2.start(now + 0.1);
                    osc2.stop(now + 0.38);

                } else if (type === 'warning') {
                    // ⚠️ SUARA PERINGATAN (Sudah Absen / Jam Tutup): Nada Ganda Sedang ("Pip-Pip")
                    [0, 0.12].forEach((offset) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'triangle';
                        osc.frequency.setValueAtTime(659.25, now + offset); // E5
                        gain.gain.setValueAtTime(0.3, now + offset);
                        gain.gain.exponentialRampToValueAtTime(0.001, now + offset + 0.09);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(now + offset);
                        osc.stop(now + offset + 0.09);
                    });

                } else {
                    // ❌ SUARA GAGAL / ERROR (Kartu Belum Terdaftar / Ditolak): Double Buzzer Rendah Kasar ("BZZT-BZZT!")
                    [0, 0.18].forEach((offset) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sawtooth'; // Gelombang gergaji kasar
                        osc.frequency.setValueAtTime(160, now + offset);
                        osc.frequency.linearRampToValueAtTime(100, now + offset + 0.14); // Turun ke nada sangat rendah
                        gain.gain.setValueAtTime(0.45, now + offset);
                        gain.gain.exponentialRampToValueAtTime(0.01, now + offset + 0.14);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(now + offset);
                        osc.stop(now + offset + 0.14);
                    });
                }
            } catch (e) {
                console.warn('Audio tone error:', e);
            }
        },

        startTimerBar() {
            clearInterval(this.resetTimer);
            this.timerPercent = 100;
            const durationMs = 4000;
            const intervalMs = 50;
            const step = (intervalMs / durationMs) * 100;

            this.resetTimer = setInterval(() => {
                this.timerPercent = Math.max(0, this.timerPercent - step);
                if (this.timerPercent <= 0) {
                    clearInterval(this.resetTimer);
                }
            }, intervalMs);
        },

        async processCardScan(uid) {
            uid = uid.trim();
            if (!uid || this.isLoading) return;

            // Debounce double tap kartu yang sama dalam kurun 2 detik
            const nowMs = Date.now();
            if (this.lastScannedUid === uid && (nowMs - this.lastScannedTime) < 2000) {
                return;
            }
            this.lastScannedUid = uid;
            this.lastScannedTime = nowMs;

            this.isLoading = true;

            try {
                const response = await fetch("{{ route('absensi.scan-usb') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        "Accept": "application/json"
                    },
                    body: JSON.stringify({ uid: uid, mode: this.scanMode })
                });

                const res = await response.json();
                const nowClock = new Date().toLocaleTimeString('id-ID', { hour12: false });
                this.lastUpdateTime = nowClock.replace(/:/g, '.');

                const soundType = res.sound || (res.ok ? 'ok' : 'error');
                this.playBeep(soundType);

                const data = res.data || {};
                const isPulang = res.type && res.type.includes('pulang');

                // Update Spotlight Siswa
                this.spotlight = {
                    foto: data.foto || null,
                    nama: data.nama || (res.ok ? 'Teridentifikasi' : 'Kartu Tidak Dikenal'),
                    nis: data.nis || '-',
                    kelas: data.kelas || data.role || '-',
                    status: data.status || (res.ok ? (isPulang ? 'Pulang' : 'Hadir') : 'Ditolak'),
                    statusType: res.status || (res.ok ? 'success' : 'error'),
                    waktu: data.jam || nowClock,
                    jam_masuk: data.jam_masuk || (isPulang ? '--:--' : nowClock.substring(0, 5)),
                    jam_pulang: data.jam_pulang || (isPulang ? nowClock.substring(0, 5) : '--:--'),
                    keterangan: data.keterangan || res.message || 'Silakan tap kartu RFID...'
                };

                this.startTimerBar();

                if (res.ok && data.nama && data.nama !== 'Tidak Dikenal') {
                    // Cari apakah data siswa sudah ada di tabel hari ini
                    const existingIdx = this.attendances.findIndex(item => item.nis && data.nis && item.nis === data.nis);

                    const newRow = {
                        id: Date.now(),
                        waktu: data.jam || nowClock,
                        nama: data.nama,
                        nis: data.nis || '-',
                        foto: data.foto || null,
                        kelas: data.kelas || '-',
                        masuk: data.jam_masuk || (existingIdx !== -1 ? this.attendances[existingIdx].masuk : nowClock.substring(0, 5)),
                        pulang: data.jam_pulang || (isPulang ? nowClock.substring(0, 5) : (existingIdx !== -1 ? this.attendances[existingIdx].pulang : '--:--')),
                        status: data.status || (isPulang ? 'Pulang' : 'Hadir'),
                        statusType: res.status || 'success',
                        keterangan: data.keterangan || res.message
                    };

                    if (existingIdx !== -1) {
                        // Perbarui data yang sudah ada dan pindahkan ke paling atas
                        this.attendances.splice(existingIdx, 1);
                        this.attendances.unshift(newRow);
                    } else {
                        // Masukkan baris baru di paling atas
                        this.attendances.unshift(newRow);
                    }

                    // Update Counters: Tepat Waktu, Terlambat, Belum Absen
                    if (res.status === 'success') {
                        if (isPulang) {
                            this.stats.totalPulang++;
                        } else {
                            this.stats.totalMasuk++;
                            this.stats.totalBelumAbsen = Math.max(0, this.stats.totalSiswa - this.stats.totalMasuk);
                            if (data.status && data.status.includes('Terlambat')) {
                                this.stats.totalTerlambat++;
                            } else {
                                this.stats.totalTepatWaktu++;
                            }
                        }
                    }
                }

            } catch (err) {
                console.error('Scan Error:', err);
                this.playBeep('error');
                this.spotlight = {
                    foto: null,
                    nama: 'Koneksi Terputus',
                    nis: '-',
                    kelas: '-',
                    status: 'Error Jaringan',
                    statusType: 'error',
                    waktu: '--:--:--',
                    jam_masuk: '--:--',
                    jam_pulang: '--:--',
                    keterangan: 'Gagal menghubungi server absensi.'
                };
            } finally {
                this.isLoading = false;
                this.uidInput = '';
                this.inputBuffer = '';
                this.$nextTick(() => this.focusInput());
            }
        }
    }
}
</script>
@endpush
