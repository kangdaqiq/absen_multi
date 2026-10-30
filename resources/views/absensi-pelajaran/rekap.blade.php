@extends('layouts.app')

@section('title', 'Rekap Absensi Siswa Per Mata Pelajaran')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between no-print">
    <div>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            Rekap Absensi Siswa Per Mata Pelajaran
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Akumulasi rekapitulasi kehadiran (Hadir, Sakit, Izin, Alpha) per siswa berdasarkan mata pelajaran dan kelas
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('absensi-pelajaran.index') }}"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-center text-xs font-semibold text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-theme-xs">
            <i class="fas fa-clipboard-check text-brand-500"></i> Absensi Hari Ini
        </a>
        <a href="{{ route('absensi-pelajaran.jurnal-kbm') }}"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-center text-xs font-semibold text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-theme-xs">
            <i class="fas fa-book-reader text-brand-500"></i> Jurnal KBM
        </a>
    </div>
</div>

<!-- Print Only Header -->
<div class="hidden print:block mb-6 text-center border-b pb-4">
    <h2 class="text-xl font-bold uppercase">Rekapitulasi Kehadiran Siswa Per Mata Pelajaran</h2>
    <p class="text-sm text-gray-600">
        Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
        @if($kelasId) &bull; Kelas: {{ $kelases->firstWhere('id', $kelasId)?->nama_kelas }} @endif
        @if($mapelId) &bull; Mapel: {{ $mapels->firstWhere('id', $mapelId)?->nama_mapel }} @endif
    </p>
</div>

<!-- Filter Card -->
<div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark no-print">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                <i class="fas fa-filter text-xs"></i>
            </div>
            <div>
                <h6 class="font-semibold text-gray-800 dark:text-white/90 text-sm">Filter Periode, Kelas, Mapel & Siswa</h6>
            </div>
        </div>
        @if(request()->hasAny(['start_date', 'end_date', 'kelas_id', 'mapel_id', 'search', 'sort_by']))
            <a href="{{ route('absensi-pelajaran.rekap') }}" 
               class="inline-flex items-center gap-1.5 text-xs text-error-600 hover:text-error-700 dark:text-error-400 font-medium hover:underline">
                <i class="fas fa-times"></i> Reset Filter
            </a>
        @endif
    </div>
    <div class="p-5">
        <form action="{{ route('absensi-pelajaran.rekap') }}" method="GET" class="flex flex-col flex-wrap gap-4 md:flex-row items-end">
            <!-- Dari Tanggal -->
            <div class="w-full md:w-auto">
                <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                    class="w-full rounded-xl border border-gray-200 bg-transparent px-3.5 py-2.5 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white {{ request('start_date') ? 'border-brand-500 bg-brand-50/20 font-semibold text-brand-600' : '' }}">
            </div>

            <!-- Sampai Tanggal -->
            <div class="w-full md:w-auto">
                <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                    class="w-full rounded-xl border border-gray-200 bg-transparent px-3.5 py-2.5 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white {{ request('end_date') ? 'border-brand-500 bg-brand-50/20 font-semibold text-brand-600' : '' }}">
            </div>

            <!-- Filter Kelas -->
            <div class="w-full md:w-auto min-w-[170px]">
                <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Kelas</label>
                <div class="relative">
                    <select name="kelas_id"
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

            <!-- Filter Mapel -->
            <div class="w-full md:w-auto min-w-[190px]">
                <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Mata Pelajaran</label>
                <div class="relative">
                    <select name="mapel_id"
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

            <!-- Kolom Search Nama -->
            <div class="w-full md:w-auto min-w-[200px]">
                <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Cari Siswa</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nama / NIS siswa..."
                        class="w-full rounded-xl border border-gray-200 bg-transparent py-2.5 pl-3.5 pr-8 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white {{ $search ? 'border-brand-500 bg-brand-50/20 font-semibold text-brand-600' : '' }}">
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                        <i class="fas fa-search text-xs"></i>
                    </div>
                </div>
            </div>

            <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
            <input type="hidden" name="sort_dir" value="{{ request('sort_dir') }}">
            @if(request('guru_id'))
                <input type="hidden" name="guru_id" value="{{ request('guru_id') }}">
            @endif

            <!-- Button Group matching Rekap Siswa: Tampilkan, Excel, PDF -->
            <div class="w-full md:w-auto flex flex-wrap gap-2 mt-2 md:mt-0">
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-center text-xs font-medium text-white hover:bg-brand-600 transition shadow-sm cursor-pointer">
                    <i class="fas fa-filter"></i> Tampilkan
                </button>
                <button type="submit" formaction="{{ route('absensi-pelajaran.rekap.export') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-success-500 px-4 py-2.5 text-center text-xs font-medium text-white hover:bg-success-600 transition shadow-sm cursor-pointer" style="background-color: #12b76a;">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <a href="{{ route('absensi-pelajaran.rekap.pdf', request()->all()) }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-xl bg-error-500 px-4 py-2.5 text-center text-xs font-medium text-white hover:bg-error-600 transition shadow-sm cursor-pointer">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Metrics Ribbon (Akumulasi Total) -->
