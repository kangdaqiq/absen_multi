@extends('layouts.app')

@section('title', 'Rekapitulasi Absensi Sholat')

@section('content')
<x-page-header 
    title="Rekapitulasi Absensi Sholat" 
    subtitle="Laporan dan rekap kehadiran sholat berjamaah siswa dan guru per periode, kelas, dan jenis sholat" 
    icon="fas fa-file-invoice">
    <div class="flex items-center gap-2">
        <a href="{{ route('absensi-sholat.index') }}" 
           class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition">
            <i class="fas fa-satellite-dish text-emerald-500"></i> Live Monitor Hari Ini
        </a>
        <a href="{{ route('absensi-sholat.export-excel', request()->all()) }}" 
           class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-emerald-700 transition">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <a href="{{ route('absensi-sholat.export-pdf', request()->all()) }}" target="_blank"
           class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-red-700 transition">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
    </div>
</x-page-header>

<!-- Filter Card -->
<div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                <i class="fas fa-filter text-xs"></i>
            </div>
            <div>
                <h6 class="font-semibold text-gray-800 dark:text-white/90 text-sm">Filter Periode, Kelas & Sholat</h6>
            </div>
        </div>
        @if(request()->hasAny(['start_date', 'end_date', 'kelas_id', 'sholat', 'person_type', 'search']))
            <a href="{{ route('absensi-sholat.rekap') }}" 
               class="inline-flex items-center gap-1.5 text-xs text-error-600 hover:text-error-700 dark:text-error-400 font-medium hover:underline">
                <i class="fas fa-times"></i> Reset Filter
            </a>
        @endif
    </div>

    <div class="p-5">
        <form action="{{ route('absensi-sholat.rekap') }}" method="GET" class="flex flex-col flex-wrap gap-4 lg:flex-row items-end">
            <div class="w-full lg:w-40">
                <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Dari Tanggal:</label>
                <input type="date" name="start_date" value="{{ $startDate }}"
                    class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>

            <div class="w-full lg:w-40">
                <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Sampai Tanggal:</label>
                <input type="date" name="end_date" value="{{ $endDate }}"
                    class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>

            <div class="w-full lg:w-48">
                <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Kelas:</label>
                <select name="kelas_id" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="all">Semua Kelas</option>
                    @foreach($kelases as $k)
                        <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-full lg:w-36">
                <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Sholat:</label>
                <select name="sholat" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="all">Semua Sholat</option>
                    <option value="dhuha" {{ request('sholat') === 'dhuha' ? 'selected' : '' }}>Dhuha</option>
                    <option value="dzuhur" {{ request('sholat') === 'dzuhur' ? 'selected' : '' }}>Dzuhur</option>
                    <option value="jumat" {{ request('sholat') === 'jumat' ? 'selected' : '' }}>Jumat</option>
                    <option value="ashar" {{ request('sholat') === 'ashar' ? 'selected' : '' }}>Ashar</option>
                    <option value="subuh" {{ request('sholat') === 'subuh' ? 'selected' : '' }}>Subuh</option>
                    <option value="maghrib" {{ request('sholat') === 'maghrib' ? 'selected' : '' }}>Maghrib</option>
                    <option value="isya" {{ request('sholat') === 'isya' ? 'selected' : '' }}>Isya</option>
                </select>
            </div>

            <div class="w-full lg:w-36">
                <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Peran:</label>
                <select name="person_type" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="all">Semua Peran</option>
                    <option value="siswa" {{ request('person_type') === 'siswa' ? 'selected' : '' }}>Siswa</option>
                    <option value="guru" {{ request('person_type') === 'guru' ? 'selected' : '' }}>Guru / Pegawai</option>
                </select>
            </div>

            <div class="w-full lg:flex-1">
                <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Cari Nama / NIS:</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama atau NIS..."
                    class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>

            <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-brand-500 px-5 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition">
                <i class="fas fa-search"></i> Tampilkan
            </button>
        </form>
    </div>
</div>

<!-- Tabel Rekap Data -->
<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="border-b border-gray-200 bg-gray-50/70 font-semibold uppercase tracking-wider text-gray-500 dark:border-gray-800 dark:bg-gray-900/50 dark:text-gray-400">
                <tr>
                    <th class="py-3.5 px-4 text-center" width="5%">No</th>
                    <th class="py-3.5 px-4">Tanggal</th>
                    <th class="py-3.5 px-4">Nama</th>
                    <th class="py-3.5 px-4">Peran</th>
                    <th class="py-3.5 px-4">Kelas / NIP</th>
                    <th class="py-3.5 px-4">Sholat</th>
                    <th class="py-3.5 px-4">Jam Tap</th>
                    <th class="py-3.5 px-4">Jadwal Hisab</th>
                    <th class="py-3.5 px-4 text-center">Status</th>
                    <th class="py-3.5 px-4">Keterangan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-800 dark:text-gray-200">
                @forelse($attendances as $row)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/40 transition">
                        <td class="py-3.5 px-4 text-center font-medium text-gray-400">
                            {{ $loop->iteration + ($attendances->currentPage() - 1) * $attendances->perPage() }}
                        </td>
                        <td class="py-3.5 px-4 font-mono">
                            {{ $row->tanggal->format('d/m/Y') }}
                        </td>
                        <td class="py-3.5 px-4 font-semibold text-gray-900 dark:text-white">
                            {{ $row->person_name }}
                        </td>
                        <td class="py-3.5 px-4">
                            @if($row->person_type === 'guru')
                                <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2.5 py-0.5 text-[11px] font-semibold text-purple-700 dark:bg-purple-500/15 dark:text-purple-300">
                                    <i class="fas fa-chalkboard-teacher"></i> Guru
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">
                                    <i class="fas fa-user-graduate"></i> Siswa
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-gray-600 dark:text-gray-400">
                            @if($row->person_type === 'guru')
                                {{ $row->guru?->nip ?: '-' }}
                            @else
                                {{ $row->student?->kelas?->nama_kelas ?: '-' }}
                            @endif
                        </td>
                        <td class="py-3.5 px-4 font-semibold">
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300">
                                <i class="fas fa-pray text-emerald-600"></i> {{ $row->sholat_label }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 font-mono font-bold text-gray-900 dark:text-white">
                            {{ substr($row->jam_absen, 0, 5) }}
                        </td>
                        <td class="py-3.5 px-4 font-mono text-gray-500">
                            {{ $row->waktu_sholat ? substr($row->waktu_sholat, 0, 5) : '-' }}
                        </td>
                        <td class="py-3.5 px-4 text-center">
                            @if($row->status === 'hadir' || $row->status === 'jamaah')
                                <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-[11px] font-bold text-success-700 dark:bg-success-500/15 dark:text-success-400">
                                    <i class="fas fa-check"></i> Tepat Waktu
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-0.5 text-[11px] font-bold text-warning-700 dark:bg-warning-500/15 dark:text-warning-400">
                                    <i class="fas fa-clock"></i> Masbuk/Telat
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-gray-500 text-[11px]">
                            {{ $row->keterangan ?: '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="py-12 text-center text-gray-400 dark:text-gray-500">
                            <i class="fas fa-file-excel text-4xl mb-3 opacity-30"></i>
                            <p class="text-sm">Tidak ada catatan kehadiran sholat untuk filter yang dipilih.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($attendances->hasPages())
        <div class="border-t border-gray-200 p-4 dark:border-gray-800">
            {{ $attendances->links() }}
        </div>
    @endif
</div>
@endsection

