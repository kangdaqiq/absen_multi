@extends('layouts.app')

@section('title', 'Detail Jurnal KBM - ' . ($jurnal->jadwal?->mapel?->nama_mapel ?? 'Pembelajaran'))

@section('content')
<div x-data="{ selectedClass: 'all' }" class="space-y-6 w-full">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between no-print">
        <div>
            <h2 class="text-title-md2 font-bold text-gray-800 dark:text-white/90 flex items-center gap-2.5">
                <i class="fas fa-book-reader text-brand-500"></i>
                <span>Detail Jurnal Pembelajaran (KBM)</span>
            </h2>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-1">
                Rincian log mengajar, materi pembelajaran, dokumentasi berkas, dan daftar presensi siswa.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <button onclick="window.print()"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-xs font-semibold text-white hover:bg-brand-600 transition shadow-sm cursor-pointer">
                <i class="fas fa-print"></i> Cetak Jurnal
            </button>
            <a href="{{ route('absensi-pelajaran.jurnal-kbm', array_merge(request()->except('id'), ['tanggal' => $jurnal->tanggal ? $jurnal->tanggal->format('Y-m-d') : date('Y-m-d')])) }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-sm">
                <i class="fas fa-arrow-left"></i> Kembali ke Jurnal KBM
            </a>
        </div>
    </div>

    <!-- Print Only Header -->
    <div class="hidden print:block mb-6 text-center border-b pb-4">
        <h2 class="text-xl font-bold uppercase">Jurnal Pembelajaran (KBM)</h2>
        <p class="text-sm text-gray-600">
            {{ \Carbon\Carbon::parse($jurnal->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') }} &bull;
            Mapel: {{ $jurnal->jadwal?->mapel?->nama_mapel ?? '-' }} &bull;
            Kelas: {{ !empty($isMultiKelas) ? $namaKelasGabungan . ' (Kelas Gabungan)' : ($jurnal->jadwal?->kelas?->nama_kelas ?? '-') }}
        </p>
    </div>

    <!-- Multi-Class / Kelas Gabungan Banner (Jika Sesi Paralel) -->
    @if(!empty($isMultiKelas))
        <div class="rounded-2xl border border-brand-200 bg-gradient-to-r from-brand-50/70 to-indigo-50/50 p-4 sm:p-5 dark:border-brand-500/20 dark:bg-brand-500/10 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3 no-print">
            <div class="flex items-start sm:items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-white font-bold text-lg shadow-sm">
                    <i class="fas fa-layer-group text-white text-base"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h4 class="font-bold text-sm sm:text-base text-gray-900 dark:text-white">Sesi Pembelajaran Kelas Gabungan (Multi-Kelas)</h4>
                        <span class="inline-flex rounded-full bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300 px-2.5 py-0.5 text-[11px] font-bold border border-brand-200 dark:border-brand-500/30">
                            {{ count($involvedClasses) }} Kelas Paralel
                        </span>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-1">
                        Sesi mengajar ini menggabungkan siswa dari kelas: 
                        <strong class="text-brand-600 dark:text-brand-400">{{ $namaKelasGabungan }}</strong> 
                        secara bersamaan dalam satu jadwal KBM.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">Total Siswa Gabungan:</span>
                <span class="inline-flex items-center px-3 py-1 rounded-xl bg-white dark:bg-gray-800 font-bold text-brand-600 dark:text-brand-400 text-sm shadow-xs border border-brand-100 dark:border-gray-700">
                    {{ $counts['total'] }} Siswa
                </span>
            </div>
        </div>
    @endif

    <!-- 1. Informasi KBM & Guru Pengajar Card -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                <i class="fas fa-chalkboard-teacher text-xs"></i>
            </div>
            <h6 class="font-semibold text-gray-800 dark:text-white/90 text-sm">Informasi Pelaksanaan KBM</h6>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <table class="w-full text-sm text-left">
                    <tr>
                        <th class="py-2 text-gray-500 dark:text-gray-400 font-medium" width="140">Mata Pelajaran</th>
                        <td class="py-2 text-gray-800 dark:text-white/90 font-bold text-brand-600 dark:text-brand-400">
                            : {{ $jurnal->jadwal?->mapel?->nama_mapel ?? 'Mata Pelajaran' }}
                        </td>
                    </tr>
                    <tr>
                        <th class="py-2 text-gray-500 dark:text-gray-400 font-medium">Kelas</th>
                        <td class="py-2 text-gray-800 dark:text-white/90 font-semibold">
                            : @if(!empty($isMultiKelas))
                                <span class="text-brand-600 dark:text-brand-400">{{ $namaKelasGabungan }}</span>
                                <span class="inline-flex rounded-md bg-brand-50 px-2 py-0.5 text-[10px] font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300 ml-1">
                                    Gabungan
                                </span>
                            @else
                                {{ $jurnal->jadwal?->kelas?->nama_kelas ?? '-' }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="py-2 text-gray-500 dark:text-gray-400 font-medium">Guru Pengajar</th>
                        <td class="py-2 text-gray-800 dark:text-white/90 font-medium">
                            : {{ $jurnal->guru?->nama ?: ($jurnal->jadwal?->guru?->nama ?? '-') }}
                            @if(!empty($jurnal->guru?->nip ?: $jurnal->jadwal?->guru?->nip))
                                <span class="text-xs text-gray-400 font-mono">(NIP: {{ $jurnal->guru?->nip ?: $jurnal->jadwal?->guru?->nip }})</span>
                            @endif
                        </td>
                    </tr>
                </table>
                <table class="w-full text-sm text-left">
                    <tr>
                        <th class="py-2 text-gray-500 dark:text-gray-400 font-medium" width="140">Hari, Tanggal</th>
                        <td class="py-2 text-gray-800 dark:text-white/90 font-medium">
                            : {{ \Carbon\Carbon::parse($jurnal->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') }}
                        </td>
                    </tr>
                    <tr>
                        <th class="py-2 text-gray-500 dark:text-gray-400 font-medium">Jam Pelajaran</th>
                        <td class="py-2 text-gray-800 dark:text-white/90 font-medium">
                            : Jam Ke-{{ $jurnal->jam_ke ?: ($jurnal->jadwal?->jam_ke ?: '-') }}
                            @if($jurnal->jam_mulai || $jurnal->jam_selesai)
                                <span class="text-xs text-gray-500">({{ substr($jurnal->jam_mulai, 0, 5) }} - {{ substr($jurnal->jam_selesai, 0, 5) }})</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="py-2 text-gray-500 dark:text-gray-400 font-medium">Waktu Pengisian</th>
                        <td class="py-2 text-gray-800 dark:text-white/90">
                            : {{ $jurnal->created_at ? $jurnal->created_at->format('d/m/Y H:i') : '-' }} WIB
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- 2. Jurnal & Materi Pembelajaran Card -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                <i class="fas fa-book-open text-xs"></i>
            </div>
            <h6 class="font-semibold text-gray-800 dark:text-white/90 text-sm">Jurnal & Materi Pembelajaran</h6>
        </div>
        <div class="p-5 space-y-4">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 block mb-1">Materi Pembelajaran yang Disampaikan:</span>
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800 text-sm text-gray-800 dark:text-gray-200 leading-relaxed font-medium">
                    {{ $jurnal->materi ?: 'Belum ada catatan materi pembelajaran yang diinput.' }}
                </div>
            </div>

            @if(!empty($jurnal->catatan))
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 block mb-1">Catatan Tambahan / Evaluasi KBM:</span>
                <div class="p-4 rounded-xl bg-amber-50/50 dark:bg-amber-500/10 border border-amber-200/60 dark:border-amber-500/20 text-xs sm:text-sm text-amber-900 dark:text-amber-200 leading-relaxed">
                    <i class="fas fa-sticky-note text-amber-500 mr-1.5"></i>
                    {{ $jurnal->catatan }}
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- 3. Dokumentasi Foto / Video KBM Card (Jika Ada) -->
    @if(!empty($docs) && count($docs) > 0)
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark no-print">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400">
                    <i class="fas fa-images text-xs"></i>
                </div>
                <h6 class="font-semibold text-gray-800 dark:text-white/90 text-sm">Dokumentasi Pembelajaran</h6>
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ count($docs) }} berkas terlampir</span>
        </div>
        <div class="p-5">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3.5">
                @foreach($docs as $doc)
                    <div class="group relative rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden bg-gray-50 dark:bg-gray-900 aspect-video flex items-center justify-center">
                        @if(!empty($doc['is_video']))
                            <video src="{{ $doc['url'] }}" controls class="w-full h-full object-cover"></video>
                        @else
                            <a href="{{ $doc['url'] }}" target="_blank" class="block w-full h-full cursor-zoom-in">
                                <img src="{{ $doc['thumbnail_url'] ?: $doc['url'] }}" alt="Dokumentasi KBM" class="w-full h-full object-cover transition duration-300 group-hover:scale-105">
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- 4. Ringkasan & Daftar Presensi Kehadiran Siswa -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex flex-col gap-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400">
                        <i class="fas fa-user-check text-xs"></i>
                    </div>
                    <div>
                        <h6 class="font-semibold text-gray-800 dark:text-white/90 text-sm">Presensi Kehadiran Siswa pada Sesi Ini</h6>
                        <span class="text-xs text-gray-400">Total {{ $counts['total'] }} siswa @if(!empty($isMultiKelas)) (dari {{ count($involvedClasses) }} kelas gabungan) @endif</span>
                    </div>
                </div>

                <!-- Quick Metrics Badge -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400 font-semibold border border-success-200/60">
                        <i class="fas fa-check text-[10px]"></i> Hadir: {{ $counts['H'] }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400 font-semibold border border-warning-200/60">
                        <i class="fas fa-clock text-[10px]"></i> Telat: {{ $counts['T'] }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400 font-semibold border border-warning-200/60">
                        <i class="fas fa-notes-medical text-[10px]"></i> Sakit: {{ $counts['S'] }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 font-semibold border border-blue-200/60">
                        <i class="fas fa-envelope-open-text text-[10px]"></i> Izin: {{ $counts['I'] }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 dark:bg-purple-500/15 dark:text-purple-400 font-semibold border border-purple-200/60">
                        <i class="fas fa-award text-[10px]"></i> Dispen: {{ $counts['D'] }}
                    </span>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400 font-semibold border border-error-200/60">
                        <i class="fas fa-times text-[10px]"></i> Alpha: {{ $counts['A'] }}
                    </span>
                </div>
            </div>

            <!-- Tab Filter Kelas (Jika Kelas Gabungan / Multi-Kelas) -->
            @if(!empty($isMultiKelas))
                <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-gray-100 dark:border-gray-800 no-print">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 mr-1">Filter Tampilan Kelas:</span>
                    <button type="button" @click="selectedClass = 'all'"
                        :class="selectedClass === 'all' ? 'bg-brand-500 text-white shadow-xs font-bold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 font-medium'"
                        class="px-3 py-1 rounded-xl text-xs transition cursor-pointer">
                        Semua Kelas ({{ $counts['total'] }})
                    </button>
                    @foreach($involvedClasses as $cls)
                        @php
                            $cStats = $classesBreakdown[$cls->id] ?? null;
                            $cTotal = $cStats ? $cStats['counts']['total'] : 0;
                        @endphp
                        <button type="button" @click="selectedClass = '{{ $cls->id }}'"
                            :class="selectedClass === '{{ $cls->id }}' ? 'bg-brand-500 text-white shadow-xs font-bold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 font-medium'"
                            class="px-3 py-1 rounded-xl text-xs transition cursor-pointer">
                            {{ $cls->nama_kelas }} ({{ $cTotal }})
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Tabel Siswa -->
        <div class="max-w-full overflow-x-auto">
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-xs">
                        <th class="px-4 py-3 xl:pl-6 border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800 text-center w-12">No</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800 w-28">NIS</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800">Nama Siswa</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800 w-32">Kelas</th>
                        <th class="px-4 py-3 text-center border-b border-gray-200 dark:border-gray-800 border-r dark:border-gray-800 w-32">Status Kehadiran</th>
                        <th class="px-4 py-3 border-b border-gray-200 dark:border-gray-800">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="text-xs">
                    @forelse($presensiList as $idx => $st)
                        @php
                            $badgeColor = 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700';
                            if ($st['status'] === 'H') $badgeColor = 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400 border border-success-200/60';
                            elseif ($st['status'] === 'T') $badgeColor = 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400 border border-warning-200/60';
                            elseif ($st['status'] === 'S') $badgeColor = 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400 border border-warning-200/60';
                            elseif ($st['status'] === 'I') $badgeColor = 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-400 border border-blue-200/60';
                            elseif ($st['status'] === 'D') $badgeColor = 'bg-purple-50 text-purple-700 dark:bg-purple-500/15 dark:text-purple-400 border border-purple-200/60';
                            elseif ($st['status'] === 'A') $badgeColor = 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400 border border-error-200/60';
                        @endphp
                        <tr x-show="selectedClass === 'all' || selectedClass === '{{ $st['kelas_id'] }}'"
                            class="hover:bg-gray-50/75 dark:hover:bg-gray-800/40 transition-colors border-b border-gray-100 dark:border-gray-800 last:border-b-0">
                            <td class="px-4 py-2.5 xl:pl-6 text-center border-r border-gray-100 dark:border-gray-800 text-gray-500">
                                {{ $idx + 1 }}
                            </td>
                            <td class="px-4 py-2.5 border-r border-gray-100 dark:border-gray-800 font-mono text-gray-500">
                                {{ $st['nis'] }}
                            </td>
                            <td class="px-4 py-2.5 border-r border-gray-100 dark:border-gray-800 font-medium text-gray-800 dark:text-white/90">
                                {{ $st['nama'] }}
                            </td>
                            <td class="px-4 py-2.5 border-r border-gray-100 dark:border-gray-800">
                                <span class="inline-flex rounded-lg bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-400">
                                    {{ $st['kelas'] }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-center border-r border-gray-100 dark:border-gray-800">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $badgeColor }}">
                                    {{ $st['status_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400">
                                {{ $st['keterangan'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                Belum ada data presensi siswa yang dicatat pada sesi KBM ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
