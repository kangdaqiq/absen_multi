@extends('layouts.app')

@php
    $school = auth()->user()->school ?? null;
    $labelKaryawan = $school?->employeeLabel() ?? 'Guru';
    $labelNIP = $school?->nipLabel() ?? 'NIP';
@endphp

@section('title', 'Rekap Absensi ' . $labelKaryawan)

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Rekap Absensi {{ $labelKaryawan }}
    </h2>
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
            <div class="w-full md:w-auto min-w-[200px]">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $labelKaryawan }} (Opsional):</label>
                <select name="guru_id" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white select2">
                    <option value="">-- Semua {{ $labelKaryawan }} --</option>
                    @foreach($gurus as $g)
                        <option value="{{ $g->id }}" {{ $guruId == $g->id ? 'selected' : '' }}>
                            {{ $g->nama }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-full md:w-auto min-w-[250px]">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cari di halaman ini:</label>
                <input type="text" id="clientSearch" placeholder="Ketik nama..." class="client-search w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>
            
            <div class="w-full md:w-auto flex flex-wrap gap-2 mt-2 md:mt-0">
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-center font-medium text-white hover:bg-brand-600 transition">
                    <i class="fas fa-search"></i> Tampilkan
                </button>
                <a href="{{ route('rekap-guru.export', request()->query()) }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-success-500 px-4 py-2 text-center font-medium text-white hover:bg-success-600 transition">
                    <i class="fas fa-file-excel"></i> Excel
                </a>
                <a href="{{ route('rekap-guru.pdf', request()->query()) }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-lg bg-error-500 px-4 py-2 text-center font-medium text-white hover:bg-error-600 transition">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </form>
    </div>
</div>



<!-- Data Table Card -->
<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Data Absensi {{ $labelKaryawan }}</h6>
    </div>
    
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                    <th class="px-4 py-4 xl:pl-6 w-16">No</th>
                    <th class="px-4 py-4">Tanggal</th>
                    <th class="px-4 py-4">Nama {{ $labelKaryawan }}</th>
                    <th class="px-4 py-4 text-center">Status</th>
                    <th class="px-4 py-4 text-center">Jam Masuk</th>
                    <th class="px-4 py-4">Keterangan</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($absensi as $a)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors border-b border-gray-100 dark:border-gray-800 last:border-b-0">
                        <td class="px-4 py-3 xl:pl-6">
                            <p class="text-gray-500 dark:text-gray-400">{{ $loop->iteration + $absensi->firstItem() - 1 }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-gray-800 dark:text-white/90">{{ \Carbon\Carbon::parse($a->tanggal)->format('d/m/Y') }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-800 dark:text-white/90">{{ $a->guru->nama ?? '-' }}</p>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($a->status == 'Hadir')
                                <span class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">Hadir</span>
                            @else
                                <span class="inline-flex rounded-full bg-warning-50 px-3 py-1 text-xs font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">{{ $a->status }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($a->jam_masuk)
                                <span class="inline-block rounded px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 font-mono text-xs font-bold">
                                    {{ \Carbon\Carbon::parse($a->jam_masuk)->format('H:i') }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-gray-500 dark:text-gray-400 text-xs">{{ $a->keterangan ?? '-' }}</p>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
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