@extends('layouts.app')

@section('title', 'Jam Pelajaran')

@section('content')
<x-page-header 
    title="Jam Pelajaran" 
    subtitle="Kelola konfigurasi sesi waktu dan jam pelajaran sekolah" 
    icon="fas fa-clock"
    x-data="{}">
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('jadwal-pelajaran.index') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-dark dark:text-gray-200 dark:hover:bg-gray-800 transition shadow-sm">
            <i class="fas fa-calendar-alt text-brand-500"></i> Jadwal Pelajaran
        </a>
        <button type="button" @click="$dispatch('open-modal', 'modalPresetJam')"
            class="inline-flex items-center gap-2 rounded-lg border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-medium text-brand-600 hover:bg-brand-100 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-400 transition shadow-sm">
            <i class="fas fa-magic"></i> Buat Preset Otomatis
        </button>
        <button type="button" @click="$dispatch('open-modal', 'modalTambahJam')"
            class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 transition shadow-sm">
            <i class="fas fa-plus"></i> Tambah Jam Pelajaran
        </button>
    </div>
</x-page-header>

@if(session('success'))
    <div class="mb-5 flex items-center gap-3 rounded-xl border border-success-200 bg-success-50 p-4 text-success-800 dark:border-success-500/20 dark:bg-success-500/10 dark:text-success-400 text-sm">
        <i class="fas fa-check-circle text-lg"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if($errors->any())
    <div class="mb-5 rounded-xl border border-error-200 bg-error-50 p-4 text-error-800 dark:border-error-500/20 dark:bg-error-500/10 dark:text-error-400 text-sm">
        <ul class="list-disc pl-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="space-y-4" x-data="{
    toggleAll(openState) {
        window.dispatchEvent(new CustomEvent('toggle-all-sessions', { detail: { open: openState } }));
    }
}">
    <!-- Header Control Bar: Search, Filter, Bulk Actions & Global Controls -->
    <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:px-5 sm:py-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <!-- Global Check All -->
                <label class="inline-flex items-center gap-2 cursor-pointer bg-gray-50 dark:bg-gray-800 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 hover:bg-gray-100 transition" title="Pilih Semua Jam di Semua Sesi">
                    <input type="checkbox" id="checkAll" class="rounded border-gray-300 text-brand-600 focus:border-brand-500 focus:ring-brand-500 cursor-pointer">
                    <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">Pilih Semua</span>
                </label>

                <!-- Bulk Action Buttons -->
                <button type="button" id="btnBulkEditJam"
                    class="hidden rounded-xl bg-brand-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-brand-600 transition shadow-sm"
                    @click="$dispatch('open-modal', 'modalBulkEditJam')">
                    <i class="fas fa-edit mr-1"></i> Edit Masal (<span id="bulkEditCount">0</span>)
                </button>
                <button type="button" id="btnBulkDeleteJam"
                    class="hidden rounded-xl bg-error-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-error-600 transition shadow-sm"
                    @click="$dispatch('open-modal', 'modalBulkDeleteJam')">
                    <i class="fas fa-trash-alt mr-1"></i> Hapus Masal (<span id="bulkDeleteCount">0</span>)
                </button>

                <!-- Expand / Collapse All Buttons -->
                @if($groupedSessions->isNotEmpty())
                    <div class="flex items-center gap-1.5 border-l border-gray-200 dark:border-gray-700 pl-3">
                        <button type="button" @click="toggleAll(true)"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-brand-600 hover:bg-brand-50 dark:hover:bg-brand-500/10 transition"
                            title="Buka seluruh kelompok sesi">
                            <i class="fas fa-chevron-circle-down text-xs"></i> Buka Semua
                        </button>
                        <span class="text-gray-300 dark:text-gray-600">•</span>
                        <button type="button" @click="toggleAll(false)"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-brand-600 hover:bg-brand-50 dark:hover:bg-brand-500/10 transition"
                            title="Tutup seluruh kelompok sesi">
                            <i class="fas fa-chevron-circle-up text-xs"></i> Tutup Semua
                        </button>
                    </div>
                @endif
            </div>

            <!-- Search & Filter Form -->
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 w-full lg:w-auto">
                <form action="{{ route('jam-pelajaran.index') }}" method="GET" class="flex flex-wrap sm:flex-nowrap items-center gap-2 w-full sm:w-auto">
                    <!-- Filter Hari -->
                    <select name="hari" onchange="this.form.submit()"
                        class="rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="">Semua Hari</option>
                        @foreach($hariList as $h)
                            <option value="{{ $h }}" {{ request('hari') == $h ? 'selected' : '' }}>{{ $h }}</option>
                        @endforeach
                    </select>

                    <!-- Input Search -->
                    <div class="relative w-full sm:w-64">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Sesi Jam..."
                            oninput="clearTimeout(this.delay); this.delay = setTimeout(() => { this.form.submit() }, 500);"
                            class="w-full rounded-xl border border-gray-200 bg-transparent py-2 pl-3.5 pr-9 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <button type="submit" class="absolute right-0 top-0 h-full px-3 text-gray-400 hover:text-brand-500">
                            <i class="fas fa-search text-xs"></i>
                        </button>
                    </div>

                    @if(request('search') || request('hari'))
                        <a href="{{ route('jam-pelajaran.index') }}" class="rounded-xl border border-gray-200 p-2 text-xs text-gray-500 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800" title="Reset Pencarian & Filter">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </form>

                <span class="text-xs font-semibold px-3 py-2 rounded-xl bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 whitespace-nowrap">
                    {{ $groupedSessions->count() }} Sesi ({{ $jamPelajarans->count() }} Jam)
                </span>
            </div>
        </div>
    </div>

    <!-- Daftar Card Utama Kelompok Sesi -->
    @forelse($groupedSessions as $session)
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark overflow-hidden transition-all duration-200 hover:border-brand-300 dark:hover:border-brand-500/40"
             x-data="{
                isOpen: {{ (request('search') || request('hari')) ? 'true' : 'false' }},
                init() {
                    window.addEventListener('toggle-all-sessions', (e) => {
                        this.isOpen = e.detail.open;
                    });
                }
             }">
            <!-- Header Card Utama Sesi (Clickable untuk Buka / Tutup) -->
            <div @click="isOpen = !isOpen"
                class="p-4 sm:p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 cursor-pointer select-none transition bg-white hover:bg-gray-50/70 dark:bg-gray-dark dark:hover:bg-gray-800/40">
                <div class="flex items-center gap-3.5">
                    <!-- Checkbox Group Sesi -->
                    <div @click.stop class="flex items-center">
                        <input type="checkbox"
                            class="checkGroupSession rounded border-gray-300 text-brand-600 focus:ring-brand-500 cursor-pointer h-4 w-4"
                            data-group-id="{{ $loop->index }}"
                            title="Pilih seluruh jam di sesi ini">
                    </div>

                    <!-- Icon Sesi -->
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-500/10 text-brand-500 dark:bg-brand-500/20 dark:text-brand-400 font-bold flex-shrink-0 shadow-sm">
                        <i class="fas fa-layer-group text-lg"></i>
                    </div>

                    <!-- Judul & Info Singkat Sesi -->
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-gray-800 dark:text-white/90">
                                {{ $session->nama }}
                            </h3>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $session->is_all_active ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400' : 'bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400' }}">
                                <i class="fas fa-circle text-[6px] mr-1"></i>
                                {{ $session->active_count }}/{{ $session->total_jam }} Aktif
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            <span class="inline-flex items-center gap-1 font-semibold text-xs text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-500/10 px-2 py-0.5 rounded-md">
                                <i class="far fa-clock"></i> {{ $session->total_jam }} Jam ({{ $session->label_jam_ke }})
                            </span>
                            <span class="inline-flex items-center gap-1 font-mono text-xs text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-md">
                                <i class="far fa-hourglass"></i> {{ $session->rentang_waktu }}
                            </span>
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                • Durasi {{ $session->total_durasi }} Menit
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Bagian Kanan Header Sesi: Hari Berlaku, Terpakai & Tombol Buka -->
                <div class="flex flex-wrap items-center justify-between md:justify-end gap-3 pt-2 md:pt-0 border-t md:border-t-0 border-gray-100 dark:border-gray-800">
                    <!-- Hari Berlaku -->
                    <div class="flex flex-wrap items-center gap-1" @click.stop>
                        @if(count($session->hari_unique) > 0)
                            @foreach($session->hari_unique as $dh)
                                <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                                    {{ $dh }}
                                </span>
                            @endforeach
                        @else
                            <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-600 dark:bg-blue-500/15 dark:text-blue-400">
                                Semua Hari
                            </span>
                        @endif
                    </div>

                    <!-- Terpakai -->
                    <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 whitespace-nowrap">
                        <i class="fas fa-calendar-check text-gray-400"></i> {{ $session->total_jadwal }} Jadwal
                    </span>

                    <!-- Tombol Toggle Accordion -->
                    <button type="button"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 bg-gray-50 text-xs font-semibold text-gray-700 hover:bg-brand-50 hover:text-brand-600 hover:border-brand-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-brand-500/10 dark:hover:text-brand-400 transition shadow-xs">
                        <span x-text="isOpen ? 'Tutup Sesi' : 'Buka {{ $session->total_jam }} Jam'"></span>
                        <i class="fas fa-chevron-down text-xs transition-transform duration-300" :class="{ 'rotate-180': isOpen }"></i>
                    </button>
                </div>
            </div>

            <!-- List Jam Pelajaran (Muncul saat Card Utama Diklik) -->
            <div x-show="isOpen" x-collapse class="border-t border-gray-200 dark:border-gray-800">
                <div class="max-w-full overflow-x-auto">
                    <table class="w-full table-auto text-sm">
                        <thead>
                            <tr class="bg-gray-50/80 text-left dark:bg-gray-800/40 text-gray-600 dark:text-gray-400 font-medium text-xs border-b border-gray-100 dark:border-gray-800">
                                <th class="px-4 py-3.5 w-12 text-center xl:pl-6">
                                    <span class="text-[11px] font-semibold text-gray-400">Pilih</span>
                                </th>
                                <th class="px-5 py-3.5 w-16 text-center">Urutan</th>
                                <th class="px-5 py-3.5 text-center">Jam Ke</th>
                                <th class="px-5 py-3.5 text-center">Rentang Waktu</th>
                                <th class="px-5 py-3.5 text-center">Durasi</th>
                                <th class="px-5 py-3.5 text-center">Hari Berlaku</th>
                                <th class="px-5 py-3.5">Keterangan</th>
                                <th class="px-5 py-3.5 text-center">Terpakai</th>
                                <th class="px-5 py-3.5 text-center">Status</th>
                                <th class="px-5 py-3.5 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 bg-white dark:bg-gray-dark">
                            @foreach($session->items as $jam)
                                <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-800/30 transition-colors">
                                    <!-- Checkbox -->
                                    <td class="px-4 py-3.5 text-center xl:pl-6">
                                        <input type="checkbox" name="selected_ids[]" value="{{ $jam->id }}"
                                            class="checkItem rounded border-gray-300 text-brand-600 focus:border-brand-500 focus:ring-brand-500 cursor-pointer h-4 w-4"
                                            data-group-id="{{ $loop->parent->index }}">
                                    </td>

                                    <!-- Urutan -->
                                    <td class="px-5 py-3.5 text-center">
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-gray-100 font-bold text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            {{ $jam->urutan }}
                                        </span>
                                    </td>

                                    <!-- Jam Ke -->
                                    <td class="px-5 py-3.5 text-center">
                                        @if($jam->jam_ke)
                                            <span class="inline-flex rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 font-mono">
                                                Jam Ke-{{ $jam->jam_ke }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>

                                    <!-- Rentang Waktu -->
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1.5 font-mono font-bold text-gray-800 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 px-3 py-1 rounded-lg text-xs">
                                            <i class="far fa-clock text-brand-500"></i>
                                            {{ $jam->formatted_jam_mulai }} - {{ $jam->formatted_jam_selesai }}
                                        </span>
                                    </td>

                                    <!-- Durasi -->
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        <span class="inline-flex rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-500/15 dark:text-purple-400">
                                            {{ $jam->durasi_menit }} Menit
                                        </span>
                                    </td>

                                    <!-- Hari Berlaku -->
                                    <td class="px-5 py-3.5 text-center">
                                        @if($jam->hari)
                                            <div class="flex flex-wrap items-center justify-center gap-1 max-w-[200px] mx-auto">
                                                @foreach($jam->hari_array as $dh)
                                                    <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                                                        {{ $dh }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-600 dark:bg-blue-500/15 dark:text-blue-400">
                                                Semua Hari
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Keterangan -->
                                    <td class="px-5 py-3.5">
                                        <span class="text-xs text-gray-600 dark:text-gray-400">
                                            {{ $jam->keterangan ?: '-' }}
                                        </span>
                                    </td>

                                    <!-- Terpakai -->
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-400">
                                            {{ $jam->jadwal_pelajarans_count }} Jadwal
                                        </span>
                                    </td>

                                    <!-- Status Toggle -->
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        <form action="{{ route('jam-pelajaran.toggle-status', $jam->id) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium transition {{ $jam->is_active ? 'bg-success-50 text-success-600 hover:bg-success-100 dark:bg-success-500/15 dark:text-success-500' : 'bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400' }}"
                                                title="{{ $jam->is_active ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan' }}">
                                                <i class="fas fa-{{ $jam->is_active ? 'check-circle' : 'ban' }}"></i>
                                                {{ $jam->is_active ? 'Aktif' : 'Nonaktif' }}
                                            </button>
                                        </form>
                                    </td>

                                    <!-- Aksi -->
                                    <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1.5" x-data="{}">
                                            <button type="button"
                                                class="btnEditJam text-warning-500 hover:text-warning-700 hover:bg-warning-50 dark:hover:bg-warning-500/10 p-2 rounded-lg transition"
                                                data-id="{{ $jam->id }}"
                                                data-nama="{{ $jam->nama_jam }}"
                                                data-jam-ke="{{ $jam->jam_ke }}"
                                                data-jam-mulai="{{ $jam->formatted_jam_mulai }}"
                                                data-jam-selesai="{{ $jam->formatted_jam_selesai }}"
                                                data-hari="{{ $jam->hari }}"
                                                data-urutan="{{ $jam->urutan }}"
                                                data-is-active="{{ $jam->is_active ? '1' : '0' }}"
                                                data-keterangan="{{ $jam->keterangan }}"
                                                @click="$dispatch('open-modal', 'modalEditJam')"
                                                title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <button type="button"
                                                class="btnHapusJam text-error-500 hover:text-error-700 hover:bg-error-50 dark:hover:bg-error-500/10 p-2 rounded-lg transition"
                                                data-id="{{ $jam->id }}"
                                                data-nama="{{ $jam->nama_jam }} (Jam Ke-{{ $jam->jam_ke }})"
                                                @click="$dispatch('open-modal', 'modalHapusJam')"
                                                title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Footer Card Sesi -->
                <div class="p-3.5 px-5 bg-gray-50/50 dark:bg-gray-800/30 border-t border-gray-100 dark:border-gray-800 flex flex-wrap items-center justify-between gap-3 text-xs">
                    <span class="text-gray-500 dark:text-gray-400">
                        Total <strong>{{ $session->total_jam }} Jam Pelajaran</strong> di Sesi {{ $session->nama }}.
                    </span>
                    <button type="button"
                        class="btnTambahJamKeSesi text-brand-600 hover:text-brand-700 dark:text-brand-400 font-semibold inline-flex items-center gap-1.5 transition"
                        data-nama-sesi="{{ $session->nama }}"
                        @click="$dispatch('open-modal', 'modalTambahJam')">
                        <i class="fas fa-plus-circle"></i> Tambah Jam ke Sesi Ini
                    </button>
                </div>
            </div>
        </div>
    @empty
        <!-- Empty State -->
        <div class="rounded-2xl border border-gray-200 bg-white p-12 text-center text-gray-500 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark dark:text-gray-400">
            <div class="flex flex-col items-center justify-center">
                @if(request('search') || request('hari'))
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 dark:bg-amber-900/20 text-amber-500 mb-3 shadow-xs">
                        <i class="fas fa-search text-2xl"></i>
                    </div>
                    <p class="font-bold text-lg text-gray-800 dark:text-gray-200">Tidak ada sesi yang cocok</p>
                    <p class="text-xs text-gray-400 mt-1 max-w-sm">
                        Coba periksa kembali kata kunci pencarian Anda atau ubah filter hari.
                    </p>
                    <a href="{{ route('jam-pelajaran.index') }}" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition shadow-xs">
                        <i class="fas fa-undo text-xs"></i> Reset Filter Pencarian
                    </a>
                @else
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 dark:bg-brand-500/10 text-brand-500 mb-3 shadow-xs">
                        <i class="far fa-clock text-3xl"></i>
                    </div>
                    <p class="font-bold text-lg text-gray-800 dark:text-gray-200">Belum ada data jam pelajaran</p>
                    <p class="text-xs text-gray-400 mt-1 max-w-md">
                        Gunakan tombol <strong>"Buat Preset Otomatis"</strong> untuk langsung membuat 8-10 sesi jam secara instan, atau klik <strong>"Tambah Jam Pelajaran"</strong>.
                    </p>
                    <div class="mt-5 flex items-center gap-3">
                        <button type="button" @click="$dispatch('open-modal', 'modalPresetJam')"
                            class="inline-flex items-center gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 py-2 text-xs font-semibold text-brand-600 hover:bg-brand-100 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-400 transition">
                            <i class="fas fa-magic"></i> Buat Preset Otomatis
                        </button>
                        <button type="button" @click="$dispatch('open-modal', 'modalTambahJam')"
                            class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-600 transition shadow-sm">
                            <i class="fas fa-plus"></i> Tambah Jam Pelajaran
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endforelse
</div>

<!-- ========================= MODALS ========================= -->

<!-- Modal Tambah Jam Pelajaran -->
<x-ui.modal id="modalTambahJam" :is-open="false" x-data="{}">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Tambah Jam Pelajaran Baru</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('jam-pelajaran.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Sesi <span class="text-error-500">*</span></label>
                        <input type="text" name="nama_jam" id="tambah_nama_jam" required placeholder="Contoh: Reguler, Kelas 1, Khusus"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Ke</label>
                        <input type="text" name="jam_ke" placeholder="Contoh: 1 atau 2"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Mulai <span class="text-error-500">*</span></label>
                        <input type="time" name="jam_mulai" required
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Selesai <span class="text-error-500">*</span></label>
                        <input type="time" name="jam_selesai" required
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor Urutan</label>
                        <input type="number" name="urutan" min="1" placeholder="Otomatis jika dikosongkan"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <p class="text-[11px] text-gray-400 mt-1">Urutan tampil sesi jam pelajaran</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan (Opsional)</label>
                        <input type="text" name="keterangan" placeholder="Contoh: Sesi Pagi, Istirahat setelah jam ini, dsb."
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>

                <!-- Hari Berlaku Checkbox -->
                <div class="rounded-xl border border-gray-200/80 bg-gray-50/50 p-3.5 dark:border-gray-800 dark:bg-gray-800/30">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                            <i class="far fa-calendar-check mr-1 text-brand-500"></i> Hari Berlaku
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="button" id="btnSelectAllTambahHari" class="text-[11px] font-semibold text-brand-600 hover:underline dark:text-brand-400">Pilih Semua</button>
                            <span class="text-gray-300 dark:text-gray-700">|</span>
                            <button type="button" id="btnClearTambahHari" class="text-[11px] font-semibold text-gray-500 hover:underline dark:text-gray-400">Kosongkan</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
                        @foreach($hariList as $h)
                            <label class="flex items-center gap-1.5 p-2 rounded-lg border border-gray-200 dark:border-gray-700/80 bg-white dark:bg-gray-900 cursor-pointer hover:border-brand-500 transition text-xs font-medium text-gray-700 dark:text-gray-300 select-none">
                                <input type="checkbox" name="hari[]" value="{{ $h }}" class="tambah_hari_checkbox rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span>{{ $h }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1.5">Centang hari berlakunya sesi ini. Biarkan kosong jika berlaku setiap hari.</p>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit"
                    class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Simpan Jam Pelajaran</button>
            </div>
        </form>
    </div>
</x-ui.modal>

<!-- Modal Edit Jam Pelajaran -->
<x-ui.modal id="modalEditJam" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Edit Jam Pelajaran</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="#" method="POST" id="formEditJam">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Sesi <span class="text-error-500">*</span></label>
                        <input type="text" name="nama_jam" id="edit_nama_jam" required
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Ke (Sesi)</label>
                        <input type="text" name="jam_ke" id="edit_jam_ke"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Mulai <span class="text-error-500">*</span></label>
                        <input type="time" name="jam_mulai" id="edit_jam_mulai" required
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Selesai <span class="text-error-500">*</span></label>
                        <input type="time" name="jam_selesai" id="edit_jam_selesai" required
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nomor Urutan</label>
                        <input type="number" name="urutan" id="edit_urutan" min="1"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <p class="text-[11px] text-gray-400 mt-1">Urutan tampil sesi jam pelajaran</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan (Opsional)</label>
                        <input type="text" name="keterangan" id="edit_keterangan"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>

                <!-- Hari Berlaku Checkbox -->
                <div class="rounded-xl border border-gray-200/80 bg-gray-50/50 p-3.5 dark:border-gray-800 dark:bg-gray-800/30">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                            <i class="far fa-calendar-check mr-1 text-brand-500"></i> Hari Berlaku
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="button" id="btnSelectAllEditHari" class="text-[11px] font-semibold text-brand-600 hover:underline dark:text-brand-400">Pilih Semua</button>
                            <span class="text-gray-300 dark:text-gray-700">|</span>
                            <button type="button" id="btnClearEditHari" class="text-[11px] font-semibold text-gray-500 hover:underline dark:text-gray-400">Kosongkan</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
                        @foreach($hariList as $h)
                            <label class="flex items-center gap-1.5 p-2 rounded-lg border border-gray-200 dark:border-gray-700/80 bg-white dark:bg-gray-900 cursor-pointer hover:border-brand-500 transition text-xs font-medium text-gray-700 dark:text-gray-300 select-none">
                                <input type="checkbox" name="hari[]" value="{{ $h }}" class="edit_hari_checkbox rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span>{{ $h }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1.5">Centang hari berlakunya sesi ini. Biarkan kosong jika berlaku setiap hari.</p>
                </div>

                <div>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1"
                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Status Aktif</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit"
                    class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</x-ui.modal>

<!-- Modal Preset Otomatis -->
<x-ui.modal id="modalPresetJam" :is-open="false" x-data="{}">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">⚡ Buat Preset Cepat Jam Pelajaran</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Otomatis buat rentang jam pelajaran berturut-turut beserta jeda istirahat</p>
            </div>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>

        <form action="{{ route('jam-pelajaran.generate-presets') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <!-- Nama Sesi Prefix -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nama Sesi (Opsional)
                    </label>
                    <input type="text" name="nama_sesi_prefix" placeholder="Contoh: Reguler, Kelas 1, Pagi, Siang, dll."
                        class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <p class="text-[11px] text-gray-400 mt-1">
                        Format nama sesi: <strong>[Nama Sesi]</strong> (Contoh: <em>Kelas 1</em>, <em>Reguler</em>, <em>Pagi</em>). Jika dikosongkan, otomatis menggunakan <em>Jam Ke 1, Jam Ke 2, dst</em>.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Mulai Masuk <span class="text-error-500">*</span></label>
                        <input type="time" name="jam_mulai_pertama" value="07:15" required
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <p class="text-[11px] text-gray-400 mt-1">Waktu mulai Jam Ke-1 (misal 07:00 atau 07:15)</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Durasi Per Jam (Menit) <span class="text-error-500">*</span></label>
                        <select name="durasi_menit" id="preset_durasi_menit" required
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                            <option value="35">35 Menit (SD)</option>
                            <option value="40">40 Menit (SMP)</option>
                            <option value="45" selected>45 Menit (SMA / SMK)</option>
                            <option value="50">50 Menit</option>
                            <option value="90">90 Menit (Blok 2 Jam)</option>
                            <option value="custom">-- Custom (Isi Sendiri) --</option>
                        </select>
                        <div id="preset_durasi_custom_container" class="hidden mt-2">
                            <div class="flex items-center gap-2">
                                <input type="number" name="durasi_menit_custom" id="preset_durasi_custom" min="10" max="180" placeholder="Masukkan menit (cth: 30, 42)"
                                    class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-1.5 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">Menit</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Total Sesi Jam <span class="text-error-500">*</span></label>
                        <input type="number" name="jumlah_jam" id="preset_jumlah_jam" min="1" max="20" value="8" required
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <p class="text-[11px] text-gray-400 mt-1">Total sesi jam yang akan dibuat (cth: 8 atau 10)</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah Istirahat</label>
                        <select name="jumlah_istirahat" id="preset_jumlah_istirahat"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                            <option value="1" selected>1 Kali Istirahat</option>
                            <option value="2">2 Kali Istirahat</option>
                            <option value="3">3 Kali Istirahat</option>
                            <option value="0">Tanpa Istirahat (0)</option>
                        </select>
                        <p class="text-[11px] text-gray-400 mt-1">Pilih 1, 2, atau 3 kali jeda istirahat</p>
                    </div>
                </div>

                <!-- Hari Berlaku Checkbox -->
                <div class="rounded-xl border border-gray-200/80 bg-gray-50/50 p-3.5 dark:border-gray-800 dark:bg-gray-800/30">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                            <i class="far fa-calendar-check mr-1 text-brand-500"></i> Hari Berlaku
                        </label>
                        <div class="flex items-center gap-2">
                            <button type="button" id="btnSelectAllPresetHari" class="text-[11px] font-semibold text-brand-600 hover:underline dark:text-brand-400">Pilih Semua</button>
                            <span class="text-gray-300 dark:text-gray-700">|</span>
                            <button type="button" id="btnClearPresetHari" class="text-[11px] font-semibold text-gray-500 hover:underline dark:text-gray-400">Kosongkan</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
                        @foreach($hariList as $h)
                            <label class="flex items-center gap-1.5 p-2 rounded-lg border border-gray-200 dark:border-gray-700/80 bg-white dark:bg-gray-900 cursor-pointer hover:border-brand-500 transition text-xs font-medium text-gray-700 dark:text-gray-300 select-none">
                                <input type="checkbox" name="hari[]" value="{{ $h }}" class="preset_hari_checkbox rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                <span>{{ $h }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[11px] text-gray-400 mt-1.5">Centang hari berlakunya sesi ini. Jika tidak ada yang dicentang / semua dipilih, sesi otomatis berlaku untuk <strong>Semua Hari</strong>.</p>
                </div>

                <!-- Container Pengaturan Jeda Istirahat Dinamis -->
                <div id="container_jeda_istirahat" class="space-y-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <h5 class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                            <i class="fas fa-coffee text-amber-500"></i> Pengaturan Jeda Istirahat
                        </h5>
                        <span class="text-[11px] font-medium text-brand-600 dark:text-brand-400" id="badge_jumlah_istirahat">1 Kali Istirahat</span>
                    </div>
                    <div id="list_jeda_istirahat" class="space-y-3">
                        <!-- Dimuat otomatis via Javascript -->
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit"
                    class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                    <i class="fas fa-magic mr-1"></i> Generate Sesi Jam Pelajaran
                </button>
            </div>
        </form>
    </div>
</x-ui.modal>

<!-- Modal Hapus Jam Pelajaran -->
<x-ui.modal id="modalHapusJam" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-error-500">Hapus Jam Pelajaran</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="#" method="POST" id="formHapusJam">
            @csrf
            @method('DELETE')
            <p class="text-gray-700 dark:text-gray-300 mb-6">
                Yakin ingin menghapus sesi jam pelajaran: <strong id="hapus_nama_jam" class="text-gray-900 dark:text-white"></strong>?
            </p>
            <div class="flex justify-end gap-3">
                <button type="button" @click="open = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit"
                    class="rounded-lg bg-error-500 px-4 py-2 text-sm font-medium text-white hover:bg-error-600">Hapus Sekarang</button>
            </div>
        </form>
    </div>
</x-ui.modal>

<!-- Modal Bulk Edit Jam Pelajaran -->
<x-ui.modal id="modalBulkEditJam" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Edit Masal Jam Pelajaran</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Ubah hari berlaku atau status untuk <span class="font-semibold text-brand-600" id="bulkEditModalCountText">0</span> sesi terpilih</p>
            </div>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('jam-pelajaran.bulk-update') }}" method="POST" id="formBulkEditJam">
            @csrf
            @method('PUT')
            <div id="bulkEditIdsContainer"></div>

            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Hari Berlaku</label>
                    <select name="hari_mode" id="bulk_hari_mode"
                        class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="no_change">-- Jangan Ubah --</option>
                        <option value="all">Set ke: Berlaku Semua Hari</option>
                        <option value="custom">Set ke: Pilih Hari Tertentu</option>
                    </select>

                    <div id="bulk_hari_custom_container" class="hidden mt-3 rounded-xl border border-gray-200/80 bg-gray-50/50 p-3.5 dark:border-gray-800 dark:bg-gray-800/30">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Pilih Hari</span>
                            <div class="flex items-center gap-2">
                                <button type="button" id="btnSelectAllBulkHari" class="text-[11px] font-semibold text-brand-600 hover:underline dark:text-brand-400">Pilih Semua</button>
                                <span class="text-gray-300 dark:text-gray-700">|</span>
                                <button type="button" id="btnClearBulkHari" class="text-[11px] font-semibold text-gray-500 hover:underline dark:text-gray-400">Kosongkan</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
                            @foreach($hariList as $h)
                                <label class="flex items-center gap-1.5 p-2 rounded-lg border border-gray-200 dark:border-gray-700/80 bg-white dark:bg-gray-900 cursor-pointer hover:border-brand-500 transition text-xs font-medium text-gray-700 dark:text-gray-300 select-none">
                                    <input type="checkbox" name="hari[]" value="{{ $h }}" class="bulk_hari_checkbox rounded border-gray-300 text-brand-600 focus:ring-brand-500">
                                    <span>{{ $h }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Status Aktif</label>
                    <select name="is_active"
                        class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="no_change">-- Jangan Ubah --</option>
                        <option value="1">Aktifkan Semua Terpilih</option>
                        <option value="0">Nonaktifkan Semua Terpilih</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                    <select name="keterangan_option" id="bulk_keterangan_option"
                        class="w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white mb-2">
                        <option value="no_change">-- Jangan Ubah Keterangan --</option>
                        <option value="custom">Ubah Keterangan</option>
                        <option value="null">Kosongkan Keterangan</option>
                    </select>
                    <input type="text" name="keterangan" id="bulk_keterangan_input" placeholder="Masukkan keterangan baru..."
                        class="hidden w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit"
                    class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white hover:bg-brand-600">
                    <i class="fas fa-save mr-1"></i> Update Masal
                </button>
            </div>
        </form>
    </div>
</x-ui.modal>

<!-- Modal Bulk Delete Jam Pelajaran -->
<x-ui.modal id="modalBulkDeleteJam" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-error-500">Hapus Masal Jam Pelajaran</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('jam-pelajaran.bulk-destroy') }}" method="POST" id="formBulkDeleteJam">
            @csrf
            @method('DELETE')
            <div id="bulkDeleteIdsContainer"></div>

            <div class="mb-5 rounded-xl border border-error-100 bg-error-50 p-4 dark:border-error-500/20 dark:bg-error-500/10 text-error-700 dark:text-error-400 text-sm">
                <p class="font-semibold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Perhatian</p>
                <p>Yakin ingin menghapus <strong id="bulkDeleteCountText" class="text-error-800 dark:text-error-300">0</strong> sesi jam pelajaran yang dipilih?</p>
                <p class="text-xs opacity-90 mt-1">Jadwal pelajaran yang terhubung dengan jam ini akan tetap ada, namun relasi jam pelajarannya akan dikosongkan.</p>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" @click="open = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit"
                    class="rounded-lg bg-error-500 px-4 py-2 text-sm font-medium text-white hover:bg-error-600 font-semibold">
                    <i class="fas fa-trash-alt mr-1"></i> Hapus Sekarang
                </button>
            </div>
        </form>
    </div>
</x-ui.modal>
@endsection

@push('scripts')
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script>
    $(document).ready(function() {
        // Edit Jam Handler
        $('.btnEditJam').on('click', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');
            var jamKe = $(this).data('jam-ke');
            var jamMulai = $(this).data('jam-mulai');
            var jamSelesai = $(this).data('jam-selesai');
            var hari = $(this).data('hari');
            var urutan = $(this).data('urutan');
            var isActive = $(this).data('is-active');
            var keterangan = $(this).data('keterangan');

            $('#edit_nama_jam').val(nama);
            $('#edit_jam_ke').val(jamKe);
            $('#edit_jam_mulai').val(jamMulai);
            $('#edit_jam_selesai').val(jamSelesai);
            $('#edit_urutan').val(urutan);
            $('#edit_keterangan').val(keterangan || '');
            $('#edit_is_active').prop('checked', isActive == '1' || isActive == 1);

            // Populate Checkbox Hari
            var hariStr = hari ? String(hari) : '';
            $('.edit_hari_checkbox').prop('checked', false);
            if (hariStr) {
                var days = hariStr.split(',').map(function(s) { return s.trim(); });
                $('.edit_hari_checkbox').each(function() {
                    if (days.indexOf($(this).val()) !== -1) {
                        $(this).prop('checked', true);
                    }
                });
            }

            $('#formEditJam').attr('action', '{{ url('jam-pelajaran') }}/' + id);
        });

        // Quick Actions & Mode Hari Checkboxes
        $('#btnSelectAllPresetHari').on('click', function() { $('.preset_hari_checkbox').prop('checked', true); });
        $('#btnClearPresetHari').on('click', function() { $('.preset_hari_checkbox').prop('checked', false); });

        $('#btnSelectAllTambahHari').on('click', function() { $('.tambah_hari_checkbox').prop('checked', true); });
        $('#btnClearTambahHari').on('click', function() { $('.tambah_hari_checkbox').prop('checked', false); });

        $('#btnSelectAllEditHari').on('click', function() { $('.edit_hari_checkbox').prop('checked', true); });
        $('#btnClearEditHari').on('click', function() { $('.edit_hari_checkbox').prop('checked', false); });

        $('#btnSelectAllBulkHari').on('click', function() { $('.bulk_hari_checkbox').prop('checked', true); });
        $('#btnClearBulkHari').on('click', function() { $('.bulk_hari_checkbox').prop('checked', false); });

        $('#bulk_hari_mode').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#bulk_hari_custom_container').removeClass('hidden');
            } else {
                $('#bulk_hari_custom_container').addClass('hidden');
                $('.bulk_hari_checkbox').prop('checked', false);
            }
        });

        // Hapus Jam Handler
        $('.btnHapusJam').on('click', function() {
            var id = $(this).data('id');
            var nama = $(this).data('nama');

            $('#hapus_nama_jam').text(nama);
            $('#formHapusJam').attr('action', '{{ url('jam-pelajaran') }}/' + id);
        });

        // Bulk Selection Logic
        function updateBulkButtons() {
            var checkedCount = $('.checkItem:checked').length;
            if (checkedCount > 0) {
                $('#btnBulkEditJam').removeClass('hidden');
                $('#btnBulkDeleteJam').removeClass('hidden');
                $('#bulkEditCount').text(checkedCount);
                $('#bulkDeleteCount').text(checkedCount);
                $('#bulkEditModalCountText').text(checkedCount);
                $('#bulkDeleteCountText').text(checkedCount);
            } else {
                $('#btnBulkEditJam').addClass('hidden');
                $('#btnBulkDeleteJam').addClass('hidden');
            }
        }

        // Check All Global Handler
        $('#checkAll').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('.checkItem').prop('checked', isChecked);
            $('.checkGroupSession').prop('checked', isChecked);
            updateBulkButtons();
        });

        // Group Session Checkbox Handler
        $(document).on('change', '.checkGroupSession', function() {
            var isChecked = $(this).prop('checked');
            var groupId = $(this).data('group-id');
            $('.checkItem[data-group-id="' + groupId + '"]').prop('checked', isChecked);

            // Sync global checkAll
            if ($('.checkItem:checked').length === $('.checkItem').length && $('.checkItem').length > 0) {
                $('#checkAll').prop('checked', true);
            } else {
                $('#checkAll').prop('checked', false);
            }
            updateBulkButtons();
        });

        // Individual Item Checkbox Handler
        $(document).on('change', '.checkItem', function() {
            var groupId = $(this).data('group-id');
            var itemsInGroup = $('.checkItem[data-group-id="' + groupId + '"]');
            var checkedInGroup = itemsInGroup.filter(':checked');

            $('.checkGroupSession[data-group-id="' + groupId + '"]').prop('checked', itemsInGroup.length > 0 && itemsInGroup.length === checkedInGroup.length);

            if ($('.checkItem:checked').length === $('.checkItem').length && $('.checkItem').length > 0) {
                $('#checkAll').prop('checked', true);
            } else {
                $('#checkAll').prop('checked', false);
            }
            updateBulkButtons();
        });

        // Button Tambah Jam Ke Sesi Handler (prefill nama_jam)
        $(document).on('click', '.btnTambahJamKeSesi', function() {
            var namaSesi = $(this).data('nama-sesi');
            $('#tambah_nama_jam').val(namaSesi);
        });

        // Toggle bulk keterangan input
        $('#bulk_keterangan_option').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#bulk_keterangan_input').removeClass('hidden').focus();
            } else {
                $('#bulk_keterangan_input').addClass('hidden').val('');
            }
        });

        // Bulk Edit Submit
        $('#formBulkEditJam').on('submit', function() {
            var container = $('#bulkEditIdsContainer');
            container.empty();
            $('.checkItem:checked').each(function() {
                container.append('<input type="hidden" name="jam_ids[]" value="' + $(this).val() + '">');
            });

            // Handle keterangan option
            var opt = $('#bulk_keterangan_option').val();
            if (opt === 'no_change') {
                $('#bulk_keterangan_input').val('no_change');
            } else if (opt === 'null') {
                $('#bulk_keterangan_input').val('null');
            }
        });

        // Bulk Delete Submit
        $('#formBulkDeleteJam').on('submit', function() {
            var container = $('#bulkDeleteIdsContainer');
            container.empty();
            $('.checkItem:checked').each(function() {
                container.append('<input type="hidden" name="jam_ids[]" value="' + $(this).val() + '">');
            });
        });

        // ================= PRESET MODAL HANDLERS =================
        // 1. Toggle Custom Durasi Menit
        $('#preset_durasi_menit').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#preset_durasi_custom_container').removeClass('hidden');
                $('#preset_durasi_custom').prop('required', true).focus();
            } else {
                $('#preset_durasi_custom_container').addClass('hidden');
                $('#preset_durasi_custom').prop('required', false);
            }
        });

        // 2. Render Dinamis Baris Jeda Istirahat Sesuai Jumlah Istirahat (1, 2, atau 3)
        function renderBreakRows() {
            var count = parseInt($('#preset_jumlah_istirahat').val()) || 0;
            var totalJam = parseInt($('#preset_jumlah_jam').val()) || 8;
            var container = $('#container_jeda_istirahat');
            var list = $('#list_jeda_istirahat');
            var badge = $('#badge_jumlah_istirahat');

            if (count === 0) {
                container.addClass('hidden');
                list.empty();
                return;
            }

            container.removeClass('hidden');
            if (badge.length) {
                badge.text(count + ' Kali Istirahat');
            }

            var defaults = [
                { jam: 4, durasi: 30, label: 'Istirahat Pertama' },
                { jam: 7, durasi: 45, label: 'Istirahat Kedua (Ishoma)' },
                { jam: totalJam >= 9 ? 9 : totalJam, durasi: 15, label: 'Istirahat Ketiga' }
            ];

            var durasiOptions = [
                { value: 15, label: '15 Menit' },
                { value: 20, label: '20 Menit' },
                { value: 30, label: '30 Menit' },
                { value: 40, label: '40 Menit' },
                { value: 45, label: '45 Menit (Ishoma)' },
                { value: 60, label: '60 Menit (1 Jam)' }
            ];

            list.empty();

            for (var i = 0; i < count; i++) {
                var d = defaults[i] || { jam: Math.min(totalJam, (i + 1) * 3), durasi: 30, label: 'Istirahat Ke-' + (i + 1) };
                var targetJam = Math.min(d.jam, totalJam);

                // Options untuk Dropdown "Setelah Jam Ke-"
                var jamOptionsHtml = '';
                for (var j = 1; j <= totalJam; j++) {
                    var isSelected = (j === targetJam) ? 'selected' : '';
                    jamOptionsHtml += '<option value="' + j + '" ' + isSelected + '>Setelah Jam Ke- ' + j + '</option>';
                }

                // Options untuk Dropdown "Durasi Istirahat"
                var durasiOptionsHtml = '';
                for (var k = 0; k < durasiOptions.length; k++) {
                    var opt = durasiOptions[k];
                    var isDurSelected = (opt.value === d.durasi) ? 'selected' : '';
                    durasiOptionsHtml += '<option value="' + opt.value + '" ' + isDurSelected + '>' + opt.label + '</option>';
                }

                var rowHtml = `
                    <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40 transition hover:border-brand-300 dark:hover:border-brand-500/40">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300 font-bold text-xs">
                                    ${i + 1}
                                </span>
                                <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">${d.label}</span>
                            </div>
                            <span class="text-[11px] text-gray-400">Jeda istirahat ke-${i + 1}</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                                    Jeda Istirahat Setelah <span class="text-error-500">*</span>
                                </label>
                                <select name="istirahat[${i}][setelah_jam]" required
                                    class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                    ${jamOptionsHtml}
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                                    Durasi Istirahat <span class="text-error-500">*</span>
                                </label>
                                <select name="istirahat[${i}][durasi]" required
                                    class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                                    ${durasiOptionsHtml}
                                </select>
                            </div>
                        </div>
                    </div>
                `;

                list.append(rowHtml);
            }
        }

        $('#preset_jumlah_istirahat').on('change', renderBreakRows);
        $('#preset_jumlah_jam').on('input change', renderBreakRows);

        // Inisialisasi awal saat halaman dibuka
        renderBreakRows();
    });
</script>
@endpush
