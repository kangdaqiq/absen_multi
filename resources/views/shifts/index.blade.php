@extends('layouts.app')

@section('title', 'Master Shift Guru & Staff')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            ⏰ Kelola Shift Guru & Staff
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Atur jam kerja, hari berlaku, toleransi keterlambatan, dan rentang waktu scan kartu/jari.
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('shifts.mapping') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition shadow-sm">
            <i class="fas fa-users-cog text-brand-500"></i> Plotting Guru ke Shift
        </a>
        <button type="button" @click="$dispatch('open-modal', 'modalTambahShift')"
            class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 transition shadow-sm">
            <i class="fas fa-plus"></i> Tambah Shift Baru
        </button>
    </div>
</div>

@if(session('success'))
    <div class="mb-4 flex items-center gap-3 rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-success-700 dark:border-success-500/30 dark:bg-success-500/15 dark:text-success-400">
        <i class="fas fa-check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif

@if(session('error'))
    <div class="mb-4 flex items-center gap-3 rounded-xl border border-error-200 bg-error-50 px-4 py-3 text-error-700 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-400">
        <i class="fas fa-exclamation-circle"></i>
        <span>{{ session('error') }}</span>
    </div>
@endif

<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex justify-between items-center">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Daftar Shift Kerja</h6>
        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
            Total {{ $shifts->count() }} Shift
        </span>
    </div>

    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto text-sm">
            <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 font-medium">
                    <th class="px-5 py-4 text-left">Nama Shift</th>
                    <th class="px-5 py-4 text-center">Hari Berlaku</th>
                    <th class="px-5 py-4 text-center">Jam Kerja</th>
                    <th class="px-5 py-4 text-center">Batas Terlambat</th>
                    <th class="px-5 py-4 text-center">Jam Absen Masuk</th>
                    <th class="px-5 py-4 text-center">Jam Absen Pulang</th>
                    <th class="px-5 py-4 text-center">Anggota Guru</th>
                    <th class="px-5 py-4 text-center">Status</th>
                    <th class="px-5 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($shifts as $shift)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <div class="w-2.5 h-2.5 rounded-full {{ $shift->is_active ? 'bg-success-500' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-white/90">{{ $shift->nama_shift }}</p>
                                    @if($shift->kode_shift)
                                        <span class="text-xs font-mono font-bold text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-500/15 px-1.5 py-0.5 rounded">
                                            {{ $shift->kode_shift }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-center whitespace-nowrap">
                            <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-600 dark:bg-blue-500/15 dark:text-blue-400">
                                <i class="fas fa-calendar-day mr-1"></i> {{ $shift->formatted_hari_kerja }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-center whitespace-nowrap">
                            <span class="inline-flex items-center gap-1 font-mono font-bold text-gray-800 dark:text-gray-200 bg-gray-100 dark:bg-gray-800 px-2.5 py-1 rounded-lg text-xs">
                                <i class="far fa-clock text-brand-500"></i>
                                {{ $shift->formatted_jam_masuk }} - {{ $shift->formatted_jam_pulang }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-center whitespace-nowrap">
                            @if($shift->jam_terlambat)
                                <span class="font-mono text-xs font-semibold text-warning-600 dark:text-warning-400 bg-warning-50 dark:bg-warning-500/10 px-2 py-0.5 rounded">
                                    {{ $shift->formatted_jam_terlambat }}
                                </span>
                            @else
                                <span class="font-mono text-xs text-gray-500 dark:text-gray-400">
                                    = {{ $shift->formatted_jam_masuk }}
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-center text-xs text-gray-500 dark:text-gray-400 font-mono whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($shift->awal_absen_masuk)->format('H:i') }} s.d {{ \Carbon\Carbon::parse($shift->akhir_absen_masuk)->format('H:i') }}
                        </td>
                        <td class="px-5 py-4 text-center text-xs text-gray-500 dark:text-gray-400 font-mono whitespace-nowrap">
                            {{ \Carbon\Carbon::parse($shift->awal_absen_pulang)->format('H:i') }} s.d {{ \Carbon\Carbon::parse($shift->akhir_absen_pulang)->format('H:i') }}
                        </td>
                        <td class="px-5 py-4 text-center whitespace-nowrap">
                            <a href="{{ route('shifts.mapping', ['shift_id' => $shift->id]) }}"
                                class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-2.5 py-1 text-xs font-semibold text-brand-600 hover:bg-brand-100 dark:bg-brand-500/15 dark:text-brand-400 transition"
                                title="Lihat & kelola guru di shift ini">
                                <i class="fas fa-users"></i> {{ $shift->gurus_count ?: $shift->shift_assignments_count }} Guru
                            </a>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <form action="{{ route('shifts.toggle-active', $shift->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold {{ $shift->is_active ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-400' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }} hover:opacity-80 transition">
                                    <i class="fas {{ $shift->is_active ? 'fa-check' : 'fa-times' }}"></i>
                                    {{ $shift->is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-5 py-4 text-center whitespace-nowrap">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('shifts.mapping', ['shift_id' => $shift->id]) }}"
                                    class="p-1.5 text-brand-500 hover:text-brand-700 hover:bg-brand-50 rounded-lg dark:text-brand-400 dark:hover:bg-brand-500/10 transition"
                                    title="Plot Guru ke Shift Ini">
                                    <i class="fas fa-user-plus"></i>
                                </a>
                                <button type="button"
                                    @click="$dispatch('open-modal', 'modalEditShift-{{ $shift->id }}')"
                                    class="p-1.5 text-gray-500 hover:text-brand-500 hover:bg-gray-100 rounded-lg dark:text-gray-400 dark:hover:bg-gray-800 transition"
                                    title="Edit Shift">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('shifts.destroy', $shift->id) }}" method="POST"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus shift ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="p-1.5 text-gray-500 hover:text-error-500 hover:bg-error-50 rounded-lg dark:text-gray-400 dark:hover:bg-error-500/10 transition"
                                        title="Hapus Shift">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-5 py-10 text-center text-gray-400 dark:text-gray-500">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <i class="far fa-clock text-4xl text-gray-300 dark:text-gray-600"></i>
                                <p class="font-medium">Belum ada shift kerja yang dibuat</p>
                                <button @click="$dispatch('open-modal', 'modalTambahShift')"
                                    class="mt-2 text-xs font-semibold text-brand-500 hover:underline">
                                    + Tambah Shift Pertama
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- MODAL TAMBAH SHIFT --}}
<x-ui.modal id="modalTambahShift" :is-open="false" class="max-w-2xl">
    <div class="p-6">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-800">
            <h5 class="text-lg font-bold text-gray-800 dark:text-white/90 flex items-center gap-2">
                <i class="fas fa-plus-circle text-brand-500"></i> Tambah Shift Baru
            </h5>
            <button @click="$dispatch('close-modal', 'modalTambahShift')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('shifts.store') }}" method="POST" class="mt-4 space-y-4"
            x-data="{
                days: [1, 2, 3, 4, 5],
                setWeekdays() { this.days = [1, 2, 3, 4, 5]; },
                setSixDays() { this.days = [1, 2, 3, 4, 5, 6]; },
                setAllDays() { this.days = [1, 2, 3, 4, 5, 6, 7]; }
            }">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Nama Shift <span class="text-error-500">*</span>
                    </label>
                    <input type="text" name="nama_shift" required placeholder="Contoh: Shift Pagi Senin"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Kode Singkat / Alias
                    </label>
                    <input type="text" name="kode_shift" placeholder="Contoh: Senin Pagi"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                </div>
            </div>

            {{-- PENGATURAN HARI KERJA SHIFT --}}
            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700 space-y-3">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                        <i class="fas fa-calendar-week text-brand-500 mr-1"></i> Hari Berlaku Shift (Hari Kerja)
                    </p>
                    <div class="flex gap-1">
                        <button type="button" @click="setWeekdays()" class="px-2 py-0.5 text-[10px] font-medium rounded bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300">
                            Sen - Jum
                        </button>
                        <button type="button" @click="setSixDays()" class="px-2 py-0.5 text-[10px] font-medium rounded bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300">
                            Sen - Sab
                        </button>
                        <button type="button" @click="setAllDays()" class="px-2 py-0.5 text-[10px] font-medium rounded bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300">
                            Semua Hari
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2 pt-1">
                    @php
                        $dayOptions = [
                            1 => 'Senin',
                            2 => 'Selasa',
                            3 => 'Rabu',
                            4 => 'Kamis',
                            5 => 'Jumat',
                            6 => 'Sabtu',
                            7 => 'Minggu',
                        ];
                    @endphp
                    @foreach($dayOptions as $dIdx => $dLabel)
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 bg-white dark:bg-gray-900 dark:border-gray-700 cursor-pointer hover:border-brand-500 transition">
                            <input type="checkbox" name="hari_kerja[]" value="{{ $dIdx }}" x-model="days"
                                class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800" />
                            <span class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ $dLabel }}</span>
                        </label>
                    @endforeach
                </div>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                    * Guru yang diplot ke shift ini hanya bisa absen pada hari-hari yang tercentang. Hari di luar itu otomatis <b>Libur</b>.
                </p>
            </div>

            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700 space-y-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Jadwal Jam Kerja & Toleransi</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Jam Masuk <span class="text-error-500">*</span>
                        </label>
                        <input type="time" name="jam_masuk" value="07:00" required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-mono text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Batas Terlambat
                        </label>
                        <input type="time" name="jam_terlambat" value="07:15"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-mono text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        <span class="text-[10px] text-gray-400">Lewat jam ini = Terlambat</span>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Jam Pulang <span class="text-error-500">*</span>
                        </label>
                        <input type="time" name="jam_pulang" value="14:00" required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-mono text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                    </div>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700 space-y-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Rentang Waktu Scan Kartu / Jari</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Jam Absen Masuk</span>
                        <div class="flex items-center gap-2">
                            <input type="time" name="awal_absen_masuk" value="06:00" required
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-mono dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                            <span class="text-xs text-gray-400">s/d</span>
                            <input type="time" name="akhir_absen_masuk" value="10:00" required
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-mono dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Jam Absen Pulang</span>
                        <div class="flex items-center gap-2">
                            <input type="time" name="awal_absen_pulang" value="13:30" required
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-mono dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                            <span class="text-xs text-gray-400">s/d</span>
                            <input type="time" name="akhir_absen_pulang" value="18:00" required
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-mono dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active_create" value="1" checked
                    class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900" />
                <label for="is_active_create" class="text-sm text-gray-700 dark:text-gray-300">
                    Aktifkan shift ini sekarang
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <button type="button" @click="$dispatch('close-modal', 'modalTambahShift')"
                    class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                    Batal
                </button>
                <button type="submit"
                    class="rounded-lg bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600 transition shadow-sm">
                    Simpan Shift
                </button>
            </div>
        </form>
    </div>
