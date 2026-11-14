@extends('layouts.app')

@section('title', 'Rekapitulasi Pelanggaran Siswa')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <x-page-header
        title="Rekapitulasi Pelanggaran Siswa"
        subtitle="Daftar rekapitulasi kumulatif poin, rekam jejak pelanggaran siswa, dan status penanganan BK"
        icon="fas fa-file-invoice">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('pelanggaran.penanganan.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50/70 px-4 py-2.5 text-xs font-semibold text-indigo-700 shadow-theme-xs hover:bg-indigo-100 dark:border-indigo-900/40 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/50 transition">
                <i class="fas fa-user-shield text-indigo-600 dark:text-indigo-400"></i> Riwayat Penanganan BK
            </a>
            <a href="{{ route('pelanggaran.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition">
                <i class="fas fa-clipboard-list text-brand-500"></i> Catat Pelanggaran
            </a>
            <a href="{{ route('pelanggaran.export-excel', request()->all()) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-emerald-700 transition">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            <a href="{{ route('pelanggaran.export-pdf', request()->all()) }}" target="_blank"
               class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-red-700 transition">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
        </div>
    </x-page-header>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Siswa Terdata</p>
            <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($totalSiswa) }}</p>
            <span class="text-[11px] text-gray-400">Populasi siswa aktif</span>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Siswa Berpoin (Pelanggar)</p>
            <p class="mt-1 text-2xl font-bold text-rose-600 dark:text-rose-400">{{ number_format($totalSiswaPelanggar) }}</p>
            <span class="text-[11px] text-gray-400">Memiliki catatan pelanggaran</span>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Kasus Pelanggaran</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($totalKasus) }}</p>
            <span class="text-[11px] text-gray-400">Insiden tercatat</span>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Akumulasi Poin</p>
            <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($totalPoin) }}</p>
            <span class="text-[11px] text-gray-400">Total akumulasi sanksi</span>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Tindakan / Penanganan BK</p>
            <p class="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ number_format($totalPenanganan) }}</p>
            <span class="text-[11px] text-gray-400">SP 1 - 4 & Konseling</span>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400">
                    <i class="fas fa-filter text-xs"></i>
                </div>
                <h6 class="font-semibold text-gray-800 dark:text-white/90 text-sm">Filter Kriteria & Periode</h6>
            </div>
            @if(request()->hasAny(['filter_poin', 'kelas_id', 'start_date', 'end_date', 'search']))
                <a href="{{ route('pelanggaran.rekap') }}" class="text-xs text-error-600 hover:text-error-700 dark:text-error-400 font-medium hover:underline">
                    <i class="fas fa-times"></i> Reset Filter
                </a>
            @endif
        </div>

        <div class="p-5">
            <form action="{{ route('pelanggaran.rekap') }}" method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6 items-end">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Tampilkan Siswa:</label>
                    <select name="filter_poin" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="with_points" {{ request('filter_poin', 'with_points') === 'with_points' ? 'selected' : '' }}>Hanya Siswa Berpoin</option>
                        <option value="all" {{ request('filter_poin') === 'all' ? 'selected' : '' }}>Semua Siswa (Termasuk 0 Poin)</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Kelas:</label>
                    <select name="kelas_id" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="all">Semua Kelas</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Dari Tanggal (Opsional):</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                           class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Sampai Tanggal (Opsional):</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                           class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Cari Siswa:</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / NIS siswa..."
                           class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>

                <div>
                    <button type="submit" class="w-full rounded-xl bg-brand-500 px-4 py-2 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition">
                        <i class="fas fa-search mr-1"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Top Pelanggaran Agregat Siswa (Jika ada) -->
    @if($topSiswa->isNotEmpty())
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-dark shadow-theme-sm">
            <h6 class="font-semibold text-gray-800 dark:text-white/90 text-sm mb-3">
                <i class="fas fa-medal text-amber-500 mr-1.5"></i> Akumulasi Poin Tertinggi (Perhatian Khusus / Guru BK)
            </h6>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                @foreach($topSiswa as $ts)
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 dark:border-gray-800 dark:bg-gray-800/40 flex items-center justify-between">
                        <div>
                            <a href="{{ route('pelanggaran.index', ['student_id' => $ts->id]) }}"
                               class="font-semibold text-xs text-gray-800 dark:text-white hover:text-brand-600 hover:underline">
                                {{ $ts->nama }}
                            </a>
                            <div class="text-[11px] text-gray-500">{{ $ts->kelas?->nama_kelas ?? '-' }} • {{ $ts->total_kasus }}x Kasus</div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center rounded-lg bg-red-100 px-2 py-1 text-xs font-bold text-red-700 dark:bg-red-500/20 dark:text-red-400">
                                {{ $ts->total_poin }} Poin
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Table Result: Siswa & Akumulasi Poin -->
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 text-center" style="width: 50px;">No</th>
                        <th class="px-4 py-3 text-left">NIS & Nama Siswa</th>
                        <th class="px-4 py-3 text-left">Kelas</th>
                        <th class="px-4 py-3 text-center">Total Kasus</th>
                        <th class="px-4 py-3 text-center">Akumulasi Poin</th>
                        <th class="px-4 py-3 text-left">Pelanggaran Terakhir</th>
                        <th class="px-4 py-3 text-left">Status Tindakan / SP</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($students as $idx => $s)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-center text-xs text-gray-500">
                                {{ $students->firstItem() + $idx }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('pelanggaran.index', ['student_id' => $s->id]) }}"
                                   class="font-semibold text-gray-900 dark:text-white hover:text-brand-600 dark:hover:text-brand-400 hover:underline">
                                    {{ $s->nama }}
                                </a>
                                <div class="text-xs text-gray-500 font-mono">NIS: {{ $s->nis ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="rounded-lg bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    {{ $s->kelas?->nama_kelas ?? '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if($s->total_kasus > 0)
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-400">
                                        {{ $s->total_kasus }} Kasus
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">0 Kasus</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @php
                                    $poin = (int) ($s->total_poin ?? 0);
                                    if ($poin > 50) {
                                        $badgeBg = 'bg-error-100 text-error-800 dark:bg-error-500/20 dark:text-error-300 font-extrabold';
                                    } elseif ($poin > 25) {
                                        $badgeBg = 'bg-orange-100 text-orange-800 dark:bg-orange-500/20 dark:text-orange-300 font-bold';
                                    } elseif ($poin > 10) {
                                        $badgeBg = 'bg-warning-100 text-warning-800 dark:bg-warning-500/20 dark:text-warning-300 font-semibold';
                                    } elseif ($poin > 0) {
                                        $badgeBg = 'bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300 font-semibold';
                                    } else {
                                        $badgeBg = 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 font-normal';
                                    }
                                @endphp
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs {{ $badgeBg }}">
                                    {{ $poin }} Poin
                                </span>
                            </td>
                            <td class="px-4 py-3 max-w-xs">
                                @if($s->latestPelanggaran)
                                    <div class="font-medium text-xs text-gray-800 dark:text-gray-200 truncate">
                                        {{ $s->latestPelanggaran->judul_pelanggaran }}
                                    </div>
                                    <div class="text-[11px] text-gray-400">
                                        {{ $s->latestPelanggaran->tanggal?->format('d/m/Y') }} • (+{{ $s->latestPelanggaran->poin }} poin)
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Tidak ada catatan</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 max-w-xs">
                                @if($s->latestPenanganan)
                                    <div class="flex items-center gap-1.5">
                                        <span class="rounded bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400">
                                            <i class="fas fa-user-shield text-[10px]"></i> {{ $s->latestPenanganan->tindakan_sp_label }}
                                        </span>
                                        @if($s->latestPenanganan->link_gdrive)
                                            <a href="{{ $s->latestPenanganan->link_gdrive }}" target="_blank" class="text-emerald-600 hover:text-emerald-700 text-xs" title="Link GDrive">
                                                <i class="fab fa-google-drive"></i>
                                            </a>
                                        @endif
                                    </div>
                                    <div class="text-[11px] text-gray-400 mt-0.5">
                                        {{ $s->latestPenanganan->tanggal?->format('d/m/Y') }} • {{ $s->total_penanganan }}x tindakan
                                    </div>
                                @elseif($s->total_kasus > 0)
                                    <span class="text-xs text-amber-600 dark:text-amber-400 font-medium">Belum ada tindakan BK</span>
                                @else
                                    <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">Tertib</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('pelanggaran.index', ['student_id' => $s->id]) }}"
                                   class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-2.5 py-1.5 text-xs font-semibold text-brand-600 hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-400 transition mr-1"
                                   title="Buka Catatan Pelanggaran Siswa Ini">
                                    <i class="fas fa-clipboard-list"></i> Detail Catatan
                                </a>
                                @if($s->total_kasus > 0)
                                    <a href="{{ route('pelanggaran.penanganan.index', ['student_id' => $s->id]) }}"
                                       class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 transition"
                                       title="Buka Riwayat Penanganan BK Siswa Ini">
                                        <i class="fas fa-user-shield"></i> BK
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-check-circle text-success-500 text-2xl mb-2 block"></i>
                                Tidak ada data siswa yang memenuhi filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($students->hasPages())
            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                {{ $students->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
