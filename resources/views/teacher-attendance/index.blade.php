@extends('layouts.app')

@php
    $school = auth()->user()->school ?? null;
    $labelKaryawan = $school?->employeeLabel() ?? 'Guru';
    $labelNIP = $school?->nipLabel() ?? 'NIP';
@endphp

@section('title', 'Absensi Harian ' . $labelKaryawan)

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        <i class="fas fa-calendar-check text-brand-500 mr-2"></i> Absensi Harian {{ $labelKaryawan }}
    </h2>
</div>

{{-- Alert Success / Error --}}
@if(session('success'))
    <div class="mb-4 rounded-lg bg-success/10 border border-success/20 px-4 py-3 text-sm font-medium text-success flex items-center gap-2">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg bg-error-500/10 border border-error-500/20 px-4 py-3 text-sm font-medium text-error-500 flex items-center gap-2">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
@endif

{{-- Filter Card --}}
<div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Filter Data</h6>
    </div>
    <div class="p-5">
        <form action="{{ route('absensi-guru.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-end flex-wrap">
            {{-- Tanggal --}}
            <div class="w-full sm:w-auto">
                <label for="tanggal" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                <input type="date" name="tanggal" id="tanggal" value="{{ $dateStr }}"
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>
            {{-- Filter Status --}}
            <div class="w-full sm:w-auto min-w-[180px]">
                <label for="filter_status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select name="status" id="filter_status"
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="">Semua Status</option>
                    <option value="Hadir"       {{ request('status') == 'Hadir'       ? 'selected' : '' }}>Hadir</option>
                    <option value="Belum Absen" {{ request('status') == 'Belum Absen' ? 'selected' : '' }}>Belum Absen</option>
                    <option value="Izin"        {{ request('status') == 'Izin'        ? 'selected' : '' }}>Izin</option>
                    <option value="Sakit"       {{ request('status') == 'Sakit'       ? 'selected' : '' }}>Sakit</option>
                    <option value="Alpha"       {{ request('status') == 'Alpha'       ? 'selected' : '' }}>Alpha</option>
                </select>
            </div>
            {{-- Cari nama (client-side) --}}
            <div class="w-full sm:w-auto relative min-w-[240px]">
                <label for="clientSearch" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cari Nama</label>
                <input type="text" id="clientSearch" placeholder="Ketik nama {{ strtolower($labelKaryawan) }}..."
                    class="w-full rounded-lg border border-gray-200 bg-transparent py-2 pl-4 pr-10 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                <div class="absolute right-3 bottom-2.5 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            {{-- Tombol Tampilkan --}}
            <div class="w-full sm:w-auto">
                <button type="submit"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-6 py-2 text-center font-medium text-white hover:bg-brand-600 transition">
                    <i class="fas fa-filter"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Data Table Card --}}