<div class="mb-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
    <div class="rounded-2xl border border-gray-200 bg-white p-3.5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark text-center">
        <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium block">Total Pertemuan</span>
        <span class="text-xl font-bold text-gray-800 dark:text-white mt-1 block">{{ number_format($globalSummary->total_records ?? 0) }}</span>
    </div>
    <div class="rounded-2xl border border-success-200 bg-success-50/50 p-3.5 shadow-theme-xs dark:border-success-500/20 dark:bg-success-500/5 text-center">
        <span class="text-[11px] text-success-700 dark:text-success-400 font-bold block">Hadir (H)</span>
        <span class="text-xl font-bold text-success-700 dark:text-success-400 mt-1 block">{{ number_format($globalSummary->count_h ?? 0) }}</span>
    </div>
    <div class="rounded-2xl border border-warning-200 bg-warning-50/50 p-3.5 shadow-theme-xs dark:border-warning-500/20 dark:bg-warning-500/5 text-center">
        <span class="text-[11px] text-warning-700 dark:text-warning-400 font-bold block">Sakit (S)</span>
        <span class="text-xl font-bold text-warning-700 dark:text-warning-400 mt-1 block">{{ number_format($globalSummary->count_s ?? 0) }}</span>
    </div>
    <div class="rounded-2xl border border-blue-200 bg-blue-50/50 p-3.5 shadow-theme-xs dark:border-blue-500/20 dark:bg-blue-500/5 text-center">
        <span class="text-[11px] text-blue-700 dark:text-blue-400 font-bold block">Izin (I)</span>
        <span class="text-xl font-bold text-blue-700 dark:text-blue-400 mt-1 block">{{ number_format($globalSummary->count_i ?? 0) }}</span>
    </div>
    <div class="rounded-2xl border border-error-200 bg-error-50/50 p-3.5 shadow-theme-xs dark:border-error-500/20 dark:bg-error-500/5 text-center">
        <span class="text-[11px] text-error-700 dark:text-error-400 font-bold block">Alpha (A)</span>
        <span class="text-xl font-bold text-error-700 dark:text-error-400 mt-1 block">{{ number_format($globalSummary->count_a ?? 0) }}</span>
    </div>
    <div class="rounded-2xl border border-purple-200 bg-purple-50/50 p-3.5 shadow-theme-xs dark:border-purple-500/20 dark:bg-purple-500/5 text-center">
        <span class="text-[11px] text-purple-700 dark:text-purple-400 font-bold block">Dispen (D)</span>
        <span class="text-xl font-bold text-purple-700 dark:text-purple-400 mt-1 block">{{ number_format($globalSummary->count_d ?? 0) }}</span>
    </div>
</div>

