@extends('layouts.app')

@section('title', 'Buku Tabungan - ' . $student->nama)

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <x-page-header
        title="Buku Tabungan Digital"
        subtitle="Riwayat transaksi, mutasi debit/kredit, dan saldo tabungan siswa"
        icon="fas fa-book-bookmark">
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('tabungan.print-book', $student->id) }}" target="_blank"
               class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-rose-700 transition">
                <i class="fas fa-print"></i> Cetak Buku Tabungan (PDF)
            </a>

            @if($canManage)
                <a href="{{ route('tabungan.index', ['student_id' => $student->id]) }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition">
                    <i class="fas fa-cash-register"></i> Input Transaksi di Loket
                </a>
            @endif

            <a href="{{ url()->previous() ?: route('tabungan.rekap') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 transition">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </x-page-header>

    <!-- Kartu Visual Buku Tabungan / Kartu ATM Siswa -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Kartu ATM / Passbook Digital -->
        <div class="lg:col-span-5">
            <div class="relative overflow-hidden rounded-[24px] bg-gradient-to-br from-blue-700 via-indigo-800 to-slate-900 p-6 text-white shadow-xl shadow-blue-900/20 space-y-6">
                <!-- Watermark Logo -->
                <div class="absolute -right-10 -bottom-10 opacity-10 text-9xl pointer-events-none">
                    <i class="fas fa-vault"></i>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-coins text-amber-300 text-xl"></i>
                        <span class="text-xs font-black tracking-widest uppercase">BUKU TABUNGAN SISWA</span>
                    </div>
                    <span class="rounded-full bg-white/20 px-2.5 py-0.5 text-[10px] font-bold backdrop-blur-xs">
                        {{ $student->kelas?->nama_kelas ?? 'Tanpa Kelas' }}
                    </span>
                </div>

                <div>
                    <span class="text-[11px] font-medium text-blue-200 uppercase tracking-wider">Total Saldo Tersimpan</span>
                    <div class="mt-1 text-3xl font-black tracking-tight text-white font-mono">
                        Rp {{ number_format((int) $student->saldo_tabungan, 0, ',', '.') }}
                    </div>
                </div>

                <div class="pt-2 border-t border-white/15 flex items-center justify-between text-xs">
                    <div>
                        <span class="text-[10px] text-blue-200 uppercase font-semibold">Nama Pemilik Akun</span>
                        <div class="font-bold text-sm truncate max-w-[200px]">{{ $student->nama }}</div>
                        <div class="text-[11px] text-blue-200 font-mono">NIS: {{ $student->nis ?? '-' }}</div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-blue-200 uppercase font-semibold">Total Mutasi</span>
                        <div class="font-bold text-sm">{{ $totalTrx }} Transaksi</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ringkasan Statistik Siswa -->
        <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-xs flex flex-col justify-between">
                <div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Akumulasi Setoran</span>
                    <p class="mt-2 text-2xl font-black text-emerald-600 dark:text-emerald-400">
                        Rp {{ number_format($totalSetor, 0, ',', '.') }}
                    </p>
                </div>
                <div class="mt-4 flex items-center gap-1.5 text-[11px] font-bold text-emerald-600">
                    <i class="fas fa-arrow-circle-down"></i> Uang Masuk Tabungan
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-xs flex flex-col justify-between">
                <div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Akumulasi Tarikan</span>
                    <p class="mt-2 text-2xl font-black text-rose-600 dark:text-rose-400">
                        Rp {{ number_format($totalTarik, 0, ',', '.') }}
                    </p>
                </div>
                <div class="mt-4 flex items-center gap-1.5 text-[11px] font-bold text-rose-600">
                    <i class="fas fa-arrow-circle-up"></i> Uang Ditarik Siswa
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-xs flex flex-col justify-between">
                <div>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Status Rekening</span>
                    <p class="mt-2 text-xl font-black text-gray-900 dark:text-white">
                        {{ $student->saldo_tabungan > 0 ? 'AKTIF' : 'SALDO NOL' }}
                    </p>
                </div>
                <div class="mt-4 text-[11px] text-gray-400">
                    Diperbarui: {{ now()->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Tabel Buku Mutasi -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] space-y-4 shadow-xs">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h4 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-history text-brand-500"></i> Riwayat Mutasi Buku Tabungan
                </h4>
                <p class="text-xs text-gray-500">Urutan pencatatan kronologis debit, kredit, dan saldo berjalan</p>
            </div>
            @if(request()->hasAny(['jenis', 'start_date', 'end_date']))
                <a href="{{ route('tabungan.show', $student->id) }}" class="text-xs text-rose-600 hover:underline font-semibold flex items-center gap-1">
                    <i class="fas fa-times"></i> Reset Filter
                </a>
            @endif
        </div>

        <form method="GET" action="{{ route('tabungan.show', $student->id) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Jenis Mutasi:</label>
                <select name="jenis" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="">Semua (Setor & Tarik)</option>
                    <option value="setor" {{ request('jenis') === 'setor' ? 'selected' : '' }}>Setor Tunai (+)</option>
                    <option value="tarik" {{ request('jenis') === 'tarik' ? 'selected' : '' }}>Tarik Tunai (-)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Dari Tanggal:</label>
                <input type="date" name="start_date" value="{{ request('start_date') }}"
                       class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Sampai Tanggal:</label>
                <input type="date" name="end_date" value="{{ request('end_date') }}"
                       class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full rounded-xl bg-brand-500 py-2.5 px-4 text-xs font-bold text-white hover:bg-brand-600 transition shadow-xs flex items-center justify-center gap-1.5">
                    <i class="fas fa-filter"></i> Terapkan Filter
                </button>
            </div>
        </form>

        <!-- Tabel Mutasi -->
        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
            <table class="w-full text-left text-xs text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50/80 text-[11px] font-bold uppercase text-gray-500 dark:bg-gray-800/60 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">No. Ref</th>
                        <th class="px-4 py-3">Tanggal & Jam</th>
                        <th class="px-4 py-3 text-center">Jenis</th>
                        <th class="px-4 py-3 text-right">Debit (Setor)</th>
                        <th class="px-4 py-3 text-right">Kredit (Tarik)</th>
                        <th class="px-4 py-3 text-right">Saldo Berjalan</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3">Petugas</th>
                        @if($canManage)
                            <th class="px-4 py-3 text-center">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($mutasi as $m)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                            <td class="px-4 py-3 font-mono font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                {{ $m->code }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $m->tanggal ? $m->tanggal->format('d/m/Y') : '-' }}</span>
                                <span class="text-[11px] text-gray-400">({{ $m->waktu ?: '-' }})</span>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if($m->jenis === 'setor')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                        Setor (+)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                        Tarik (-)
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                {{ $m->jenis === 'setor' ? $m->nominal_formatted : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-rose-600 dark:text-rose-400 whitespace-nowrap">
                                {{ $m->jenis === 'tarik' ? $m->nominal_formatted : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right font-black text-gray-900 dark:text-white whitespace-nowrap">
                                {{ $m->saldo_sesudah_formatted }}
                            </td>
                            <td class="px-4 py-3 max-w-xs truncate text-[11px] text-gray-600 dark:text-gray-300" title="{{ $m->keterangan }}">
                                {{ $m->keterangan ?: '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-[11px] text-gray-500">
                                {{ $m->recorder?->full_name ?? 'Admin' }}
                            </td>
                            @if($canManage)
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <form action="{{ route('tabungan.destroy', $m->id) }}" method="POST"
                                          onsubmit="return confirm('Hapus transaksi ini? Saldo tabungan siswa akan dihitung ulang otomatis.')"
                                          class="inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1 text-gray-400 hover:text-rose-600 transition" title="Hapus">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManage ? 9 : 8 }}" class="py-12 text-center text-gray-400">
                                <i class="fas fa-receipt text-3xl mb-2 text-gray-300"></i>
                                <p class="text-sm font-semibold">Belum Ada Catatan Mutasi</p>
                                <p class="text-xs">Siswa ini belum memiliki catatan transaksi tabungan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-2">
            {{ $mutasi->links() }}
        </div>
    </div>
</div>
@endsection
