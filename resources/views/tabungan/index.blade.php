@extends('layouts.app')

@section('title', 'Loket & Mutasi Tabungan Siswa')

@section('content')
<div class="space-y-6" x-data="tabunganLoket()">
    <!-- Header -->
    <x-page-header
        title="Tabungan Siswa"
        subtitle="Loket transaksi setor/tarik tunai, pemindaian kartu RFID, dan monitoring mutasi tabungan"
        icon="fas fa-wallet">
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('tabungan.rekap') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition">
                <i class="fas fa-file-invoice-dollar text-emerald-600"></i> Rekap Saldo Kelas
            </a>

            <a href="{{ route('tabungan.export-excel', array_merge(request()->all(), ['mode' => 'mutasi'])) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-emerald-700 transition">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>

            @if($canManage)
                <button type="button" @click="focusKasir()"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition">
                    <i class="fas fa-cash-register"></i> Buka Loket Kasir
                </button>
            @endif
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
                <i class="fas fa-vault text-brand-500"></i>
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

    @if($canManage)
        <!-- ============================================================== -->
        <!-- LOKET CEPAT TRANSAKSI KASIR (SCAN RFID USB / CARI NAMA / NIS) -->
        <!-- ============================================================== -->
        <div id="loketKasirSection" class="rounded-2xl border-2 border-brand-500/80 bg-white shadow-xl dark:border-brand-500/60 dark:bg-gray-900 overflow-hidden transition-all">
            <div class="bg-gradient-to-r from-blue-700 via-blue-800 to-indigo-900 px-6 py-4 text-white flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/15 backdrop-blur-md shadow-inner text-white text-lg">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold tracking-tight">Loket Kasir Tabungan Siswa</h3>
                        <p class="text-xs text-blue-200 font-medium">Tempelkan kartu RFID ke USB Reader atau ketik NIS / Nama siswa</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-bold text-emerald-300 border border-emerald-400/30">
                        <span class="h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span>
                        Loket Online & Siap Scan
                    </span>
                </div>
            </div>

            <div class="p-6 grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Kolom Kiri: Input Scan & Pencarian Siswa -->
                <div class="lg:col-span-5 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5 flex items-center justify-between">
                            <span>Scan RFID / Cari Siswa:</span>
                            <span class="text-[11px] text-brand-600 dark:text-brand-400 font-semibold cursor-pointer" @click="clearStudent()">
                                <i class="fas fa-rotate-left mr-1"></i> Reset
                            </span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400">
                                <i class="fas fa-barcode text-sm" :class="{ 'animate-spin': isSearching }"></i>
                            </span>
                            <input
                                type="text"
                                x-ref="scanInput"
                                x-model="searchQuery"
                                @keydown.enter.prevent="handleScanLookup()"
                                @input.debounce.300ms="handleScanLookup()"
                                placeholder="Tempel kartu RFID atau ketik nama/NIS..."
                                class="w-full rounded-xl border border-gray-300 bg-gray-50/50 py-3 pl-10 pr-10 text-sm font-semibold text-gray-900 focus:bg-white focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none dark:border-gray-700 dark:bg-gray-800 dark:text-white transition"
                                autofocus
                            />
                            <button
                                type="button"
                                x-show="searchQuery"
                                @click="searchQuery = ''; clearStudent();"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times-circle text-xs"></i>
                            </button>
                        </div>
                        <p class="mt-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                            *Tekan <b>Enter</b> setelah scan RFID atau pilih siswa dari daftar rekomendasi di bawah.
                        </p>
                    </div>

                    <!-- Rekomendasi Kandidat Siswa (Jika ada banyak hasil pencarian) -->
                    <div x-show="candidates.length > 1" class="rounded-xl border border-gray-200 bg-white p-2.5 dark:border-gray-800 dark:bg-gray-800/80 max-h-48 overflow-y-auto space-y-1.5">
                        <div class="text-[11px] font-bold text-gray-500 uppercase px-2">Pilih Siswa (<span x-text="candidates.length"></span> ditemukan):</div>
                        <template x-for="c in candidates" :key="c.id">
                            <div @click="selectStudent(c)"
                                 class="flex items-center justify-between p-2 rounded-lg hover:bg-brand-50 dark:hover:bg-brand-900/30 cursor-pointer transition border border-transparent hover:border-brand-200">
                                <div>
                                    <div class="text-xs font-bold text-gray-900 dark:text-white" x-text="c.nama"></div>
                                    <div class="text-[10px] text-gray-500" x-text="'NIS: ' + c.nis + ' • Kelas: ' + c.kelas_nama"></div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs font-extrabold text-emerald-600" x-text="c.saldo_formatted"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Profil Siswa Terpilih (Kartu Visual Tabungan) -->
                    <div x-show="activeStudent" class="rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50/90 to-indigo-50/70 p-4 dark:border-blue-900/50 dark:bg-gray-800/90 space-y-3.5 shadow-xs">
                        <div class="flex items-center gap-3">
                            <div class="relative shrink-0">
                                <img :src="activeStudent?.foto_url || 'https://ui-avatars.com/api/?name=Siswa&background=1E40AF&color=fff'"
                                     class="h-14 w-14 rounded-2xl object-cover ring-2 ring-blue-500/30 shadow-sm"
                                     alt="Foto Siswa" />
                                <span class="absolute -bottom-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500 text-white ring-2 ring-white text-[8px]">
                                    <i class="fas fa-check"></i>
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-black text-gray-900 dark:text-white truncate" x-text="activeStudent?.nama"></div>
                                <div class="text-xs font-medium text-gray-600 dark:text-gray-300 mt-0.5">
                                    <span class="font-bold text-brand-600 dark:text-brand-400" x-text="activeStudent?.kelas_nama"></span> • NIS: <span x-text="activeStudent?.nis"></span>
                                </div>
                                <div class="text-[11px] text-gray-500 mt-0.5 font-mono">
                                    RFID: <span x-text="activeStudent?.uid_rfid || '-'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Saldo Box -->
                        <div class="rounded-xl bg-white p-3.5 border border-blue-100 dark:bg-gray-900 dark:border-gray-700 flex items-center justify-between shadow-2xs">
                            <div>
                                <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Saldo Saat Ini</span>
                                <div class="text-lg font-black text-emerald-600 dark:text-emerald-400 mt-0.5" x-text="activeStudent?.saldo_formatted"></div>
                            </div>
                            <a :href="'/tabungan/siswa/' + (activeStudent?.id || 0)" target="_blank"
                               class="rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300 transition flex items-center gap-1.5">
                                <i class="fas fa-book-open text-xs"></i>
                                <span>Buku Mutasi</span>
                            </a>
                        </div>
                    </div>

                    <!-- Placeholder jika belum ada siswa dipilih -->
                    <div x-show="!activeStudent" class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50/50 p-8 text-center text-gray-400 dark:border-gray-800 dark:bg-gray-900/40">
                        <i class="fas fa-id-card text-3xl mb-2 text-gray-300"></i>
                        <p class="text-xs font-bold text-gray-600 dark:text-gray-300">Belum Ada Siswa Terpilih</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Silakan scan kartu siswa atau cari namanya untuk memulai transaksi.</p>
                    </div>
                </div>

                <!-- Kolom Kanan: Form Transaksi (Setor / Tarik & Nominal Cepat) -->
                <div class="lg:col-span-7 bg-slate-50/70 dark:bg-gray-800/40 rounded-2xl p-5 border border-gray-200/80 dark:border-gray-800 space-y-4">
                    <form @submit.prevent="submitTransaction()">
                        <!-- Pilihan Jenis Transaksi -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <label class="relative flex items-center justify-center p-3.5 rounded-xl border-2 cursor-pointer font-bold text-sm transition select-none"
                                   :class="formJenis === 'setor' ? 'border-emerald-500 bg-emerald-50/80 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300'">
                                <input type="radio" name="jenis" value="setor" x-model="formJenis" class="sr-only">
                                <i class="fas fa-arrow-circle-down text-emerald-600 text-lg mr-2"></i>
                                <span>SETOR TUNAI (MASUK)</span>
                            </label>

                            <label class="relative flex items-center justify-center p-3.5 rounded-xl border-2 cursor-pointer font-bold text-sm transition select-none"
                                   :class="formJenis === 'tarik' ? 'border-rose-500 bg-rose-50/80 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300'">
                                <input type="radio" name="jenis" value="tarik" x-model="formJenis" class="sr-only">
                                <i class="fas fa-arrow-circle-up text-rose-600 text-lg mr-2"></i>
                                <span>TARIK TUNAI (KELUAR)</span>
                            </label>
                        </div>

                        <!-- Nominal Input Besar -->
                        <div class="space-y-1.5 mb-4">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Nominal Transaksi (Rp):</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-lg font-black"
                                      :class="formJenis === 'setor' ? 'text-emerald-600' : 'text-rose-600'">
                                    Rp
                                </span>
                                <input
                                    type="text"
                                    x-model="formNominalDisplay"
                                    @input="formatNominalInput()"
                                    placeholder="0"
                                    class="w-full rounded-2xl border-2 bg-white py-3 pl-14 pr-4 text-2xl font-black focus:outline-none focus:ring-2 dark:bg-gray-900 dark:text-white transition"
                                    :class="formJenis === 'setor' ? 'border-emerald-300 focus:border-emerald-500 focus:ring-emerald-500/20 text-emerald-700 dark:text-emerald-400' : 'border-rose-300 focus:border-rose-500 focus:ring-rose-500/20 text-rose-700 dark:text-rose-400'"
                                />
                            </div>
                        </div>

                        <!-- Tombol Cepat Nominal Instan -->
                        <div class="space-y-1.5 mb-4">
                            <span class="text-[11px] font-bold text-gray-500 uppercase">Pilih Nominal Instan:</span>
                            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                                <button type="button" @click="setNominal(5000)" class="rounded-xl border border-gray-300 bg-white py-2 text-xs font-bold text-gray-700 hover:bg-brand-50 hover:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 transition">5.000</button>
                                <button type="button" @click="setNominal(10000)" class="rounded-xl border border-gray-300 bg-white py-2 text-xs font-bold text-gray-700 hover:bg-brand-50 hover:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 transition">10.000</button>
                                <button type="button" @click="setNominal(20000)" class="rounded-xl border border-gray-300 bg-white py-2 text-xs font-bold text-gray-700 hover:bg-brand-50 hover:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 transition">20.000</button>
                                <button type="button" @click="setNominal(50000)" class="rounded-xl border border-gray-300 bg-white py-2 text-xs font-bold text-gray-700 hover:bg-brand-50 hover:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 transition">50.000</button>
                                <button type="button" @click="setNominal(100000)" class="rounded-xl border border-gray-300 bg-white py-2 text-xs font-bold text-gray-700 hover:bg-brand-50 hover:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 transition">100.000</button>
                                <button type="button" @click="setNominal(200000)" class="rounded-xl border border-gray-300 bg-white py-2 text-xs font-bold text-gray-700 hover:bg-brand-50 hover:border-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 transition">200.000</button>
                            </div>
                        </div>

                        <!-- Baris Tambahan: Tanggal, Metode, Keterangan -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Tanggal Transaksi:</label>
                                <input type="date" x-model="formTanggal" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none focus:border-brand-500" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Metode:</label>
                                <select x-model="formMetode" class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none focus:border-brand-500">
                                    <option value="tunai">Tunai / Cash</option>
                                    <option value="transfer">Transfer Bank</option>
                                    <option value="qris">QRIS</option>
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Catatan / Keterangan (Opsional):</label>
                                <input type="text" x-model="formKeterangan" placeholder="Contoh: Setoran harian, tabungan bekal, keperluan beli buku..." class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white outline-none focus:border-brand-500" />
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="!activeStudent || formNominal <= 0 || isSubmitting"
                            class="w-full flex items-center justify-center gap-2 rounded-2xl py-3.5 px-4 text-sm font-extrabold text-white shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed select-none active:scale-[0.99]"
                            :class="formJenis === 'setor' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'">
                            <i class="fas fa-paper-plane" :class="{ 'fa-spin': isSubmitting }"></i>
                            <span x-text="isSubmitting ? 'Memproses Transaksi...' : (formJenis === 'setor' ? 'SIMPAN SETORAN TABUNGAN' : 'PROSES PENARIKAN TABUNGAN')"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- FILTER & DAFTAR MUTASI TRANSAKSI TABUNGAN SISWA                -->
    <!-- ============================================================== -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] space-y-4 shadow-xs">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
                <h4 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-list-check text-brand-500"></i> Riwayat Mutasi Transaksi Tabungan
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

