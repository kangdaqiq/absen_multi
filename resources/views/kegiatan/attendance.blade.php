@extends('layouts.app')

@section('title', 'Rekap Absensi Kegiatan: ' . $kegiatan->nama_kegiatan)

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <a href="{{ route('kegiatan.index') }}" class="mb-1 inline-flex items-center gap-1 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition">
            <i class="fas fa-arrow-left text-xs"></i> Kembali ke Kegiatan
        </a>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            🎯 {{ $kegiatan->nama_kegiatan }}
        </h2>
        @if($kegiatan->deskripsi)
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $kegiatan->deskripsi }}</p>
        @endif
    </div>
        <span class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300">
            <i class="fas fa-calendar text-brand-500"></i>
            Mulai: {{ \Carbon\Carbon::parse($kegiatan->tanggal_mulai)->format('d/m/Y') }}
            <span class="ml-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                {{ $kegiatan->formatted_hari }}
            </span>
        </span>
</div>

{{-- Filter Tanggal --}}
<div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Filter Tanggal</h6>
    </div>
    <div class="p-5">
        <form action="{{ route('kegiatan.attendance', $kegiatan->id) }}" method="GET"
              class="flex flex-col sm:flex-row gap-4 items-end">
            <div class="w-full sm:w-auto">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                <input type="date" name="tanggal" value="{{ $tanggal }}"
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>
            @if($availableDates->isNotEmpty())
            <div class="w-full sm:w-auto min-w-[200px]">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Atau pilih tanggal tercatat</label>
                <select name="tanggal" onchange="this.form.submit()"
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="">-- Pilih tanggal --</option>
                    @foreach($availableDates as $d)
                        <option value="{{ $d }}" {{ $tanggal == $d ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($d)->translatedFormat('l, d F Y') }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600 transition">
                    <i class="fas fa-filter"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Summary Card --}}
<div class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="rounded-2xl border border-success-200 bg-success-50 p-5 dark:border-success-500/30 dark:bg-success-500/10">
        <p class="text-sm text-success-600 dark:text-success-400 font-medium mb-1">Total Hadir (Hari Ini)</p>
        <p class="text-3xl font-bold text-success-700 dark:text-success-300">{{ $records->count() }}</p>
    </div>
    <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5 dark:border-gray-700 dark:bg-gray-800/50">
        <p class="text-sm text-gray-600 dark:text-gray-400 font-medium mb-1">Tanggal</p>
        <p class="text-lg font-bold text-gray-700 dark:text-gray-300">
            {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('l, d F Y') }}
        </p>
    </div>
    <div class="rounded-2xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-500/30 dark:bg-brand-500/10">
        <p class="text-sm text-brand-600 dark:text-brand-400 font-medium mb-1">Total Pertemuan</p>
        <p class="text-3xl font-bold text-brand-700 dark:text-brand-300">{{ $availableDates->count() }}</p>
    </div>
</div>

{{-- Tabel Rekap --}}
<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex justify-between items-center">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">
            Data Kehadiran — {{ \Carbon\Carbon::parse($tanggal)->format('d/m/Y') }}
        </h6>
        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $records->count() }} siswa hadir</span>
    </div>

    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 font-medium">
                    <th class="px-5 py-4 text-left w-10">#</th>
                    <th class="px-5 py-4 text-left">Nama Siswa</th>
                    <th class="px-5 py-4 text-left">Kelas</th>
                    <th class="px-5 py-4 text-center">Jam Masuk</th>
                    <th class="px-5 py-4 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $i => $record)
                    <tr class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-5 py-4 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-5 py-4">
                            <p class="font-medium text-gray-800 dark:text-white/90">
                                {{ $record->student->nama ?? '-' }}
                            </p>
                        </td>
                        <td class="px-5 py-4 text-gray-500 dark:text-gray-400">
                            {{ $record->student->kelas->nama_kelas ?? '-' }}
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if($record->jam_masuk)
                                <span class="inline-block rounded px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 font-mono text-xs">
                                    {{ \Carbon\Carbon::parse($record->jam_masuk)->format('H:i') }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center">
                            <span class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-400">
                                Hadir
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-400 dark:text-gray-600">
                                <i class="fas fa-user-slash text-4xl"></i>
                                <p class="text-sm">Tidak ada data kehadiran pada tanggal ini.</p>
                                <p class="text-xs">Pastikan sesi scan telah diaktifkan dan siswa sudah melakukan scan.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
