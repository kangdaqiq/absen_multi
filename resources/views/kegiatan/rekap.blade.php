@extends('layouts.app')

@section('title', 'Rekap Kehadiran Kegiatan')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            📊 Rekap Kehadiran Kegiatan
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400">Akumulasi & Laporan Kehadiran Kegiatan Santri</p>
    </div>
</div>

{{-- Filter Card --}}
<div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Filter Periode & Kategori</h6>
    </div>
    <div class="p-5">
        <form action="{{ route('kegiatan.rekap') }}" method="GET" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4 items-end">
            <div>
                <label for="start_date" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Mulai</label>
                <input type="date" name="start_date" id="start_date" value="{{ $startDate }}" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>

            <div>
                <label for="end_date" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Selesai</label>
                <input type="date" name="end_date" id="end_date" value="{{ $endDate }}" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>

            <div>
                <label for="kegiatan_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Kegiatan</label>
                <select name="kegiatan_id" id="kegiatan_id" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="">Semua Kegiatan</option>
                    @foreach($kegiatans as $k)
                        <option value="{{ $k->id }}" {{ $kegiatanId == $k->id ? 'selected' : '' }}>{{ $k->nama_kegiatan }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="kelas_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Kelas</label>
                <select name="kelas_id" id="kelas_id" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="">Semua Kelas</option>
                    @foreach($allKelas as $k)
                        <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>

            <div class="relative">
                <label for="search" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cari Nama Santri</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Ketik nama santri..." 
                    oninput="clearTimeout(this.delay); this.delay = setTimeout(() => { this.form.submit() }, 600);"
                    class="w-full rounded-lg border border-gray-200 bg-transparent py-2 pl-4 pr-10 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                <div class="absolute right-3 bottom-2 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>

            <div class="md:col-span-2 xl:col-span-5 flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-6 py-2.5 text-center font-medium text-white hover:bg-brand-600 transition">
                    <i class="fas fa-filter"></i> Tampilkan Rekap
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Summary Cards --}}
<div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
    <div class="rounded-2xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-500/30 dark:bg-brand-500/10">
        <p class="text-sm text-brand-600 dark:text-brand-400 font-medium mb-1">Periode Laporan</p>
        <p class="text-base font-bold text-brand-800 dark:text-brand-300">
            {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s.d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
        </p>
    </div>

    <div class="rounded-2xl border border-success-200 bg-success-50 p-5 dark:border-success-500/30 dark:bg-success-500/10">
        <p class="text-sm text-success-600 dark:text-success-400 font-medium mb-1">Total Pertemuan Tercatat</p>
        <p class="text-3xl font-bold text-success-700 dark:text-success-300">
            {{ $totalMeetings }} <span class="text-sm font-semibold">pertemuan</span>
        </p>
    </div>

    <div class="rounded-2xl border border-info-200 bg-info-50 p-5 dark:border-info-500/30 dark:bg-info-500/10">
        <p class="text-sm text-info-600 dark:text-info-400 font-medium mb-1">Status Pencarian</p>
        <p class="text-base font-bold text-info-800 dark:text-info-300">
            @if($kegiatanId)
                Spesifik 1 Kegiatan
            @else
                Akumulasi Semua Kegiatan
            @endif
        </p>
    </div>
</div>

{{-- Data Table Card --}}
<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex justify-between items-center">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Hasil Rekapitulasi Presensi</h6>
        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $students->total() }} santri</span>
    </div>

    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                    <th class="px-5 py-4 w-12 text-center">#</th>
                    <th class="px-5 py-4">Nama Lengkap</th>
                    <th class="px-5 py-4">NIS</th>
                    <th class="px-5 py-4">Kelas</th>
                    <th class="px-5 py-4 text-center w-48">Total Kehadiran</th>
                    <th class="px-5 py-4 text-center w-60">Rasio Kehadiran</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($students as $index => $student)
                    @php
                        $attendanceCount = $student->total_kehadiran;
                        $percentage = $totalMeetings > 0 ? round(($attendanceCount / $totalMeetings) * 100) : 0;
                        
                        // Harmonious colors based on percentage
                        if ($percentage >= 80) {
                            $badgeClass = 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400';
                            $barClass = 'bg-success-500';
                        } elseif ($percentage >= 50) {
                            $badgeClass = 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400';
                            $barClass = 'bg-warning-500';
                        } else {
                            $badgeClass = 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400';
                            $barClass = 'bg-error-500';
                        }
                    @endphp
                    <tr class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition-colors">
                        <td class="px-5 py-4 text-center text-gray-400 dark:text-gray-600">
                            {{ ($students->currentPage() - 1) * $students->perPage() + $index + 1 }}
                        </td>
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-800 dark:text-white/90">
                                {{ $student->nama }}
                            </p>
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-400 font-mono">
                            {{ $student->nis }}
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-400">
                            {{ $student->kelas->nama_kelas ?? '-' }}
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex rounded-full px-3 py-1 font-semibold text-xs {{ $badgeClass }}">
                                {{ $attendanceCount }} kali hadir
                            </span>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $barClass }} transition-all duration-500" style="width: {{ $percentage }}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300 w-12 text-right">
                                    {{ $attendanceCount }} / {{ $totalMeetings }}
                                    <span class="text-gray-400 block text-[10px] mt-0.5">({{ $percentage }}%)</span>
                                </span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400 dark:text-gray-600">
                                <i class="fas fa-user-slash text-4xl"></i>
                                <p class="text-sm">Tidak ada data rekap ditemukan.</p>
                                <p class="text-xs">Pastikan filter tanggal atau pilihan kelas sesuai.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($students->hasPages())
        <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800">
            {{ $students->links() }}
        </div>
    @endif
</div>
@endsection