<script>
function tabunganLoket() {
    return {
        searchQuery: '',
        isSearching: false,
        candidates: [],
        activeStudent: {!! json_encode($selectedStudentPayload) !!},

        formJenis: 'setor',
        formNominal: 0,
        formNominalDisplay: '',
        formTanggal: new Date().toISOString().split('T')[0],
        formMetode: 'tunai',
        formKeterangan: '',
        isSubmitting: false,

        focusKasir() {
            const el = document.getElementById('loketKasirSection');
            if (el) {
                el.scrollIntoView({ behavior: 'smooth' });
                this.$refs.scanInput.focus();
            }
        },

        async handleScanLookup() {
            const q = this.searchQuery.trim();
            if (!q) {
                this.candidates = [];
                return;
            }

            this.isSearching = true;
            try {
                const res = await fetch(`/tabungan/scan-lookup?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (data.success) {
                    this.candidates = data.candidates || [];
                    if (data.exact || this.candidates.length === 1) {
                        this.selectStudent(data.student);
                        this.candidates = [];
                    }
                } else {
                    this.candidates = [];
                }
            } catch (err) {
                console.error('Scan lookup error:', err);
            } finally {
                this.isSearching = false;
            }
        },

        selectStudent(student) {
            this.activeStudent = student;
            this.searchQuery = student.nama;
        },

        clearStudent() {
            this.activeStudent = null;
            this.searchQuery = '';
            this.candidates = [];
            this.formNominal = 0;
            this.formNominalDisplay = '';
            this.formKeterangan = '';
            if (this.$refs.scanInput) {
                this.$refs.scanInput.focus();
            }
        },

        setNominal(amount) {
            this.formNominal = amount;
            this.formNominalDisplay = new Intl.NumberFormat('id-ID').format(amount);
        },

        formatNominalInput() {
            const clean = this.formNominalDisplay.replace(/[^0-9]/g, '');
            const val = clean ? parseInt(clean, 10) : 0;
            this.formNominal = val;
            this.formNominalDisplay = val > 0 ? new Intl.NumberFormat('id-ID').format(val) : '';
        },

        async submitTransaction() {
            if (!this.activeStudent) {
                alert('Pilih siswa terlebih dahulu!');
                return;
            }
            if (this.formNominal < 500) {
                alert('Nominal minimal transaksi adalah Rp 500');
                return;
            }
            if (this.formJenis === 'tarik' && this.formNominal > this.activeStudent.saldo_tabungan) {
                alert(`Saldo siswa tidak mencukupi! Saldo saat ini: ${this.activeStudent.saldo_formatted}`);
                return;
            }

            this.isSubmitting = true;

            const payload = {
                student_id: this.activeStudent.id,
                jenis: this.formJenis,
                nominal: this.formNominal,
                tanggal: this.formTanggal,
                metode: this.formMetode,
                keterangan: this.formKeterangan,
            };

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('/tabungan', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token || ''
                    },
                    body: JSON.stringify(payload)
                });

                const json = await res.json();
                if (res.ok && json.success) {
                    alert(json.message);
                    // Update saldo lokal siswa
                    if (json.student) {
                        this.activeStudent = json.student;
                    }
                    this.formNominal = 0;
                    this.formNominalDisplay = '';
                    this.formKeterangan = '';
                    // Muat ulang halaman agar mutasi langsung terupdate
                    window.location.reload();
                } else {
                    alert(json.message || 'Gagal memproses transaksi.');
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan.');
                console.error(err);
            } finally {
                this.isSubmitting = false;
            }
        }
    };
}
</script>
@endsection
