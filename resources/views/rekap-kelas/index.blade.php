@extends('layouts.app')

@section('title', 'Rekap Kehadiran Kelas')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Rekap Kehadiran Kelas
    </h2>
</div>

<!-- Filter Card -->
<div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Filter Periode</h6>
    </div>
    <div class="p-5">
        <form action="{{ route('rekap-kelas.index') }}" method="GET" class="flex flex-col flex-wrap gap-4 md:flex-row items-end">
            <div class="w-full md:w-auto">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Dari Tanggal:</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>
            <div class="w-full md:w-auto">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai Tanggal:</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>
            <div class="w-full md:w-auto min-w-[250px]">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cari Kelas:</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama kelas..." 
                    oninput="clearTimeout(this.delay); this.delay = setTimeout(() => { this.form.submit() }, 500);"
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>
            
            <div class="w-full md:w-auto flex flex-wrap gap-2 mt-2 md:mt-0">
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-center font-medium text-white hover:bg-brand-600 transition">
                    <i class="fas fa-search"></i> Tampilkan
                </button>
                <button type="submit" formaction="{{ route('rekap-kelas.export') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-success-500 px-4 py-2 text-center font-medium text-white hover:bg-success-600 transition">
                    <i class="fas fa-file-excel"></i> Excel
                </button>
                <a href="{{ route('rekap-kelas.pdf', request()->all()) }}" target="_blank" class="inline-flex items-center justify-center gap-2 rounded-lg bg-error-500 px-4 py-2 text-center font-medium text-white hover:bg-error-600 transition">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Data Table Card -->