<!-- Data Table Card (Hasil Rekap Berbasis Siswa) -->
<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <h6 class="font-semibold text-gray-800 dark:text-white/90">Hasil Rekap Kehadiran Siswa</h6>
            <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-bold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                {{ $allSiswa->total() }} Siswa
            </span>
        </div>
        @if(request('sort_by'))
            <a href="{{ request()->fullUrlWithQuery(['sort_by' => null, 'sort_dir' => null]) }}" class="text-xs text-brand-500 hover:underline inline-flex items-center gap-1">
                <i class="fas fa-undo"></i> Reset Urutan
            </a>
        @endif
    </div>

    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-xs">
                    <th rowspan="2" class="px-4 py-3.5 xl:pl-6 align-middle border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'nama', 'sort_dir' => (request('sort_by') === 'nama' && request('sort_dir', 'asc') === 'asc' ? 'desc' : 'asc')]) }}"
                            class="group inline-flex items-center gap-1.5 hover:text-brand-500 transition select-none cursor-pointer"
                            title="Urutkan Nama Siswa">
                            <span>Nama Siswa</span>
                            <span class="inline-flex text-[10px]">
                                @if(request('sort_by') === 'nama')
                                    <i class="fas fa-arrow-{{ request('sort_dir', 'asc') === 'desc' ? 'down' : 'up' }} text-brand-500 font-bold"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400 group-hover:text-gray-600"></i>
                                @endif
                            </span>
                        </a>
                    </th>
                    <th rowspan="2" class="px-4 py-3.5 align-middle border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'kelas', 'sort_dir' => (request('sort_by') === 'kelas' && request('sort_dir', 'asc') === 'asc' ? 'desc' : 'asc')]) }}"
                            class="group inline-flex items-center gap-1.5 hover:text-brand-500 transition select-none cursor-pointer"
                            title="Urutkan Kelas">
                            <span>Kelas</span>
                            <span class="inline-flex text-[10px]">
                                @if(request('sort_by') === 'kelas')
                                    <i class="fas fa-arrow-{{ request('sort_dir', 'asc') === 'desc' ? 'down' : 'up' }} text-brand-500 font-bold"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400 group-hover:text-gray-600"></i>
                                @endif
                            </span>
                        </a>
                    </th>
                    <th colspan="7" class="px-4 py-2 text-center border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800 font-semibold uppercase tracking-wider text-[11px] text-gray-500 dark:text-gray-400">
                        Akumulasi Kehadiran Mapel
                    </th>
                    <th rowspan="2" class="px-4 py-3.5 align-middle text-center border-b border-gray-200 dark:border-gray-800 no-print">
                        Aksi
                    </th>
                </tr>
                <tr class="text-xs text-white">
                    <!-- Hadir -->
                    <th class="px-2.5 py-2 text-center bg-success-500 border-r border-white/20 min-w-[70px]">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'hadir', 'sort_dir' => (request('sort_by') === 'hadir' && request('sort_dir', 'desc') === 'desc' ? 'asc' : 'desc')]) }}"
                            class="group flex items-center justify-center gap-1 w-full text-white font-medium hover:opacity-90 select-none py-0.5"
                            title="Urutkan Hadir">
                            <span>Hadir</span>
                            @if(request('sort_by') === 'hadir')
                                <i class="fas fa-arrow-{{ request('sort_dir', 'desc') === 'asc' ? 'up' : 'down' }} text-[10px] font-bold"></i>
                            @else
                                <i class="fas fa-sort text-[10px] text-white/50 group-hover:text-white transition"></i>
                            @endif
                        </a>
                    </th>
                    <!-- Sakit -->
                    <th class="px-2.5 py-2 text-center bg-warning-500 border-r border-white/20 min-w-[70px]">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'sakit', 'sort_dir' => (request('sort_by') === 'sakit' && request('sort_dir', 'desc') === 'desc' ? 'asc' : 'desc')]) }}"
                            class="group flex items-center justify-center gap-1 w-full text-white font-medium hover:opacity-90 select-none py-0.5"
                            title="Urutkan Sakit">
                            <span>Sakit</span>
                            @if(request('sort_by') === 'sakit')
                                <i class="fas fa-arrow-{{ request('sort_dir', 'desc') === 'asc' ? 'up' : 'down' }} text-[10px] font-bold"></i>
                            @else
                                <i class="fas fa-sort text-[10px] text-white/50 group-hover:text-white transition"></i>
                            @endif
                        </a>
                    </th>
                    <!-- Izin -->
                    <th class="px-2.5 py-2 text-center bg-blue-500 border-r border-white/20 min-w-[70px]">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'izin', 'sort_dir' => (request('sort_by') === 'izin' && request('sort_dir', 'desc') === 'desc' ? 'asc' : 'desc')]) }}"
                            class="group flex items-center justify-center gap-1 w-full text-white font-medium hover:opacity-90 select-none py-0.5"
                            title="Urutkan Izin">
                            <span>Izin</span>
                            @if(request('sort_by') === 'izin')
                                <i class="fas fa-arrow-{{ request('sort_dir', 'desc') === 'asc' ? 'up' : 'down' }} text-[10px] font-bold"></i>
                            @else
                                <i class="fas fa-sort text-[10px] text-white/50 group-hover:text-white transition"></i>
                            @endif
                        </a>
                    </th>
                    <!-- Alpha -->
                    <th class="px-2.5 py-2 text-center bg-error-500 border-r border-white/20 min-w-[70px]">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'alpha', 'sort_dir' => (request('sort_by') === 'alpha' && request('sort_dir', 'desc') === 'desc' ? 'asc' : 'desc')]) }}"
                            class="group flex items-center justify-center gap-1 w-full text-white font-medium hover:opacity-90 select-none py-0.5"
                            title="Urutkan Alpha">
                            <span>Alpha</span>
                            @if(request('sort_by') === 'alpha')
                                <i class="fas fa-arrow-{{ request('sort_dir', 'desc') === 'asc' ? 'up' : 'down' }} text-[10px] font-bold"></i>
                            @else
                                <i class="fas fa-sort text-[10px] text-white/50 group-hover:text-white transition"></i>
                            @endif
                        </a>
                    </th>
                    <!-- Dispen -->
                    <th class="px-2.5 py-2 text-center bg-purple-500 border-r border-white/20 min-w-[70px]">
                        <span class="font-medium">Dispen</span>
                    </th>
                    <!-- Total Sesi -->
                    <th class="px-2.5 py-2 text-center bg-gray-600 dark:bg-gray-700 border-r border-white/20 min-w-[80px]">
                        <span class="font-medium">Total Sesi</span>
                    </th>
                    <!-- % Hadir -->
                    <th class="px-2.5 py-2 text-center bg-brand-500 border-r border-white/20 min-w-[80px]">
                        <span class="font-medium">% Hadir</span>
                    </th>
                </tr>
            </thead>
            <tbody class="text-xs">
                @forelse($allSiswa as $s)
                    @php
                        $sum = $summary[$s->id] ?? ['H'=>0, 'S'=>0, 'I'=>0, 'A'=>0, 'D'=>0, 'T'=>0, 'total'=>0, 'persentase'=>0];
                    @endphp
                    <tr class="hover:bg-gray-50/75 dark:hover:bg-gray-800/40 transition-colors border-b border-gray-100 dark:border-gray-800 last:border-b-0">
                        <!-- Nama Siswa (Cukup nama saja tanpa thumbnail / NIS / NISN) -->
                        <td class="px-4 py-3 xl:pl-6 border-r border-gray-100 dark:border-gray-800">
                            <p class="font-medium text-gray-800 dark:text-white/90">{{ $s->nama }}</p>
                        </td>

                        <!-- Kelas -->
                        <td class="px-4 py-3 border-r border-gray-100 dark:border-gray-800">
                            <p class="text-gray-500 dark:text-gray-400">{{ $s->kelas->nama_kelas ?? '-' }}</p>
                        </td>

                        <!-- Hadir (H) -->
                        <td class="px-2.5 py-3 text-center font-bold text-success-600 dark:text-success-400 border-r border-gray-100 dark:border-gray-800 bg-success-50/40 dark:bg-success-500/5 align-middle">
                            {{ $sum['H'] }}
                        </td>

                        <!-- Sakit (S) -->
                        <td class="px-2.5 py-3 text-center font-bold text-warning-600 dark:text-warning-400 border-r border-gray-100 dark:border-gray-800 bg-warning-50/40 dark:bg-warning-500/5 align-middle">
                            {{ $sum['S'] }}
                        </td>

                        <!-- Izin (I) -->
                        <td class="px-2.5 py-3 text-center font-bold text-blue-600 dark:text-blue-400 border-r border-gray-100 dark:border-gray-800 bg-blue-50/40 dark:bg-blue-500/5 align-middle">
                            {{ $sum['I'] }}
                        </td>

                        <!-- Alpha (A) -->
                        <td class="px-2.5 py-3 text-center font-bold text-error-600 dark:text-error-400 border-r border-gray-100 dark:border-gray-800 bg-error-50/40 dark:bg-error-500/5 align-middle">
                            {{ $sum['A'] }}
                        </td>

                        <!-- Dispen (D) -->
                        <td class="px-2.5 py-3 text-center font-bold text-purple-600 dark:text-purple-400 border-r border-gray-100 dark:border-gray-800 bg-purple-50/40 dark:bg-purple-500/5 align-middle">
                            {{ $sum['D'] }}
                        </td>

                        <!-- Total Pertemuan -->
                        <td class="px-2.5 py-3 text-center font-bold text-gray-700 dark:text-gray-300 border-r border-gray-100 dark:border-gray-800 bg-gray-50/40 dark:bg-gray-800/40 align-middle">
                            {{ $sum['total'] }}
                        </td>

                        <!-- % Hadir -->
                        <td class="px-2.5 py-3 text-center border-r border-gray-100 dark:border-gray-800 align-middle whitespace-nowrap">
                            @php
                                $badgeColor = $sum['persentase'] >= 85
                                    ? 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400 border-success-200/60'
                                    : ($sum['persentase'] >= 70
                                        ? 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400 border-warning-200/60'
                                        : 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400 border-error-200/60');
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $badgeColor }}">
                                {{ $sum['persentase'] }}%
                            </span>
                        </td>

                        <!-- Aksi (Detail) -->
                        <td class="px-4 py-3 text-center align-middle whitespace-nowrap no-print">
                            <a href="{{ route('absensi-pelajaran.rekap.show', array_merge(request()->all(), ['id' => $s->id])) }}"
                                class="inline-flex items-center justify-center gap-1 rounded-lg bg-info-50 px-3 py-1.5 text-xs font-semibold text-info-600 hover:bg-info-100 dark:bg-info-500/15 dark:text-info-400 dark:hover:bg-info-500/25 transition shadow-xs cursor-pointer">
                                <i class="fas fa-eye text-[11px]"></i> Detail
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <i class="fas fa-users-slash text-3xl text-gray-300 dark:text-gray-700"></i>
                                <p class="text-sm">Tidak ada data siswa ditemukan pada filter yang dipilih.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800 no-print">
        {{ $allSiswa->links('vendor.pagination.tailwind') }}
    </div>
</div>
@endsection
