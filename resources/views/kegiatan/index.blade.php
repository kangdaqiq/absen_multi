@extends('layouts.app')

@section('title', 'Kelola Kegiatan')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        🎯 Kelola Kegiatan
    </h2>
    <button @click="$dispatch('open-modal', 'modalTambahKegiatan')"
        class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-brand-600 transition">
        <i class="fas fa-plus"></i> Tambah Kegiatan
    </button>
</div>

@if(session('success'))
    <div class="mb-4 flex items-center gap-3 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-success-700 dark:border-success-500/30 dark:bg-success-500/15 dark:text-success-400">
        <i class="fas fa-check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if(session('info'))
    <div class="mb-4 flex items-center gap-3 rounded-xl border border-info-200 bg-info-50 px-4 py-3 text-info-700 dark:border-info-500/30 dark:bg-info-500/15 dark:text-info-400">
        <i class="fas fa-info-circle"></i>
        <span>{{ session('info') }}</span>
    </div>
@endif
@if(session('error'))
    <div class="mb-4 flex items-center gap-3 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-error-700 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-400">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

{{-- Kartu info cara kerja --}}
<div class="mb-6 rounded-2xl border border-brand-200 bg-brand-50 p-5 dark:border-brand-500/30 dark:bg-brand-500/10">
    <div class="flex items-start gap-3">
        <div class="mt-0.5 text-brand-500 text-lg"><i class="fas fa-info-circle"></i></div>
        <div>
            <h6 class="font-semibold text-brand-700 dark:text-brand-400 mb-1">Cara Kerja Absensi Kegiatan</h6>
            <ol class="text-sm text-brand-600 dark:text-brand-300 space-y-1 list-decimal list-inside">
                <li>Buat kegiatan dan atur tanggal serta jadwal jam kegiatan (<strong>Jam Mulai</strong> & <strong>Jam Selesai</strong>).</li>
                <li>Pastikan status kegiatan diatur ke <strong>Aktif</strong>.</li>
                <li>Siswa scan kartu RFID masing-masing pada jam kegiatan tersebut berlangsung.</li>
                <li>Sistem otomatis mencatat kehadiran siswa pada kegiatan tersebut .</li>
            </ol>
        </div>
    </div>
</div>