</x-ui.modal>

{{-- MODAL EDIT SHIFT --}}
@foreach($shifts as $shift)
@php
    $curDays = $shift->hari_kerja ?: [1, 2, 3, 4, 5];
@endphp
<x-ui.modal id="modalEditShift-{{ $shift->id }}" :is-open="false" class="max-w-2xl">
    <div class="p-6">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-800">
            <h5 class="text-lg font-bold text-gray-800 dark:text-white/90 flex items-center gap-2">
                <i class="fas fa-edit text-brand-500"></i> Edit Shift: {{ $shift->nama_shift }}
            </h5>
            <button @click="$dispatch('close-modal', 'modalEditShift-{{ $shift->id }}')" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form action="{{ route('shifts.update', $shift->id) }}" method="POST" class="mt-4 space-y-4"
            x-data="{
                days: {{ json_encode(array_map('intval', $curDays)) }},
                setWeekdays() { this.days = [1, 2, 3, 4, 5]; },
                setSixDays() { this.days = [1, 2, 3, 4, 5, 6]; },
                setAllDays() { this.days = [1, 2, 3, 4, 5, 6, 7]; }
            }">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Nama Shift <span class="text-error-500">*</span>
                    </label>
                    <input type="text" name="nama_shift" value="{{ $shift->nama_shift }}" required
                        class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Kode Singkat / Alias
                    </label>
                    <input type="text" name="kode_shift" value="{{ $shift->kode_shift }}" placeholder="Contoh: PAGI / SIANG"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                </div>
            </div>

            {{-- PENGATURAN HARI KERJA SHIFT --}}
            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700 space-y-3">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">
                        <i class="fas fa-calendar-week text-brand-500 mr-1"></i> Hari Berlaku Shift (Hari Kerja)
                    </p>
                    <div class="flex gap-1">
                        <button type="button" @click="setWeekdays()" class="px-2 py-0.5 text-[10px] font-medium rounded bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300">
                            Sen - Jum
                        </button>
                        <button type="button" @click="setSixDays()" class="px-2 py-0.5 text-[10px] font-medium rounded bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300">
                            Sen - Sab
                        </button>
                        <button type="button" @click="setAllDays()" class="px-2 py-0.5 text-[10px] font-medium rounded bg-white border border-gray-200 text-gray-700 hover:bg-gray-100 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300">
                            Semua Hari
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2 pt-1">
                    @foreach($dayOptions as $dIdx => $dLabel)
                        <label class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 bg-white dark:bg-gray-900 dark:border-gray-700 cursor-pointer hover:border-brand-500 transition">
                            <input type="checkbox" name="hari_kerja[]" value="{{ $dIdx }}" x-model="days"
                                class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-800" />
                            <span class="text-xs font-medium text-gray-800 dark:text-gray-200">{{ $dLabel }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700 space-y-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Jadwal Jam Kerja & Toleransi</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Jam Masuk <span class="text-error-500">*</span>
                        </label>
                        <input type="time" name="jam_masuk" value="{{ \Carbon\Carbon::parse($shift->jam_masuk)->format('H:i') }}" required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-mono text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Batas Terlambat
                        </label>
                        <input type="time" name="jam_terlambat" value="{{ $shift->jam_terlambat ? \Carbon\Carbon::parse($shift->jam_terlambat)->format('H:i') : '' }}"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-mono text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        <span class="text-[10px] text-gray-400">Lewat jam ini = Terlambat</span>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Jam Pulang <span class="text-error-500">*</span>
                        </label>
                        <input type="time" name="jam_pulang" value="{{ \Carbon\Carbon::parse($shift->jam_pulang)->format('H:i') }}" required
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-mono text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                    </div>
                </div>
            </div>

            <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700 space-y-4">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Rentang Waktu Scan Kartu / Jari</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Jam Absen Masuk</span>
                        <div class="flex items-center gap-2">
                            <input type="time" name="awal_absen_masuk" value="{{ \Carbon\Carbon::parse($shift->awal_absen_masuk)->format('H:i') }}" required
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-mono dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                            <span class="text-xs text-gray-400">s/d</span>
                            <input type="time" name="akhir_absen_masuk" value="{{ \Carbon\Carbon::parse($shift->akhir_absen_masuk)->format('H:i') }}" required
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-mono dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        </div>
                    </div>
                    <div class="space-y-2">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">Jam Absen Pulang</span>
                        <div class="flex items-center gap-2">
                            <input type="time" name="awal_absen_pulang" value="{{ \Carbon\Carbon::parse($shift->awal_absen_pulang)->format('H:i') }}" required
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-mono dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                            <span class="text-xs text-gray-400">s/d</span>
                            <input type="time" name="akhir_absen_pulang" value="{{ \Carbon\Carbon::parse($shift->akhir_absen_pulang)->format('H:i') }}" required
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-mono dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="is_active" id="is_active_edit_{{ $shift->id }}" value="1" {{ $shift->is_active ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900" />
                <label for="is_active_edit_{{ $shift->id }}" class="text-sm text-gray-700 dark:text-gray-300">
                    Shift Aktif
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <button type="button" @click="$dispatch('close-modal', 'modalEditShift-{{ $shift->id }}')"
                    class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                    Batal
                </button>
                <button type="submit"
                    class="rounded-lg bg-brand-500 px-5 py-2 text-sm font-medium text-white hover:bg-brand-600 transition shadow-sm">
                    Perbarui Shift
                </button>
            </div>
        </form>
    </div>
</x-ui.modal>
@endforeach

@endsection
