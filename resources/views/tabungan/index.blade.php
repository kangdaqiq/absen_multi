@extends('layouts.app')

@section('title', 'Monitoring & Mutasi Tabungan Siswa')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <x-page-header
        title="Tabungan Siswa"
        subtitle="Monitoring mutasi tabungan siswa, rekapitulasi saldo kelas, dan ekspor laporan"
        icon="fas fa-wallet">
        <div class="flex flex-wrap items-center gap-2.5">
            @if($canManage)
                <a href="{{ route('tabungan.kasir') }}" target="_blank"
                   class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-xs font-bold text-white shadow-theme-xs hover:bg-brand-600 transition">
                    <i class="fas fa-desktop"></i> Buka Layar POS Tabungan
                </a>
            @endif

            <a href="{{ route('tabungan.rekap') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition">
                <i class="fas fa-file-invoice-dollar text-emerald-600"></i> Rekap Saldo Kelas
            </a>

            <a href="{{ route('tabungan.export-excel', array_merge(request()->all(), ['mode' => 'mutasi'])) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-emerald-700 transition">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
        </div>
    </x-page-header>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400 flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle text-emerald-600 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-400 shadow-xs">
            <div class="font-bold flex items-center gap-2 mb-1">
                <i class="fas fa-exclamation-triangle text-rose-600"></i> Terjadi Kesalahan:
            </div>
            <ul class="list-disc pl-5 space-y-0.5 text-xs">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Stat Cards Ringkasan Tabungan -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-xs">
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-xs font-medium">
                <span>Total Saldo Aktif</span>
                <i class="fas fa-wallet text-brand-500"></i>
            </div>
            <p class="mt-2 text-xl font-black text-gray-900 dark:text-white">Rp {{ number_format($totalSaldoAktif, 0, ',', '.') }}</p>
            <span class="text-[11px] text-gray-400 font-medium">Dana tabungan tersimpan</span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-xs">
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-xs font-medium">
                <span>Siswa Menabung</span>
                <i class="fas fa-users text-emerald-500"></i>
            </div>
            <p class="mt-2 text-xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($jumlahPenabung) }} Siswa</p>
            <span class="text-[11px] text-gray-400 font-medium">Saldo > Rp 0</span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-xs">
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-xs font-medium">
                <span>Setoran Hari Ini</span>
                <i class="fas fa-arrow-down text-emerald-500"></i>
            </div>
            <p class="mt-2 text-xl font-black text-emerald-600 dark:text-emerald-400">Rp {{ number_format($totalSetorToday, 0, ',', '.') }}</p>
            <span class="text-[11px] text-gray-400 font-medium">Masuk kas hari ini</span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-xs">
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-xs font-medium">
                <span>Tarikan Hari Ini</span>
                <i class="fas fa-arrow-up text-rose-500"></i>
            </div>
            <p class="mt-2 text-xl font-black text-rose-600 dark:text-rose-400">Rp {{ number_format($totalTarikToday, 0, ',', '.') }}</p>
            <span class="text-[11px] text-gray-400 font-medium">Keluar kas hari ini</span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-xs">
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-xs font-medium">
                <span>Total Setoran (All)</span>
                <i class="fas fa-coins text-emerald-500"></i>
            </div>
            <p class="mt-2 text-xl font-black text-gray-800 dark:text-white">Rp {{ number_format($totalSetorAll, 0, ',', '.') }}</p>
            <span class="text-[11px] text-gray-400 font-medium">Akumulasi setor</span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-xs">
            <div class="flex items-center justify-between text-gray-500 dark:text-gray-400 text-xs font-medium">
                <span>Total Tarikan (All)</span>
                <i class="fas fa-hand-holding-usd text-rose-500"></i>
            </div>
            <p class="mt-2 text-xl font-black text-gray-800 dark:text-white">Rp {{ number_format($totalTarikAll, 0, ',', '.') }}</p>
            <span class="text-[11px] text-gray-400 font-medium">Akumulasi penarikan</span>
        </div>
    </div>



    <!-- ============================================================== -->
    <!-- FILTER & DAFTAR MUTASI TRANSAKSI TABUNGAN SISWA                -->
    <!-- ============================================================== -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] space-y-4 shadow-xs">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h4 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-list-alt text-brand-500"></i> Riwayat Mutasi Transaksi Tabungan
                </h4>
                <p class="text-xs text-gray-500">Seluruh catatan pemasukan dan penarikan tabungan siswa terdata</p>
            </div>
            @if(request()->hasAny(['jenis', 'kelas_id', 'student_id', 'tanggal_mulai', 'tanggal_selesai', 'metode', 'search']))
                <a href="{{ route('tabungan.index') }}" class="text-xs text-rose-600 hover:underline font-semibold flex items-center gap-1">
                    <i class="fas fa-times"></i> Reset Semua Filter
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('tabungan.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            @if(request('student_id'))
                <input type="hidden" name="student_id" value="{{ request('student_id') }}">
            @endif

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Jenis Mutasi:</label>
                <select name="jenis" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="">Semua Jenis (Setor & Tarik)</option>
                    <option value="setor" {{ request('jenis') === 'setor' ? 'selected' : '' }}>Setor Tunai</option>
                    <option value="tarik" {{ request('jenis') === 'tarik' ? 'selected' : '' }}>Tarik Tunai</option>
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
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Dari Tanggal:</label>
                <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}"
                       class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Sampai Tanggal:</label>
                <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}"
                       class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Cari Nama/NIS/Ref:</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..."
                       class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full rounded-xl bg-brand-500 py-2.5 px-4 text-xs font-bold text-white hover:bg-brand-600 transition shadow-xs flex items-center justify-center gap-1.5">
                    <i class="fas fa-filter"></i>
                    <span>Terapkan Filter</span>
                </button>
            </div>
        </form>

        <!-- Tabel Mutasi -->
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
            <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50/80 text-[11px] font-bold uppercase text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">No. Ref & Waktu</th>
                        <th class="px-4 py-3">Siswa</th>
                        <th class="px-4 py-3">Kelas</th>
                        <th class="px-4 py-3 text-center">Jenis</th>
                        <th class="px-4 py-3 text-right">Nominal</th>
                        <th class="px-4 py-3 text-right">Saldo Sesudah</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3">Petugas</th>
                        @if($canManage)
                            <th class="px-4 py-3 text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($tabungans as $t)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $t->code }}</span>
                                <div class="text-[11px] text-gray-400 mt-0.5">
                                    {{ $t->tanggal ? $t->tanggal->format('d/m/Y') : '-' }} • {{ $t->waktu ?: '-' }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('tabungan.show', $t->student_id) }}" class="font-bold text-brand-600 hover:underline dark:text-brand-400">
                                    {{ $t->student?->nama ?? 'Siswa Dihapus' }}
                                </a>
                                <div class="text-[10px] text-gray-400">NIS: {{ $t->student?->nis ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-700 dark:text-gray-300">
                                {{ $t->student?->kelas?->nama_kelas ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if($t->jenis === 'setor')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[11px] font-bold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        <i class="fas fa-arrow-down text-[10px]"></i> Setor
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-bold text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                        <i class="fas fa-arrow-up text-[10px]"></i> Tarik
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-black whitespace-nowrap {{ $t->jenis === 'setor' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $t->jenis === 'setor' ? '+' : '-' }}{{ $t->nominal_formatted }}
                            </td>
                            <td class="px-4 py-3 text-right font-black whitespace-nowrap text-gray-900 dark:text-white">
                                {{ $t->saldo_sesudah_formatted }}
                            </td>
                            <td class="px-4 py-3 max-w-xs truncate text-[11px] text-gray-600 dark:text-gray-300" title="{{ $t->keterangan }}">
                                {{ $t->keterangan ?: '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-[11px] text-gray-500">
                                {{ $t->recorder?->full_name ?? 'Admin' }}
                            </td>
                            @if($canManage)
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <form action="{{ route('tabungan.destroy', $t->id) }}" method="POST"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi {{ $t->code }}? Saldo siswa akan disinkronkan kembali.')"
                                          class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-gray-400 hover:text-rose-600 transition" title="Hapus Transaksi">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 9 : 8 }}" class="py-12 text-center text-gray-400">
                                <i class="fas fa-receipt text-3xl mb-2 text-gray-300"></i>
                                <p class="text-sm font-semibold">Belum Ada Riwayat Mutasi Tabungan</p>
                                <p class="text-xs">Transaksi setor atau tarik tunai yang dilakukan akan tampil di sini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-2">
            {{ $tabungans->links() }}
        </div>
    </div>
</div>

@endsection