<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex justify-between items-center">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">
            Tabel Absensi: {{ \Carbon\Carbon::parse($dateStr)->isoFormat('D MMMM Y') }}
            <span class="ml-2 inline-flex rounded-full bg-info/10 px-3 py-1 text-xs font-medium text-info">{{ $dayName }}</span>
        </h6>
    </div>

    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto" id="guruTable">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                    <th class="px-4 py-4 xl:pl-6" width="5%">No</th>
                    <th class="px-4 py-4">Nama {{ $labelKaryawan }}</th>
                    <th class="px-4 py-4">{{ $labelNIP }}</th>
                    <th class="px-4 py-4 text-center">Status</th>
                    <th class="px-4 py-4 text-center">Jam Masuk</th>
                    <th class="px-4 py-4 text-center">Jam Pulang</th>
                    <th class="px-4 py-4">Keterangan</th>
                    <th class="px-4 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-sm" x-data="{}">
                @forelse($report as $index => $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors border-b border-gray-100 dark:border-gray-800 last:border-b-0 guru-row"
                        data-status="{{ $item['status'] }}">
                        <td class="px-4 py-4 xl:pl-6">
                            <p class="text-gray-500 dark:text-gray-400">{{ $loop->iteration }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <p class="font-medium text-gray-800 dark:text-white/90 guru-nama">{{ $item['guru']->nama }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ $item['guru']->nip }}</p>
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($item['status'] == 'Hadir')
                                <span class="inline-flex rounded-full bg-success/10 px-3 py-1 text-xs font-medium text-success">Hadir</span>
                            @elseif($item['status'] == 'Belum Absen')
                                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Belum Absen</span>
                            @elseif($item['status'] == 'Izin')
                                <span class="inline-flex rounded-full bg-info/10 px-3 py-1 text-xs font-medium text-info">Izin</span>
                            @elseif($item['status'] == 'Sakit')
                                <span class="inline-flex rounded-full bg-warning/10 px-3 py-1 text-xs font-medium text-warning">Sakit</span>
                            @else
                                <span class="inline-flex rounded-full bg-error-500/10 px-3 py-1 text-xs font-medium text-error-500">{{ $item['status'] }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center font-medium">
                            @if($item['jam_masuk'] && $item['jam_masuk'] !== '-')
                                <span class="inline-block rounded px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 font-mono text-xs">{{ $item['jam_masuk'] }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center font-medium">
                            @if($item['jam_pulang'] && $item['jam_pulang'] !== '-')
                                <span class="inline-block rounded px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 font-mono text-xs">{{ $item['jam_pulang'] }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <p class="text-gray-500 dark:text-gray-400 text-xs">{{ $item['keterangan'] ?: '-' }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-center gap-2">
                                {{-- Tombol Edit --}}
                                <button class="btnEditGuru text-brand-500 hover:text-brand-700 hover:bg-brand-50 dark:hover:bg-brand-500/10 p-2 rounded-lg transition"
                                    data-guru-id="{{ $item['guru']->id }}"
                                    data-nama="{{ $item['guru']->nama }}"
                                    data-status="{{ $item['status'] }}"
                                    data-jam-masuk="{{ ($item['jam_masuk'] && $item['jam_masuk'] !== '-') ? $item['jam_masuk'] : '' }}"
                                    data-jam-pulang="{{ ($item['jam_pulang'] && $item['jam_pulang'] !== '-') ? $item['jam_pulang'] : '' }}"
                                    data-keterangan="{{ $item['keterangan'] }}"
                                    @click="$dispatch('open-modal', 'modalEditGuru')"
                                    title="Edit Absensi">
                                    <i class="fas fa-edit"></i>
                                </button>

                                {{-- Tombol Hapus (hanya tampil jika ada record absensi) --}}
                                @if($item['attendance_id'])
                                    <button class="btnHapusGuru text-error-500 hover:text-error-700 hover:bg-error-50 dark:hover:bg-error-500/10 p-2 rounded-lg transition"
                                        data-guru-id="{{ $item['guru']->id }}"
                                        data-nama="{{ $item['guru']->nama }}"
                                        @click="$dispatch('open-modal', 'modalHapusGuru')"
                                        title="Hapus Data Absensi">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data {{ strtolower($labelKaryawan) }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ========================= MODALS ========================= --}}

{{-- Modal Edit Absensi Guru --}}
<x-ui.modal id="modalEditGuru" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Update Absensi {{ $labelKaryawan }}</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="{{ route('absensi-guru.store') }}" method="POST">
            @csrf
            <input type="hidden" name="tanggal" value="{{ $dateStr }}">
            <input type="hidden" name="guru_id" id="edit_guru_id">

            <div class="mb-4 p-3 bg-gray-50 rounded-lg dark:bg-gray-800/50">
                <h5 id="edit_guru_nama" class="font-bold text-gray-800 dark:text-white/90 text-lg"></h5>
                <p class="text-sm text-gray-500 mt-1">Tanggal: {{ \Carbon\Carbon::parse($dateStr)->isoFormat('D MMMM Y') }}</p>
            </div>

            <div class="space-y-4">
                <div class="flex gap-4">
                    <div class="w-1/2">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Masuk</label>
                        <input type="time" name="jam_masuk" id="edit_jam_masuk"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div class="w-1/2">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Pulang</label>
                        <input type="time" name="jam_pulang" id="edit_jam_pulang"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select name="status" id="edit_status" required
                        class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="Hadir">Hadir</option>
                        <option value="Izin">Izin</option>
                        <option value="Sakit">Sakit</option>
                        <option value="Alpha">Alpha</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                    <textarea name="keterangan" id="edit_keterangan" rows="3"
                        class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white"
                        placeholder="Opsional..."></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                    Batal
                </button>
                <button type="submit"
                    class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</x-ui.modal>

{{-- Modal Hapus Absensi Guru --}}
<x-ui.modal id="modalHapusGuru" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-error-500">Hapus Data Absensi</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="{{ route('absensi-guru.destroy') }}" method="POST">
            @csrf
            @method('DELETE')
            <input type="hidden" name="tanggal" value="{{ $dateStr }}">
            <input type="hidden" name="guru_id" id="hapus_guru_id">

            <div class="mb-6">
                <p class="text-gray-700 dark:text-gray-300 mb-2">Yakin ingin menghapus data absensi untuk:</p>
                <h5 id="hapus_guru_nama" class="font-bold text-gray-900 dark:text-white text-lg"></h5>
                <p class="text-sm text-gray-500 mt-1">Tanggal: {{ \Carbon\Carbon::parse($dateStr)->isoFormat('D MMMM Y') }}</p>
                <div class="mt-3 p-3 bg-warning/10 text-warning rounded-lg text-sm dark:bg-warning/15">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Data yang dihapus akan kembali berstatus "Belum Absen".
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" @click="open = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                    Batal
                </button>
                <button type="submit" class="rounded-lg bg-error-500 px-4 py-2 text-white hover:bg-error-600">
                    Hapus Data
                </button>
            </div>
        </form>
    </div>
</x-ui.modal>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {

        // ===== Filter: Cari nama (client-side) =====
        const searchInput = document.getElementById('clientSearch');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const keyword = this.value.toLowerCase();
                document.querySelectorAll('.guru-row').forEach(function (row) {
                    const nama = row.querySelector('.guru-nama')?.textContent.toLowerCase() ?? '';
                    row.style.display = nama.includes(keyword) ? '' : 'none';
                });
            });
        }

        // ===== Modal Edit =====
        document.querySelectorAll('.btnEditGuru').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('edit_guru_id').value   = this.dataset.guruId;
                document.getElementById('edit_guru_nama').textContent = this.dataset.nama;
                document.getElementById('edit_status').value    = this.dataset.status;
                document.getElementById('edit_jam_masuk').value = this.dataset.jamMasuk ?? '';
                document.getElementById('edit_jam_pulang').value = this.dataset.jamPulang ?? '';
                document.getElementById('edit_keterangan').value = this.dataset.keterangan ?? '';
            });
        });

        // ===== Modal Hapus =====
        document.querySelectorAll('.btnHapusGuru').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('hapus_guru_id').value  = this.dataset.guruId;
                document.getElementById('hapus_guru_nama').textContent = this.dataset.nama;
            });
        });
    });
</script>
@endpush