@extends('layouts.app')

@section('title', 'Dashboard Siswa')

@section('content')
<div class="space-y-6">

    @if(!$linked)
        <!-- Alert Akun Belum Ditautkan -->
        <div class="rounded-2xl border border-warning/20 bg-warning/5 p-5 text-warning dark:border-warning/30 dark:bg-warning/10">
            <div class="flex items-start gap-4">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-warning/10 text-warning">
                    <i class="fas fa-exclamation-triangle fa-lg"></i>
                </div>
                <div>
                    <h5 class="text-base font-semibold text-gray-800 dark:text-white/90">Akun Siswa Belum Ditautkan</h5>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        Akun pengguna Anda (<strong>{{ auth()->user()->username }}</strong>) belum terhubung dengan data master siswa. Silakan hubungi Administrator atau Wali Kelas untuk menghubungkan data Anda.
                    </p>
                </div>
            </div>
        </div>
    @else

        <!-- Page Header -->
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
                    Dashboard Siswa
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Selamat datang, <span class="font-medium text-gray-800 dark:text-white/90">{{ $siswa->nama }}</span> (NIS: {{ $siswa->nis }} &bull; Kelas: {{ $siswa->kelas->nama_kelas ?? $siswa->kelas->nama ?? '-' }})
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('siswa.riwayat') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-center font-medium text-white hover:bg-brand-600 transition text-sm">
                    <i class="fas fa-calendar-alt"></i> Riwayat Kehadiran
                </a>
            </div>
        </div>

        <!-- Presensi Hari Ini Card -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h6 class="font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2">
                    <i class="fas fa-calendar-day text-brand-500"></i>
                    <span>Presensi Hari Ini: {{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</span>
                </h6>
                <div>
                    @if($todayAttendance)
                        @php
                            $statusHadir = $todayAttendance->status === 'H';
                            $isTerlambat = $statusHadir && (str_contains(strtolower($todayAttendance->keterangan ?? ''), 'telat') || str_contains(strtolower($todayAttendance->keterangan ?? ''), 'terlambat'));
                        @endphp
                        @if($isTerlambat)
                            <span class="inline-flex rounded-full bg-warning-50 px-3 py-1 text-xs font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">Terlambat</span>
                        @elseif($statusHadir)
                            <span class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">Hadir Tepat Waktu</span>
                        @elseif($todayAttendance->status === 'I')
                            <span class="inline-flex rounded-full bg-info-50 px-3 py-1 text-xs font-medium text-info-600 dark:bg-info-500/15 dark:text-info-500">Izin</span>
                        @elseif($todayAttendance->status === 'S')
                            <span class="inline-flex rounded-full bg-warning-50 px-3 py-1 text-xs font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">Sakit</span>
                        @else
                            <span class="inline-flex rounded-full bg-error-50 px-3 py-1 text-xs font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">Alpha</span>
                        @endif
                    @else
                        <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">Belum Presensi Hari Ini</span>
                    @endif
                </div>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/30 p-4">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Jam Masuk</span>
                        <h5 class="mt-1 font-bold text-gray-800 dark:text-white/90 font-mono text-base">
                            {{ $todayAttendance && $todayAttendance->jam_masuk ? substr($todayAttendance->jam_masuk, 0, 5) : '-' }}
                        </h5>
                    </div>
                    <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/30 p-4">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Jam Pulang</span>
                        <h5 class="mt-1 font-bold text-gray-800 dark:text-white/90 font-mono text-base">
                            {{ $todayAttendance && $todayAttendance->jam_pulang ? substr($todayAttendance->jam_pulang, 0, 5) : '-' }}
                        </h5>
                    </div>
                    <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/30 p-4">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Jadwal Sekolah</span>
                        <h5 class="mt-1 font-medium text-gray-800 dark:text-white/90 text-sm">
                            {{ $jadwalHariIni ? (substr($jadwalHariIni->jam_masuk, 0, 5) . ' - ' . substr($jadwalHariIni->jam_pulang, 0, 5)) : 'Tidak ada jadwal' }}
                        </h5>
                    </div>
                    <div class="rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/30 p-4">
                        <span class="text-xs text-gray-500 dark:text-gray-400">Keterangan</span>
                        <h5 class="mt-1 font-medium text-gray-800 dark:text-white/90 text-sm truncate">
                            {{ $todayAttendance->keterangan ?? '-' }}
                        </h5>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-5 2xl:gap-7.5">
            <!-- Hadir Card -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
                <div class="flex items-center justify-center w-12 h-12 bg-success-50 rounded-xl dark:bg-success-500/15 text-success-500">
                    <i class="fas fa-check-circle fa-lg"></i>
                </div>
                <div class="flex items-end justify-between mt-5">
                    <div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Total Hadir</span>
                        <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $statsAll['H'] }}</h4>
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
                        <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $statsAll['T'] }}</h4>
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
                        <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $statsAll['I'] }}</h4>
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
                        <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $statsAll['S'] }}</h4>
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
                        <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $statsAll['A'] }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Aktivitas Kehadiran Terakhir Table Card -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex items-center justify-between">
                <div>
                    <h6 class="font-semibold text-gray-800 dark:text-white/90">Aktivitas Presensi Terakhir</h6>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Catatan presensi terbaru Anda</p>
                </div>
                <a href="{{ route('siswa.riwayat') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-3.5 py-1.5 text-center font-medium text-white hover:bg-brand-600 transition text-xs">
                    <span>Lihat Seluruh Riwayat</span>
                    <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
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
                        @forelse($recentLogs->take(7) as $row)
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
                                    Belum ada data riwayat presensi terbaru
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 px-5 py-4 dark:border-gray-800 text-center">
                <a href="{{ route('siswa.riwayat') }}" class="text-xs font-semibold text-brand-500 hover:text-brand-600 transition inline-flex items-center gap-1.5">
                    <span>Buka Halaman Riwayat Kehadiran Lengkap (Bulanan)</span>
                    <i class="fas fa-arrow-right text-[10px]"></i>
                </a>
            </div>
        </div>

    @endif

</div>
@endsection