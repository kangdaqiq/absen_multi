@extends('layouts.app')

@section('title', 'Dashboard Bendahara Tabungan Siswa')

@section('content')
<div class="space-y-5">
    <!-- Header Dashboard Bendahara -->
    <x-page-header
        title="Dashboard Bendahara Tabungan"
        subtitle="Pusat monitoring arus kas tabungan siswa, statistik saldo berjalan, dan akses loket kasir"
        icon="fas fa-coins">
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('tabungan.kasir') }}" target="_blank"
               class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2 text-xs font-bold text-white shadow-theme-xs hover:bg-brand-600 transition">
                <i class="fas fa-desktop"></i> Buka Layar Kasir POS
            </a>

            <a href="{{ route('tabungan.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition">
                <i class="fas fa-list-alt text-brand-500"></i> Monitoring Mutasi
            </a>

            <a href="{{ route('tabungan.rekap') }}"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-theme-xs hover:bg-emerald-700 transition">
                <i class="fas fa-file-invoice-dollar"></i> Rekap Saldo Kelas
            </a>
        </div>
    </x-page-header>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400 flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    <!-- 1. STAT CARDS KEUANGAN (4 KOLOM SERAGAM & PROPORSIONAL) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5">
        <!-- Card 1: Total Kas Tersimpan -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 p-5 md:p-6 shadow-theme-sm relative flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total Kas Tabungan</span>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 shrink-0">
                    <i class="fas fa-wallet text-base"></i>
                </span>
            </div>
            <div class="mt-4">
                <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white font-mono tracking-tight">
                    Rp {{ number_format($totalSaldoAktif, 0, ',', '.') }}
                </p>
                <div class="mt-2 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <span class="font-bold text-emerald-600 dark:text-emerald-400">
                        {{ number_format($jumlahPenabung) }} Siswa
                    </span>
                    <span>({{ $persenPenabung }}% dari {{ number_format($totalSiswa) }} siswa)</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Setoran Hari Ini -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 p-5 md:p-6 shadow-theme-sm relative flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Setoran Hari Ini</span>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400 shrink-0">
                    <i class="fas fa-arrow-down text-sm"></i>
                </span>
            </div>
            <div class="mt-4">
                <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 font-mono tracking-tight">
                    +Rp {{ number_format($setoranHariIni, 0, ',', '.') }}
                </p>
                <p class="mt-2 text-xs text-gray-400 font-medium">Uang masuk loket kasir hari ini</p>
            </div>
        </div>

        <!-- Card 3: Tarikan Hari Ini -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 p-5 md:p-6 shadow-theme-sm relative flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tarikan Hari Ini</span>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400 shrink-0">
                    <i class="fas fa-arrow-up text-sm"></i>
                </span>
            </div>
            <div class="mt-4">
                <p class="text-2xl sm:text-3xl font-black text-rose-600 dark:text-rose-400 font-mono tracking-tight">
                    Rp {{ number_format($tarikanHariIni, 0, ',', '.') }}
                </p>
                <p class="mt-2 text-xs text-gray-400 font-medium">Uang keluar kas diambil hari ini</p>
            </div>
        </div>

        <!-- Card 4: Net Surplus & Aktivitas Hari Ini -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 p-5 md:p-6 shadow-theme-sm relative flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Net Kas & Aktivitas</span>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-900/40 dark:text-brand-300 shrink-0">
                    <i class="fas fa-balance-scale text-sm"></i>
                </span>
            </div>
            <div class="mt-4">
                <p class="text-2xl sm:text-3xl font-black font-mono tracking-tight {{ $netKasHariIni >= 0 ? 'text-gray-900 dark:text-white' : 'text-rose-600' }}">
                    {{ $netKasHariIni >= 0 ? '+' : '' }}Rp {{ number_format($netKasHariIni, 0, ',', '.') }}
                </p>
                <div class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>Surplus kas hari ini</span>
                    <span class="font-bold text-brand-600 dark:text-brand-400">{{ number_format($totalTrxHariIni) }} Transaksi</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. QUICK ACCESS BAR KASIR POS (COMPACT & SLEEK) -->
    <div class="rounded-2xl border border-brand-200/80 bg-gradient-to-r from-brand-600 via-brand-700 to-indigo-700 p-4 text-white shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <div class="h-10 w-10 rounded-xl bg-white/20 backdrop-blur-xs flex items-center justify-center text-white shrink-0">
                <i class="fas fa-desktop text-base"></i>
            </div>
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-black truncate">Layar Kasir POS Fullscreen (Zero Reload)</p>
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-400/20 text-emerald-200 border border-emerald-300/30 px-2 py-0.2 text-[10px] font-bold">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Online & Realtime
                    </span>
                </div>
                <p class="text-xs text-brand-100 truncate mt-0.5">
                    Scan kartu RFID USB atau ketik nama/NIS siswa untuk setor/tarik tunai instan, struk thermal, & auto notif WA.
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('tabungan.kasir') }}" target="_blank"
               class="inline-flex items-center gap-1.5 rounded-xl bg-white px-4 py-2 text-xs font-black text-brand-700 hover:bg-brand-50 shadow-sm transition active:scale-[0.98]">
                <i class="fas fa-external-link-alt text-[10px]"></i> Buka Kasir POS
            </a>
            <a href="{{ route('tabungan.index') }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-white/30 bg-white/10 hover:bg-white/20 px-3.5 py-2 text-xs font-bold text-white transition">
                <i class="fas fa-search text-[10px]"></i> Cari Mutasi
            </a>
        </div>
    </div>

    <!-- 3. MAIN SECTION: DUA KOLOM -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

        <!-- KOLOM KIRI (7 SPAN): TREN MUTASI 7 HARI & MUTASI TERBARU -->
        <div class="lg:col-span-7 space-y-5">

            <!-- Card Tren 7 Hari Terakhir (Horizontal Grid Terjamin) -->
            <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 dark:border-gray-800 dark:bg-gray-900 shadow-xs">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-chart-line text-brand-500"></i> Tren Transaksi Tabungan (7 Hari Terakhir)
                        </h4>
                        <p class="text-xs text-gray-400">Arus setoran & tarikan harian</p>
                    </div>
                    <span class="text-xs font-mono font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-1 rounded-lg">
                        Setoran Bulan Ini: Rp {{ number_format($setoranBulanIni, 0, ',', '.') }}
                    </span>
                </div>

                <!-- 7 Days Inline Grid -->
                <div class="mt-3.5" style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 0.5rem;">
                    @foreach($chartDays as $day)
                        <div class="flex flex-col items-center justify-between p-2 sm:p-2.5 rounded-xl border border-gray-100 bg-gray-50/70 hover:bg-white dark:border-gray-800 dark:bg-gray-800/40 dark:hover:bg-gray-800 transition text-center shadow-2xs">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ $day['hari'] }}</span>
                            <span class="text-xs font-black text-gray-800 dark:text-gray-200 my-1 font-mono">{{ $day['tanggal'] }}</span>
                            <div class="w-full space-y-1">
                                <div class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 truncate" title="Setor: Rp {{ number_format($day['setor'], 0, ',', '.') }}">
                                    @if($day['setor'] > 0)
                                        +{{ round($day['setor'] / 1000) }}k
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">-</span>
                                    @endif
                                </div>
                                <div class="text-[10px] font-bold text-rose-600 dark:text-rose-400 truncate" title="Tarik: Rp {{ number_format($day['tarik'], 0, ',', '.') }}">
                                    @if($day['tarik'] > 0)
                                        -{{ round($day['tarik'] / 1000) }}k
                                    @else
                                        <span class="text-gray-300 dark:text-gray-600">-</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Card Daftar Transaksi Terbaru -->
            <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 dark:border-gray-800 dark:bg-gray-900 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-stream text-brand-500"></i> Mutasi Transaksi Terbaru
                        </h4>
                        <p class="text-xs text-gray-400">10 transaksi setor/tarik terakhir tercatat</p>
                    </div>
                    <a href="{{ route('tabungan.index') }}" class="text-xs font-bold text-brand-600 dark:text-brand-400 hover:underline flex items-center gap-1">
                        <span>Lihat Semua</span>
                        <i class="fas fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800/60">
                    @forelse($recentTransactions as $trx)
                        <div class="py-2.5 flex items-center justify-between gap-3 first:pt-1 last:pb-1">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="h-9 w-9 rounded-xl flex items-center justify-center font-black text-xs shrink-0 {{ $trx->jenis === 'setor' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300' }}">
                                    <i class="fas {{ $trx->jenis === 'setor' ? 'fa-arrow-down' : 'fa-arrow-up' }} text-xs"></i>
                                </div>
                                <div class="min-w-0 leading-tight">
                                    <p class="text-xs font-bold text-gray-900 dark:text-white truncate">
                                        {{ $trx->student?->nama ?? '-' }}
                                    </p>
                                    <p class="text-[11px] text-gray-500 mt-0.5">
                                        <span class="font-bold text-brand-600 dark:text-brand-400">{{ $trx->student?->kelas?->nama_kelas ?? '-' }}</span>
                                        • Ref: <span class="font-mono text-[10px]">{{ $trx->code }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-xs font-black font-mono {{ $trx->jenis === 'setor' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                    {{ $trx->jenis === 'setor' ? '+' : '-' }}{{ $trx->nominal_formatted }}
                                </span>
                                <p class="text-[10px] text-gray-400 font-mono mt-0.5">
                                    {{ $trx->waktu ?: ($trx->tanggal ? $trx->tanggal->format('d/m') : '-') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-gray-400">
                            <i class="fas fa-receipt text-2xl text-gray-300 mb-1"></i>
                            <p class="text-xs font-semibold">Belum Ada Transaksi Tercatat</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN (5 SPAN): TOP SAVERS, REKAP KELAS, & SUMMARY BULANAN -->
        <div class="lg:col-span-5 space-y-5">

            <!-- Card Top 5 Penabung Terbanyak -->
            <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 dark:border-gray-800 dark:bg-gray-900 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-medal text-amber-500"></i> Top Siswa Penabung Tertinggi
                    </h4>
                    <span class="text-[11px] text-gray-400 font-medium">Buku Saldo</span>
                </div>

                <div class="space-y-2">
                    @forelse($topSavers as $index => $saver)
                        <div class="flex items-center justify-between p-2.5 rounded-xl border border-gray-100 bg-gray-50/70 hover:bg-white dark:border-gray-800 dark:bg-gray-800/40 dark:hover:bg-gray-800 transition">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-black shrink-0 {{ $index === 0 ? 'bg-amber-400 text-amber-950 shadow-xs' : ($index === 1 ? 'bg-gray-300 text-gray-800' : ($index === 2 ? 'bg-amber-700 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300')) }}">
                                    {{ $index + 1 }}
                                </span>
                                <div class="min-w-0 leading-tight">
                                    <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $saver->nama }}</p>
                                    <p class="text-[11px] text-gray-500 mt-0.5">{{ $saver->kelas?->nama_kelas ?? '-' }} • NIS: {{ $saver->nis ?? '-' }}</p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-xs font-black text-emerald-600 dark:text-emerald-400 font-mono">
                                    Rp {{ number_format((int)$saver->saldo_tabungan, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 text-center py-4">Belum ada siswa dengan saldo aktif.</p>
                    @endforelse
                </div>
            </div>

            <!-- Card Saldo per Kelas Tertinggi -->
            <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 dark:border-gray-800 dark:bg-gray-900 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-school text-brand-500"></i> Akumulasi Saldo per Kelas
                    </h4>
                    <a href="{{ route('tabungan.rekap') }}" class="text-xs font-bold text-brand-600 dark:text-brand-400 hover:underline">
                        Lihat Semua
                    </a>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800/60">
                    @forelse($kelasSummaries as $k)
                        <div class="py-2.5 flex items-center justify-between text-xs first:pt-1 last:pb-1">
                            <div>
                                <span class="font-bold text-gray-900 dark:text-white">{{ $k->nama_kelas }}</span>
                                <p class="text-[11px] text-gray-400 mt-0.5">
                                    {{ $k->total_penabung ?? 0 }} dari {{ $k->total_siswa ?? 0 }} siswa menabung
                                </p>
                            </div>
                            <div class="text-right">
                                <span class="font-black font-mono text-gray-900 dark:text-white">
                                    Rp {{ number_format((int)($k->total_saldo ?? 0), 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 text-center py-4">Belum ada data kelas.</p>
                    @endforelse
                </div>
            </div>

            <!-- Card Ringkasan Arus Kas Bulan Ini -->
            <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 dark:border-gray-800 dark:bg-gray-900 shadow-xs space-y-3">
                <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-800">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Akumulasi Kas Bulan Ini ({{ now()->translatedFormat('F Y') }})
                    </h4>
                </div>
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between items-center py-1 border-b border-dashed border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500">Total Setoran Masuk:</span>
                        <span class="font-bold text-emerald-600 font-mono">+Rp {{ number_format($setoranBulanIni, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-dashed border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500">Total Tarikan Keluar:</span>
                        <span class="font-bold text-rose-600 font-mono">-Rp {{ number_format($tarikanBulanIni, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1 border-b border-dashed border-gray-100 dark:border-gray-800">
                        <span class="text-gray-500">Total Volume Mutasi:</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200 font-mono">{{ number_format($totalTrxBulanIni) }} Transaksi</span>
                    </div>
                    <div class="flex justify-between items-center pt-1 font-bold text-sm">
                        <span class="text-gray-700 dark:text-gray-300">Surplus Kas Bulan Ini:</span>
                        <span class="font-black font-mono text-emerald-600">
                            Rp {{ number_format($setoranBulanIni - $tarikanBulanIni, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
