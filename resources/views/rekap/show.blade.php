@extends('layouts.app')

@section('title', 'Detail Rekap Absensi')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Detail Rekap Absensi
    </h2>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('rekap.exportDetail', ['id' => $siswa->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="inline-flex items-center justify-center gap-2.5 rounded-lg bg-success-500 px-4 py-2 text-center font-medium text-white hover:bg-success-600 transition">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <a href="{{ route('rekap.index', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="inline-flex items-center justify-center gap-2.5 rounded-lg bg-gray-500 px-4 py-2 text-center font-medium text-white hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>
</div>

<div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Informasi Siswa</h6>
    </div>
    <div class="p-5">
        <table class="w-full text-sm text-left">
            <tr>
                <th class="py-2 text-gray-600 dark:text-gray-400 font-medium" width="150">Nama</th>
                <td class="py-2 text-gray-800 dark:text-white/90">: {{ $siswa->nama }}</td>
            </tr>
            <tr>
                <th class="py-2 text-gray-600 dark:text-gray-400 font-medium">Kelas</th>
                <td class="py-2 text-gray-800 dark:text-white/90">: {{ $siswa->kelas->nama_kelas ?? '-' }}</td>
            </tr>
            <tr>
                <th class="py-2 text-gray-600 dark:text-gray-400 font-medium">NIS</th>
                <td class="py-2 text-gray-800 dark:text-white/90">: {{ $siswa->nis }}</td>
            </tr>
            <tr>
                <th class="py-2 text-gray-600 dark:text-gray-400 font-medium">Periode</th>
                <td class="py-2 text-gray-800 dark:text-white/90">: {{ \Carbon\Carbon::parse($startDate)->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d-m-Y') }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Riwayat Kehadiran</h6>
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
                @forelse($attendance as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                    <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 xl:pl-6 text-gray-500 dark:text-gray-400">
                        {{ \Carbon\Carbon::parse($row->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                    </td>
                    <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 text-gray-500 dark:text-gray-400">
                        {{ $row->jam_masuk ?? '-' }}
                    </td>
                    <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 text-gray-500 dark:text-gray-400">
                        {{ $row->jam_pulang ?? '-' }}
                    </td>
                    <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 text-center">
                        @if($row->status == 'H') <span class="inline-flex rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">Hadir</span>
                        @elseif($row->status == 'I') <span class="inline-flex rounded-full bg-info/10 px-2.5 py-1 text-xs font-medium text-info">Izin</span>
                        @elseif($row->status == 'S') <span class="inline-flex rounded-full bg-warning/10 px-2.5 py-1 text-xs font-medium text-warning">Sakit</span>
                        @elseif($row->status == 'T') <span class="inline-flex rounded-full bg-warning/10 px-2.5 py-1 text-xs font-medium text-warning">Terlambat</span>
                        @elseif($row->status == 'B') <span class="inline-flex rounded-full bg-danger/10 px-2.5 py-1 text-xs font-medium text-danger">Bolos</span>
                        @else <span class="inline-flex rounded-full bg-danger/10 px-2.5 py-1 text-xs font-medium text-danger">Alpha</span>
                        @endif
                    </td>
                    <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 text-gray-500 dark:text-gray-400">
                        {{ $row->keterangan }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="border-b border-gray-100 px-4 py-8 dark:border-gray-800 text-center text-gray-500 dark:text-gray-400">
                        Tidak ada riwayat kehadiran
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
