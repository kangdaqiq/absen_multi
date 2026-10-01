@extends('layouts.app')

@php
    $school = auth()->user()->school ?? null;
    $labelKaryawan = $school?->employeeLabel() ?? 'Guru';
    $labelNIP = $school?->nipLabel() ?? 'NIP';
@endphp

@section('title', 'Rekap Absensi ' . $labelKaryawan)

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            📊 Rekap Absensi {{ $labelKaryawan }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
            Laporan dan akumulasi kehadiran kerja {{ strtolower($labelKaryawan) }}/staff berdasarkan shift.
        </p>
    </div>
</div>

<!-- Stats Cards -->
<div class="mb-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
    <div class="rounded-xl border border-gray-200 bg-white p-3.5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <p class="text-xs text-gray-500 dark:text-gray-400">Total {{ $labelKaryawan }}</p>
        <h4 class="text-xl font-bold text-gray-800 dark:text-white mt-1">{{ $stats['total_guru'] ?? $allGurus->total() }}</h4>
    </div>
    <div class="rounded-xl border border-success-200 bg-success-50/50 p-3.5 shadow-theme-sm dark:border-success-500/30 dark:bg-success-500/10">
        <p class="text-xs text-success-700 dark:text-success-400 font-medium">Hadir Tepat Waktu</p>
        <h4 class="text-xl font-bold text-success-700 dark:text-success-400 mt-1">{{ $stats['hadir'] ?? 0 }}</h4>
    </div>
    <div class="rounded-xl border border-warning-200 bg-warning-50/50 p-3.5 shadow-theme-sm dark:border-warning-500/30 dark:bg-warning-500/10">
        <p class="text-xs text-warning-700 dark:text-warning-400 font-medium">Terlambat</p>
        <h4 class="text-xl font-bold text-warning-700 dark:text-warning-400 mt-1">{{ $stats['terlambat'] ?? 0 }}</h4>
    </div>
    <div class="rounded-xl border border-info-200 bg-info-50/50 p-3.5 shadow-theme-sm dark:border-info-500/30 dark:bg-info-500/10">
        <p class="text-xs text-info-700 dark:text-info-400 font-medium">Izin & Sakit</p>
        <h4 class="text-xl font-bold text-info-700 dark:text-info-400 mt-1">{{ ($stats['izin'] ?? 0) + ($stats['sakit'] ?? 0) }}</h4>
    </div>
    <div class="rounded-xl border border-error-200 bg-error-50/50 p-3.5 shadow-theme-sm dark:border-error-500/30 dark:bg-error-500/10">
        <p class="text-xs text-error-700 dark:text-error-400 font-medium">Tidak Hadir / Alpha</p>
        <h4 class="text-xl font-bold text-error-700 dark:text-error-400 mt-1">{{ $stats['tidak_hadir'] ?? 0 }}</h4>
    </div>
</div>

<!-- Filter Card -->
<div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex items-center justify-between">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Filter Rekap</h6>
        @if(request('sort_by'))
            <a href="{{ route('rekap-guru.index', request()->except(['sort_by', 'sort_dir', 'page'])) }}" 
               class="inline-flex items-center gap-1.5 text-xs text-brand-600 hover:text-brand-700 dark:text-brand-400 font-medium hover:underline">
                <i class="fas fa-undo"></i> Reset Urutan ({{ ucfirst(str_replace('_', ' ', request('sort_by'))) }} {{ strtoupper(request('sort_dir', 'asc')) }})
            </a>
        @endif
    </div>
    <div class="p-5">
        <form action="{{ route('rekap-guru.index') }}" method="GET" class="flex flex-col flex-wrap gap-4 md:flex-row items-end">
            @if(request('sort_by'))
                <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                <input type="hidden" name="sort_dir" value="{{ request('sort_dir', 'asc') }}">
            @endif

            <div class="w-full md:w-auto">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Mulai:</label>
                <input type="date" name="start_date" value="{{ $startDate }}" required class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>
            <div class="w-full md:w-auto">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Akhir:</label>
                <input type="date" name="end_date" value="{{ $endDate }}" required class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>
            <div class="w-full md:w-auto min-w-[180px]">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Filter Shift:</label>
                <select name="shift_id" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="">-- Semua Shift --</option>
                    @foreach($shifts as $s)
                        <option value="{{ $s->id }}" {{ $shiftId == $s->id ? 'selected' : '' }}>
                            {{ $s->nama_shift }} ({{ $s->formatted_jam_masuk }}-{{ $s->formatted_jam_pulang }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-full md:w-auto min-w-[200px]">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $labelKaryawan }}:</label>
                <select name="guru_id" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white select2">
                    <option value="">-- Semua {{ $labelKaryawan }} --</option>
                    @foreach($gurusList ?? [] as $g)
                        <option value="{{ $g->id }}" {{ $guruId == $g->id ? 'selected' : '' }}>
                            {{ $g->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-full md:w-auto min-w-[220px]">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cari {{ $labelKaryawan }}:</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama atau {{ $labelNIP }}..." 
                    oninput="clearTimeout(this.delay); this.delay = setTimeout(() => { this.form.submit() }, 500);"
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>
            
            <div class="w-full md:w-auto flex flex-wrap gap-2 mt-2 md:mt-0">
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-center font-medium text-white hover:bg-brand-600 transition shadow-sm">
                    <i class="fas fa-search"></i> Tampilkan
                </button>
                <a href="{{ route('rekap-guru.export', request()->query()) }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-success-500 px-4 py-2 text-center font-medium text-white hover:bg-success-600 transition shadow-sm">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('rekap-guru.pdf', request()->query()) }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-lg bg-error-500 px-4 py-2 text-center font-medium text-white hover:bg-error-600 transition shadow-sm">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Data Table Card -->
<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex justify-between items-center">
        <div>
            <h6 class="font-semibold text-gray-800 dark:text-white/90">Data Rekap Absensi {{ $labelKaryawan }}</h6>
            @if(request('sort_by'))
                <span class="text-xs text-brand-600 dark:text-brand-400 font-medium">
                    Diurutkan berdasarkan: <b>{{ ucfirst(str_replace('_', ' ', request('sort_by'))) }}</b> ({{ strtoupper(request('sort_dir', 'asc')) }})
                </span>
            @endif
        </div>
        <div class="flex items-center gap-3">
            @if(request('sort_by'))
                <a href="{{ route('rekap-guru.index', request()->except(['sort_by', 'sort_dir', 'page'])) }}" 
                   class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition" 
                   title="Reset Urutan">
                    <i class="fas fa-times-circle"></i> Reset Urutan
                </a>
            @endif
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $allGurus->total() }} {{ strtolower($labelKaryawan) }}</span>
        </div>
    </div>
    
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto border-collapse">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                    <th rowspan="2" class="px-3 py-4 xl:pl-6 text-center border-b border-gray-200 dark:border-gray-800 border-r w-12">No</th>
                    
                    {{-- Nama Guru --}}
                    <th rowspan="2" class="px-4 py-4 align-middle border-b border-gray-200 dark:border-gray-800 border-r text-left">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'nama', 'sort_dir' => (request('sort_by') === 'nama' && request('sort_dir', 'asc') === 'asc' ? 'desc' : 'asc')]) }}" 
                           class="group inline-flex items-center gap-1.5 hover:text-brand-500 transition select-none cursor-pointer" 
                           title="Urutkan Nama {{ $labelKaryawan }} ({{ request('sort_by') === 'nama' && request('sort_dir', 'asc') === 'asc' ? 'Z-A' : 'A-Z' }})">
                            <span>Nama {{ $labelKaryawan }}</span>
                            <span class="inline-flex text-[11px]">
                                @if(request('sort_by') === 'nama')
                                    <i class="fas fa-arrow-{{ request('sort_dir', 'asc') === 'desc' ? 'down' : 'up' }} text-brand-500 font-bold"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400 group-hover:text-gray-600 dark:text-gray-500 dark:group-hover:text-gray-300"></i>
                                @endif
                            </span>
                        </a>
                    </th>

                    {{-- NIP --}}
                    <th rowspan="2" class="px-4 py-4 align-middle border-b border-gray-200 dark:border-gray-800 border-r text-left">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'nip', 'sort_dir' => (request('sort_by') === 'nip' && request('sort_dir', 'asc') === 'asc' ? 'desc' : 'asc')]) }}" 
                           class="group inline-flex items-center gap-1.5 hover:text-brand-500 transition select-none cursor-pointer" 
                           title="Urutkan {{ $labelNIP }} ({{ request('sort_by') === 'nip' && request('sort_dir', 'asc') === 'asc' ? 'Z-A' : 'A-Z' }})">
                            <span>{{ $labelNIP }}</span>
                            <span class="inline-flex text-[11px]">
                                @if(request('sort_by') === 'nip')
                                    <i class="fas fa-arrow-{{ request('sort_dir', 'asc') === 'desc' ? 'down' : 'up' }} text-brand-500 font-bold"></i>
                                @else
                                    <i class="fas fa-sort text-gray-400 group-hover:text-gray-600 dark:text-gray-500 dark:group-hover:text-gray-300"></i>
                                @endif
                            </span>
                        </a>
                    </th>

                    {{-- Shift Utama --}}
                    <th rowspan="2" class="px-4 py-4 align-middle text-center border-b border-gray-200 dark:border-gray-800 border-r min-w-[130px]">
                        Shift Utama
                    </th>

                    {{-- Header Group Kehadiran --}}
                    <th colspan="7" class="px-4 py-2 text-center border-b border-gray-200 dark:border-gray-800">
                        Jumlah Kehadiran
                    </th>
                </tr>

                {{-- Baris Sub-Kolom Kehadiran --}}
                <tr class="text-xs text-white">
                    {{-- Hadir --}}
                    <th class="px-2 py-2 text-center bg-success-500 border-r border-white/20 min-w-[75px]">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'hadir', 'sort_dir' => (request('sort_by') === 'hadir' && request('sort_dir', 'desc') === 'desc' ? 'asc' : 'desc')]) }}" 
                           class="group flex items-center justify-center gap-1 w-full text-white font-medium hover:opacity-90 select-none py-1" 
                           title="Urutkan Hadir ({{ request('sort_by') === 'hadir' && request('sort_dir', 'desc') === 'desc' ? 'Terkecil' : 'Terbesar' }})">
                            <span>Hadir</span>
                            @if(request('sort_by') === 'hadir')
                                <i class="fas fa-arrow-{{ request('sort_dir', 'desc') === 'asc' ? 'up' : 'down' }} text-[11px] font-bold"></i>
                            @else
                                <i class="fas fa-sort text-[11px] text-white/50 group-hover:text-white transition"></i>
                            @endif
                        </a>
                    </th>

                    {{-- Terlambat --}}
                    <th class="px-2 py-2 text-center bg-warning-500 border-r border-white/20 min-w-[85px]">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'terlambat', 'sort_dir' => (request('sort_by') === 'terlambat' && request('sort_dir', 'desc') === 'desc' ? 'asc' : 'desc')]) }}" 
                           class="group flex items-center justify-center gap-1 w-full text-white font-medium hover:opacity-90 select-none py-1" 
                           title="Urutkan Terlambat ({{ request('sort_by') === 'terlambat' && request('sort_dir', 'desc') === 'desc' ? 'Terkecil' : 'Terbesar' }})">
                            <span>Terlambat</span>
                            @if(request('sort_by') === 'terlambat')
                                <i class="fas fa-arrow-{{ request('sort_dir', 'desc') === 'asc' ? 'up' : 'down' }} text-[11px] font-bold"></i>
                            @else
                                <i class="fas fa-sort text-[11px] text-white/50 group-hover:text-white transition"></i>
                            @endif
                        </a>
                    </th>

                    {{-- Tidak Hadir --}}
                    <th class="px-2 py-2 text-center bg-gray-500 border-r border-white/20 min-w-[85px]">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'tidak_hadir', 'sort_dir' => (request('sort_by') === 'tidak_hadir' && request('sort_dir', 'desc') === 'desc' ? 'asc' : 'desc')]) }}" 
                           class="group flex items-center justify-center gap-1 w-full text-white font-medium hover:opacity-90 select-none py-1" 
                           title="Urutkan Tidak Hadir ({{ request('sort_by') === 'tidak_hadir' && request('sort_dir', 'desc') === 'desc' ? 'Terkecil' : 'Terbesar' }})">
                            <span>Tidak Hadir</span>
                            @if(request('sort_by') === 'tidak_hadir')
                                <i class="fas fa-arrow-{{ request('sort_dir', 'desc') === 'asc' ? 'up' : 'down' }} text-[11px] font-bold"></i>
                            @else
                                <i class="fas fa-sort text-[11px] text-white/50 group-hover:text-white transition"></i>
                            @endif
                        </a>
                    </th>

                    {{-- Izin --}}
                    <th class="px-2 py-2 text-center bg-info-500 border-r border-white/20 min-w-[70px]">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'izin', 'sort_dir' => (request('sort_by') === 'izin' && request('sort_dir', 'desc') === 'desc' ? 'asc' : 'desc')]) }}" 
                           class="group flex items-center justify-center gap-1 w-full text-white font-medium hover:opacity-90 select-none py-1" 
                           title="Urutkan Izin ({{ request('sort_by') === 'izin' && request('sort_dir', 'desc') === 'desc' ? 'Terkecil' : 'Terbesar' }})">
                            <span>Izin</span>
                            @if(request('sort_by') === 'izin')
                                <i class="fas fa-arrow-{{ request('sort_dir', 'desc') === 'asc' ? 'up' : 'down' }} text-[11px] font-bold"></i>
                            @else
                                <i class="fas fa-sort text-[11px] text-white/50 group-hover:text-white transition"></i>
                            @endif
                        </a>
                    </th>

                    {{-- Sakit --}}
                    <th class="px-2 py-2 text-center bg-warning-500 border-r border-white/20 min-w-[70px]">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'sakit', 'sort_dir' => (request('sort_by') === 'sakit' && request('sort_dir', 'desc') === 'desc' ? 'asc' : 'desc')]) }}" 
                           class="group flex items-center justify-center gap-1 w-full text-white font-medium hover:opacity-90 select-none py-1" 
                           title="Urutkan Sakit ({{ request('sort_by') === 'sakit' && request('sort_dir', 'desc') === 'desc' ? 'Terkecil' : 'Terbesar' }})">
                            <span>Sakit</span>
                            @if(request('sort_by') === 'sakit')
                                <i class="fas fa-arrow-{{ request('sort_dir', 'desc') === 'asc' ? 'up' : 'down' }} text-[11px] font-bold"></i>
                            @else
                                <i class="fas fa-sort text-[11px] text-white/50 group-hover:text-white transition"></i>
                            @endif
                        </a>
                    </th>

                    {{-- Alpha --}}
                    <th class="px-2 py-2 text-center bg-gray-600 dark:bg-gray-700 border-r border-white/20 min-w-[70px]">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'alpha', 'sort_dir' => (request('sort_by') === 'alpha' && request('sort_dir', 'desc') === 'desc' ? 'asc' : 'desc')]) }}" 
                           class="group flex items-center justify-center gap-1 w-full text-white font-medium hover:opacity-90 select-none py-1" 
                           title="Urutkan Alpha ({{ request('sort_by') === 'alpha' && request('sort_dir', 'desc') === 'desc' ? 'Terkecil' : 'Terbesar' }})">
                            <span>Alpha</span>
                            @if(request('sort_by') === 'alpha')
                                <i class="fas fa-arrow-{{ request('sort_dir', 'desc') === 'asc' ? 'up' : 'down' }} text-[11px] font-bold"></i>
                            @else
                                <i class="fas fa-sort text-[11px] text-white/50 group-hover:text-white transition"></i>
                            @endif
                        </a>
                    </th>

                    {{-- % Hadir --}}
                    <th class="px-2 py-2 text-center bg-brand-500 min-w-[80px]">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'persentase', 'sort_dir' => (request('sort_by') === 'persentase' && request('sort_dir', 'desc') === 'desc' ? 'asc' : 'desc')]) }}" 
                           class="group flex items-center justify-center gap-1 w-full text-white font-medium hover:opacity-90 select-none py-1" 
                           title="Urutkan % Hadir ({{ request('sort_by') === 'persentase' && request('sort_dir', 'desc') === 'desc' ? 'Terkecil' : 'Terbesar' }})">
                            <span>% Hadir</span>
                            @if(request('sort_by') === 'persentase')
                                <i class="fas fa-arrow-{{ request('sort_dir', 'desc') === 'asc' ? 'up' : 'down' }} text-[11px] font-bold"></i>
                            @else
                                <i class="fas fa-sort text-[11px] text-white/50 group-hover:text-white transition"></i>
                            @endif
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($allGurus as $g)
                    @php 
                        $sum = $summary[$g->id] ?? [
                            'hadir' => 0,
                            'terlambat' => 0,
                            'tidak_hadir' => 0,
                            'izin' => 0,
                            'sakit' => 0,
                            'alpha' => 0,
                            'total' => 0,
                            'persen' => 0,
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors border-b border-gray-100 dark:border-gray-800 last:border-b-0">
                        {{-- No --}}
                        <td class="px-3 py-3 text-center border-r border-gray-100 dark:border-gray-800 text-gray-500 dark:text-gray-400">
                            {{ $loop->iteration + $allGurus->firstItem() - 1 }}
                        </td>

                        {{-- Nama Guru --}}
                        <td class="px-4 py-3 xl:pl-6 border-r border-gray-100 dark:border-gray-800">
                            <p class="font-semibold text-gray-800 dark:text-white/90">{{ $g->nama }}</p>
                        </td>

                        {{-- NIP --}}
                        <td class="px-4 py-3 border-r border-gray-100 dark:border-gray-800 font-mono text-xs text-gray-600 dark:text-gray-400">
                            {{ $g->nip ?? '-' }}
                        </td>

                        {{-- Shift Utama --}}
                        <td class="px-4 py-3 text-center border-r border-gray-100 dark:border-gray-800 whitespace-nowrap">
                            @if($g->defaultShift)
                                <span class="inline-flex items-center gap-1 font-mono text-xs font-semibold px-2.5 py-1 rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                    <i class="far fa-clock text-[10px]"></i> {{ $g->defaultShift->nama_shift }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400 italic">-</span>
                            @endif
                        </td>

                        {{-- Hadir --}}
                        <td class="px-2 py-3 text-center font-bold text-success-600 dark:text-success-400 border-r border-gray-100 dark:border-gray-800 bg-success-50/50 dark:bg-success-500/5">
                            {{ $sum['hadir'] }}
                        </td>

                        {{-- Terlambat --}}
                        <td class="px-2 py-3 text-center font-bold text-warning-600 dark:text-warning-400 border-r border-gray-100 dark:border-gray-800 bg-warning-50/50 dark:bg-warning-500/5">
                            {{ $sum['terlambat'] }}
                        </td>

                        {{-- Tidak Hadir --}}
                        <td class="px-2 py-3 text-center font-bold text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50">
                            {{ $sum['tidak_hadir'] }}
                        </td>

                        {{-- Izin --}}
                        <td class="px-2 py-3 text-center font-bold text-info-600 dark:text-info-400 border-r border-gray-100 dark:border-gray-800 bg-info-50/50 dark:bg-info-500/5">
                            {{ $sum['izin'] }}
                        </td>

                        {{-- Sakit --}}
                        <td class="px-2 py-3 text-center font-bold text-warning-600 dark:text-warning-400 border-r border-gray-100 dark:border-gray-800 bg-warning-50/50 dark:bg-warning-500/5">
                            {{ $sum['sakit'] }}
                        </td>

                        {{-- Alpha --}}
                        <td class="px-2 py-3 text-center font-bold text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50">
                            {{ $sum['alpha'] }}
                        </td>

                        {{-- % Hadir --}}
                        <td class="px-2 py-3 text-center font-bold text-brand-600 dark:text-brand-400 bg-brand-50/50 dark:bg-brand-500/5">
                            {{ $sum['persen'] }}%
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data rekap absensi {{ strtolower($labelKaryawan) }} ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
        {{ $allGurus->links('vendor.pagination.tailwind') }}
    </div>
</div>
@endsection