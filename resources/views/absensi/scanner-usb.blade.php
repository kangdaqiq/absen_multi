@extends('layouts.app')

@section('title', 'Scanner Absensi USB RFID')

@section('content')
<div x-data="fullPageUsbScanner()" x-init="initScanner()" class="space-y-5">
    
    <!-- Top Bar: Title, Clock & Controls -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-white shadow-md shadow-brand-500/25">
                <i class="fas fa-id-card text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white/90">
                    Scanner Absensi
                </h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">Tempel kartu ke reader USB untuk absen</p>
            </div>
        </div>

        <!-- Right Action & Controls -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Digital Clock & Date -->
            <div class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark dark:text-gray-200">
                <i class="fas fa-clock text-brand-500"></i>
                <span x-text="currentTime" class="font-mono text-sm text-gray-800 dark:text-white font-bold">--:--:--</span>
                <span class="text-gray-300 dark:text-gray-700">|</span>
                <span x-text="currentDate" class="text-gray-500 dark:text-gray-400 font-medium">--</span>
            </div>

            <!-- Audio Toggle -->
            <button type="button" @click="soundEnabled = !soundEnabled"
                :title="soundEnabled ? 'Suara Aktif (Klik untuk Mute)' : 'Suara Bisu (Klik untuk Bunyi)'"
                class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 hover:border-brand-500 hover:bg-brand-50 hover:text-brand-600 shadow-theme-xs transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-400 dark:hover:bg-gray-800">
                <i class="fas fa-volume-up text-brand-500 text-sm" x-show="soundEnabled"></i>
                <i class="fas fa-volume-mute text-gray-400 text-sm" x-show="!soundEnabled" style="display: none;"></i>
            </button>

            <!-- Fullscreen Toggle -->
            <button type="button" @click="toggleFullscreen()"
                title="Layar Penuh (Kiosk Mode)"
                class="flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 hover:border-brand-500 hover:bg-brand-50 hover:text-brand-600 shadow-theme-xs transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-400 dark:hover:bg-gray-800">
                <i class="fas fa-expand text-gray-600 dark:text-gray-300 text-sm" x-show="!isFullscreen"></i>
                <i class="fas fa-compress text-brand-500 text-sm" x-show="isFullscreen" style="display: none;"></i>
            </button>

            <!-- Back to Table Link -->
            <a href="{{ route('absensi.index') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 shadow-theme-xs transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800">
                <i class="fas fa-list text-gray-400"></i>
                <span>Tabel Data</span>
            </a>
        </div>
    </div>

    <!-- Live Counter Stats Bar -->
    <div class="grid grid-cols-2 gap-3.5 sm:grid-cols-2 lg:grid-cols-4">
        
        <!-- Total Siswa -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Siswa</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    <i class="fas fa-users text-sm"></i>
                </span>
            </div>
            <h4 class="mt-2 text-2xl font-bold text-gray-800 dark:text-white/90" x-text="stats.totalSiswa">{{ $totalSiswa }}</h4>
        </div>

        <!-- Tepat Waktu -->
        <div class="rounded-2xl border border-success-200 bg-success-50/60 p-4 shadow-theme-sm dark:border-success-800/40 dark:bg-success-500/10">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-success-700 dark:text-success-400">Tepat Waktu</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-success-500 text-white shadow-sm shadow-success-500/20">
                    <i class="fas fa-user-check text-xs"></i>
                </span>
            </div>
            <h4 class="mt-2 text-2xl font-bold text-success-700 dark:text-success-400" x-text="stats.totalTepatWaktu">{{ $totalTepatWaktu }}</h4>
        </div>

        <!-- Terlambat -->
        <div class="rounded-2xl border border-warning-200 bg-warning-50/60 p-4 shadow-theme-sm dark:border-warning-800/40 dark:bg-warning-500/10">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-warning-700 dark:text-warning-400">Terlambat</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-warning-500 text-white shadow-sm shadow-warning-500/20">
                    <i class="fas fa-user-clock text-xs"></i>
                </span>
            </div>
            <h4 class="mt-2 text-2xl font-bold text-warning-700 dark:text-warning-400" x-text="stats.totalTerlambat">{{ $totalTerlambat }}</h4>
        </div>

        <!-- Belum Absen -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Belum Absen</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-orange-50 text-orange-500 dark:bg-orange-950/40 dark:text-orange-400 shadow-sm">
                    <i class="fas fa-hourglass-half text-xs"></i>
                </span>
            </div>
            <h4 class="mt-2 text-2xl font-bold text-orange-600 dark:text-orange-400" x-text="stats.totalBelumAbsen">{{ $totalBelumAbsen }}</h4>
        </div>
    </div>

    <!-- Main Content Layout (Left: Scanner Terminal & Hero Result, Right: Live Activity Stream) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        
        <!-- Left Column: Scanner Card & Result Banner (7 cols) -->
        <div class="lg:col-span-7 space-y-5">
            
            <!-- RFID Contactless Terminal Card -->
            <div class="relative overflow-hidden rounded-2xl border-2 transition-all p-6 sm:p-8 bg-white dark:bg-gray-dark shadow-theme-sm"
                :class="isLoading 
                    ? 'border-brand-500 ring-4 ring-brand-500/10' 
                    : (lastResult 
                        ? (lastResult.status === 'success' ? 'border-success-500' : (lastResult.status === 'warning' ? 'border-warning-500' : 'border-error-500')) 
                        : 'border-brand-300 dark:border-brand-700')">
                
                <div class="flex flex-col items-center justify-center text-center">
                    
                    <!-- Scanner Animation Target Icon -->
                    <div class="relative my-3 cursor-pointer group" @click="focusInput()">
                        <div class="absolute -inset-2.5 rounded-3xl bg-brand-500/20 animate-ping opacity-60" x-show="!isLoading"></div>
                        <div class="flex h-24 w-24 sm:h-28 sm:w-28 items-center justify-center rounded-3xl bg-brand-500 text-white shadow-xl shadow-brand-500/35 transition transform group-hover:scale-105"
                            :class="isLoading ? 'scale-110' : ''">
                            <i class="fas fa-rss text-4xl sm:text-5xl text-white" x-show="!isLoading"></i>
                            <i class="fas fa-spinner fa-spin text-4xl sm:text-5xl text-white" x-show="isLoading" style="display: none;"></i>
                        </div>
                    </div>

                    <h3 class="mt-3 text-lg sm:text-xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                        <span x-show="!isLoading">TEMPELKAN KARTU RFID</span>
                        <span x-show="isLoading" style="display: none;" class="text-brand-500">MEMPROSES KARTU...</span>
                    </h3>
                    
                    <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400 max-w-sm">
                        Tempelkan kartu RFID ke scanner USB.
                    </p>

                    <!-- Auto-Focused Input (Catches USB RFID Reader Keystrokes) -->
                    <div class="w-full max-w-md mt-5">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-brand-500">
                                <i class="fas fa-credit-card text-sm"></i>
                            </div>
                            <input type="text" x-ref="rfidInput" x-model="uidInput"
                                @input="handleInput"
                                @keydown.enter.prevent="triggerManualScan"
                                placeholder="Status: Siap menerima scan kartu..."
                                autofocus
                                autocomplete="off"
                                class="w-full rounded-xl border-2 border-brand-500 bg-brand-50/40 py-3 pl-10 pr-10 text-center font-mono text-sm sm:text-base font-bold tracking-widest text-brand-700 outline-none focus:border-brand-600 focus:ring-4 focus:ring-brand-500/15 dark:bg-brand-950/40 dark:text-brand-300 dark:border-brand-500/60 transition">
                            
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <span class="flex h-2.5 w-2.5 rounded-full bg-success-500 animate-pulse" title="Input aktif"></span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-2 px-1 text-[11px] text-gray-400">
                            <button type="button" @click="focusInput()" class="text-brand-500 hover:underline font-medium">
                                <i class="fas fa-crosshairs mr-1"></i> Fokus Input
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Live Scan Result Card Banner -->
            <div x-show="lastResult" x-cloak x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="rounded-2xl border p-4 sm:p-5 shadow-theme-sm"
                    :class="{
                        'border-success-300 bg-success-50/90 text-success-900 dark:border-success-800/60 dark:bg-success-950/40 dark:text-success-200': lastResult && lastResult.status === 'success',
                        'border-warning-300 bg-warning-50/90 text-warning-900 dark:border-warning-800/60 dark:bg-warning-950/40 dark:text-warning-200': lastResult && lastResult.status === 'warning',
                        'border-error-300 bg-error-50/90 text-error-900 dark:border-error-800/60 dark:bg-error-950/40 dark:text-error-200': lastResult && lastResult.status === 'error',
                    }">
                    
                    <div class="flex items-start sm:items-center gap-4">
                        
                        <!-- Status Icon -->
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl text-white shadow-md"
                            :class="{
                                'bg-success-500 shadow-success-500/30': lastResult && lastResult.status === 'success',
                                'bg-warning-500 shadow-warning-500/30': lastResult && lastResult.status === 'warning',
                                'bg-error-500 shadow-error-500/30': lastResult && lastResult.status === 'error',
                            }">
                            <i class="fas text-xl sm:text-2xl"
                                :class="{
                                    'fa-check': lastResult && lastResult.status === 'success',
                                    'fa-exclamation-triangle': lastResult && lastResult.status === 'warning',
                                    'fa-times': lastResult && lastResult.status === 'error',
                                }"></i>
                        </div>

                        <!-- Details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2 flex-wrap">
                                <h4 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate" 
                                    x-text="lastResult && lastResult.data ? lastResult.data.nama : 'Hasil Scan'"></h4>
                                
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold shadow-sm"
                                    :class="{
                                        'bg-success-600 text-white': lastResult && lastResult.status === 'success',
                                        'bg-warning-500 text-white': lastResult && lastResult.status === 'warning',
                                        'bg-error-600 text-white': lastResult && lastResult.status === 'error',
                                    }"
                                    x-text="lastResult && lastResult.data ? lastResult.data.status : (lastResult ? lastResult.status : '')">
                                </span>
                            </div>

                            <p class="mt-0.5 text-xs sm:text-sm font-semibold"
                                :class="{
                                    'text-success-800 dark:text-success-300': lastResult && lastResult.status === 'success',
                                    'text-warning-800 dark:text-warning-300': lastResult && lastResult.status === 'warning',
                                    'text-error-800 dark:text-error-300': lastResult && lastResult.status === 'error',
                                }"
                                x-text="lastResult ? lastResult.message : ''"></p>

                            <!-- Metadata Badges -->
                            <div class="mt-2.5 flex flex-wrap items-center gap-1.5 text-xs">
                                <template x-if="lastResult && lastResult.data && lastResult.data.kelas">
                                    <span class="rounded-lg bg-white/90 dark:bg-gray-800 px-2 py-0.5 font-semibold text-gray-800 dark:text-gray-200 border border-gray-200/50 dark:border-gray-700">
                                        <i class="fas fa-graduation-cap text-brand-500 mr-1"></i> <span x-text="lastResult.data.kelas"></span>
                                    </span>
                                </template>

                                <template x-if="lastResult && lastResult.data && lastResult.data.jam">
                                    <span class="rounded-lg bg-white/90 dark:bg-gray-800 px-2 py-0.5 font-mono font-semibold text-gray-800 dark:text-gray-200 border border-gray-200/50 dark:border-gray-700">
                                        <i class="fas fa-clock text-blue-light-600 mr-1"></i> <span x-text="lastResult.data.jam"></span>
                                    </span>
                                </template>

                                <template x-if="lastResult && lastResult.data && lastResult.data.uid">
                                    <span class="rounded-lg bg-white/90 dark:bg-gray-800 px-2 py-0.5 font-mono text-gray-600 dark:text-gray-300 border border-gray-200/50 dark:border-gray-700">
                                        UID: <span x-text="lastResult.data.uid"></span>
                                    </span>
                                </template>

                                <template x-if="lastResult && lastResult.data && lastResult.data.keterangan">
                                    <span class="rounded-lg bg-white/90 dark:bg-gray-800 px-2 py-0.5 font-medium text-gray-600 dark:text-gray-300 italic border border-gray-200/50 dark:border-gray-700">
                                        (<span x-text="lastResult.data.keterangan"></span>)
                                    </span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule Info Box -->
            @if($jadwal)
            <div class="rounded-2xl border border-gray-200 bg-white p-3.5 text-xs dark:border-gray-800 dark:bg-gray-dark shadow-theme-xs">
                <div class="flex items-center justify-between font-semibold text-gray-800 dark:text-white/90 mb-1.5">
                    <span class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300">
                        <i class="fas fa-calendar-alt text-brand-500"></i> Jadwal Masuk & Pulang Hari Ini
                    </span>
                    <span class="rounded-md bg-brand-50 px-2 py-0.5 text-brand-600 font-semibold dark:bg-brand-500/15 dark:text-brand-400">
                        {{ \Carbon\Carbon::now()->isoFormat('dddd') }}
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-2 text-gray-600 dark:text-gray-400 text-center pt-1 border-t border-gray-100 dark:border-gray-800">
                    <div>Jam Masuk: <strong class="text-gray-800 dark:text-gray-200 font-mono">{{ \Carbon\Carbon::parse($jadwal->jam_masuk)->format('H:i') }}</strong></div>
                    <div>Batas Masuk: <strong class="text-gray-800 dark:text-gray-200 font-mono">{{ \Carbon\Carbon::parse($jadwal->akhir_absen_masuk)->format('H:i') }}</strong></div>
                    <div>Jam Pulang: <strong class="text-gray-800 dark:text-gray-200 font-mono">{{ \Carbon\Carbon::parse($jadwal->jam_pulang)->format('H:i') }}</strong></div>
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column: Real-time Live Activity Feed (5 cols) -->
        <div class="lg:col-span-5">
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark overflow-hidden flex flex-col">
                
                <!-- Feed Header -->
                <div class="border-b border-gray-100 px-4 py-3.5 dark:border-gray-800 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <h4 class="font-bold text-gray-900 dark:text-white text-sm">Aktivitas Absensi</h4>
                        <span class="flex h-2 w-2 rounded-full bg-success-500 animate-ping"></span>
                    </div>
                    <span class="text-xs font-semibold text-brand-600 dark:text-brand-400" x-text="sessionScans.length + ' aktivitas'"></span>
                </div>

                <!-- Feed List -->
                <div class="overflow-y-auto max-h-[500px] p-2.5 space-y-2">
                    <template x-if="sessionScans.length === 0">
                        <div class="py-14 text-center text-gray-400">
                            <div class="mx-auto mb-2.5 flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/15">
                                <i class="fas fa-id-card text-xl"></i>
                            </div>
                            <p class="font-semibold text-xs text-gray-600 dark:text-gray-400">Belum ada scan pada sesi ini</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">Tempelkan kartu RFID pada scanner USB</p>
                        </div>
                    </template>

                    <template x-for="(item, index) in sessionScans" :key="index">
                        <div class="rounded-xl border border-gray-100 dark:border-gray-800 p-3 bg-gray-50/60 dark:bg-gray-900/40 hover:bg-gray-100/70 dark:hover:bg-gray-800/60 transition-all">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-white font-bold text-xs"
                                        :class="{
                                            'bg-success-500': item.statusType === 'success',
                                            'bg-warning-500': item.statusType === 'warning',
                                            'bg-error-500': item.statusType === 'error'
                                        }">
                                        <i class="fas"
                                            :class="{
                                                'fa-check': item.statusType === 'success',
                                                'fa-exclamation': item.statusType === 'warning',
                                                'fa-times': item.statusType === 'error'
                                            }"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <h5 class="text-xs font-bold text-gray-900 dark:text-white truncate" x-text="item.nama"></h5>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate" x-text="item.kelas"></p>
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold"
                                        :class="{
                                            'bg-success-100 text-success-800 dark:bg-success-500/15 dark:text-success-400': item.statusType === 'success',
                                            'bg-warning-100 text-warning-800 dark:bg-warning-500/15 dark:text-warning-400': item.statusType === 'warning',
                                            'bg-error-100 text-error-800 dark:bg-error-500/15 dark:text-error-400': item.statusType === 'error'
                                        }"
                                        x-text="item.status"></span>
                                    <div class="text-[10px] font-mono text-gray-400 mt-0.5" x-text="item.jam"></div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function fullPageUsbScanner() {
    return {
        uidInput: '',
        inputBuffer: '',
        debounceTimer: null,
        isLoading: false,
        lastResult: null,
        sessionScans: [],
        sessionScansCount: 0,
        soundEnabled: true,
        isFullscreen: false,
        currentTime: '',
        currentDate: '',
        lastScannedUid: '',
        lastScannedTime: 0,

        stats: {
            totalSiswa: {{ $totalSiswa }},
            totalMasuk: {{ $totalMasuk }},
            totalTepatWaktu: {{ $totalTepatWaktu }},
            totalTerlambat: {{ $totalTerlambat }},
            totalPulang: {{ $totalPulang }},
            totalBelumAbsen: {{ $totalBelumAbsen }}
        },

        initScanner() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);

            // Focus input immediately and maintain focus
            this.focusInput();
            document.addEventListener('click', (e) => {
                // Return focus after click unless clicking on button/link
                if (!e.target.closest('button, a, select, textarea')) {
                    setTimeout(() => this.focusInput(), 100);
                }
            });

            // Global Keystroke Catcher: catches scanner even if input loses focus
            window.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                    return; // Normal input handling
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
                    const isFs = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
                    this.isFullscreen = isFs;
                    if (window.Alpine && Alpine.store('sidebar')) {
                        Alpine.store('sidebar').setFullScreen(isFs);
                    }
                });
            });
        },

        updateClock() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('id-ID', { hour12: false });
            this.currentDate = now.toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
        },

        focusInput() {
            if (this.$refs && this.$refs.rfidInput) {
                this.$refs.rfidInput.focus();
            }
        },

        toggleFullscreen() {
            const isCurrentlyFs = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement || this.isFullscreen);
            if (!isCurrentlyFs) {
                const docEl = document.documentElement;
                const req = docEl.requestFullscreen || docEl.webkitRequestFullscreen || docEl.mozRequestFullScreen || docEl.msRequestFullscreen;
                if (req) {
                    req.call(docEl).catch(err => {
                        console.warn(err);
                    });
                }
                this.isFullscreen = true;
                if (window.Alpine && Alpine.store('sidebar')) {
                    Alpine.store('sidebar').setFullScreen(true);
                }
            } else {
                const exit = document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen || document.msExitFullscreen;
                if (exit && document.fullscreenElement) {
                    exit.call(document).catch(err => console.warn(err));
                }
                this.isFullscreen = false;
                if (window.Alpine && Alpine.store('sidebar')) {
                    Alpine.store('sidebar').setFullScreen(false);
                }
            }
        },

        handleInput(e) {
            clearTimeout(this.debounceTimer);
            const val = this.uidInput.trim();

            if (!val) return;

            // Auto-detect when RFID reader finished typing (stream ends after 180ms)
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

        playBeep(type) {
            if (!this.soundEnabled) return;
            try {
                const AudioContext = window.AudioContext || window.webkitAudioContext;
                if (!AudioContext) return;
                const ctx = new AudioContext();

                if (type === 'ok') {
                    // Pleasant High Dual Chime
                    const now = ctx.currentTime;
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(880, now);
                    osc.frequency.exponentialRampToValueAtTime(1320, now + 0.12);
                    gain.gain.setValueAtTime(0.3, now);
                    gain.gain.exponentialRampToValueAtTime(0.01, now + 0.25);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.25);
                } else if (type === 'warning') {
                    // Medium Double Pulse
                    const now = ctx.currentTime;
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(600, now);
                    osc.frequency.setValueAtTime(500, now + 0.1);
                    gain.gain.setValueAtTime(0.25, now);
                    gain.gain.exponentialRampToValueAtTime(0.01, now + 0.25);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.25);
                } else {
                    // Low Buzzer
                    const now = ctx.currentTime;
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(220, now);
                    osc.frequency.setValueAtTime(160, now + 0.15);
                    gain.gain.setValueAtTime(0.3, now);
                    gain.gain.exponentialRampToValueAtTime(0.01, now + 0.35);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(now);
                    osc.stop(now + 0.35);
                }
            } catch (e) {
                console.warn('Audio Tone Error:', e);
            }
        },

        async processCardScan(uid) {
            uid = uid.trim();
            if (!uid || this.isLoading) return;

            // Cooldown protection: prevent accidental double-tap of the same card within 2 seconds
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
                    body: JSON.stringify({ uid: uid })
                });

                const res = await response.json();
                this.lastResult = res;

                const soundType = res.sound || (res.ok ? 'ok' : 'error');
                this.playBeep(soundType);

                // Add to Session Feed
                const data = res.data || {};
                const feedItem = {
                    jam: data.jam || new Date().toLocaleTimeString('id-ID', { hour12: false }),
                    nama: data.nama || (res.ok ? 'Teridentifikasi' : 'Tidak Dikenal'),
                    kelas: data.kelas || data.role || '-',
                    status: data.status || (res.ok ? 'Berhasil' : 'Gagal'),
                    statusType: res.status || (res.ok ? 'success' : 'error'),
                    keterangan: data.keterangan || res.message,
                };

                this.sessionScans.unshift(feedItem);
                if (this.sessionScans.length > 50) this.sessionScans.pop();
                this.sessionScansCount++;

                // Update Real-time Counter Stats
                if (res.status === 'success') {
                    if (res.type && res.type.includes('pulang')) {
                        this.stats.totalPulang++;
                    } else {
                        this.stats.totalMasuk++;
                        this.stats.totalBelumAbsen = Math.max(0, this.stats.totalSiswa - this.stats.totalMasuk);
                        if (res.data && res.data.status && res.data.status.includes('Terlambat')) {
                            this.stats.totalTerlambat++;
                        } else {
                            this.stats.totalTepatWaktu++;
                        }
                    }
                }

            } catch (err) {
                console.error('Scan Error:', err);
                this.lastResult = {
                    ok: false,
                    status: 'error',
                    message: 'Gagal terhubung ke server atau terjadi gangguan koneksi.'
                };
                this.playBeep('error');
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
