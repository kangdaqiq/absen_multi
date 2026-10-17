@extends('layouts.app')

@section('title', 'Rekap Absensi Pembelajaran (KBM)')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" x-data="{}">
        <div>
            <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
                Rekap Absensi Pembelajaran
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Laporan rekapitulasi presensi siswa dan materi KBM per mata pelajaran
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('absensi-pelajaran.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-center font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-sm text-sm">
                <i class="fas fa-clipboard-check text-brand-500"></i> Absensi Hari Ini
            </a>
            <button onclick="window.print()"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-center font-medium text-white hover:bg-brand-600 transition shadow-sm text-sm">
                <i class="fas fa-print"></i> Cetak Laporan
            </button>
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
                    <h6 class="font-semibold text-gray-800 dark:text-white/90 text-sm">Filter Rekap Pembelajaran</h6>
                </div>
            </div>
            @if(request()->hasAny(['start_date', 'end_date', 'kelas_id', 'mapel_id']))
                <a href="{{ route('absensi-pelajaran.rekap') }}" 
                   class="inline-flex items-center gap-1.5 text-xs text-error-600 hover:text-error-700 dark:text-error-400 font-medium hover:underline">
                    <i class="fas fa-times"></i> Reset Filter
                </a>
            @endif
        </div>
        <div class="p-5">
            <form action="{{ route('absensi-pelajaran.rekap') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <!-- Dari Tanggal -->
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate }}"
                        class="w-full rounded-xl border border-gray-200 bg-transparent px-3.5 py-2.5 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white {{ request('start_date') ? 'border-brand-500 bg-brand-50/20 font-semibold text-brand-600' : '' }}">
                </div>

                <!-- Sampai Tanggal -->
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}"
                        class="w-full rounded-xl border border-gray-200 bg-transparent px-3.5 py-2.5 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white {{ request('end_date') ? 'border-brand-500 bg-brand-50/20 font-semibold text-brand-600' : '' }}">
                </div>

                <!-- Filter Kelas -->
                <div>
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
                <div>
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

                <!-- Button Submit Filter -->
                <div class="flex items-end">
                    <button type="submit"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-xs font-medium text-white hover:bg-brand-600 transition shadow-sm cursor-pointer">
                        <i class="fas fa-filter"></i> Terapkan Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Metrics Ribbon -->
    <div class="mb-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
        <div class="rounded-xl border border-gray-200 bg-white p-3.5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark text-center">
            <span class="text-xs text-gray-500 dark:text-gray-400 font-medium block">Total Catatan</span>
            <span class="text-xl font-bold text-gray-800 dark:text-white mt-1 block">{{ $summary->total_records ?? 0 }}</span>
        </div>
        <div class="rounded-xl border border-success-200 bg-success-50/50 p-3.5 shadow-theme-xs dark:border-success-500/20 dark:bg-success-500/5 text-center">
            <span class="text-xs text-success-700 dark:text-success-400 font-bold block">Hadir (H)</span>
            <span class="text-xl font-bold text-success-700 dark:text-success-400 mt-1 block">{{ $summary->count_h ?? 0 }}</span>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-3.5 shadow-theme-xs dark:border-blue-500/20 dark:bg-blue-500/5 text-center">
            <span class="text-xs text-blue-700 dark:text-blue-400 font-bold block">Izin (I)</span>
            <span class="text-xl font-bold text-blue-700 dark:text-blue-400 mt-1 block">{{ $summary->count_i ?? 0 }}</span>
        </div>
        <div class="rounded-xl border border-warning-200 bg-warning-50/50 p-3.5 shadow-theme-xs dark:border-warning-500/20 dark:bg-warning-500/5 text-center">
            <span class="text-xs text-warning-700 dark:text-warning-400 font-bold block">Sakit (S)</span>
            <span class="text-xl font-bold text-warning-700 dark:text-warning-400 mt-1 block">{{ $summary->count_s ?? 0 }}</span>
        </div>
        <div class="rounded-xl border border-error-200 bg-error-50/50 p-3.5 shadow-theme-xs dark:border-error-500/20 dark:bg-error-500/5 text-center">
            <span class="text-xs text-error-700 dark:text-error-400 font-bold block">Alpa (A)</span>
            <span class="text-xl font-bold text-error-700 dark:text-error-400 mt-1 block">{{ $summary->count_a ?? 0 }}</span>
        </div>
    </div>

    <!-- Table Card -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="flex justify-between items-center px-5 py-4 border-b border-gray-200 dark:border-gray-800">
            <h4 class="font-bold text-gray-800 dark:text-white/90">Detail Presensi</h4>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                Periode: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            </span>
        </div>

        <div class="max-w-full overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-xs">
                        <th class="px-4 py-3.5 xl:pl-6 w-12 text-center">No</th>
                        <th class="px-4 py-3.5">Tanggal & Jam</th>
                        <th class="px-4 py-3.5">Siswa</th>
                        <th class="px-4 py-3.5">Kelas</th>
                        <th class="px-4 py-3.5">Mata Pelajaran</th>
                        <th class="px-4 py-3.5 text-center">Status</th>
                        <th class="px-4 py-3.5">Materi Pembelajaran</th>
                        <th class="px-4 py-3.5">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @forelse($records as $r)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                            <td class="border-b border-gray-100 px-4 py-3 dark:border-gray-800 xl:pl-6 text-center text-xs text-gray-400">
                                {{ $loop->iteration + $records->firstItem() - 1 }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-3 dark:border-gray-800 text-xs">
                                <span class="font-semibold text-gray-800 dark:text-white block">
                                    {{ \Carbon\Carbon::parse($r->tanggal)->format('d/m/Y') }}
                                </span>
                                <span class="text-gray-400">
                                    {{ $r->jadwal?->hari }} {{ $r->jadwal?->jam_ke ? '(Ke-' . $r->jadwal->jam_ke . ')' : '' }}
                                </span>
                            </td>
                            <td class="border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                                <span class="font-semibold text-gray-800 dark:text-white block">{{ $r->student?->nama ?? '-' }}</span>
                                <span class="text-xs text-gray-400">NIS: {{ $r->student?->nis ?? '-' }}</span>
                            </td>
                            <td class="border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                                <span class="inline-flex rounded-lg bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700 dark:bg-blue-500/15 dark:text-blue-400">
                                    {{ $r->student?->kelas?->nama_kelas ?? '-' }}
                                </span>
                            </td>
                            <td class="border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                                <span class="font-medium text-gray-800 dark:text-white block">{{ $r->jadwal?->mapel?->nama_mapel ?? '-' }}</span>
                                <span class="text-xs text-gray-400">{{ $r->guru?->nama ?? $r->jadwal?->guru?->nama ?? '-' }}</span>
                            </td>
                            <td class="border-b border-gray-100 px-4 py-3 dark:border-gray-800 text-center">
                                <span class="inline-flex rounded-md px-2.5 py-1 text-xs font-bold {{ $r->status_badge }}">
                                    {{ $r->status_label }}
                                </span>
                            </td>
                            <td class="border-b border-gray-100 px-4 py-3 dark:border-gray-800 text-xs text-gray-700 dark:text-gray-300 max-w-xs truncate">
                                {{ $r->jurnal?->materi ?: '-' }}
                            </td>
                            <td class="border-b border-gray-100 px-4 py-3 dark:border-gray-800 text-xs text-gray-500 dark:text-gray-400">
                                {{ $r->keterangan ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="border-b border-gray-100 px-4 py-12 dark:border-gray-800 text-center text-gray-500 dark:text-gray-400">
                                Tidak ada data presensi pembelajaran pada filter tanggal yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800 no-print">
            {{ $records->links('vendor.pagination.tailwind') }}
        </div>
    </div>
@endsection
