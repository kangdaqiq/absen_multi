@extends('layouts.app')

@section('title', 'Detail Rekap Absensi Mapel - ' . $siswa->nama)

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
                Detail Rekap Absensi Pembelajaran
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Rincian riwayat kehadiran per mata pelajaran untuk siswa {{ $siswa->nama }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('absensi-pelajaran.rekap.export-detail', array_merge(request()->all(), ['id' => $siswa->id])) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-success-500 px-4 py-2.5 text-center text-xs font-semibold text-white hover:bg-success-600 transition shadow-sm"
                style="background-color: #12b76a;">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            <a href="{{ route('absensi-pelajaran.rekap', request()->except('id')) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-center text-xs font-semibold text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-sm">
                <i class="fas fa-arrow-left"></i> Kembali ke Rekap
            </a>
        </div>
    </div>

    <!-- Informasi Siswa Card -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                <i class="fas fa-id-card text-xs"></i>
            </div>
            <h6 class="font-semibold text-gray-800 dark:text-white/90 text-sm">Informasi Siswa</h6>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <table class="w-full text-sm text-left">
                    <tr>
                        <th class="py-2 text-gray-500 dark:text-gray-400 font-medium" width="140">Nama Siswa</th>
                        <td class="py-2 text-gray-800 dark:text-white/90 font-semibold">: {{ $siswa->nama }}</td>
                    </tr>
                    <tr>
                        <th class="py-2 text-gray-500 dark:text-gray-400 font-medium">Kelas</th>
                        <td class="py-2 text-gray-800 dark:text-white/90">: {{ $siswa->kelas->nama_kelas ?? '-' }}</td>
                    </tr>
                    <tr>
                        <th class="py-2 text-gray-500 dark:text-gray-400 font-medium">NIS</th>
                        <td class="py-2 text-gray-800 dark:text-white/90">: {{ $siswa->nis ?: '-' }}</td>
                    </tr>
                </table>
                <table class="w-full text-sm text-left">
                    <tr>
                        <th class="py-2 text-gray-500 dark:text-gray-400 font-medium" width="140">Periode Rekap</th>
                        <td class="py-2 text-gray-800 dark:text-white/90">:
                            {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                        </td>
                    </tr>
                    <tr>
                        <th class="py-2 text-gray-500 dark:text-gray-400 font-medium">Mata Pelajaran</th>
                        <td class="py-2 text-gray-800 dark:text-white/90 font-medium text-brand-600 dark:text-brand-400">
                            : {{ $selectedMapel ? $selectedMapel->nama_mapel : 'Semua Mata Pelajaran' }}
                        </td>
                    </tr>
                    <tr>
                        <th class="py-2 text-gray-500 dark:text-gray-400 font-medium">Persentase Hadir</th>
                        <td class="py-2">
                            : <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold {{ $counts['persentase'] >= 85 ? 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400' : ($counts['persentase'] >= 70 ? 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400' : 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400') }}">
                                {{ $counts['persentase'] }}%
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Ringkasan Akumulasi Angka Kehadiran -->
    <div class="mb-6 grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <div class="rounded-2xl border border-gray-200 bg-white p-3.5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark text-center">
            <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium block">Hadir (H)</span>
            <span class="text-xl font-bold text-success-600 dark:text-success-400 mt-1 block">{{ $counts['H'] }}</span>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-3.5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark text-center">
            <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium block">Telat (T)</span>
            <span class="text-xl font-bold text-warning-600 dark:text-warning-400 mt-1 block">{{ $counts['T'] }}</span>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-3.5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark text-center">
            <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium block">Sakit (S)</span>
            <span class="text-xl font-bold text-warning-600 dark:text-warning-400 mt-1 block">{{ $counts['S'] }}</span>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-3.5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark text-center">
            <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium block">Izin (I)</span>
            <span class="text-xl font-bold text-blue-600 dark:text-blue-400 mt-1 block">{{ $counts['I'] }}</span>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-3.5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark text-center">
            <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium block">Dispen (D)</span>
            <span class="text-xl font-bold text-purple-600 dark:text-purple-400 mt-1 block">{{ $counts['D'] }}</span>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-3.5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark text-center">
            <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium block">Alpha (A)</span>
            <span class="text-xl font-bold text-error-600 dark:text-error-400 mt-1 block">{{ $counts['A'] }}</span>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-3.5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark text-center">
            <span class="text-[11px] text-gray-500 dark:text-gray-400 font-medium block">Total Sesi</span>
            <span class="text-xl font-bold text-gray-800 dark:text-white mt-1 block">{{ $counts['total'] }}</span>
        </div>
    </div>

    <!-- Riwayat Kehadiran Table Card -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex justify-between items-center">
            <h6 class="font-semibold text-gray-800 dark:text-white/90">Riwayat Kehadiran Pembelajaran</h6>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ count($attendance) }} pertemuan tercatat</span>
        </div>
        <div class="max-w-full overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-xs">
                        <th class="px-4 py-3.5 xl:pl-6 border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800">Tanggal</th>
                        <th class="px-3 py-3.5 text-center border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800 min-w-[70px]">Jam Ke</th>
                        <th class="px-4 py-3.5 border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800">Mata Pelajaran</th>
                        <th class="px-4 py-3.5 border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800">Guru Pengampu</th>
                        <th class="px-3 py-3.5 text-center border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800 min-w-[90px]">Status</th>
                        <th class="px-4 py-3.5 border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800">Materi KBM</th>
                        <th class="px-4 py-3.5 border-b border-gray-200 dark:border-gray-800">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="text-xs">
                    @forelse($attendance as $row)
                        @php
                            $badgeClass = 'bg-gray-100 text-gray-700';
                            if ($row->status === 'H') $badgeClass = 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400 border border-success-200/60';
                            elseif ($row->status === 'T') $badgeClass = 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400 border border-warning-200/60';
                            elseif ($row->status === 'S') $badgeClass = 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400 border border-warning-200/60';
                            elseif ($row->status === 'I') $badgeClass = 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 border border-blue-200/60';
                            elseif ($row->status === 'D') $badgeClass = 'bg-purple-50 text-purple-700 dark:bg-purple-500/15 dark:text-purple-400 border border-purple-200/60';
                            elseif ($row->status === 'A') $badgeClass = 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400 border border-error-200/60';
                        @endphp
                        <tr class="hover:bg-gray-50/75 dark:hover:bg-gray-800/40 transition-colors border-b border-gray-100 dark:border-gray-800 last:border-b-0">
                            <!-- Tanggal -->
                            <td class="px-4 py-3 xl:pl-6 border-r border-gray-100 dark:border-gray-800 align-middle whitespace-nowrap">
                                <span class="font-semibold text-gray-800 dark:text-white/90 block">
                                    {{ \Carbon\Carbon::parse($row->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                                </span>
                            </td>

                            <!-- Jam Ke -->
                            <td class="px-3 py-3 text-center border-r border-gray-100 dark:border-gray-800 align-middle whitespace-nowrap font-medium text-gray-600 dark:text-gray-400">
                                {{ $row->jadwal?->jam_ke ?: ($row->jurnal?->jam_ke ?: '-') }}
                            </td>

                            <!-- Mata Pelajaran -->
                            <td class="px-4 py-3 border-r border-gray-100 dark:border-gray-800 align-middle font-medium text-gray-800 dark:text-white/90">
                                {{ $row->jadwal?->mapel?->nama_mapel ?? 'Mata Pelajaran' }}
                            </td>

                            <!-- Guru Pengampu -->
                            <td class="px-4 py-3 border-r border-gray-100 dark:border-gray-800 align-middle text-gray-600 dark:text-gray-400">
                                {{ $row->jadwal?->guru?->nama ?? '-' }}
                            </td>

                            <!-- Status -->
                            <td class="px-3 py-3 text-center border-r border-gray-100 dark:border-gray-800 align-middle whitespace-nowrap">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $badgeClass }}">
                                    {{ $row->status_label ?: $row->status }}
                                </span>
                            </td>

                            <!-- Materi KBM -->
                            <td class="px-4 py-3 border-r border-gray-100 dark:border-gray-800 align-middle max-w-xs text-gray-700 dark:text-gray-300">
                                {{ $row->jurnal?->materi ?: '-' }}
                            </td>

                            <!-- Keterangan -->
                            <td class="px-4 py-3 align-middle text-gray-500 dark:text-gray-400">
                                {{ $row->keterangan ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="fas fa-clipboard-list text-3xl text-gray-300 dark:text-gray-700"></i>
                                    <p class="text-sm">Tidak ada riwayat kehadiran mata pelajaran pada periode ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
