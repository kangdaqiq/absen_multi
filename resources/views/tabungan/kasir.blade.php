@extends('layouts.fullscreen-layout')

@section('title', 'POS Loket Kasir Tabungan Siswa')

@push('styles')
<style>
    /* Styling scrollbar untuk feed transaksi */
    .custom-scroll::-webkit-scrollbar {
        width: 6px;
    }
    .custom-scroll::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scroll::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.4);
        border-radius: 9999px;
    }
    .custom-scroll::-webkit-scrollbar-thumb:hover {
        background: rgba(156, 163, 175, 0.7);
    }
    /* Animasi pulsa halus untuk scanner ready */
    @keyframes pulse-ring {
        0% { transform: scale(0.95); opacity: 0.8; }
        50% { transform: scale(1.15); opacity: 0.3; }
        100% { transform: scale(0.95); opacity: 0.8; }
    }
    .pulse-ring {
        animation: pulse-ring 2s infinite ease-in-out;
    }
    /* Thermal Print Rules */
    @media print {
        body * {
            visibility: hidden !important;
        }
        #thermalReceiptPrintArea, #thermalReceiptPrintArea * {
            visibility: visible !important;
        }
        #thermalReceiptPrintArea {
            position: absolute !important;
            left: 0 !important;
            top: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 10px !important;
            border: none !important;
            background: white !important;
            color: black !important;
            box-shadow: none !important;
        }
    }
</style>
@endpush

