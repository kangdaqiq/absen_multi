@extends('layouts.app')

@section('title', 'Rekapitulasi Saldo Tabungan Siswa')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <x-page-header
        title="Rekapitulasi Saldo Tabungan"
        subtitle="Daftar saldo berjalan, total setoran, dan penarikan tabungan siswa per kelas"
        icon="fas fa-file-invoice-dollar">
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('tabungan.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition">
                <i class="fas fa-cash-register text-brand-500"></i> Loket & Mutasi
            </a>

            <a href="{{ route('tabungan.export-excel', array_merge(request()->all(), ['mode' => 'rekap'])) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-emerald-700 transition">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>

            <a href="{{ route('tabungan.export-pdf', request()->all()) }}" target="_blank"
               class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-rose-700 transition">
                <i class="fas fa-file-pdf"></i> Cetak PDF Rekap
            </a>
        </div>
    </x-page-header>

    <!-- Stat Cards Ringkasan -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-xs">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Populasi Siswa</span>
            <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ number_format($totalSiswa) }}</p>
            <span class="text-[11px] text-gray-400 font-medium">Siswa terdaftar</span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-xs">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Siswa Memiliki Saldo</span>
            <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($totalPenabung) }}</p>
            <span class="text-[11px] text-gray-400 font-medium">Penabung aktif</span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-xs">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Saldo Kelas/Sekolah</span>
            <p class="mt-1 text-2xl font-black text-brand-600 dark:text-brand-400">Rp {{ number_format($totalSaldoKelas, 0, ',', '.') }}</p>
            <span class="text-[11px] text-gray-400 font-medium">Dana tabungan tersimpan</span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-xs">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Setoran Periode Ini</span>
            <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">Rp {{ number_format($totalSetorPeriode, 0, ',', '.') }}</p>
            <span class="text-[11px] text-gray-400 font-medium">Pemasukan kas</span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-xs">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Penarikan Periode Ini</span>
            <p class="mt-1 text-2xl font-bold text-rose-600 dark:text-rose-400">Rp {{ number_format($totalTarikPeriode, 0, ',', '.') }}</p>
            <span class="text-[11px] text-gray-400 font-medium">Pengeluaran kas</span>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-xs">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800 mb-4">
            <div class="flex items-center gap-2">
                <i class="fas fa-filter text-brand-500 text-sm"></i>
                <h5 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Filter Kriteria Rekapitulasi</h5>
            </div>
            @if(request()->hasAny(['filter_saldo', 'kelas_id', 'start_date', 'end_date', 'sort', 'search']))
                <a href="{{ route('tabungan.rekap') }}" class="text-xs text-rose-600 hover:underline font-semibold flex items-center gap-1">
                    <i class="fas fa-times"></i> Reset Filter
                </a>
            @endif
        </div>

        <form action="{{ route('tabungan.rekap') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Status Saldo:</label>
                <select name="filter_saldo" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="all">Semua Siswa</option>
                    <option value="has_balance" {{ request('filter_saldo') === 'has_balance' ? 'selected' : '' }}>Hanya yang Memiliki Saldo (> 0)</option>
                    <option value="no_balance" {{ request('filter_saldo') === 'no_balance' ? 'selected' : '' }}>Saldo Nol / Kosong (Rp 0)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Kelas:</label>
                <select name="kelas_id" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="all">Semua Kelas</option>
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Dari Tanggal (Statistik):</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}"
                       class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Sampai Tanggal:</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}"
                       class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Cari Siswa:</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / NIS siswa..."
                       class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full rounded-xl bg-brand-500 py-2.5 px-4 text-xs font-bold text-white hover:bg-brand-600 transition shadow-xs flex items-center justify-center gap-1.5">
                    <i class="fas fa-search"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>

    <!-- Tabel Rekapitulasi Siswa -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] space-y-4 shadow-xs">
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
            <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50/80 text-[11px] font-bold uppercase text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 text-center w-12">No</th>
                        <th class="px-4 py-3">Nama Siswa</th>
                        <th class="px-4 py-3">Kelas</th>
                        <th class="px-4 py-3 text-right">Total Setor (Periode)</th>
                        <th class="px-4 py-3 text-right">Total Tarik (Periode)</th>
                        <th class="px-4 py-3 text-center">Jml Transaksi</th>
                        <th class="px-4 py-3 text-right">Saldo Saat Ini</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($students as $idx => $s)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                            <td class="px-4 py-3 text-center font-semibold text-gray-400">
                                {{ $students->firstItem() + $idx }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $s->foto_url }}" alt="Foto" class="h-9 w-9 rounded-xl object-cover ring-1 ring-gray-200 dark:ring-gray-700 shrink-0" />
                                    <div>
                                        <a href="{{ route('tabungan.show', $s->id) }}" class="font-bold text-gray-900 hover:text-brand-600 dark:text-white dark:hover:text-brand-400 transition">
                                            {{ $s->nama }}
                                        </a>
                                        <div class="text-[10px] text-gray-400">NIS: {{ $s->nis ?? '-' }} • RFID: {{ $s->uid_rfid ?? '-' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">
                                {{ $s->kelas?->nama_kelas ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                Rp {{ number_format((int) ($s->total_setor ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                Rp {{ number_format((int) ($s->total_tarik ?? 0), 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-500 whitespace-nowrap">
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[10px] dark:bg-gray-800">
                                    {{ (int) ($s->total_transaksi ?? 0) }} Trx
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-black whitespace-nowrap text-base"
                                :class="{{ (int) $s->saldo_tabungan }} > 0 ? 'text-gray-900 dark:text-white' : 'text-gray-400'">
                                Rp {{ number_format((int) $s->saldo_tabungan, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ route('tabungan.show', $s->id) }}"
                                       class="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50/70 px-2.5 py-1 text-xs font-bold text-blue-700 hover:bg-blue-100 dark:border-blue-900/40 dark:bg-blue-950/40 dark:text-blue-300 transition"
                                       title="Buku Mutasi Siswa">
                                        <i class="fas fa-book-open text-[10px]"></i> Buku Mutasi
                                    </a>
                                    <a href="{{ route('tabungan.print-book', $s->id) }}" target="_blank"
                                       class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white p-1 text-xs font-bold text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition"
                                       title="Cetak Buku Tabungan">
                                        <i class="fas fa-print text-xs"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-gray-400">
                                <i class="fas fa-search text-3xl mb-2 text-gray-300"></i>
                                <p class="text-sm font-semibold">Tidak Ada Data Siswa Ditemukan</p>
                                <p class="text-xs">Coba ubah kriteria filter kelas atau kata kunci pencarian Anda.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-2">
            {{ $students->links() }}
        </div>
    </div>
</div>
@endsection