<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Hasil Rekap Kelas</h6>
    </div>
    
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                    <th rowspan="2" class="px-4 py-4 xl:pl-6 align-middle border-b border-gray-200 dark:border-gray-800 border-r w-12 text-center">No</th>
                    <th rowspan="2" class="px-4 py-4 align-middle border-b border-gray-200 dark:border-gray-800 border-r">Nama Kelas</th>
                    <th colspan="8" class="px-4 py-2 text-center border-b border-gray-200 dark:border-gray-800">Jumlah Kehadiran Siswa</th>
                </tr>
                <tr class="text-xs text-white">
                    <th class="px-2 py-2 text-center bg-success-500 border-r border-white/20 min-w-[80px] w-[9%]">Hadir</th>
                    <th class="px-2 py-2 text-center bg-gray-500 border-r border-white/20 min-w-[80px] w-[9%]">Tidak Hadir</th>
                    <th class="px-2 py-2 text-center bg-warning-500 border-r border-white/20 min-w-[80px] w-[9%]">Telat</th>
                    <th class="px-2 py-2 text-center bg-info-500 border-r border-white/20 min-w-[80px] w-[9%]">Izin</th>
                    <th class="px-2 py-2 text-center bg-warning-500 border-r border-white/20 min-w-[80px] w-[9%]">Sakit</th>
                    <th class="px-2 py-2 text-center bg-error-500 border-r border-white/20 min-w-[80px] w-[9%]">Bolos</th>
                    <th class="px-2 py-2 text-center bg-gray-600 dark:bg-gray-700 border-r border-white/20 min-w-[80px] w-[9%]">Alpha</th>
                    <th class="px-2 py-2 text-center bg-brand-500 border-white/20 min-w-[80px] w-[9%]">Persentase (%)</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($allKelas as $index => $kelas)
                    @php 
                        $sum = $summary[$kelas->id] ?? ['H'=>0,'T'=>0,'I'=>0,'S'=>0,'B'=>0,'A'=>0];
                        $tidakHadir = $sum['I'] + $sum['S'] + $sum['B'] + $sum['A'];
                        $total = $sum['H'] + $sum['T'] + $tidakHadir;
                        $persentase = $total > 0 ? round((($sum['H'] + $sum['T']) / $total) * 100, 1) : 0;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors border-b border-gray-100 dark:border-gray-800 last:border-b-0">
                        <td class="px-4 py-3 xl:pl-6 border-r border-gray-100 dark:border-gray-800 text-center">
                            <p class="text-gray-500 dark:text-gray-400">{{ $allKelas->firstItem() + $index }}</p>
                        </td>
                        <td class="px-4 py-3 border-r border-gray-100 dark:border-gray-800">
                            <p class="font-medium text-gray-800 dark:text-white/90">{{ $kelas->nama_kelas }}</p>
                        </td>
                        <td class="px-2 py-3 text-center font-bold text-success-600 dark:text-success-400 border-r border-gray-100 dark:border-gray-800 bg-success-50/50 dark:bg-success-500/5">{{ $sum['H'] }}</td>
                        <td class="px-2 py-3 text-center font-bold text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50">{{ $tidakHadir }}</td>
                        <td class="px-2 py-3 text-center font-bold text-warning-600 dark:text-warning-400 border-r border-gray-100 dark:border-gray-800 bg-warning-50/50 dark:bg-warning-500/5">{{ $sum['T'] }}</td>
                        <td class="px-2 py-3 text-center font-bold text-info-600 dark:text-info-400 border-r border-gray-100 dark:border-gray-800 bg-info-50/50 dark:bg-info-500/5">{{ $sum['I'] }}</td>
                        <td class="px-2 py-3 text-center font-bold text-warning-600 dark:text-warning-400 border-r border-gray-100 dark:border-gray-800 bg-warning-50/50 dark:bg-warning-500/5">{{ $sum['S'] }}</td>
                        <td class="px-2 py-3 text-center font-bold text-error-600 dark:text-error-400 border-r border-gray-100 dark:border-gray-800 bg-error-50/50 dark:bg-error-500/5">{{ $sum['B'] }}</td>
                        <td class="px-2 py-3 text-center font-bold text-gray-600 dark:text-gray-400 border-r border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/50">{{ $sum['A'] }}</td>
                        <td class="px-2 py-3 text-center font-bold text-brand-600 dark:text-brand-400 border-gray-100 dark:border-gray-800 bg-brand-50/50 dark:bg-brand-500/5">{{ $persentase }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data kelas ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if($allKelas->count() > 0)
            @php
                $totH = 0; $totT = 0; $totI = 0; $totS = 0; $totB = 0; $totA = 0;
                foreach($allKelas as $k) {
                    $s = $summary[$k->id] ?? ['H'=>0,'T'=>0,'I'=>0,'S'=>0,'B'=>0,'A'=>0];
                    $totH += $s['H']; $totT += $s['T']; $totI += $s['I'];
                    $totS += $s['S']; $totB += $s['B']; $totA += $s['A'];
                }
                $totTidakHadir = $totI + $totS + $totB + $totA;
                $totGlobal = $totH + $totT + $totTidakHadir;
                $totPersen = $totGlobal > 0 ? round((($totH + $totT) / $totGlobal) * 100, 1) : 0;
            @endphp
            <tfoot class="bg-gray-50 dark:bg-gray-800/80 font-bold border-t-2 border-gray-200 dark:border-gray-700">
                <tr>
                    <td colspan="2" class="px-4 py-3 text-right border-r border-gray-200 dark:border-gray-700 text-gray-800 dark:text-white/90 uppercase tracking-wider text-xs">Total Kehadiran</td>
                    <td class="px-2 py-3 text-center text-success-600 dark:text-success-400 border-r border-gray-200 dark:border-gray-700">{{ $totH }}</td>
                    <td class="px-2 py-3 text-center text-gray-600 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700">{{ $totTidakHadir }}</td>
                    <td class="px-2 py-3 text-center text-warning-600 dark:text-warning-400 border-r border-gray-200 dark:border-gray-700">{{ $totT }}</td>
                    <td class="px-2 py-3 text-center text-info-600 dark:text-info-400 border-r border-gray-200 dark:border-gray-700">{{ $totI }}</td>
                    <td class="px-2 py-3 text-center text-warning-600 dark:text-warning-400 border-r border-gray-200 dark:border-gray-700">{{ $totS }}</td>
                    <td class="px-2 py-3 text-center text-error-600 dark:text-error-400 border-r border-gray-200 dark:border-gray-700">{{ $totB }}</td>
                    <td class="px-2 py-3 text-center text-gray-600 dark:text-gray-400 border-r border-gray-200 dark:border-gray-700">{{ $totA }}</td>
                    <td class="px-2 py-3 text-center text-brand-600 dark:text-brand-400">{{ $totPersen }}%</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
    
    <!-- Pagination -->
    @if($allKelas->hasPages())
    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
        {{ $allKelas->links('vendor.pagination.tailwind') }}
    </div>
    @endif
</div>
@endsection
