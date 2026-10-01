@extends('layouts.app')

@section('title', 'Riwayat Kehadiran Siswa')

@section('content')
    <!-- Page Header -->
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
                Riwayat Kehadiran Siswa
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Catatan absensi lengkap: <span class="font-medium text-gray-800 dark:text-white/90">{{ $siswa->nama }}</span> (NIS: {{ $siswa->nis }} &bull; Kelas: {{ $siswa->kelas->nama_kelas ?? $siswa->kelas->nama ?? '-' }})
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center justify-center gap-2.5 rounded-lg bg-gray-500 px-4 py-2 text-center font-medium text-white hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
            <h6 class="font-semibold text-gray-800 dark:text-white/90">Filter Periode Absensi</h6>
        </div>
        <div class="p-5">
            <form action="{{ route('siswa.riwayat') }}" method="GET" class="flex flex-col flex-wrap gap-4 md:flex-row items-end">
                @php
                    $months = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                @endphp
                <div class="w-full md:w-auto min-w-[180px]">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Bulan:</label>
                    <select name="bulan" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        @foreach($months as $num => $name)
                            <option value="{{ $num }}" {{ (int)$bulan === $num ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full md:w-auto min-w-[140px]">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tahun:</label>
                    <select name="tahun" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        @for($y = date('Y'); $y >= date('Y') - 3; $y--)
                            <option value="{{ $y }}" {{ (int)$tahun === $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="w-full md:w-auto min-w-[220px]">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Kata Kunci:</label>
                    <input type="text" name="q" value="{{ $search }}" placeholder="Status atau keterangan..."
                        class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>
                
                <div class="w-full md:w-auto flex flex-wrap gap-2 mt-2 md:mt-0">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-center font-medium text-white hover:bg-brand-600 transition">
                        <i class="fas fa-search"></i> Tampilkan
                    </button>
                    @if($search || $bulan != date('m') || $tahun != date('Y'))
                        <a href="{{ route('siswa.riwayat') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-center font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Ringkasan Periode Cards -->
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-5 2xl:gap-7.5">
        <!-- Hadir Card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
            <div class="flex items-center justify-center w-12 h-12 bg-success-50 rounded-xl dark:bg-success-500/15 text-success-500">
                <i class="fas fa-check-circle fa-lg"></i>
            </div>
            <div class="flex items-end justify-between mt-5">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Total Hadir</span>
                    <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $statsPeriod['H'] }}</h4>
                </div>
                <span class="text-xs font-medium text-success-600 dark:text-success-400">{{ $rateHadir }}%</span>
            </div>
        </div>

        <!-- Terlambat Card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
            <div class="flex items-center justify-center w-12 h-12 bg-warning-50 rounded-xl dark:bg-warning-500/15 text-warning-500">
                <i class="fas fa-clock fa-lg"></i>
            </div>
            <div class="flex items-end justify-between mt-5">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Terlambat</span>
                    <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $statsPeriod['T'] }}</h4>
                </div>
            </div>
        </div>

        <!-- Izin Card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
            <div class="flex items-center justify-center w-12 h-12 bg-info-50 rounded-xl dark:bg-info-500/15 text-info-500">
                <i class="fas fa-file-alt fa-lg"></i>
            </div>
            <div class="flex items-end justify-between mt-5">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Izin</span>
                    <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $statsPeriod['I'] }}</h4>
                </div>
            </div>
        </div>

        <!-- Sakit Card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
            <div class="flex items-center justify-center w-12 h-12 bg-warning-50 rounded-xl dark:bg-warning-500/15 text-warning-500">
                <i class="fas fa-notes-medical fa-lg"></i>
            </div>
            <div class="flex items-end justify-between mt-5">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Sakit</span>
                    <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $statsPeriod['S'] }}</h4>
                </div>
            </div>
        </div>

        <!-- Alpha Card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
            <div class="flex items-center justify-center w-12 h-12 bg-error-50 rounded-xl dark:bg-error-500/15 text-error-500">
                <i class="fas fa-times-circle fa-lg"></i>
            </div>
            <div class="flex items-end justify-between mt-5">
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Alpha</span>
                    <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $statsPeriod['A'] }}</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex items-center justify-between">
            <h6 class="font-semibold text-gray-800 dark:text-white/90">
                Hasil Rekap: {{ $months[$bulan] ?? $bulan }} {{ $tahun }}
            </h6>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                Total: {{ $attendances->total() }} catatan
            </span>
        </div>
        
        <div class="max-w-full overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                        <th class="px-4 py-4 xl:pl-6">Tanggal</th>
                        <th class="px-4 py-4">Jam Masuk</th>
                        <th class="px-4 py-4">Jam Pulang</th>
                        <th class="px-4 py-4 text-center">Status</th>
                        <th class="px-4 py-4">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @forelse($attendances as $row)
                        @php
                            $isTelat = $row->status == 'H' && (str_contains(strtolower($row->keterangan ?? ''), 'telat') || str_contains(strtolower($row->keterangan ?? ''), 'terlambat'));
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 xl:pl-6 text-gray-800 dark:text-white/90 font-medium">
                                {{ \Carbon\Carbon::parse($row->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 text-gray-600 dark:text-gray-300 font-mono">
                                {{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '-' }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 text-gray-600 dark:text-gray-300 font-mono">
                                {{ $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '-' }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 text-center">
                                @if($isTelat)
                                    <span class="inline-flex rounded-full bg-warning-50 px-2.5 py-1 text-xs font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">Terlambat</span>
                                @elseif($row->status == 'H')
                                    <span class="inline-flex rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">Hadir</span>
                                @elseif($row->status == 'I')
                                    <span class="inline-flex rounded-full bg-info-50 px-2.5 py-1 text-xs font-medium text-info-600 dark:bg-info-500/15 dark:text-info-500">Izin</span>
                                @elseif($row->status == 'S')
                                    <span class="inline-flex rounded-full bg-warning-50 px-2.5 py-1 text-xs font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">Sakit</span>
                                @elseif($row->status == 'T')
                                    <span class="inline-flex rounded-full bg-warning-50 px-2.5 py-1 text-xs font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">Terlambat</span>
                                @elseif($row->status == 'B')
                                    <span class="inline-flex rounded-full bg-error-50 px-2.5 py-1 text-xs font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">Bolos</span>
                                @else
                                    <span class="inline-flex rounded-full bg-error-50 px-2.5 py-1 text-xs font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">Alpha</span>
                                @endif
                            </td>
                            <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 text-gray-500 dark:text-gray-400">
                                {{ $row->keterangan ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="border-b border-gray-100 px-4 py-8 dark:border-gray-800 text-center text-gray-500 dark:text-gray-400">
                                Tidak ada data riwayat kehadiran pada periode ini
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attendances->hasPages())
            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
                {{ $attendances->links() }}
            </div>
        @endif
    </div>
@endsection
