@extends('layouts.app')

@section('title', 'Dokumentasi Pembelajaran (KBM)')

@section('content')
<div class="space-y-6">
    <!-- Header Title & Action Buttons -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-title-md2 font-bold text-gray-800 dark:text-white/90 flex items-center gap-2.5">
                <i class="fas fa-photo-video text-brand-500"></i>
                <span>Dokumentasi Pembelajaran</span>
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Daftar guru pengajar dan ringkasan arsip foto serta video kegiatan belajar mengajar (KBM).
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('absensi-pelajaran.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-xs">
                <i class="fas fa-clipboard-check text-brand-500"></i>
                <span>Absensi Mapel Hari Ini</span>
            </a>
            <a href="{{ route('absensi-pelajaran.rekap') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-xs">
                <i class="fas fa-book-open text-brand-500"></i>
                <span>Rekap Jurnal & Absensi</span>
            </a>
        </div>
    </div>

    <!-- Statistik Ringkasan (Cards Atas Tetap Dipertahankan) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-5 gap-3.5 sm:gap-4">
        <!-- 1. Total Guru -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400 block truncate">Guru Pengunggah</span>
                    <h4 class="mt-1 text-xl sm:text-2xl font-bold text-gray-800 dark:text-white/90">
                        {{ $stats['total_gurus'] }}
                    </h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15">
                    <i class="fas fa-chalkboard-teacher text-lg"></i>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-gray-400">Guru yang mendokumentasikan KBM</p>
        </div>

        <!-- 2. Sesi KBM Terdokumentasi -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400 block truncate">Sesi KBM</span>
                    <h4 class="mt-1 text-xl sm:text-2xl font-bold text-gray-800 dark:text-white/90">
                        {{ $stats['total_sessions'] }}
                    </h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-500 dark:bg-emerald-500/15">
                    <i class="fas fa-calendar-check text-lg"></i>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-gray-400">Total pertemuan KBM terarsip</p>
        </div>

        <!-- 3. Total Berkas Media -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400 block truncate">Total Berkas</span>
                    <h4 class="mt-1 text-xl sm:text-2xl font-bold text-gray-800 dark:text-white/90">
                        {{ $stats['total_files'] }}
                    </h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-50 text-purple-500 dark:bg-purple-500/15">
                    <i class="fas fa-folder-open text-lg"></i>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-gray-400">Media tersimpan di cloud storage</p>
        </div>

        <!-- 4. Total Foto -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400 block truncate">Foto Kegiatan</span>
                    <h4 class="mt-1 text-xl sm:text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $stats['total_images'] }}
                    </h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-500 dark:bg-blue-500/15">
                    <i class="fas fa-image text-lg"></i>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-gray-400">Dokumentasi foto kegiatan KBM</p>
        </div>

        <!-- 5. Total Video -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark col-span-2 sm:col-span-1">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400 block truncate">Video Pembelajaran</span>
                    <h4 class="mt-1 text-xl sm:text-2xl font-bold text-amber-500 dark:text-amber-400">
                        {{ $stats['total_videos'] }}
                    </h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-500 dark:bg-amber-500/15">
                    <i class="fas fa-video text-lg"></i>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-gray-400">Streaming video kegiatan KBM</p>
        </div>
    </div>

    <!-- Panel Daftar Guru Pengunggah Dokumentasi -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark">
        <!-- Header Tabel & Search Bar -->
        <div class="p-4 sm:p-5 border-b border-gray-100 dark:border-gray-800 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="shrink-0">
                <h3 class="text-base sm:text-lg font-bold text-gray-800 dark:text-white/90 flex items-center gap-2">
                    <i class="fas fa-users-class text-brand-500"></i>
                    <span>Daftar Guru & Rekap Berkas KBM</span>
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    Klik pada nama guru untuk membuka galeri dokumentasi lengkap di tab baru.
                </p>
            </div>

            <!-- Form Pencarian Cepat Nama Guru / NIP -->
            <form method="GET" action="{{ route('absensi-pelajaran.dokumentasi') }}" class="w-full md:w-80 shrink-0" style="max-width: 320px;">
                <div class="relative">
                    <input type="text" name="q" value="{{ $search }}"
                        placeholder="Cari nama guru atau NIP..."
                        class="w-full rounded-xl border border-gray-200 bg-gray-50/50 py-2 pl-9 pr-8 text-xs text-gray-800 focus:border-brand-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-white transition">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    @if(!empty($search))
                        <a href="{{ route('absensi-pelajaran.dokumentasi') }}" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <i class="fas fa-times text-xs"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tabel Daftar Guru -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/75 dark:border-gray-800 dark:bg-gray-800/40 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <th class="py-3.5 px-4 sm:px-6">Nama Guru</th>
                        <th class="py-3.5 px-4 text-center">Sesi KBM</th>
                        <th class="py-3.5 px-4 text-center">Foto</th>
                        <th class="py-3.5 px-4 text-center">Video</th>
                        <th class="py-3.5 px-4 text-center">Total Berkas</th>
                        <th class="py-3.5 px-4 sm:px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/80 text-xs sm:text-sm">
                    @forelse($groupedByGuru as $item)
                        @php
                            $guruUrl = $item->guru_id ? route('absensi-pelajaran.dokumentasi.guru', $item->guru_id) : '#';
                        @endphp
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition group">
                            <!-- Profil Guru -->
                            <td class="py-3.5 px-4 sm:px-6">
                                <a href="{{ $guruUrl }}" target="_blank" rel="noopener noreferrer"
                                   class="flex items-center gap-3 group-hover:text-brand-600 transition">
                                    @if(!empty($item->guru_foto))
                                        <img src="{{ $item->guru_foto }}" alt="{{ $item->guru_nama }}"
                                             class="h-10 w-10 rounded-full object-cover border border-gray-200 dark:border-gray-700 shrink-0 shadow-xs">
                                    @else
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500 text-white font-bold text-xs shrink-0 shadow-xs">
                                            {{ strtoupper(substr($item->guru_nama, 0, 2)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <h4 class="font-bold text-gray-800 dark:text-white/90 group-hover:text-brand-600 dark:group-hover:text-brand-400 transition flex items-center gap-1.5">
                                            <span>{{ $item->guru_nama }}</span>
                                            <i class="fas fa-external-link-alt text-[10px] text-gray-400 opacity-0 group-hover:opacity-100 transition"></i>
                                        </h4>
                                        <span class="text-[11px] text-gray-400 font-mono">NIP: {{ $item->guru_nip }}</span>
                                    </div>
                                </a>
                            </td>

                            <!-- Jumlah Sesi KBM -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200/60 dark:border-emerald-500/20">
                                    <i class="fas fa-calendar-check text-[10px]"></i>
                                    <span>{{ $item->total_kbm }} Sesi</span>
                                </span>
                            </td>

                            <!-- Jumlah Foto -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 border border-blue-200/60 dark:border-blue-500/20">
                                    <i class="fas fa-image text-[10px]"></i>
                                    <span>{{ $item->total_images }} Foto</span>
                                </span>
                            </td>

                            <!-- Jumlah Video -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400 border border-amber-200/60 dark:border-amber-500/20">
                                    <i class="fas fa-video text-[10px]"></i>
                                    <span>{{ $item->total_videos }} Video</span>
                                </span>
                            </td>

                            <!-- Total Berkas -->
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-700 dark:bg-purple-500/15 dark:text-purple-400 border border-purple-200/60 dark:border-purple-500/20">
                                    <i class="fas fa-folder text-[10px]"></i>
                                    <span>{{ $item->total_files }} Berkas</span>
                                </span>
                            </td>

                            <!-- Tombol Buka Tab Baru -->
                            <td class="py-3.5 px-4 sm:px-6 text-right">
                                <a href="{{ $guruUrl }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 rounded-xl bg-brand-500 hover:bg-brand-600 px-3.5 py-2 text-xs font-semibold text-white transition shadow-xs hover:shadow-sm">
                                    <span>Buka Galeri</span>
                                    <i class="fas fa-arrow-up-right-from-square text-[11px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 px-4 text-center">
                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/15 mb-3">
                                    <i class="fas fa-photo-video text-xl"></i>
                                </div>
                                <h4 class="text-sm sm:text-base font-bold text-gray-800 dark:text-white/90">
                                    Belum Ada Dokumentasi KBM
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                                    @if(!empty($search))
                                        Tidak ditemukan guru dengan kata kunci "{{ $search }}".
                                    @else
                                        Belum ada guru yang mengunggah foto atau video dokumentasi kegiatan belajar mengajar.
                                    @endif
                                </p>
                                @if(!empty($search))
                                    <div class="mt-4">
                                        <a href="{{ route('absensi-pelajaran.dokumentasi') }}"
                                           class="inline-flex items-center gap-1.5 text-xs text-brand-500 hover:underline font-semibold">
                                            <i class="fas fa-times-circle"></i> Bersihkan Pencarian
                                        </a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
