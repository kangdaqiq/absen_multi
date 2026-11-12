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

    <!-- Filter -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <form method="GET" action="{{ route('pelanggaran.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
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
                                <div class="text-[11px] text-gray-400 mt-0.5">Dicatat: {{ $item->recorder?->full_name ?? '-' }}</div>
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
                    <select name="student_id" required class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white">
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
        openCreateModal: false,
        openEditModal: false,
        openKategoriModal: false,
        selectedKategoriId: '',
        formJudul: '',
        formKategori: 'ringan',
        formPoin: 5,
        formSanksi: '',
        editId: null,
        editStatus: 'tercatat',
        editTindakLanjut: '',
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
        }
    }
}
</script>
@endpush
@endsection

