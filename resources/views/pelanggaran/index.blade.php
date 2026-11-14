@extends('layouts.app')

@section('title', 'Catatan Pelanggaran Siswa')

@section('content')
<div class="space-y-6" x-data="pelanggaranAdmin()">
    <!-- Header -->
    <x-page-header
        title="Catatan Pelanggaran Siswa"
        subtitle="Catat, pantau poin pelanggaran, dan kelola tindak lanjut kedisiplinan siswa"
        icon="fas fa-exclamation-triangle">
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('pelanggaran.penanganan.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50/70 px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-900/50 dark:bg-indigo-950/40 dark:text-indigo-300 dark:hover:bg-indigo-900/50 transition shadow-theme-xs">
                <i class="fas fa-user-shield text-indigo-600 dark:text-indigo-400"></i> Riwayat Penanganan BK
            </a>

            <a href="{{ route('pelanggaran.rekap') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition shadow-theme-xs">
                <i class="fas fa-file-invoice text-emerald-500"></i> Rekap Pelanggaran
            </a>

            <button type="button" @click="openKategoriModal = true"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition shadow-theme-xs">
                <i class="fas fa-list-ul text-brand-500"></i> Kategori Pelanggaran
            </button>

            <button type="button" @click="openCreateModal = true"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 transition shadow-theme-xs">
                <i class="fas fa-plus"></i> Catat Pelanggaran
            </button>
        </div>
    </x-page-header>

    @if(session('success'))
        <div class="rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Kasus</p>
            <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ $totalAll }}</p>
        </div>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Hari Ini</p>
            <p class="mt-1 text-2xl font-bold text-brand-500">{{ $totalToday }}</p>
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
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Akumulasi Poin</p>
            <p class="mt-1 text-2xl font-bold text-gray-800 dark:text-white">{{ $totalPoin }}</p>
        </div>
    </div>

    @if($selectedStudent)
        <!-- Banner Filter Siswa Aktif dari Rekap -->
        <div class="rounded-2xl border border-brand-200 bg-brand-50/70 p-4 dark:border-brand-900/40 dark:bg-brand-950/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-theme-xs">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-500 text-white font-bold text-base shadow-sm">
                    {{ strtoupper(substr($selectedStudent->nama, 0, 2)) }}
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-400">Filter Siswa Aktif</span>
                        <span class="rounded-md bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700 dark:bg-red-500/20 dark:text-red-400">
                            Total: {{ $selectedStudent->pelanggarans->sum('poin') }} Poin
                        </span>
                    </div>
                    <div class="text-base font-bold text-gray-900 dark:text-white mt-0.5">
                        {{ $selectedStudent->nama }}
                        <span class="text-sm font-normal text-gray-500">(NIS: {{ $selectedStudent->nis ?? '-' }} • Kelas: {{ $selectedStudent->kelas?->nama_kelas ?? '-' }})</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" @click="openCreateForStudent({{ $selectedStudent->id }})"
                        class="rounded-xl bg-brand-500 px-3.5 py-2 text-xs font-semibold text-white hover:bg-brand-600 transition shadow-xs">
                    <i class="fas fa-plus mr-1"></i> Catat Pelanggaran Siswa Ini
                </button>
                <a href="{{ route('pelanggaran.penanganan.index', ['student_id' => $selectedStudent->id]) }}"
                   class="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-300 transition">
                    <i class="fas fa-user-shield mr-1"></i> Riwayat BK
                </a>
                <a href="{{ route('pelanggaran.index') }}"
                   class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 transition">
                    <i class="fas fa-times mr-1"></i> Reset Filter
                </a>
            </div>
        </div>
    @endif

    <!-- Filter -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('pelanggaran.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
            @if(request('student_id'))
                <input type="hidden" name="student_id" value="{{ request('student_id') }}">
            @endif
            <select name="kelas_id" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">Semua Kelas</option>
                @foreach($kelasList as $kelas)
                    <option value="{{ $kelas->id }}" {{ request('kelas_id') == $kelas->id ? 'selected' : '' }}>
                        {{ $kelas->nama_kelas }}
                    </option>
                @endforeach
            </select>

            <select name="kategori" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">Semua Kategori</option>
                <option value="ringan" {{ request('kategori') === 'ringan' ? 'selected' : '' }}>Ringan</option>
                <option value="sedang" {{ request('kategori') === 'sedang' ? 'selected' : '' }}>Sedang</option>
                <option value="berat" {{ request('kategori') === 'berat' ? 'selected' : '' }}>Berat</option>
            </select>

            <select name="status" class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">Semua Status</option>
                <option value="tercatat" {{ request('status') === 'tercatat' ? 'selected' : '' }}>Tercatat</option>
                <option value="diproses" {{ request('status') === 'diproses' ? 'selected' : '' }}>Diproses</option>
                <option value="selesai" {{ request('status') === 'selesai' ? 'selected' : '' }}>Selesai</option>
            </select>

            <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}"
                   class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">

            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIS, kode, kasus..."
                   class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">

            <div class="flex gap-2">
                <button type="submit" class="flex-1 rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600 transition">
                    Filter
                </button>
                <a href="{{ route('pelanggaran.index') }}" class="rounded-xl border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/50 text-xs uppercase text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3 text-left">Kode & Tanggal</th>
                        <th class="px-4 py-3 text-left">Siswa</th>
                        <th class="px-4 py-3 text-left">Pelanggaran</th>
                        <th class="px-4 py-3 text-center">Kategori / Poin</th>
                        <th class="px-4 py-3 text-left">Tindak Lanjut</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @forelse($pelanggarans as $item)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-mono text-xs font-semibold text-gray-800 dark:text-white">{{ $item->code }}</div>
                                <div class="text-xs text-gray-500">{{ $item->tanggal?->format('d M Y') }} {{ $item->waktu }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800 dark:text-white">{{ $item->student?->nama ?? '-' }}</div>
                                <div class="text-xs text-gray-500">NIS: {{ $item->student?->nis ?? '-' }} • {{ $item->student?->kelas?->nama_kelas ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 max-w-xs">
                                <div class="font-medium text-gray-800 dark:text-white">{{ $item->judul_pelanggaran }}</div>
                                @if($item->keterangan)
                                    <div class="text-xs text-gray-500 line-clamp-2">{{ $item->keterangan }}</div>
                                @endif
                                @if($item->bukti_foto_url)
                                    <a href="{{ $item->bukti_foto_url }}" target="_blank" class="mt-1 inline-flex items-center gap-1 text-xs text-brand-500 hover:underline">
                                        <i class="fas fa-paperclip"></i> Lihat Bukti
                                    </a>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @php
                                    $katClass = match($item->kategori) {
                                        'berat' => 'bg-error-50 text-error-600 dark:bg-error-500/10 dark:text-error-400',
                                        'sedang' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400',
                                        default => 'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $katClass }}">
                                    {{ $item->kategori_label }} (+{{ $item->poin }})
                                </span>
                            </td>
                            <td class="px-4 py-3 max-w-xs">
                                <div class="text-xs text-gray-700 dark:text-gray-300">{{ $item->tindak_lanjut ?: '-' }}</div>
                                @if($item->penanganans->isNotEmpty())
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @foreach($item->penanganans->take(2) as $pn)
                                            <span class="inline-flex items-center gap-1 rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400">
                                                <i class="fas fa-user-shield text-[9px]"></i> {{ $pn->tindakan_sp_label }}
                                            </span>
                                        @endforeach
                                        @if($item->penanganans->count() > 2)
                                            <span class="text-[10px] text-gray-400">+{{ $item->penanganans->count() - 2 }} lainnya</span>
                                        @endif
                                    </div>
                                @endif
                                <div class="text-[11px] text-gray-400 mt-1">Dicatat: {{ $item->recorder?->full_name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @php
                                    $stClass = match($item->status) {
                                        'selesai' => 'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400',
                                        'diproses' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400',
                                        default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $stClass }}">
                                    {{ $item->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('pelanggaran.penanganan.index', ['student_id' => $item->student_id]) }}"
                                   class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400 mr-1"
                                   title="Buka Riwayat Penanganan BK Siswa">
                                    <i class="fas fa-user-shield"></i> BK
                                    @if($item->penanganans->isNotEmpty())
                                        <span class="ml-0.5 rounded-full bg-indigo-600 px-1.5 py-0.2 text-[10px] text-white">{{ $item->penanganans->count() }}</span>
                                    @endif
                                </a>
                                <button type="button"
                                        @click="openEdit({{ $item->id }}, '{{ $item->status }}', @js($item->tindak_lanjut ?? ''))"
                                        class="inline-flex items-center rounded-lg bg-brand-50 px-2.5 py-1.5 text-xs font-semibold text-brand-600 hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-400 mr-1">
                                    Proses
                                </button>
                                <form method="POST" action="{{ route('pelanggaran.destroy', $item->id) }}" class="inline" onsubmit="return confirm('Hapus catatan pelanggaran ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center rounded-lg bg-error-50 px-2.5 py-1.5 text-xs font-semibold text-error-600 hover:bg-error-100 dark:bg-error-500/10 dark:text-error-400">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                Belum ada catatan pelanggaran siswa.
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

    <!-- Modal Catat Pelanggaran -->
    <div x-show="openCreateModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.away="openCreateModal = false" class="w-full max-w-xl rounded-2xl bg-white p-6 dark:bg-gray-900 shadow-xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">Catat Pelanggaran Siswa</h3>
                <button type="button" @click="openCreateModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form method="POST" action="{{ route('pelanggaran.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Siswa *</label>
                    <select name="student_id" x-model="createStudentId" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">-- Pilih Siswa --</option>
                        @foreach($siswaList as $s)
                            <option value="{{ $s->id }}">{{ $s->nama }} ({{ $s->nis ?? '-' }}) - {{ $s->kelas?->nama_kelas ?? '-' }}</option>
                        @endforeach
                    </select>
                </div>

                @if($kategoriList->isNotEmpty())
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pilih dari Kategori Pelanggaran</label>
                    <select name="kategori_pelanggaran_id" x-model="selectedKategoriId" @change="applyKategori()"
                            class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="">-- Input Manual --</option>
                        @foreach($kategoriList->where('is_active', true) as $kat)
                            <option value="{{ $kat->id }}">[{{ ucfirst($kat->kategori) }} • {{ $kat->poin }} poin] {{ $kat->nama }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal *</label>
                        <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Waktu</label>
                        <input type="time" name="waktu" value="{{ date('H:i') }}" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Judul Pelanggaran *</label>
                    <input type="text" name="judul_pelanggaran" x-model="formJudul" required placeholder="Contoh: Terlambat masuk sekolah / Tidak memakai atribut"
                           class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori *</label>
                        <select name="kategori" x-model="formKategori" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="ringan">Ringan</option>
                            <option value="sedang">Sedang</option>
                            <option value="berat">Berat</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Poin *</label>
                        <input type="number" name="poin" x-model="formPoin" min="0" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kronologi / Keterangan</label>
                    <textarea name="keterangan" rows="2" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tindak Lanjut / Sanksi</label>
                    <textarea name="tindak_lanjut" x-model="formSanksi" rows="2" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Bukti Foto / Dokumen (Opsional)</label>
                    <input type="file" name="bukti_foto" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm text-gray-500">
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openCreateModal = false" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Batal</button>
                    <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Update Status & Tindak Lanjut -->
    <div x-show="openEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.away="openEditModal = false" class="w-full max-w-md rounded-2xl bg-white p-6 dark:bg-gray-900 shadow-xl">
            <h3 class="text-lg font-bold text-gray-800 dark:text-white border-b border-gray-200 pb-3 dark:border-gray-800">Update Tindak Lanjut</h3>
            <form method="POST" :action="`{{ url('/pelanggaran') }}/${editId}`" class="mt-4 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                    <select name="status" x-model="editStatus" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="tercatat">Tercatat</option>
                        <option value="diproses">Diproses</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tindak Lanjut / Sanksi</label>
                    <textarea name="tindak_lanjut" x-model="editTindakLanjut" rows="3" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="openEditModal = false" class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">Batal</button>
                    <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Catatan Penanganan Pelanggaran (Guru BK / Kesiswaan) -->
    <div x-show="openPenangananModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.away="openPenangananModal = false" class="w-full max-w-2xl rounded-2xl bg-white p-6 dark:bg-gray-900 shadow-xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800">
                <div>
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white flex items-center gap-2">
                        <i class="fas fa-user-shield text-indigo-600"></i> Catatan Penanganan Kasus (Guru BK)
                    </h3>
                    <p class="text-xs text-gray-500 mt-0.5" x-text="penangananSiswaTitle"></p>
                </div>
                <button type="button" @click="openPenangananModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>

            <!-- Form Tambah Penanganan / Tindakan SP -->
            <form method="POST" :action="`{{ url('/pelanggaran') }}/${penangananPelanggaranId}/penanganan`" enctype="multipart/form-data" class="mt-4 space-y-4 border-b border-gray-200 pb-6 dark:border-gray-800">
                @csrf
                <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-3 text-xs text-indigo-900 dark:border-indigo-900/40 dark:bg-indigo-950/20 dark:text-indigo-300">
                    <span class="font-bold">Kasus:</span> <span x-text="penangananKasusText"></span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tanggal Tindakan *</label>
                        <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" required
                               class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Tindakan / Sanksi SP *</label>
                        <select name="tindakan_sp" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="pembinaan">Pembinaan BK</option>
                            <option value="teguran_lisan">Teguran Lisan</option>
                            <option value="teguran_tertulis">Teguran Tertulis</option>
                            <option value="sp_1">Surat Peringatan 1 (SP 1)</option>
                            <option value="sp_2">Surat Peringatan 2 (SP 2)</option>
                            <option value="sp_3">Surat Peringatan 3 (SP 3)</option>
                            <option value="sp_4">Surat Peringatan 4 (SP 4 / Terakhir)</option>
                            <option value="skorsing">Skorsing</option>
                            <option value="dikembalikan_ortu">Dikembalikan ke Orang Tua</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Catatan Penanganan / Proses Konseling *</label>
                    <textarea name="catatan_penanganan" required rows="3" placeholder="Uraikan proses bimbingan, konseling, atau pemanggilan orang tua yang dilakukan oleh Guru BK..."
                              class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Hasil Kesepakatan / Perjanjian (Opsional)</label>
                    <textarea name="hasil_kesepakatan" rows="2" placeholder="Komitmen siswa, perjanjian bermaterai, poin perjanjian..."
                              class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        <i class="fab fa-google-drive text-emerald-600 mr-1"></i> Link Google Drive Berkas / Surat SP / Dokumentasi Guru BK (Opsional)
                    </label>
                    <input type="url" name="link_gdrive" placeholder="https://drive.google.com/drive/folders/..."
                           class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <p class="text-[11px] text-gray-400 mt-1">Masukkan URL folder Google Drive atau file dokumen surat pernyataan / SP untuk arsip digital.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Upload File Lampiran (Opsional, PDF/Foto/Doc max 10MB)</label>
                        <input type="file" name="lampiran_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                               class="w-full text-xs text-gray-500 file:mr-2 file:rounded-lg file:border-0 file:bg-gray-100 file:px-2.5 file:py-1.5 file:text-xs file:font-semibold">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Status Penanganan *</label>
                        <select name="status_penanganan" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                            <option value="selesai">Selesai (Kasus Ditutup / Selesai)</option>
                            <option value="dalam_proses">Dalam Proses (Masih Diproses)</option>
                            <option value="evaluasi_lanjutan">Evaluasi Lanjutan (Dalam Pengawasan)</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition">
                        <i class="fas fa-save"></i> Simpan Catatan Penanganan
                    </button>
                </div>
            </form>

            <!-- Riwayat Penanganan Sebelumnya -->
            <div class="mt-5">
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">Riwayat Penanganan Kasus</h4>
                <template x-if="penangananList.length === 0">
                    <p class="text-xs text-gray-400 py-3 text-center italic">Belum ada catatan penanganan BK untuk kasus ini.</p>
                </template>
                <div class="space-y-3">
                    <template x-for="pn in penangananList" :key="pn.id">
                        <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-3.5 dark:border-gray-800 dark:bg-gray-800/40">
                            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200/60 pb-2 dark:border-gray-700/60">
                                <div class="flex items-center gap-2">
                                    <span class="rounded bg-indigo-100 px-2 py-0.5 text-xs font-bold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300" x-text="pn.tindakan_sp_label"></span>
                                    <span class="text-xs text-gray-500" x-text="pn.tanggal"></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-[11px] text-gray-500">Oleh: <strong x-text="pn.handler_name || 'Guru BK'"></strong></span>
                                    <form :action="`{{ url('/pelanggaran/penanganan') }}/${pn.id}`" method="POST" onsubmit="return confirm('Hapus riwayat penanganan ini?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-error-500 hover:text-error-700 ml-2" title="Hapus"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </div>
                            </div>
                            <div class="mt-2 text-xs text-gray-700 dark:text-gray-300 whitespace-pre-line" x-text="pn.catatan_penanganan"></div>
                            <template x-if="pn.hasil_kesepakatan">
                                <div class="mt-2 rounded-lg bg-white p-2 text-xs border border-gray-100 dark:border-gray-800 dark:bg-gray-900/60">
                                    <span class="font-semibold text-gray-600 dark:text-gray-400">Hasil Kesepakatan:</span>
                                    <span class="text-gray-800 dark:text-gray-200" x-text="pn.hasil_kesepakatan"></span>
                                </div>
                            </template>
                            <div class="mt-2 flex flex-wrap items-center gap-3 pt-1 text-xs">
                                <template x-if="pn.link_gdrive">
                                    <a :href="pn.link_gdrive" target="_blank" class="inline-flex items-center gap-1.5 font-semibold text-emerald-600 hover:underline">
                                        <i class="fab fa-google-drive"></i> Buka Berkas Google Drive
                                    </a>
                                </template>
                                <template x-if="pn.lampiran_file_url">
                                    <a :href="pn.lampiran_file_url" target="_blank" class="inline-flex items-center gap-1.5 font-semibold text-brand-600 hover:underline">
                                        <i class="fas fa-paperclip"></i> Unduh File Lampiran
                                    </a>
                                </template>
                                <span class="text-[11px] text-gray-400 ml-auto">Status: <span class="font-medium" x-text="pn.status_label"></span></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Master Kategori -->
    <div x-show="openKategoriModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.away="openKategoriModal = false" class="w-full max-w-xl rounded-2xl bg-white p-6 dark:bg-gray-900 shadow-xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800">
                <h3 class="text-lg font-bold text-gray-800 dark:text-white">Kategori Pelanggaran</h3>
                <button type="button" @click="openKategoriModal = false" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form method="POST" action="{{ route('pelanggaran.kategori.store') }}" class="mt-4 space-y-3 border-b border-gray-200 pb-4 dark:border-gray-800">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <input type="text" name="kode" placeholder="Kode (opsional)" class="rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <input type="text" name="nama" required placeholder="Nama Pelanggaran *" class="sm:col-span-2 rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <select name="kategori" required class="rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                        <option value="ringan">Ringan</option>
                        <option value="sedang">Sedang</option>
                        <option value="berat">Berat</option>
                    </select>
                    <input type="number" name="poin" value="5" min="1" required placeholder="Poin *" class="rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                    <button type="submit" class="col-span-2 sm:col-span-1 rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600">Tambah</button>
                </div>
                <input type="text" name="sanksi_default" placeholder="Sanksi default (opsional)" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
            </form>

            <div class="mt-4 space-y-2">
                @forelse($kategoriList as $kat)
                    <div class="flex items-center justify-between rounded-xl border border-gray-200 px-3 py-2 text-sm dark:border-gray-800">
                        <div>
                            <div class="font-semibold text-gray-800 dark:text-white">{{ $kat->nama }}</div>
                            <div class="text-xs text-gray-500">{{ ucfirst($kat->kategori) }} • {{ $kat->poin }} poin {{ $kat->sanksi_default ? '• ' . $kat->sanksi_default : '' }}</div>
                        </div>
                        <form method="POST" action="{{ route('pelanggaran.kategori.destroy', $kat->id) }}" onsubmit="return confirm('Hapus kategori ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs font-semibold text-error-600 hover:underline">Hapus</button>
                        </form>
                    </div>
                @empty
                    <p class="text-center text-sm text-gray-500 py-4">Belum ada kategori pelanggaran.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function pelanggaranAdmin() {
    const kategoris = @json($kategoriList->keyBy('id'));
    return {
        createStudentId: '{{ $selectedStudent?->id ?? '' }}',
        openCreateModal: false,
        openEditModal: false,
        openKategoriModal: false,
        openPenangananModal: false,
        selectedKategoriId: '',
        formJudul: '',
        formKategori: 'ringan',
        formPoin: 5,
        formSanksi: '',
        editId: null,
        editStatus: 'tercatat',
        editTindakLanjut: '',
        penangananPelanggaranId: null,
        penangananSiswaTitle: '',
        penangananKasusText: '',
        penangananList: [],
        openCreateForStudent(studentId) {
            this.createStudentId = String(studentId);
            this.openCreateModal = true;
        },
        applyKategori() {
            const kat = kategoris[this.selectedKategoriId];
            if (kat) {
                this.formJudul = kat.nama;
                this.formKategori = kat.kategori;
                this.formPoin = kat.poin;
                this.formSanksi = kat.sanksi_default || '';
            }
        },
        openEdit(id, status, tindakLanjut) {
            this.editId = id;
            this.editStatus = status;
            this.editTindakLanjut = tindakLanjut || '';
            this.openEditModal = true;
        },
        openPenangananModalAction(item) {
            this.penangananPelanggaranId = item.id;
            const siswaNama = item.student ? item.student.nama : 'Siswa';
            const siswaNis = item.student ? (item.student.nis || '-') : '-';
            const kelas = item.student && item.student.kelas ? item.student.kelas.nama_kelas : '-';
            this.penangananSiswaTitle = `Siswa: ${siswaNama} (NIS: ${siswaNis}) • Kelas: ${kelas} • Kode: ${item.code}`;
            this.penangananKasusText = `${item.judul_pelanggaran} (${item.kategori_label || item.kategori} - ${item.poin} poin)`;
            
            // Format penanganans list
            this.penangananList = (item.penanganans || []).map(p => ({
                id: p.id,
                tanggal: p.tanggal ? new Date(p.tanggal).toLocaleDateString('id-ID', {day: 'numeric', month: 'short', year: 'numeric'}) : '-',
                tindakan_sp_label: p.tindakan_sp_label || p.tindakan_sp,
                catatan_penanganan: p.catatan_penanganan,
                hasil_kesepakatan: p.hasil_kesepakatan,
                link_gdrive: p.link_gdrive,
                lampiran_file_url: p.lampiran_file_url,
                status_label: p.status_penanganan_label || p.status_penanganan,
                handler_name: p.handler ? p.handler.full_name : null,
            }));

            this.openPenangananModal = true;
        }
    }
}
</script>
@endpush
@endsection

