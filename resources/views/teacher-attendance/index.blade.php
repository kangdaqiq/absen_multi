@extends('layouts.app')

@php
    $school = auth()->user()->school ?? null;
    $labelKaryawan = $school?->employeeLabel() ?? 'Guru';
    $labelNIP = $school?->nipLabel() ?? 'NIP';
@endphp

@section('title', 'Absensi Harian ' . $labelKaryawan)

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            <i class="fas fa-calendar-check text-brand-500 mr-2"></i> Absensi Harian {{ $labelKaryawan }}
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
            Monitoring kehadiran harian dan evaluasi shift guru/staff.
        </p>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('shifts.mapping') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 transition shadow-sm">
            <i class="fas fa-users-cog text-brand-500"></i> Plotting Shift
        </a>
    </div>
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
            {{-- Filter Shift --}}
            <div class="w-full sm:w-auto min-w-[180px]">
                <label for="filter_shift" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Shift Kerja</label>
                <select name="shift_id" id="filter_shift"
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="">Semua Shift</option>
                    @foreach($shifts as $s)
                        <option value="{{ $s->id }}" {{ ($filterShift ?? '') == $s->id ? 'selected' : '' }}>
                            {{ $s->nama_shift }} ({{ $s->formatted_jam_masuk }}-{{ $s->formatted_jam_pulang }})
                        </option>
                    @endforeach
                </select>
            </div>
            {{-- Filter Status --}}
            <div class="w-full sm:w-auto min-w-[160px]">
                <label for="filter_status" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select name="status" id="filter_status"
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="">Semua Status</option>
                    <option value="Hadir"       {{ ($filterStatus ?? '') == 'Hadir'       ? 'selected' : '' }}>Hadir (Tepat Waktu)</option>
                    <option value="Terlambat"   {{ ($filterStatus ?? '') == 'Terlambat'   ? 'selected' : '' }}>Terlambat</option>
                    <option value="Belum Absen" {{ ($filterStatus ?? '') == 'Belum Absen' ? 'selected' : '' }}>Belum Absen</option>
                    <option value="Izin"        {{ ($filterStatus ?? '') == 'Izin'        ? 'selected' : '' }}>Izin</option>
                    <option value="Sakit"       {{ ($filterStatus ?? '') == 'Sakit'       ? 'selected' : '' }}>Sakit</option>
                    <option value="Alpha"       {{ ($filterStatus ?? '') == 'Alpha'       ? 'selected' : '' }}>Alpha / Tidak Hadir</option>
                </select>
            </div>
            {{-- Cari nama (client-side) --}}
            <div class="w-full sm:w-auto relative min-w-[220px]">
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
        <span class="text-xs text-gray-400">{{ count($report) }} {{ $labelKaryawan }}</span>
    </div>

    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto" id="guruTable">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                    <th class="px-4 py-4 xl:pl-6" width="5%">No</th>
                    <th class="px-4 py-4">Nama {{ $labelKaryawan }}</th>
                    <th class="px-4 py-4">{{ $labelNIP }}</th>
                    <th class="px-4 py-4 text-center">Shift</th>
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
                            <p class="text-gray-500 dark:text-gray-400 text-sm font-mono">{{ $item['guru']->nip ?: '-' }}</p>
                        </td>
                        <td class="px-4 py-4 text-center whitespace-nowrap">
                            @if($item['shift'])
                                <span class="inline-flex items-center gap-1 font-mono text-xs font-semibold px-2.5 py-1 rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                    <i class="far fa-clock text-[10px]"></i> {{ $item['shift']->kode_shift ?: $item['shift']->nama_shift }}
                                </span>
                                <div class="text-[10px] text-gray-400 font-mono">{{ $item['shift']->formatted_jam_masuk }}-{{ $item['shift']->formatted_jam_pulang }}</div>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-400">
                                    Tidak Ada Shift
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center whitespace-nowrap">
                            @if($item['status'] == 'Hadir')
                                <span class="inline-flex rounded-full bg-success/10 px-3 py-1 text-xs font-medium text-success">Hadir</span>
                            @elseif($item['status'] == 'Terlambat')
                                <span class="inline-flex rounded-full bg-warning-500/15 px-3 py-1 text-xs font-semibold text-warning-600 dark:text-warning-400">
                                    Terlambat (+{{ $item['menit_terlambat'] }}m)
                                </span>
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
                                    data-shift-id="{{ $item['shift']?->id }}"
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
                        <td colspan="9" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
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
                {{-- Shift --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Shift Kerja</label>
                    <select name="shift_id" id="edit_shift_id"
                        class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="">-- Otomatis (Ikuti Penugasan Guru) --</option>
                        @foreach($shifts as $s)
                            <option value="{{ $s->id }}">{{ $s->nama_shift }} ({{ $s->formatted_jam_masuk }} - {{ $s->formatted_jam_pulang }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Status --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Status Kehadiran <span class="text-error-500">*</span></label>
                    <select name="status" id="edit_status" required
                        class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="Hadir">Hadir</option>
                        <option value="Terlambat">Terlambat</option>
                        <option value="Izin">Izin</option>
                        <option value="Sakit">Sakit</option>
                        <option value="Alpha">Alpha / Tidak Hadir</option>
                    </select>
                </div>

                {{-- Jam Masuk & Jam Pulang --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Masuk</label>
                        <input type="time" name="jam_masuk" id="edit_jam_masuk"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white font-mono">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Pulang</label>
                        <input type="time" name="jam_pulang" id="edit_jam_pulang"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white font-mono">
                    </div>
                </div>

                {{-- Keterangan --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                    <textarea name="keterangan" id="edit_keterangan" rows="2" placeholder="Catatan tambahan (opsional)"
                        class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                    Batal
                </button>
                <button type="submit" class="rounded-lg bg-brand-500 px-5 py-2 text-white hover:bg-brand-600 transition">
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
            <h3 class="text-xl font-bold text-error-500">Hapus Absensi {{ $labelKaryawan }}</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form action="{{ route('absensi-guru.destroy') }}" method="POST">
            @csrf
            @method('DELETE')
            <input type="hidden" name="tanggal" value="{{ $dateStr }}">
            <input type="hidden" name="guru_id" id="hapus_guru_id">

            <p class="text-gray-600 dark:text-gray-300">
                Apakah Anda yakin ingin menghapus data absensi untuk <strong id="hapus_guru_nama"></strong> pada tanggal <strong>{{ \Carbon\Carbon::parse($dateStr)->isoFormat('D MMMM Y') }}</strong>?
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                    Batal
                </button>
                <button type="submit" class="rounded-lg bg-error-500 px-5 py-2 text-white hover:bg-error-600 transition">
                    Hapus
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
    // Client-side search
    $('#clientSearch').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.guru-row').filter(function() {
            var name = $(this).find('.guru-nama').text().toLowerCase();
            $(this).toggle(name.indexOf(value) > -1);
        });
    });

    // Populate Edit Modal
    $('.btnEditGuru').on('click', function() {
        var guruId = $(this).data('guru-id');
        var nama = $(this).data('nama');
        var shiftId = $(this).data('shift-id');
        var status = $(this).data('status');
        var jamMasuk = $(this).data('jam-masuk');
        var jamPulang = $(this).data('jam-pulang');
        var keterangan = $(this).data('keterangan');

        $('#edit_guru_id').val(guruId);
        $('#edit_guru_nama').text(nama);
        $('#edit_shift_id').val(shiftId || '');
        
        if (status === 'Belum Absen') {
            $('#edit_status').val('Hadir');
        } else {
            $('#edit_status').val(status);
        }

        $('#edit_jam_masuk').val(jamMasuk);
        $('#edit_jam_pulang').val(jamPulang);
        $('#edit_keterangan').val(keterangan);
    });

    // Populate Delete Modal
    $('.btnHapusGuru').on('click', function() {
        var guruId = $(this).data('guru-id');
        var nama = $(this).data('nama');

        $('#hapus_guru_id').val(guruId);
        $('#hapus_guru_nama').text(nama);
    });
});
</script>
@endpush