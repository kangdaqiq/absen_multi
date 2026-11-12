@extends('layouts.app')

@section('title', 'Rekapitulasi Pelanggaran Siswa')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <x-page-header
        title="Rekapitulasi Pelanggaran Siswa"
        subtitle="Laporan kumulatif poin, rekap kasus kedisiplinan siswa per periode, kelas, dan kategori"
        icon="fas fa-file-invoice">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('pelanggaran.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition">
                <i class="fas fa-clipboard-list text-brand-500"></i> Kelola Catatan
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
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Kasus</p>
            <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ $totalKasus }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Akumulasi Poin</p>
            <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ $totalPoin }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Ringan</p>
            <p class="mt-1 text-2xl font-bold text-success-600 dark:text-success-400">{{ $totalRingan }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Sedang</p>
            <p class="mt-1 text-2xl font-bold text-warning-600 dark:text-warning-400">{{ $totalSedang }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Berat</p>
            <p class="mt-1 text-2xl font-bold text-error-600 dark:text-error-400">{{ $totalBerat }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Terselesaikan</p>
            <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $totalSelesai }}</p>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400">
                    <i class="fas fa-filter text-xs"></i>
                </div>
                <h6 class="font-semibold text-gray-800 dark:text-white/90 text-sm">Filter Periode & Kriteria</h6>
            </div>
            @if(request()->hasAny(['start_date', 'end_date', 'kelas_id', 'kategori', 'status', 'search']))
                <a href="{{ route('pelanggaran.rekap') }}" class="text-xs text-error-600 hover:text-error-700 dark:text-error-400 font-medium hover:underline">
                    <i class="fas fa-times"></i> Reset Filter
                </a>
            @endif
        </div>

        <div class="p-5">
            <form action="{{ route('pelanggaran.rekap') }}" method="GET" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-6 items-end">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Dari Tanggal:</label>
                    <input type="date" name="start_date" value="{{ $startDate }}"
                           class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Sampai Tanggal:</label>
                    <input type="date" name="end_date" value="{{ $endDate }}"
                           class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
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
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Kategori:</label>
                    <select name="kategori" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="all">Semua Kategori</option>
                        <option value="ringan" {{ request('kategori') === 'ringan' ? 'selected' : '' }}>Ringan</option>
                        <option value="sedang" {{ request('kategori') === 'sedang' ? 'selected' : '' }}>Sedang</option>
                        <option value="berat" {{ request('kategori') === 'berat' ? 'selected' : '' }}>Berat</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Status:</label>
                    <select name="status" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="all">Semua Status</option>
                        <option value="tercatat" {{ request('status') === 'tercatat' ? 'selected' : '' }}>Tercatat</option>
                        <option value="diproses" {{ request('status') === 'diproses' ? 'selected' : '' }}>Diproses</option>
                        <option value="selesai" {{ request('status') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                    </select>
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
                <i class="fas fa-medal text-amber-500 mr-1.5"></i> Akumulasi Poin Tertinggi (Perlu Perhatian Khusus / BK)
            </h6>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                @foreach($topSiswa as $ts)
                    <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 dark:border-gray-800 dark:bg-gray-800/40 flex items-center justify-between">
                        <div>
                            <div class="font-semibold text-xs text-gray-800 dark:text-white">{{ $ts->student?->nama ?? '-' }}</div>
                            <div class="text-[11px] text-gray-500">{{ $ts->student?->kelas?->nama_kelas ?? '-' }} • {{ $ts->total_pelanggaran }}x Kasus</div>
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

    <!-- Table Result -->
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 text-center" style="width: 50px;">No</th>
                        <th class="px-4 py-3 text-left">Kode & Waktu</th>
                        <th class="px-4 py-3 text-left">Siswa & Kelas</th>
                        <th class="px-4 py-3 text-left">Pelanggaran</th>
                        <th class="px-4 py-3 text-center">Kategori / Poin</th>
                        <th class="px-4 py-3 text-left">Tindak Lanjut</th>
                        <th class="px-4 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($pelanggarans as $idx => $p)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                            <td class="px-4 py-3 text-center text-xs text-gray-500">
                                {{ $pelanggarans->firstItem() + $idx }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-mono text-xs font-semibold text-gray-800 dark:text-white">{{ $p->code }}</span>
                                <div class="text-xs text-gray-500">{{ $p->tanggal?->format('d M Y') }} {{ $p->waktu }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800 dark:text-white">{{ $p->student?->nama ?? '-' }}</div>
                                <div class="text-xs text-gray-500">NIS: {{ $p->student?->nis ?? '-' }} • {{ $p->student?->kelas?->nama_kelas ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 max-w-xs">
                                <div class="font-medium text-gray-800 dark:text-white">{{ $p->judul_pelanggaran }}</div>
                                @if($p->keterangan)
                                    <div class="text-xs text-gray-500 line-clamp-2">{{ $p->keterangan }}</div>
                                @endif
                                @if($p->bukti_foto_url)
                                    <a href="{{ $p->bukti_foto_url }}" target="_blank" class="mt-1 inline-flex items-center gap-1 text-xs text-brand-500 hover:underline">
                                        <i class="fas fa-paperclip"></i> Bukti Lampiran
                                    </a>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @php
                                    $katClass = match($p->kategori) {
                                        'berat' => 'bg-error-50 text-error-600 dark:bg-error-500/10 dark:text-error-400',
                                        'sedang' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400',
                                        default => 'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $katClass }}">
                                    {{ $p->kategori_label }} (+{{ $p->poin }})
                                </span>
                            </td>
                            <td class="px-4 py-3 max-w-xs">
                                <div class="text-xs text-gray-700 dark:text-gray-300">{{ $p->tindak_lanjut ?: '-' }}</div>
                                <div class="text-[11px] text-gray-400 mt-0.5">Dicatat: {{ $p->recorder?->full_name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @php
                                    $stClass = match($p->status) {
                                        'selesai' => 'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400',
                                        'diproses' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400',
                                        default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $stClass }}">
                                    {{ $p->status_label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-check-circle text-success-500 text-2xl mb-2 block"></i>
                                Tidak ada data catatan pelanggaran siswa pada rentang periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pelanggarans->hasPages())
            <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                {{ $pelanggarans->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

