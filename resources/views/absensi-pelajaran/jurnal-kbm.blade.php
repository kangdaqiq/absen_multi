@extends('layouts.app')

@section('title', 'Jurnal Pembelajaran (KBM)')

@section('content')
<div class="space-y-6 w-full">

    <!-- Header Title & Action Buttons -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between no-print">
        <div>
            <h2 class="text-title-md2 font-bold text-gray-800 dark:text-white/90 flex items-center gap-2.5">
                <i class="fas fa-book-reader text-brand-500"></i>
                <span>Jurnal Pembelajaran (KBM)</span>
            </h2>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                Daftar log aktivitas mengajar guru, mata pelajaran, kelas, dan materi jurnal pembelajaran.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('absensi-pelajaran.index', ['tanggal' => $tanggal]) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-xs">
                <i class="fas fa-clipboard-check text-brand-500"></i>
                <span>Absensi Mapel Hari Ini</span>
            </a>
            <a href="{{ route('absensi-pelajaran.rekap') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-xs">
                <i class="fas fa-chart-line text-emerald-500"></i>
                <span>Rekap Absensi Siswa</span>
            </a>
            <a href="{{ route('absensi-pelajaran.dokumentasi') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-xs">
                <i class="fas fa-photo-video text-purple-500"></i>
                <span>Dokumentasi KBM</span>
            </a>
        </div>
    </div>

    <!-- Ringkasan Statistik Kartu Atas -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 no-print">
        <!-- 1. Guru Mengajar -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400 block truncate">Guru Mengisi Jurnal</span>
                    <h4 class="mt-1 text-xl sm:text-2xl font-bold text-gray-800 dark:text-white/90">
                        {{ $stats['total_guru'] }}
                    </h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15">
                    <i class="fas fa-chalkboard-teacher text-lg"></i>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-gray-400">Guru yang merekam jurnal pada tanggal ini</p>
        </div>

        <!-- 2. Sesi KBM -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400 block truncate">Sesi KBM Terisi</span>
                    <h4 class="mt-1 text-xl sm:text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ $stats['total_sesi'] }}
                    </h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-500 dark:bg-emerald-500/15">
                    <i class="fas fa-book-open text-lg"></i>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-gray-400">Total pertemuan belajar tersimpan</p>
        </div>

        <!-- 3. Berkas Dokumentasi -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400 block truncate">Berkas Dokumentasi</span>
                    <h4 class="mt-1 text-xl sm:text-2xl font-bold text-purple-600 dark:text-purple-400">
                        {{ $stats['total_docs'] }}
                    </h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-50 text-purple-500 dark:bg-purple-500/15">
                    <i class="fas fa-images text-lg"></i>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-gray-400">
                <span>{{ $stats['total_images'] }} foto</span> &bull; <span>{{ $stats['total_videos'] }} video</span>
            </p>
        </div>

        <!-- 4. Ketidakhadiran Siswa -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark transition hover:shadow-md">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400 block truncate">Siswa Tidak Hadir</span>
                    <h4 class="mt-1 text-xl sm:text-2xl font-bold {{ $stats['total_absent'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                        {{ $stats['total_absent'] }}
                    </h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $stats['total_absent'] > 0 ? 'bg-amber-50 text-amber-500 dark:bg-amber-500/15' : 'bg-emerald-50 text-emerald-500 dark:bg-emerald-500/15' }}">
                    <i class="fas {{ $stats['total_absent'] > 0 ? 'fa-user-times' : 'fa-user-check' }} text-lg"></i>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-gray-400">Siswa berstatus S, I, A, D pada sesi KBM</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark no-print">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    <i class="fas fa-filter text-xs"></i>
                </div>
                <div>
                    <h6 class="font-semibold text-gray-800 dark:text-white/90 text-sm">Filter Jurnal KBM</h6>
                </div>
            </div>
            @if(request()->hasAny(['kelas_id', 'mapel_id', 'search', 'q']) || (request('tanggal') && request('tanggal') !== now()->format('Y-m-d')))
                <a href="{{ route('absensi-pelajaran.jurnal-kbm') }}" 
                   class="inline-flex items-center gap-1.5 text-xs text-error-600 hover:text-error-700 dark:text-error-400 font-medium hover:underline">
                    <i class="fas fa-times"></i> Reset Filter
                </a>
            @endif
        </div>
        <div class="p-5">
            <form action="{{ route('absensi-pelajaran.jurnal-kbm') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-end flex-wrap">
                <!-- Tanggal -->
                <div class="w-full sm:w-auto">
                    <label for="tanggal" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Tanggal</label>
                    <input type="date" name="tanggal" id="tanggal" value="{{ $tanggal }}" 
                        class="w-full rounded-xl border border-gray-200 bg-transparent px-3.5 py-2.5 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white {{ request('tanggal') && request('tanggal') !== now()->format('Y-m-d') ? 'border-brand-500 bg-brand-50/20 font-semibold text-brand-600' : '' }}">
                </div>

                <!-- Kelas -->
                <div class="w-full sm:w-auto min-w-[180px]">
                    <label for="kelas_id" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Kelas</label>
                    <div class="relative">
                        <select name="kelas_id" id="kelas_id" 
                            class="w-full appearance-none rounded-xl border border-gray-200 bg-transparent py-2.5 pl-3.5 pr-8 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white cursor-pointer {{ $kelasId ? 'border-brand-500 bg-brand-50/20 font-semibold text-brand-600' : '' }}">
                            <option value="">Semua Kelas</option>
                            @foreach($kelases as $k)
                                <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                            <i class="fas fa-chevron-down text-[10px]"></i>
                        </div>
                    </div>
                </div>

                <!-- Mata Pelajaran -->
                <div class="w-full sm:w-auto min-w-[200px]">
                    <label for="mapel_id" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Mata Pelajaran</label>
                    <div class="relative">
                        <select name="mapel_id" id="mapel_id" 
                            class="w-full appearance-none rounded-xl border border-gray-200 bg-transparent py-2.5 pl-3.5 pr-8 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white cursor-pointer {{ $mapelId ? 'border-brand-500 bg-brand-50/20 font-semibold text-brand-600' : '' }}">
                            <option value="">Semua Mapel</option>
                            @foreach($mapels as $m)
                                <option value="{{ $m->id }}" {{ $mapelId == $m->id ? 'selected' : '' }}>{{ $m->nama_mapel }}</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                            <i class="fas fa-chevron-down text-[10px]"></i>
                        </div>
                    </div>
                </div>

                <!-- Cari Guru / Materi -->
                <div class="w-full sm:w-auto relative min-w-[240px]">
                    <label for="search" class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Cari Guru / Materi</label>
                    <div class="relative">
                        <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Nama guru, materi..." 
                            class="w-full rounded-xl border border-gray-200 bg-transparent py-2.5 pl-3.5 pr-8 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white {{ $search ? 'border-brand-500 bg-brand-50/20 font-semibold text-brand-600' : '' }}">
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                            <i class="fas fa-search text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- Button Submit Filter -->
                <div class="w-full sm:w-auto">
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-2.5 text-center text-xs font-medium text-white hover:bg-brand-600 transition shadow-sm cursor-pointer">
                        <i class="fas fa-filter"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Jurnal Pembelajaran (KBM) -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex justify-between items-center">
            <h6 class="font-semibold text-gray-800 dark:text-white/90">Daftar Jurnal Pembelajaran</h6>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ count($consolidatedJurnals) }} sesi KBM</span>
        </div>
        
        <div class="max-w-full overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-xs">
                        <th class="px-4 py-3.5 xl:pl-6 border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800 text-center w-12">No</th>
                        <th class="px-4 py-3.5 border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800 min-w-[140px]">Jam & Waktu</th>
                        <th class="px-4 py-3.5 border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800 min-w-[160px]">Mata Pelajaran</th>
                        <th class="px-4 py-3.5 border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800 min-w-[110px]">Kelas</th>
                        <th class="px-4 py-3.5 border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800 min-w-[180px]">Guru Pengajar</th>
                        <th class="px-4 py-3.5 border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800">Jurnal Pembelajaran</th>
                        <th class="px-4 py-3.5 text-center border-b border-gray-200 dark:border-gray-800 min-w-[90px] no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-xs">
                    @forelse($consolidatedJurnals as $index => $j)
                        @php
                            $guruNama = $j->guru?->nama ?: ($j->jadwal?->guru?->nama ?? '-');
                            $guruNip = $j->guru?->nip ?: ($j->jadwal?->guru?->nip ?? null);
                            $mapelNama = $j->jadwal?->mapel?->nama_mapel ?? 'Mata Pelajaran';
                            $jamKeLabel = $j->jam_ke ?: ($j->jadwal?->jam_ke ?: '-');
                            $timeStr = ($j->jam_mulai || $j->jam_selesai)
                                ? substr($j->jam_mulai, 0, 5) . ' - ' . substr($j->jam_selesai, 0, 5)
                                : null;
                        @endphp
                        <tr class="hover:bg-gray-50/75 dark:hover:bg-gray-800/40 transition-colors border-b border-gray-100 dark:border-gray-800 last:border-b-0">
                            <!-- No -->
                            <td class="px-4 py-3.5 xl:pl-6 text-center border-r border-gray-100 dark:border-gray-800 text-gray-500 font-medium">
                                {{ $index + 1 }}
                            </td>

                            <!-- Jam & Waktu -->
                            <td class="px-4 py-3.5 border-r border-gray-100 dark:border-gray-800 whitespace-nowrap">
                                <span class="font-bold text-gray-800 dark:text-white block">
                                    Jam Ke-{{ $jamKeLabel }}
                                </span>
                                @if($timeStr)
                                    <span class="text-[11px] text-gray-400 font-mono">{{ $timeStr }}</span>
                                @endif
                            </td>

                            <!-- Mata Pelajaran -->
                            <td class="px-4 py-3.5 border-r border-gray-100 dark:border-gray-800 font-semibold text-gray-800 dark:text-white/90">
                                {{ $mapelNama }}
                            </td>

                            <!-- Kelas -->
                            <td class="px-4 py-3.5 border-r border-gray-100 dark:border-gray-800 text-gray-600 dark:text-gray-300 font-medium">
                                <span class="inline-flex rounded-lg bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-400">
                                    {{ $j->nama_kelas }}
                                </span>
                            </td>

                            <!-- Guru Pengajar -->
                            <td class="px-4 py-3.5 border-r border-gray-100 dark:border-gray-800">
                                <p class="font-medium text-gray-800 dark:text-white/90">{{ $guruNama }}</p>
                                @if($guruNip)
                                    <span class="text-[10px] text-gray-400 font-mono">NIP: {{ $guruNip }}</span>
                                @endif
                            </td>

                            <!-- Jurnal Pembelajaran (Materi & Catatan) -->
                            <td class="px-4 py-3.5 border-r border-gray-100 dark:border-gray-800 max-w-md">
                                <div class="font-medium text-gray-800 dark:text-gray-200 line-clamp-2" title="{{ $j->materi }}">
                                    {{ $j->materi ?: 'Belum ada catatan materi.' }}
                                </div>
                                @if(!empty($j->catatan))
                                    <span class="text-[11px] text-gray-400 italic block mt-0.5 line-clamp-1" title="{{ $j->catatan }}">
                                        Catatan: {{ $j->catatan }}
                                    </span>
                                @endif
                                <div class="flex items-center gap-2 mt-1">
                                    @if(!empty($j->dokumentasi_list) && count($j->dokumentasi_list) > 0)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-purple-600 dark:text-purple-400">
                                            <i class="fas fa-camera text-[9px]"></i> {{ count($j->dokumentasi_list) }} Berkas
                                        </span>
                                    @endif
                                    @if(!empty($j->absent_count) && $j->absent_count > 0)
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-600 dark:text-amber-400">
                                            <i class="fas fa-user-times text-[9px]"></i> {{ $j->absent_count }} Tidak Hadir
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Aksi (Detail) -->
                            <td class="px-4 py-3.5 text-center whitespace-nowrap no-print">
                                <a href="{{ route('absensi-pelajaran.jurnal-kbm.show', array_merge(request()->all(), ['id' => $j->id])) }}"
                                    class="inline-flex items-center justify-center gap-1 rounded-lg bg-info-50 px-3 py-1.5 text-xs font-semibold text-info-600 hover:bg-info-100 dark:bg-info-500/15 dark:text-info-400 dark:hover:bg-info-500/25 transition shadow-xs cursor-pointer">
                                    <i class="fas fa-eye text-[11px]"></i> Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="fas fa-book-reader text-3xl text-gray-300 dark:text-gray-700"></i>
                                    <p class="text-sm">Tidak ada data jurnal KBM ditemukan untuk kriteria filter ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