@section('content')
<div x-data="posKasirApp()" x-init="init()" class="min-h-screen bg-slate-100/90 dark:bg-gray-950 text-gray-800 dark:text-gray-100 flex flex-col font-outfit select-none">

    <!-- ============================================================== -->
    <!-- 1. TOP BAR POS HEADER                                          -->
    <!-- ============================================================== -->
    <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-4 sm:px-6 py-2.5 shrink-0 shadow-xs z-20">
        <div class="flex flex-wrap items-center justify-between gap-3">
            
            <!-- Logo & Loket Title -->
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-brand-500 text-white shadow-md shadow-brand-500/25">
                    <i class="fas fa-cash-register text-lg"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-base sm:text-lg font-black tracking-tight text-gray-900 dark:text-white">
                            POS KASIR TABUNGAN
                        </h1>
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">
                        {{ $school->nama_sekolah ?? config('app.name', 'Sistem Absensi & Tabungan Siswa') }}
                    </p>
                </div>
            </div>

            <!-- Ringkasan Statistik Kasir Hari Ini (Pills) -->
            <div class="hidden md:flex items-center gap-2.5">
                <div class="flex items-center gap-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50 px-3 py-1.5">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-600 text-white text-[10px]">
                        <i class="fas fa-arrow-down"></i>
                    </span>
                    <div>
                        <div class="text-[9px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider">Setor Hari Ini</div>
                        <div class="text-xs font-black text-emerald-700 dark:text-emerald-300" x-text="statsToday.setor_formatted"></div>
                    </div>
                </div>

                <div class="flex items-center gap-2 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/50 px-3 py-1.5">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-rose-600 text-white text-[10px]">
                        <i class="fas fa-arrow-up"></i>
                    </span>
                    <div>
                        <div class="text-[9px] font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider">Tarik Hari Ini</div>
                        <div class="text-xs font-black text-rose-700 dark:text-rose-300" x-text="statsToday.tarik_formatted"></div>
                    </div>
                </div>

                <div class="flex items-center gap-2 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800/50 px-3 py-1.5">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-600 text-white text-[10px]">
                        <i class="fas fa-receipt"></i>
                    </span>
                    <div>
                        <div class="text-[9px] font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider">Total Trx</div>
                        <div class="text-xs font-black text-blue-700 dark:text-blue-300" x-text="statsToday.trx_count + ' Transaksi'"></div>
                    </div>
                </div>
            </div>

            <!-- Jam, Petugas & Kontrol Toolbar -->
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Real-time Clock -->
                <div class="text-right hidden sm:block">
                    <div class="text-sm font-black font-mono tracking-tight text-gray-800 dark:text-gray-100" x-text="currentTime"></div>
                    <div class="text-[10px] text-gray-500 font-medium" x-text="currentDate"></div>
                </div>

                <!-- Info Petugas Kasir -->
                <div class="hidden xl:flex items-center gap-2 border-l border-gray-200 dark:border-gray-800 pl-3">
                    <div class="h-8 w-8 rounded-full bg-brand-100 dark:bg-brand-900/40 text-brand-600 dark:text-brand-300 flex items-center justify-center font-bold text-xs">
                        {{ strtoupper(substr(auth()->user()->full_name ?? 'AD', 0, 2)) }}
                    </div>
                    <div class="text-left leading-tight">
                        <div class="text-xs font-bold text-gray-800 dark:text-white truncate max-w-[120px]">{{ auth()->user()->full_name ?? 'Admin' }}</div>
                        <div class="text-[10px] text-gray-400">Kasir Loket</div>
                    </div>
                </div>

                <!-- Sound Mute/Unmute -->
                <button type="button" @click="soundEnabled = !soundEnabled"
                        :title="soundEnabled ? 'Suara Aktif (Klik untuk Mute)' : 'Suara Senyap (Klik untuk Bunyikan)'"
                        class="h-9 w-9 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 flex items-center justify-center text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition shadow-2xs">
                    <i :class="soundEnabled ? 'fas fa-volume-up text-emerald-600 dark:text-emerald-400' : 'fas fa-volume-mute text-gray-400'"></i>
                </button>

                <!-- Fullscreen Toggle -->
                <button type="button" @click="toggleFullscreen()"
                        title="Mode Layar Penuh (F11)"
                        class="h-9 w-9 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-800 flex items-center justify-center text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition shadow-2xs">
                    <i :class="isFullscreen ? 'fas fa-compress' : 'fas fa-expand'"></i>
                </button>

                <!-- Tombol Kembali ke Menu Utama -->
                <a href="{{ route('tabungan.index') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 px-3 py-2 text-xs font-bold text-gray-700 dark:text-gray-200 transition shadow-2xs">
                    <i class="fas fa-arrow-left"></i>
                    <span class="hidden sm:inline">Menu Utama</span>
                </a>
            </div>
        </div>
    </header>

    <!-- ============================================================== -->
    <!-- 2. MAIN LAYOUT: TWO COLUMNS POS                                -->
    <!-- ============================================================== -->
    <main class="flex-1 p-3 sm:p-5 grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-5 overflow-hidden">

        <!-- ========================================================== -->
        <!-- KOLOM KIRI (7 SPAN): SCANNER, DATA SISWA & FORM TRANSAKSI  -->
        <!-- ========================================================== -->
        <section class="lg:col-span-7 flex flex-col space-y-4 overflow-y-auto pr-0.5 custom-scroll">

            <!-- KARTU 1: SCANNER & IDENTITAS SISWA TERPADU -->
            <div class="rounded-2xl border-2 transition-all duration-200 bg-white dark:bg-gray-900 p-4 sm:p-5 shadow-xs relative"
                 :class="isScanFocused ? 'border-brand-500 shadow-md shadow-brand-500/10' : 'border-gray-200 dark:border-gray-800'">
                
                <!-- Header Card -->
                <div class="flex items-center justify-between mb-2.5">
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                        </span>
                        <label for="rfidScanInput" class="text-xs font-black uppercase tracking-wider text-gray-800 dark:text-gray-200 flex items-center gap-1.5 cursor-pointer">
                            <i class="fas fa-barcode text-brand-500 text-sm"></i>
                            <span>Scan Kartu RFID / Cari Siswa</span>
                        </label>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-[11px] text-gray-400 font-medium hidden sm:inline">
                            Shortcut: <kbd class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-mono text-[10px] border border-gray-200 dark:border-gray-700">ESC</kbd> Batal
                        </span>
                        <button type="button" @click="clearStudent()" x-show="activeStudent || searchQuery"
                                class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:text-rose-700 flex items-center gap-1 ml-1.5">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>

                <!-- Input Scanner with Absolute Floating Dropdown -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400">
                        <i class="fas fa-id-card text-base" :class="{ 'animate-spin text-brand-500': isSearching }"></i>
                    </div>
                    <input
                        id="rfidScanInput"
                        type="text"
                        x-ref="scanInput"
                        x-model="searchQuery"
                        @focus="isScanFocused = true"
                        @blur="isScanFocused = false"
                        @keydown.enter.prevent="handleScanLookup()"
                        @input.debounce.300ms="handleScanLookup()"
                        placeholder="Tempelkan kartu RFID ke USB Reader atau ketik Nama / NIS..."
                        class="w-full rounded-xl border-2 border-gray-200 dark:border-gray-700 bg-gray-50/70 dark:bg-gray-800/60 py-3 pl-11 pr-10 text-sm sm:text-base font-bold text-gray-900 dark:text-white placeholder:text-gray-400 focus:bg-white focus:border-brand-500 focus:ring-4 focus:ring-brand-500/15 outline-none transition"
                        autocomplete="off"
                    />
                    <button
                        type="button"
                        x-show="searchQuery"
                        @click="searchQuery = ''; candidates = []; focusScanInput();"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <i class="fas fa-times-circle text-sm"></i>
                    </button>

                    <!-- DROPDOWN FLOATING REKOMENDASI (ABSOLUTE OVERLAY, TIDAK MERUSAK LAYOUT) -->
                    <div x-show="!activeStudent && candidates.length > 1" x-cloak
                         @click.outside="candidates = []"
                         class="absolute left-0 right-0 top-full mt-1.5 rounded-2xl border border-gray-200 bg-white p-2 dark:border-gray-700 dark:bg-gray-800 max-h-56 overflow-y-auto space-y-1 shadow-2xl z-30 custom-scroll">
                        <div class="text-[10px] font-bold text-gray-400 uppercase px-2.5 py-1 flex items-center justify-between">
                            <span>Pilih Siswa (<span x-text="candidates.length"></span> ditemukan):</span>
                            <span class="text-[9px] lowercase font-normal">klik salah satu</span>
                        </div>
                        <template x-for="c in candidates" :key="c.id">
                            <div @click="selectStudent(c)"
                                 class="flex items-center justify-between p-2.5 rounded-xl hover:bg-brand-50 dark:hover:bg-brand-900/40 cursor-pointer transition border border-transparent hover:border-brand-200">
                                <div>
                                    <div class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white" x-text="c.nama"></div>
                                    <div class="text-[11px] text-gray-500" x-text="'NIS: ' + c.nis + ' • Kelas: ' + c.kelas_nama"></div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-black text-emerald-600 dark:text-emerald-400" x-text="c.saldo_formatted"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- PROFIL SISWA AKTIF (SEAMLESS DI DALAM SATU KARTU) -->
                <div x-show="activeStudent" x-cloak class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-3.5">
                            <div class="relative shrink-0">
                                <img :src="activeStudent?.foto_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(activeStudent?.nama || 'Siswa') + '&background=4f6bed&color=fff'"
                                     class="h-16 w-16 sm:h-18 sm:w-18 rounded-2xl object-cover ring-2 ring-brand-500/30 shadow-md"
                                     alt="Foto Siswa" />
                                <span class="absolute -bottom-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-emerald-500 text-white ring-2 ring-white dark:ring-gray-900 text-[10px] shadow-sm">
                                    <i class="fas fa-check"></i>
                                </span>
                            </div>
                            <div>
                                <div class="text-base sm:text-xl font-black text-gray-900 dark:text-white tracking-tight" x-text="activeStudent?.nama"></div>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-brand-100 text-brand-700 dark:bg-brand-900/60 dark:text-brand-300 text-xs font-extrabold" x-text="activeStudent?.kelas_nama"></span>
                                    <span class="text-xs text-gray-500">NIS: <b class="text-gray-800 dark:text-gray-200" x-text="activeStudent?.nis"></b></span>
                                    <span class="text-[11px] text-gray-400 font-mono hidden sm:inline" x-text="'RFID: ' + (activeStudent?.uid_rfid || '-')"></span>
                                </div>
                                <button type="button" @click="clearStudent()"
                                        class="mt-1.5 text-[11px] font-bold text-rose-600 dark:text-rose-400 hover:underline flex items-center gap-1">
                                    <i class="fas fa-user-times text-xs"></i> Ganti / Batal Siswa (ESC)
                                </button>
                            </div>
                        </div>

                        <!-- Card Saldo Berjalan Siswa Terpilih -->
                        <div class="rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-700 p-3.5 sm:p-4 text-white text-right sm:min-w-[190px] shadow-md shadow-emerald-500/20">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-100 flex items-center justify-end gap-1">
                                <i class="fas fa-wallet text-[10px]"></i> Saldo Tabungan
                            </div>
                            <div class="text-2xl sm:text-3xl font-black mt-0.5 font-mono" x-text="activeStudent?.saldo_formatted"></div>
                            <div class="text-[10px] text-emerald-100/80 mt-0.5 font-medium">Buku Mutasi Siap Transaksi</div>
                        </div>
                    </div>
                </div>

                <!-- STANDBY HINT (KETIKA BELUM ADA SISWA DIPILIH) -->
                <div x-show="!activeStudent" class="mt-3.5 py-3 px-4 rounded-xl bg-gray-50/80 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-wifi text-brand-500 animate-pulse text-sm"></i>
                        <span>Loket Kasir siap. Tempelkan kartu RFID siswa pada USB reader atau ketik nama/NIS di atas.</span>
                    </div>
                    <span class="text-[10px] font-mono font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider hidden sm:inline">
                        ● USB READER STANDBY
                    </span>
                </div>
            </div>

            <!-- FORM TRANSAKSI INSTAN -->
            <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 sm:p-6 shadow-xs">
                <form @submit.prevent="submitTransaction()" class="space-y-5">

                    <!-- Pilihan Mode: SETOR vs TARIK (Tactile POS Buttons) -->
                    <div>
                        <div class="flex items-center justify-between mb-2.5">
                            <label class="text-xs font-bold text-gray-700 dark:text-gray-300">Pilih Jenis Transaksi:</label>
                            <span class="text-[11px] text-gray-400 font-medium">
                                Shortcut: <kbd class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 font-mono text-[10px] border border-gray-200 dark:border-gray-700">F2</kbd> Setor • <kbd class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 font-mono text-[10px] border border-gray-200 dark:border-gray-700">F4</kbd> Tarik
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-3.5">
                            <button
                                type="button"
                                @click="formJenis = 'setor'; focusNominal();"
                                class="flex items-center justify-center gap-2.5 py-3.5 px-4 rounded-xl border-2 font-black text-xs sm:text-sm transition select-none shadow-xs"
                                :class="formJenis === 'setor'
                                    ? 'border-emerald-500 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 ring-2 ring-emerald-500/20'
                                    : 'border-gray-200 bg-gray-50/70 text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-800/40 dark:text-gray-400'">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-white text-xs shrink-0">
                                    <i class="fas fa-arrow-down text-[10px]"></i>
                                </span>
                                <span>SETOR TUNAI (MASUK)</span>
                            </button>

                            <button
                                type="button"
                                @click="formJenis = 'tarik'; focusNominal();"
                                class="flex items-center justify-center gap-2.5 py-3.5 px-4 rounded-xl border-2 font-black text-xs sm:text-sm transition select-none shadow-xs"
                                :class="formJenis === 'tarik'
                                    ? 'border-rose-500 bg-rose-50 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300 ring-2 ring-rose-500/20'
                                    : 'border-gray-200 bg-gray-50/70 text-gray-600 hover:bg-gray-100 dark:border-gray-800 dark:bg-gray-800/40 dark:text-gray-400'">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-rose-600 text-white text-xs shrink-0">
                                    <i class="fas fa-arrow-up text-[10px]"></i>
                                </span>
                                <span>TARIK TUNAI (KELUAR)</span>
                            </button>
                        </div>
                    </div>

                    <!-- Input Nominal Transaksi Besar (ATM / POS Display) -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Nominal Transaksi (Rp):</label>
                            <span class="text-[11px] font-bold"
                                  :class="formJenis === 'setor' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                                  x-text="formJenis === 'setor' ? 'Uang Masuk ke Saldo' : 'Uang Diambil Siswa'"></span>
                        </div>
                        <div class="flex items-center rounded-xl border-2 transition overflow-hidden bg-white dark:bg-gray-950 shadow-theme-xs"
                             :class="formJenis === 'setor' ? 'border-emerald-500 focus-within:ring-4 focus-within:ring-emerald-500/15' : 'border-rose-500 focus-within:ring-4 focus-within:ring-rose-500/15'">
                            <span class="inline-flex items-center px-4 py-3 bg-gray-100 dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 text-base sm:text-lg font-black select-none shrink-0"
                                  :class="formJenis === 'setor' ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400'">
                                Rp
                            </span>
                            <input
                                type="text"
                                x-ref="nominalInput"
                                x-model="formNominalDisplay"
                                @input="formatNominalInput()"
                                placeholder="0"
                                class="w-full bg-transparent py-3 px-4 text-2xl sm:text-3xl font-black font-mono outline-none tracking-tight text-gray-900 dark:text-white"
                                :class="formJenis === 'setor' ? 'placeholder:text-emerald-300' : 'placeholder:text-rose-300'"
                            />
                            <button
                                type="button"
                                x-show="formNominal > 0"
                                @click="formNominal = 0; formNominalDisplay = ''; focusNominal();"
                                class="px-3.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                title="Reset Nominal">
                                <i class="fas fa-backspace text-base"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Tombol Cepat Nominal Instan (Cash Chips) -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold text-gray-500 uppercase">Pilih Nominal Cepat:</span>
                            <span class="text-[11px] text-gray-500 font-medium" x-show="formNominal > 0">
                                Nominal: <b class="text-gray-800 dark:text-gray-100 font-mono" x-text="'Rp ' + formNominalDisplay"></b>
                            </span>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 sm:gap-2.5">
                            <template x-for="(amt, idx) in [5000, 10000, 20000, 50000, 100000, 200000]" :key="amt">
                                <button
                                    type="button"
                                    @click="setNominal(amt)"
                                    class="rounded-xl border py-2.5 px-1 text-xs font-black transition select-none shadow-2xs"
                                    :class="formNominal === amt
                                        ? (formJenis === 'setor' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-rose-600 text-white border-rose-600 shadow-sm')
                                        : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 hover:border-gray-300 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700'">
                                    <span x-text="new Intl.NumberFormat('id-ID').format(amt)"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Keterangan & Metode -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Metode Pembayaran:</label>
                            <select x-model="formMetode" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-xs font-bold text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10">
                                <option value="tunai">Tunai / Cash</option>
                                <option value="transfer">Transfer Bank</option>
                                <option value="qris">QRIS</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">Catatan / Keterangan (Opsional):</label>
                            <input type="text" x-model="formKeterangan" placeholder="Contoh: Setoran harian, tabungan bekal, keperluan beli buku..."
                                   class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-xs text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/10" />
                        </div>
                    </div>

                    <!-- Tombol Eksekusi Transaksi Kasir (Stateful & Energetic) -->
                    <div class="pt-2">
                        <template x-if="!activeStudent">
                            <div class="w-full py-4 px-4 rounded-xl bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-400 dark:text-gray-500 text-center text-xs font-bold flex items-center justify-center gap-2 select-none">
                                <i class="fas fa-hand-pointer"></i>
                                <span>Silakan Tempelkan Kartu RFID Siswa Terlebih Dahulu</span>
                            </div>
                        </template>

                        <template x-if="activeStudent && formNominal <= 0">
                            <div class="w-full py-4 px-4 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/40 text-amber-700 dark:text-amber-300 text-center text-xs font-bold flex items-center justify-center gap-2 select-none">
                                <i class="fas fa-coins text-amber-500"></i>
                                <span>Siswa Siap! Masukkan atau Pilih Nominal Transaksi di Atas</span>
                            </div>
                        </template>

                        <template x-if="activeStudent && formNominal > 0">
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="clearStudent()"
                                    class="py-4 px-4 rounded-xl border-2 border-gray-200 hover:border-rose-300 bg-gray-50 hover:bg-rose-50 text-gray-600 hover:text-rose-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-rose-950/40 text-xs font-bold transition flex items-center justify-center gap-1.5 shrink-0 shadow-xs"
                                    title="Batal transaksi saat ini (ESC)">
                                    <i class="fas fa-times-circle text-rose-500"></i>
                                    <span>Batal (ESC)</span>
                                </button>

                                <button
                                    type="submit"
                                    :disabled="isSubmitting"
                                    class="flex-1 flex items-center justify-center gap-2.5 rounded-xl py-4 px-6 text-base font-black text-white shadow-lg transition select-none active:scale-[0.99]"
                                    :class="formJenis === 'setor'
                                        ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/30'
                                        : 'bg-rose-600 hover:bg-rose-700 shadow-rose-600/30'">
                                    <i class="fas fa-paper-plane" :class="{ 'fa-spin': isSubmitting }"></i>
                                    <span x-text="isSubmitting
                                        ? 'Memproses Transaksi...'
                                        : (formJenis === 'setor' ? 'SIMPAN SETORAN: Rp ' + formNominalDisplay + ' [ENTER]' : 'PROSES PENARIKAN: Rp ' + formNominalDisplay + ' [ENTER]')"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                </form>
            </div>
        </section>

        <!-- ========================================================== -->
        <!-- KOLOM KANAN (5 SPAN): LIVE FEED MUTASI & CETAK STRUK       -->
        <!-- ========================================================== -->
        <section class="lg:col-span-5 flex flex-col space-y-4 overflow-hidden">

            <!-- Card Live Feed Transaksi -->
            <div class="flex-1 rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-4 sm:p-5 flex flex-col overflow-hidden shadow-xs">
                
                <!-- Feed Header -->
                <div class="flex items-center justify-between pb-3.5 border-b border-gray-200 dark:border-gray-800 mb-2 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300 text-xs shadow-2xs">
                            <i class="fas fa-stream"></i>
                        </span>
                        <div>
                            <h3 class="text-xs sm:text-sm font-extrabold text-gray-900 dark:text-white flex items-center gap-1.5">
                                <span>Transaksi Hari Ini</span>
                                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            </h3>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-3 py-1 text-xs font-black text-brand-700 dark:bg-brand-900/40 dark:text-brand-300 border border-brand-200/80 dark:border-brand-800/40"
                          x-text="todayTransactions.length + ' Transaksi'"></span>
                </div>

                <!-- Feed List (Scrollable) -->
                <div class="flex-1 overflow-y-auto space-y-2.5 pr-1 custom-scroll">
                    <template x-for="item in todayTransactions" :key="item.id">
                        <div class="p-3.5 rounded-xl border border-gray-100 bg-gray-50/70 hover:bg-white dark:border-gray-800 dark:bg-gray-800/40 dark:hover:bg-gray-800 transition flex items-center justify-between gap-3 shadow-2xs group">
                            
                            <!-- Left: Initial + Student Info -->
                            <div class="flex items-center gap-3 min-w-0 flex-1">
                                <div class="h-9 w-9 rounded-xl flex items-center justify-center font-black text-xs shrink-0"
                                     :class="item.jenis === 'setor' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300'">
                                    <span x-text="item.student_nama ? item.student_nama.substring(0, 2).toUpperCase() : 'SW'"></span>
                                </div>
                                <div class="min-w-0 flex-1 leading-tight">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-black text-gray-900 dark:text-white truncate" x-text="item.student_nama"></span>
                                        <span class="text-[10px] font-mono font-bold text-gray-400" x-text="item.waktu"></span>
                                    </div>
                                    <div class="text-[11px] text-gray-500 mt-0.5">
                                        <span class="font-bold text-brand-600 dark:text-brand-400" x-text="item.student_kelas"></span> • Ref: <span class="font-mono text-[10px]" x-text="item.code"></span>
                                    </div>
                                    <div class="text-[10px] text-gray-400 truncate mt-0.5" x-show="item.keterangan" x-text="item.keterangan"></div>
                                </div>
                            </div>

                            <!-- Right: Nominal, Balance & Print Button -->
                            <div class="text-right shrink-0">
                                <div class="text-xs sm:text-sm font-black tracking-tight"
                                     :class="item.jenis === 'setor' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                                     x-text="(item.jenis === 'setor' ? '+' : '-') + item.nominal_formatted">
                                </div>
                                <div class="text-[10px] text-gray-400 font-medium" x-text="'Saldo: ' + item.saldo_sesudah_formatted"></div>
                                <div class="flex items-center justify-end gap-1.5 mt-1">
                                    <button type="button" @click="openStruk(item)"
                                            class="inline-flex items-center gap-1 rounded-lg bg-gray-100 hover:bg-brand-50 text-gray-700 hover:text-brand-600 dark:bg-gray-700 dark:hover:bg-brand-900/50 dark:text-gray-200 dark:hover:text-brand-300 px-2 py-0.5 text-[10px] font-bold transition">
                                        <i class="fas fa-print text-[9px]"></i> Struk
                                    </button>
                                    <button type="button" @click="promptCancelTransaction(item)"
                                            class="inline-flex items-center gap-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 hover:text-rose-800 dark:bg-rose-950/40 dark:hover:bg-rose-900/60 dark:text-rose-300 px-2 py-0.5 text-[10px] font-bold transition"
                                            title="Batalkan / Void Transaksi Ini">
                                        <i class="fas fa-undo text-[9px]"></i> Batal
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty State Feed -->
                    <div x-show="todayTransactions.length === 0" class="py-16 text-center text-gray-400">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-400 mb-2">
                            <i class="fas fa-receipt text-xl"></i>
                        </div>
                        <p class="text-xs font-bold text-gray-700 dark:text-gray-300">Belum Ada Transaksi Hari Ini</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Transaksi baru akan otomatis meluncur ke sini secara real-time.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- ============================================================== -->
    <!-- MODAL CETAK STRUK THERMAL KASIR (58mm / 80mm)                  -->
    <!-- ============================================================== -->
    <div x-show="strukModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
        <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-gray-900 p-5 shadow-2xl border border-gray-200 dark:border-gray-800 space-y-4" @click.away="strukModalOpen = false">
            <div class="flex items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-800">
                <h4 class="text-xs font-bold text-gray-700 dark:text-gray-200 flex items-center gap-1.5">
                    <i class="fas fa-receipt text-brand-500"></i> Struk Transaksi Kasir
                </h4>
                <button type="button" @click="strukModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Area Cetak Struk (Ukuran Thermal) -->
            <div id="thermalReceiptPrintArea" class="bg-amber-50/40 dark:bg-gray-950 p-4 rounded-xl border border-dashed border-gray-300 dark:border-gray-800 font-mono text-[11px] text-gray-800 dark:text-gray-200 space-y-2">
                <div class="text-center pb-2 border-b border-dashed border-gray-300 dark:border-gray-700">
                    <div class="font-black text-sm uppercase">{{ $school->nama_sekolah ?? 'TABUNGAN SISWA' }}</div>
                    <div class="text-[9px] text-gray-500">Bukti Transaksi Tabungan Siswa</div>
                    <div class="text-[9px] text-gray-500" x-text="currentDate + ' ' + (selectedStruk?.waktu || '')"></div>
                </div>

                <div class="space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-500">No. Ref:</span>
                        <span class="font-bold" x-text="selectedStruk?.code"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Siswa:</span>
                        <span class="font-bold truncate max-w-[160px]" x-text="selectedStruk?.student_nama"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">NIS / Kelas:</span>
                        <span x-text="(selectedStruk?.student_nis || '-') + ' / ' + (selectedStruk?.student_kelas || '-')"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Petugas:</span>
                        <span x-text="selectedStruk?.recorder_nama"></span>
                    </div>
                </div>

                <div class="pt-2 border-t border-dashed border-gray-300 dark:border-gray-700 space-y-1">
                    <div class="flex justify-between items-center">
                        <span class="font-bold uppercase" x-text="selectedStruk?.jenis_label"></span>
                        <span class="font-black text-xs"
                              :class="selectedStruk?.jenis === 'setor' ? 'text-emerald-600' : 'text-rose-600'"
                              x-text="selectedStruk?.nominal_formatted"></span>
                    </div>
                    <div class="flex justify-between items-center text-xs pt-1 border-t border-gray-200 dark:border-gray-800">
                        <span class="font-bold">SALDO AKHIR:</span>
                        <span class="font-black text-emerald-600" x-text="selectedStruk?.saldo_sesudah_formatted"></span>
                    </div>
                </div>

                <div class="text-center pt-2 text-[9px] text-gray-400">
                    *Simpan struk ini sebagai bukti transaksi resmi. Terima kasih.
                </div>
            </div>

            <!-- Tombol Aksi Modal -->
            <div class="flex items-center gap-2">
                <button type="button" @click="printReceipt()"
                        class="flex-1 py-2.5 rounded-xl bg-brand-500 text-white font-bold text-xs hover:bg-brand-600 transition flex items-center justify-center gap-1.5 shadow-sm">
                    <i class="fas fa-print"></i> Cetak Struk
                </button>
                <button type="button" @click="promptCancelTransaction(selectedStruk); strukModalOpen = false;"
                        class="py-2.5 px-3 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300 font-bold text-xs transition flex items-center justify-center gap-1"
                        title="Batalkan Transaksi Ini">
                    <i class="fas fa-trash-alt"></i> Batal Trx
                </button>
                <button type="button" @click="strukModalOpen = false"
                        class="py-2.5 px-3 rounded-xl border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 font-bold text-xs hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- MODAL KONFIRMASI BATALKAN TRANSAKSI (VOID)                     -->
    <!-- ============================================================== -->
    <div x-show="cancelModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
        <div class="w-full max-w-sm rounded-2xl bg-white dark:bg-gray-900 p-5 shadow-2xl border border-gray-200 dark:border-gray-800 space-y-4" @click.away="cancelModalOpen = false">
            <div class="flex items-center justify-between pb-2 border-b border-gray-200 dark:border-gray-800">
                <h4 class="text-xs font-bold text-rose-600 dark:text-rose-400 flex items-center gap-1.5">
                    <i class="fas fa-exclamation-triangle"></i> Konfirmasi Pembatalan Transaksi
                </h4>
                <button type="button" @click="cancelModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="text-center py-2 space-y-2">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 text-xl shadow-xs">
                    <i class="fas fa-trash-alt"></i>
                </div>
                <div class="text-sm font-bold text-gray-900 dark:text-white">Batalkan Transaksi Ini?</div>
                <div class="text-xs text-gray-500 leading-relaxed">
                    Transaksi <b class="text-gray-800 dark:text-gray-200" x-text="cancelingTransaction?.code"></b> an. <b class="text-gray-800 dark:text-gray-200" x-text="cancelingTransaction?.student_nama"></b> sebesar <b :class="cancelingTransaction?.jenis === 'setor' ? 'text-emerald-600' : 'text-rose-600'" x-text="cancelingTransaction?.nominal_formatted"></b> akan dihapus dan saldo tabungan siswa akan otomatis dikembalikan.
                </div>
            </div>

            <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/40 text-[11px] text-amber-800 dark:text-amber-300 flex items-start gap-2">
                <i class="fas fa-info-circle text-amber-600 mt-0.5 shrink-0"></i>
                <span>Tindakan ini permanen dan akan langsung memperbarui saldo buku tabungan siswa.</span>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="executeCancelTransaction()" :disabled="isCanceling"
                        class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition flex items-center justify-center gap-1.5 shadow-md shadow-rose-600/20 disabled:opacity-50">
                    <i class="fas fa-trash-alt" :class="{ 'fa-spin': isCanceling }"></i>
                    <span x-text="isCanceling ? 'Membatalkan...' : 'Ya, Batalkan Transaksi'"></span>
                </button>
                <button type="button" @click="cancelModalOpen = false" :disabled="isCanceling"
                        class="py-2.5 px-4 rounded-xl border border-gray-200 dark:border-gray-800 text-gray-700 dark:text-gray-300 font-bold text-xs hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                    Kembali
                </button>
            </div>
        </div>
    </div>

    <!-- TOAST NOTIFICATION CONTAINER -->
    <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm pointer-events-none">
        <template x-for="t in toasts" :key="t.id">
            <div class="p-3.5 rounded-xl text-xs font-bold shadow-xl border flex items-center gap-2.5 transition-all duration-300 pointer-events-auto"
                 :class="t.type === 'success' ? 'bg-emerald-600 text-white border-emerald-700' : 'bg-rose-600 text-white border-rose-700'">
                <i :class="t.type === 'success' ? 'fas fa-check-circle text-base' : 'fas fa-exclamation-circle text-base'"></i>
                <span class="flex-1" x-text="t.message"></span>
            </div>
        </template>
    </div>
</div>

<script>
function posKasirApp() {
    return {
        currentTime: '',
        currentDate: '',
        soundEnabled: true,
        isFullscreen: false,
        isScanFocused: false,
        searchQuery: '',
        isSearching: false,
        candidates: [],
        activeStudent: null,

        formJenis: 'setor',
        formNominal: 0,
        formNominalDisplay: '',
        formMetode: 'tunai',
        formKeterangan: '',
        isSubmitting: false,

        todayTransactions: @json($todayTransactions),
        statsToday: {
            setor: {{ (int) $totalSetorToday }},
            setor_formatted: 'Rp {{ number_format((int) $totalSetorToday, 0, ",", ".") }}',
            tarik: {{ (int) $totalTarikToday }},
            tarik_formatted: 'Rp {{ number_format((int) $totalTarikToday, 0, ",", ".") }}',
            trx_count: {{ (int) $totalTrxToday }},
        },

        strukModalOpen: false,
        selectedStruk: null,
        cancelModalOpen: false,
        cancelingTransaction: null,
        isCanceling: false,
        toasts: [],
        audioCtx: null,

        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
            
            // Triple-tier autofocus saat startup untuk mengatasi preloader fade
            this.focusScanInput();
            setTimeout(() => this.focusScanInput(), 150);
            setTimeout(() => this.focusScanInput(), 600);
            setTimeout(() => this.focusScanInput(), 1200);

            this.setupHotkeys();
            this.setupAutoRefocus();
        },

        updateClock() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            this.currentDate = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'short', year: 'numeric' });
        },

        // Fokuskan kolom scanner RFID
        focusScanInput() {
            this.$nextTick(() => {
                const el = this.$refs.scanInput;
                if (el) {
                    el.focus();
                    el.select();
                    this.isScanFocused = true;
                }
            });
        },

        focusNominal() {
            this.$nextTick(() => {
                const el = this.$refs.nominalInput;
                if (el) {
                    el.focus();
                    el.select();
                }
            });
        },

        // Auto re-focus ke scanner ketika klik di area kosong
        setupAutoRefocus() {
            document.addEventListener('click', (e) => {
                this.unlockAudio();
                // Jika klik bukan pada elemen interaktif input/tombol/modal
                if (!e.target.closest('input, button, select, textarea, a, #thermalReceiptPrintArea')) {
                    if (!this.activeStudent) {
                        this.focusScanInput();
                    }
                }
            });

            // Keystroke catcher fail-safe: jika scanner USB membaca saat fokus terlepas
            window.addEventListener('keydown', (e) => {
                this.unlockAudio();
                const activeTag = document.activeElement ? document.activeElement.tagName : '';
                if (activeTag !== 'INPUT' && activeTag !== 'TEXTAREA' && activeTag !== 'SELECT') {
                    if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
                        if (!this.activeStudent) {
                            this.focusScanInput();
                        }
                    }
                }
            });
        },

        unlockAudio() {
            try {
                if (!this.audioCtx) {
                    this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (this.audioCtx && this.audioCtx.state === 'suspended') {
                    this.audioCtx.resume();
                }
            } catch (e) {}
        },

        playTone(type) {
            if (!this.soundEnabled) return;
            try {
                this.unlockAudio();
                const ctx = this.audioCtx;
                if (!ctx) return;

                if (type === 'success') {
                    // Two-tone chime (880Hz -> 1320Hz)
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'sine';
                    const now = ctx.currentTime;
                    osc.frequency.setValueAtTime(880, now);
                    osc.frequency.setValueAtTime(1320, now + 0.08);
                    gain.gain.setValueAtTime(0.2, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);
                    osc.start(now);
                    osc.stop(now + 0.35);
                } else {
                    // Error buzz (180Hz saw)
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.type = 'sawtooth';
                    const now = ctx.currentTime;
                    osc.frequency.setValueAtTime(180, now);
                    gain.gain.setValueAtTime(0.25, now);
                    gain.gain.exponentialRampToValueAtTime(0.001, now + 0.25);
                    osc.start(now);
                    osc.stop(now + 0.25);
                }
            } catch (e) {
                console.warn('Audio tone error:', e);
            }
        },

        setupHotkeys() {
            window.addEventListener('keydown', (e) => {
                // F2 -> Setor
                if (e.key === 'F2') {
                    e.preventDefault();
                    this.formJenis = 'setor';
                    if (this.activeStudent) this.focusNominal();
                }
                // F4 -> Tarik
                else if (e.key === 'F4') {
                    e.preventDefault();
                    this.formJenis = 'tarik';
                    if (this.activeStudent) this.focusNominal();
                }
                // ESC -> Reset Siswa & Fokus ke Scan / Tutup Modal
                else if (e.key === 'Escape') {
                    e.preventDefault();
                    if (this.cancelModalOpen) {
                        this.cancelModalOpen = false;
                    } else if (this.strukModalOpen) {
                        this.strukModalOpen = false;
                    } else if (this.candidates.length > 0) {
                        this.candidates = [];
                    } else {
                        this.clearStudent();
                    }
                }
            });
        },

        toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().then(() => this.isFullscreen = true).catch(() => {});
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen().then(() => this.isFullscreen = false).catch(() => {});
                }
            }
        },

        showToast(message, type = 'success') {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, message, type });
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 3500);
        },

        async handleScanLookup() {
            const q = this.searchQuery.trim();
            if (!q) {
                this.candidates = [];
                return;
            }

            // Jika siswa sudah terpilih dan query sama dengan nama siswa, jangan cari ulang
            if (this.activeStudent && q.toLowerCase() === this.activeStudent.nama.toLowerCase()) {
                this.candidates = [];
                return;
            }

            this.isSearching = true;
            try {
                const res = await fetch(`/tabungan/scan-lookup?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (data.success) {
                    this.candidates = data.candidates || [];
                    if (data.exact || this.candidates.length === 1) {
                        this.selectStudent(data.student);
                    }
                } else {
                    this.candidates = [];
                }
            } catch (err) {
                console.error('Scan lookup error:', err);
            } finally {
                this.isSearching = false;
            }
        },

        selectStudent(student) {
            this.activeStudent = student;
            this.searchQuery = student.nama;
            this.candidates = []; // Bersihkan langsung agar dropdown tertutup
            this.playTone('success');
            this.focusNominal();
        },

        clearStudent() {
            this.activeStudent = null;
            this.searchQuery = '';
            this.candidates = [];
            this.formNominal = 0;
            this.formNominalDisplay = '';
            this.formKeterangan = '';
            this.focusScanInput();
        },

        setNominal(amount) {
            this.formNominal = amount;
            this.formNominalDisplay = new Intl.NumberFormat('id-ID').format(amount);
            this.focusNominal();
        },

        formatNominalInput() {
            const clean = this.formNominalDisplay.replace(/[^0-9]/g, '');
            const val = clean ? parseInt(clean, 10) : 0;
            this.formNominal = val;
            this.formNominalDisplay = val > 0 ? new Intl.NumberFormat('id-ID').format(val) : '';
        },

        async submitTransaction() {
            if (!this.activeStudent) {
                this.playTone('error');
                this.showToast('Tempel kartu RFID atau pilih siswa terlebih dahulu!', 'error');
                this.focusScanInput();
                return;
            }
            if (this.formNominal < 500) {
                this.playTone('error');
                this.showToast('Nominal minimal transaksi adalah Rp 500', 'error');
                this.focusNominal();
                return;
            }
            if (this.formJenis === 'tarik' && this.formNominal > this.activeStudent.saldo_tabungan) {
                this.playTone('error');
                this.showToast(`Saldo siswa tidak cukup! Tersedia: ${this.activeStudent.saldo_formatted}`, 'error');
                this.focusNominal();
                return;
            }

            this.isSubmitting = true;
            try {
                const payload = {
                    student_id: this.activeStudent.id,
                    jenis: this.formJenis,
                    nominal: this.formNominal,
                    tanggal: new Date().toISOString().split('T')[0],
                    waktu: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
                    metode: this.formMetode,
                    keterangan: this.formKeterangan,
                };

                const res = await fetch('/tabungan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify(payload),
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Gagal memproses transaksi.');
                }

                // 1. Play success chime
                this.playTone('success');

                // 2. Prepend live feed
                if (data.tabungan) {
                    this.todayTransactions.unshift(data.tabungan);
                }

                // 3. Update stats live
                if (data.stats_today) {
                    this.statsToday = data.stats_today;
                }

                // 4. Update student balance in memory
                if (data.student) {
                    this.activeStudent = data.student;
                }

                // 5. Toast notifikasi
                this.showToast(data.message || 'Transaksi berhasil disimpan!');

                // 6. Reset nominal & form untuk transaksi selanjutnya
                this.formNominal = 0;
                this.formNominalDisplay = '';
                this.formKeterangan = '';

                // 7. Auto-focus kembali ke Scanner setelah 1.5 detik
                setTimeout(() => {
                    this.clearStudent();
                }, 1600);

            } catch (err) {
                this.playTone('error');
                this.showToast(err.message, 'error');
            } finally {
                this.isSubmitting = false;
            }
        },

        openStruk(item) {
            this.selectedStruk = item;
            this.strukModalOpen = true;
        },

        printReceipt() {
            window.print();
        },

        promptCancelTransaction(item) {
            if (!item) return;
            this.cancelingTransaction = item;
            this.cancelModalOpen = true;
        },

        async executeCancelTransaction() {
            if (!this.cancelingTransaction || this.isCanceling) return;
            this.isCanceling = true;
            try {
                const res = await fetch(`/tabungan/${this.cancelingTransaction.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    }
                });

                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.message || 'Gagal membatalkan transaksi.');
                }

                // 1. Play success chime
                this.playTone('success');

                // 2. Remove from live feed
                this.todayTransactions = this.todayTransactions.filter(t => t.id !== this.cancelingTransaction.id);

                // 3. Update stats live
                if (data.stats_today) {
                    this.statsToday = data.stats_today;
                }

                // 4. Update student balance if currently active on screen
                if (this.activeStudent && data.student && this.activeStudent.id === data.student.id) {
                    this.activeStudent = data.student;
                }

                // 5. Toast
                this.showToast(data.message || 'Transaksi berhasil dibatalkan!');
                this.cancelModalOpen = false;
                this.cancelingTransaction = null;
                this.focusScanInput();

            } catch (err) {
                this.playTone('error');
                this.showToast(err.message, 'error');
            } finally {
                this.isCanceling = false;
            }
        }
    };
}
</script>
@endsection