{{-- Tabel Kegiatan --}}
<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex justify-between items-center">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Daftar Kegiatan</h6>
        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $kegiatans->total() }} kegiatan</span>
    </div>

    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 font-medium">
                    <th class="px-5 py-4 text-left">Nama Kegiatan</th>
                    <th class="px-5 py-4 text-left">Tanggal</th>
                    <th class="px-5 py-4 text-center">Jadwal Jam</th>
                    <th class="px-5 py-4 text-center">Total Hadir</th>
                    <th class="px-5 py-4 text-center">Status</th>
                    <th class="px-5 py-4 text-center">Aktif</th>
                    <th class="px-5 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($kegiatans as $kegiatan)
                    @php $isOngoing = $kegiatan->isScheduledNow(); @endphp
                    <tr class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-800 dark:text-white/90">{{ $kegiatan->nama_kegiatan }}</p>
                            @if($kegiatan->deskripsi)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $kegiatan->deskripsi }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-gray-600 dark:text-gray-400 whitespace-nowrap">
                            <div class="flex flex-col gap-1">
                                <span class="font-medium text-gray-800 dark:text-white/90">{{ \Carbon\Carbon::parse($kegiatan->tanggal_mulai)->format('d/m/Y') }}</span>
                                <span class="inline-flex items-center self-start px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                                    {{ $kegiatan->formatted_hari }}
                                </span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-center text-gray-600 dark:text-gray-400 font-mono whitespace-nowrap">
                            @if($kegiatan->jam_mulai && $kegiatan->jam_selesai)
                                {{ \Carbon\Carbon::parse($kegiatan->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($kegiatan->jam_selesai)->format('H:i') }}
                            @else
                                <span class="text-gray-400 text-xs italic">-</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center">
                            <a href="{{ route('kegiatan.attendance', $kegiatan->id) }}"
                               class="inline-flex items-center gap-1 rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-600 hover:bg-success-100 dark:bg-success-500/15 dark:text-success-400 transition">
                                <i class="fas fa-users"></i>
                                {{ $kegiatan->total_hadir ?? 0 }}
                            </a>
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if($isOngoing)
                                <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-3 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-400 animate-pulse">
                                    <span class="h-2 w-2 rounded-full bg-success-500"></span>
                                    Sedang Berjalan
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    <span class="h-2 w-2 rounded-full bg-gray-400"></span>
                                    Selesai / Belum Mulai
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center">
                            @if($kegiatan->is_active)
                                <span class="inline-flex rounded-full bg-success-50 px-2 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-400">Aktif</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center justify-center gap-2 flex-wrap">
                                {{-- Rekap Absensi --}}
                                <a href="{{ route('kegiatan.attendance', $kegiatan->id) }}"
                                    class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-3 py-1.5 text-xs font-medium text-brand-600 hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400 transition"
                                    title="Lihat Rekap Absensi">
                                    <i class="fas fa-list-alt"></i> Rekap
                                </a>

                                {{-- Edit --}}
                                <button
                                    @click="$dispatch('open-modal', 'modalEditKegiatan-{{ $kegiatan->id }}')"
                                    class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-300 transition"
                                    title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>

                                {{-- Hapus --}}
                                <button
                                    @click="$dispatch('open-modal', 'modalHapusKegiatan-{{ $kegiatan->id }}')"
                                    class="rounded-lg p-2 text-error-500 hover:bg-error-50 hover:text-error-700 dark:hover:bg-error-500/15 transition"
                                    title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>

                    {{-- Modal Edit Kegiatan --}}
                    <x-ui.modal id="modalEditKegiatan-{{ $kegiatan->id }}" :is-open="false">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Edit Kegiatan</h3>
                                <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:hover:text-white"><i class="fas fa-times"></i></button>
                            </div>
                            <form action="{{ route('kegiatan.update', $kegiatan->id) }}" method="POST">
                                @csrf @method('PUT')
                                <div class="space-y-4">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Kegiatan <span class="text-error-500">*</span></label>
                                        <input type="text" name="nama_kegiatan" value="{{ $kegiatan->nama_kegiatan }}" required
                                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi</label>
                                        <textarea name="deskripsi" rows="2"
                                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">{{ $kegiatan->deskripsi }}</textarea>
                                    </div>
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <div class="sm:w-1/3">
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Mulai <span class="text-error-500">*</span></label>
                                            <input type="date" name="tanggal_mulai" value="{{ $kegiatan->tanggal_mulai->format('Y-m-d') }}" required
                                                class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                        </div>
                                        <div class="flex-1" x-data="{ 
                                            selected: '{{ $kegiatan->frekuensi ?? 'harian' }}',
                                            days: {{ json_encode(array_map('intval', is_array($kegiatan->hari) ? $kegiatan->hari : [1, 2, 3, 4, 5, 6, 7])) }}
                                        }">
                                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Frekuensi Kegiatan <span class="text-error-500">*</span></label>
                                            <input type="hidden" name="frekuensi" :value="selected">
                                            <div class="flex flex-wrap items-center gap-3 mt-2">
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input type="radio" name="frekuensi_choice_{{ $kegiatan->id }}" :checked="selected === 'sekali'" @change="selected = 'sekali'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                                    <span class="text-xs text-gray-700 dark:text-gray-300">Sekali (Insidental)</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input type="radio" name="frekuensi_choice_{{ $kegiatan->id }}" :checked="selected === 'harian'" @change="selected = 'harian'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                                    <span class="text-xs text-gray-700 dark:text-gray-300">Harian</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input type="radio" name="frekuensi_choice_{{ $kegiatan->id }}" :checked="selected === 'mingguan'" @change="selected = 'mingguan'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                                    <span class="text-xs text-gray-700 dark:text-gray-300">Mingguan</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input type="radio" name="frekuensi_choice_{{ $kegiatan->id }}" :checked="selected === 'bulanan'" @change="selected = 'bulanan'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                                    <span class="text-xs text-gray-700 dark:text-gray-300">Bulanan</span>
                                                </label>
                                            </div>

                                            {{-- Options hari jika harian --}}
                                            <div x-show="selected === 'harian'" x-transition class="mt-3 p-3 rounded-xl border border-gray-200 bg-gray-50/70 dark:border-gray-800 dark:bg-gray-900/50">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Pilih Hari Kegiatan:</span>
                                                    <div class="flex items-center gap-2 text-xs">
                                                        <button type="button" @click="days = [1,2,3,4,5,6,7]" class="text-brand-600 hover:underline dark:text-brand-400 font-medium">Semua Hari</button>
                                                        <span class="text-gray-300 dark:text-gray-700">•</span>
                                                        <button type="button" @click="days = [1,2,3,4,5]" class="text-brand-600 hover:underline dark:text-brand-400 font-medium">Senin - Jumat</button>
                                                    </div>
                                                </div>
                                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                                    <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                                        <input type="checkbox" name="hari[]" :value="1" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                                        <span>Senin</span>
                                                    </label>
                                                    <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                                        <input type="checkbox" name="hari[]" :value="2" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                                        <span>Selasa</span>
                                                    </label>
                                                    <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                                        <input type="checkbox" name="hari[]" :value="3" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                                        <span>Rabu</span>
                                                    </label>
                                                    <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                                        <input type="checkbox" name="hari[]" :value="4" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                                        <span>Kamis</span>
                                                    </label>
                                                    <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                                        <input type="checkbox" name="hari[]" :value="5" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                                        <span>Jumat</span>
                                                    </label>
                                                    <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                                        <input type="checkbox" name="hari[]" :value="6" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                                        <span>Sabtu</span>
                                                    </label>
                                                    <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                                        <input type="checkbox" name="hari[]" :value="7" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                                        <span>Minggu</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-3">
                                        <div class="flex-1">
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Mulai <span class="text-error-500">*</span></label>
                                            <input type="time" name="jam_mulai" value="{{ $kegiatan->jam_mulai ? \Carbon\Carbon::parse($kegiatan->jam_mulai)->format('H:i') : '' }}" required
                                                class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                        </div>
                                        <div class="flex-1">
                                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Selesai <span class="text-error-500">*</span></label>
                                            <input type="time" name="jam_selesai" value="{{ $kegiatan->jam_selesai ? \Carbon\Carbon::parse($kegiatan->jam_selesai)->format('H:i') : '' }}" required
                                                class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox" name="is_active" id="edit_is_active_{{ $kegiatan->id }}" value="1"
                                            {{ $kegiatan->is_active ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                                        <label for="edit_is_active_{{ $kegiatan->id }}" class="text-sm text-gray-700 dark:text-gray-300">Kegiatan Aktif</label>
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end gap-3">
                                    <button type="button" @click="open = false"
                                        class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                                    <button type="submit"
                                        class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">Simpan</button>
                                </div>
                            </form>
                        </div>
                    </x-ui.modal>

                    {{-- Modal Hapus Kegiatan --}}
                    <x-ui.modal id="modalHapusKegiatan-{{ $kegiatan->id }}" :is-open="false">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-xl font-bold text-error-500">Hapus Kegiatan</h3>
                                <button @click="open = false" class="text-gray-500 hover:text-gray-800"><i class="fas fa-times"></i></button>
                            </div>
                            <p class="text-gray-700 dark:text-gray-300 mb-2">Yakin ingin menghapus kegiatan:</p>
                            <p class="font-bold text-gray-900 dark:text-white text-lg mb-4">{{ $kegiatan->nama_kegiatan }}</p>
                            <div class="p-3 bg-error-50 text-error-700 rounded-lg text-sm dark:bg-error-500/15 dark:text-error-400 mb-5">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Semua data absensi kegiatan ini akan ikut terhapus.
                            </div>
                            <form action="{{ route('kegiatan.destroy', $kegiatan->id) }}" method="POST">
                                @csrf @method('DELETE')
                                <div class="flex justify-end gap-3">
                                    <button type="button" @click="open = false"
                                        class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                                    <button type="submit"
                                        class="rounded-lg bg-error-500 px-4 py-2 text-white hover:bg-error-600">Hapus</button>
                                </div>
                            </form>
                        </div>
                    </x-ui.modal>

                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-10 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center gap-2">
                                <i class="fas fa-calendar-times text-4xl text-gray-300 dark:text-gray-600"></i>
                                <p>Belum ada kegiatan. Klik tombol <strong>Tambah Kegiatan</strong> untuk membuat.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($kegiatans->hasPages())
    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
        {{ $kegiatans->links('vendor.pagination.tailwind') }}
    </div>
    @endif
</div>

{{-- Modal Tambah Kegiatan --}}
<x-ui.modal id="modalTambahKegiatan" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">
                <i class="fas fa-plus-circle text-brand-500 mr-2"></i>Tambah Kegiatan Baru
            </h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('kegiatan.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Kegiatan <span class="text-error-500">*</span></label>
                    <input type="text" name="nama_kegiatan" required placeholder="Contoh: Pramuka, OSIS, Ekskul Basket..."
                        class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi</label>
                    <textarea name="deskripsi" rows="2" placeholder="Keterangan singkat kegiatan (opsional)"
                        class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white"></textarea>
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="sm:w-1/3">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal Mulai <span class="text-error-500">*</span></label>
                        <input type="date" name="tanggal_mulai" value="{{ now()->format('Y-m-d') }}" required
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div class="flex-1" x-data="{ selected: 'harian', days: [1, 2, 3, 4, 5, 6, 7] }">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Frekuensi Kegiatan <span class="text-error-500">*</span></label>
                        <input type="hidden" name="frekuensi" :value="selected">
                        <div class="flex flex-wrap items-center gap-3 mt-2">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="frekuensi_choice_tambah" :checked="selected === 'sekali'" @change="selected = 'sekali'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                <span class="text-xs text-gray-700 dark:text-gray-300">Sekali (Insidental)</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="frekuensi_choice_tambah" :checked="selected === 'harian'" @change="selected = 'harian'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                <span class="text-xs text-gray-700 dark:text-gray-300">Harian</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="frekuensi_choice_tambah" :checked="selected === 'mingguan'" @change="selected = 'mingguan'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                <span class="text-xs text-gray-700 dark:text-gray-300">Mingguan</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" name="frekuensi_choice_tambah" :checked="selected === 'bulanan'" @change="selected = 'bulanan'" class="text-brand-500 focus:ring-brand-500 dark:border-gray-800 dark:bg-gray-900">
                                <span class="text-xs text-gray-700 dark:text-gray-300">Bulanan</span>
                            </label>
                        </div>

                        {{-- Options hari jika harian --}}
                        <div x-show="selected === 'harian'" x-transition class="mt-3 p-3 rounded-xl border border-gray-200 bg-gray-50/70 dark:border-gray-800 dark:bg-gray-900/50">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Pilih Hari Kegiatan:</span>
                                <div class="flex items-center gap-2 text-xs">
                                    <button type="button" @click="days = [1,2,3,4,5,6,7]" class="text-brand-600 hover:underline dark:text-brand-400 font-medium">Semua Hari</button>
                                    <span class="text-gray-300 dark:text-gray-700">•</span>
                                    <button type="button" @click="days = [1,2,3,4,5]" class="text-brand-600 hover:underline dark:text-brand-400 font-medium">Senin - Jumat</button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                    <input type="checkbox" name="hari[]" :value="1" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                    <span>Senin</span>
                                </label>
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                    <input type="checkbox" name="hari[]" :value="2" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                    <span>Selasa</span>
                                </label>
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                    <input type="checkbox" name="hari[]" :value="3" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                    <span>Rabu</span>
                                </label>
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                    <input type="checkbox" name="hari[]" :value="4" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                    <span>Kamis</span>
                                </label>
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                    <input type="checkbox" name="hari[]" :value="5" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                    <span>Jumat</span>
                                </label>
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                    <input type="checkbox" name="hari[]" :value="6" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                    <span>Sabtu</span>
                                </label>
                                <label class="flex items-center gap-1.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer p-1 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                    <input type="checkbox" name="hari[]" :value="7" x-model.number="days" class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800">
                                    <span>Minggu</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div class="flex-1">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Mulai <span class="text-error-500">*</span></label>
                        <input type="time" name="jam_mulai" required
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div class="flex-1">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Selesai <span class="text-error-500">*</span></label>
                        <input type="time" name="jam_selesai" required
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit"
                    class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">
                    <i class="fas fa-save mr-1"></i> Simpan Kegiatan
                </button>
            </div>
        </form>
    </div>
</x-ui.modal>

@endsection
