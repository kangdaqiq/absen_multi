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
            Laporan dan akumulasi kehadiran kerja guru/staff berdasarkan shift.
        </p>
    </div>
</div>

<!-- Stats Cards -->
<div class="mb-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
    <div class="rounded-xl border border-gray-200 bg-white p-3.5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <p class="text-xs text-gray-500 dark:text-gray-400">Total Catatan</p>
        <h4 class="text-xl font-bold text-gray-800 dark:text-white mt-1">{{ $stats['total'] ?? 0 }}</h4>
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
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Filter Rekap</h6>
    </div>
    <div class="p-5">
        <form action="{{ route('rekap-guru.index') }}" method="GET" class="flex flex-col flex-wrap gap-4 md:flex-row items-end">
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
                    @foreach($gurus as $g)
                        <option value="{{ $g->id }}" {{ $guruId == $g->id ? 'selected' : '' }}>
                            {{ $g->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-full md:w-auto min-w-[220px]">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cari Nama:</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama..." 
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
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Data Rekap Absensi {{ $labelKaryawan }}</h6>
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $absensi->total() }} catatan</span>
    </div>
    
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto text-sm">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                    <th class="px-4 py-4 xl:pl-6 w-16">No</th>
                    <th class="px-4 py-4">Tanggal</th>
                    <th class="px-4 py-4">Nama {{ $labelKaryawan }}</th>
                    <th class="px-4 py-4 text-center">Shift</th>
                    <th class="px-4 py-4 text-center">Status</th>
                    <th class="px-4 py-4 text-center">Jam Masuk</th>
                    <th class="px-4 py-4 text-center">Jam Pulang</th>
                    <th class="px-4 py-4">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($absensi as $a)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-4 py-3.5 xl:pl-6">
                            <p class="text-gray-500 dark:text-gray-400">{{ $loop->iteration + $absensi->firstItem() - 1 }}</p>
                        </td>
                        <td class="px-4 py-3.5 whitespace-nowrap">
                            <p class="font-medium text-gray-800 dark:text-white/90">{{ \Carbon\Carbon::parse($a->tanggal)->format('d/m/Y') }}</p>
                        </td>
                        <td class="px-4 py-3.5">
                            <p class="font-semibold text-gray-800 dark:text-white/90">{{ $a->guru->nama ?? '-' }}</p>
                            @if(!empty($a->guru->nip))
                                <span class="text-xs text-gray-400 font-mono">{{ $a->guru->nip }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                            @if($a->shift)
                                <span class="inline-flex items-center gap-1 font-mono text-xs font-semibold px-2.5 py-1 rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                    <i class="far fa-clock text-[10px]"></i> {{ $a->shift->nama_shift }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400 italic">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                            @if($a->status == 'Hadir')
                                <span class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-500">Hadir</span>
                            @elseif($a->status == 'Terlambat')
                                <span class="inline-flex rounded-full bg-warning-50 px-3 py-1 text-xs font-semibold text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">
                                    Terlambat (+{{ $a->menit_terlambat }}m)
                                </span>
                            @elseif($a->status == 'Izin')
                                <span class="inline-flex rounded-full bg-info-50 px-3 py-1 text-xs font-semibold text-info-600 dark:bg-info-500/15 dark:text-info-500">Izin</span>
                            @elseif($a->status == 'Sakit')
                                <span class="inline-flex rounded-full bg-warning-50 px-3 py-1 text-xs font-semibold text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">Sakit</span>
                            @else
                                <span class="inline-flex rounded-full bg-error-50 px-3 py-1 text-xs font-semibold text-error-600 dark:bg-error-500/15 dark:text-error-500">{{ $a->status }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                            @if($a->jam_masuk)
                                <span class="inline-block rounded px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 font-mono text-xs font-bold">
                                    {{ \Carbon\Carbon::parse($a->jam_masuk)->format('H:i') }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 text-center whitespace-nowrap">
                            @if($a->jam_pulang)
                                <span class="inline-block rounded px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 font-mono text-xs font-bold">
                                    {{ \Carbon\Carbon::parse($a->jam_pulang)->format('H:i') }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5">
                            <p class="text-gray-500 dark:text-gray-400 text-xs">{{ $a->keterangan ?? '-' }}</p>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data absensi ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
        {{ $absensi->links('vendor.pagination.tailwind') }}
    </div>
</div>
@endsection