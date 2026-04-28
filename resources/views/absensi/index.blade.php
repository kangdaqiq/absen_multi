@extends('layouts.app')

@section('title', 'Data Absensi Harian')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Absensi Harian
    </h2>
</div>

<!-- Filter Card -->
<div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Filter Data</h6>
    </div>
    <div class="p-5">
        <form action="{{ route('absensi.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4 items-end">
            <div class="w-full sm:w-auto">
                <label for="tanggal" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                <input type="date" name="tanggal" id="tanggal" value="{{ $tanggal }}" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
            </div>
            <div class="w-full sm:w-auto min-w-[200px]">
                <label for="kelas_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Kelas</label>
                <select name="kelas_id" id="kelas_id" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="">Semua Kelas</option>
                    @foreach($allKelas as $k)
                        <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full sm:w-auto relative min-w-[250px]">
                <label for="clientSearch" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Cari di halaman ini</label>
                <input type="text" id="clientSearch" placeholder="Ketik nama siswa..." class="client-search w-full rounded-lg border border-gray-200 bg-transparent py-2 pl-4 pr-10 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                <div class="absolute right-3 bottom-2 text-gray-400">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            <div class="w-full sm:w-auto">
                <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-6 py-2 text-center font-medium text-white hover:bg-brand-600 transition">
                    <i class="fas fa-filter"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Data Table Card -->
<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex justify-between items-center">
        <h6 class="font-semibold text-gray-800 dark:text-white/90">Log Absensi: {{ \Carbon\Carbon::parse($tanggal)->format('d-m-Y') }}</h6>
    </div>

    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                    <th class="px-4 py-4 xl:pl-6">Nama Siswa</th>
                    <th class="px-4 py-4">Kelas</th>
                    <th class="px-4 py-4 text-center">Jam Masuk</th>
                    <th class="px-4 py-4 text-center">Jam Pulang</th>
                    <th class="px-4 py-4 text-center">Status</th>
                    <th class="px-4 py-4">Keterangan</th>
                    <th class="px-4 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-sm" x-data="{}">
                @forelse($data as $row)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors border-b border-gray-100 dark:border-gray-800 last:border-b-0">
                        <td class="px-4 py-4 xl:pl-6">
                            <p class="font-medium text-gray-800 dark:text-white/90">{{ $row->nama }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <p class="text-gray-500 dark:text-gray-400">{{ $row->kelas }}</p>
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($row->jam_masuk != '-')
                                <span class="inline-block rounded px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 font-mono text-xs">
                                    {{ \Carbon\Carbon::parse($row->jam_masuk)->format('H:i') }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($row->jam_pulang != '-')
                                <span class="inline-block rounded px-2 py-1 bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 font-mono text-xs">
                                    {{ \Carbon\Carbon::parse($row->jam_pulang)->format('H:i') }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center">
                            @if($row->status == 'H')
                                <span class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">Hadir</span>
                            @elseif($row->status == 'I')
                                <span class="inline-flex rounded-full bg-info-50 px-3 py-1 text-xs font-medium text-info-600 dark:bg-info-500/15 dark:text-info-500">Izin</span>
                            @elseif($row->status == 'S')
                                <span class="inline-flex rounded-full bg-warning-50 px-3 py-1 text-xs font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">Sakit</span>
                            @elseif($row->status == 'T')
                                <span class="inline-flex rounded-full bg-warning-50 px-3 py-1 text-xs font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">Terlambat</span>
                            @elseif($row->status == 'B')
                                <span class="inline-flex rounded-full bg-error-50 px-3 py-1 text-xs font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">Bolos</span>
                            @else
                                <span class="inline-flex rounded-full bg-error-50 px-3 py-1 text-xs font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">Alpha</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            <p class="text-gray-500 dark:text-gray-400 text-xs">{{ $row->keterangan }}</p>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <button class="btnEditStatus text-brand-500 hover:text-brand-700 hover:bg-brand-50 p-2 rounded-lg transition" 
                                    data-id="{{ $row->id }}" data-nama="{{ $row->nama }}" data-status="{{ $row->status }}" data-keterangan="{{ $row->keterangan }}"
                                    data-masuk="{{ $row->jam_masuk != '-' ? \Carbon\Carbon::parse($row->jam_masuk)->format('H:i') : '' }}"
                                    data-pulang="{{ $row->jam_pulang != '-' ? \Carbon\Carbon::parse($row->jam_pulang)->format('H:i') : '' }}"
                                    @click="$dispatch('open-modal', 'modalEditStatus')" title="Edit Status">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                @if($row->absen_id)
                                    <button class="btnHapus text-error-500 hover:text-error-700 hover:bg-error-50 p-2 rounded-lg transition" 
                                        data-id="{{ $row->id }}" data-nama="{{ $row->nama }}"
                                        @click="$dispatch('open-modal', 'modalHapus')" title="Hapus Data">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data absensi ditemukan untuk filter yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
        {{ $allSiswa->links('vendor.pagination.tailwind') }}
    </div>
</div>

<!-- ========================= MODALS ========================= -->

<!-- Modal Edit Status -->
<x-ui.modal id="modalEditStatus" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Update Status Absensi</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('absensi.update') }}" method="POST">
            @csrf
            <input type="hidden" name="tanggal" value="{{ $tanggal }}">
            <input type="hidden" name="student_id" id="edit_student_id">

            <div class="mb-4 p-3 bg-gray-50 rounded-lg dark:bg-gray-800/50">
                <h5 id="edit_nama_label" class="font-bold text-gray-800 dark:text-white/90 text-lg"></h5>
                <p class="text-sm text-gray-500 mt-1">Tanggal: {{ \Carbon\Carbon::parse($tanggal)->format('d-m-Y') }}</p>
            </div>

            <div class="space-y-4">
                <div class="flex gap-4">
                    <div class="w-1/2">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Masuk</label>
                        <input type="time" name="jam_masuk" id="edit_jam_masuk" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div class="w-1/2">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Pulang</label>
                        <input type="time" name="jam_pulang" id="edit_jam_pulang" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select name="status" id="edit_status" required class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="H">Hadir (H)</option>
                        <option value="T">Terlambat (T)</option>
                        <option value="I">Izin (I)</option>
                        <option value="S">Sakit (S)</option>
                        <option value="A">Alpha (A)</option>
                        <option value="B">Bolos (B)</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan</label>
                    <textarea name="keterangan" id="edit_keterangan" rows="3" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white"></textarea>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</x-ui.modal>

<!-- Modal Hapus -->
<x-ui.modal id="modalHapus" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-error-500">Hapus Data Absensi</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('absensi.destroy') }}" method="POST">
            @csrf
            @method('DELETE')
            <input type="hidden" name="tanggal" value="{{ $tanggal }}">
            <input type="hidden" name="student_id" id="hapus_student_id">

            <div class="mb-6">
                <p class="text-gray-700 dark:text-gray-300 mb-2">Yakin ingin menghapus data absensi untuk:</p>
                <h5 id="hapus_nama" class="font-bold text-gray-900 dark:text-white text-lg"></h5>
                <p class="text-sm text-gray-500 mt-1">Tanggal: {{ \Carbon\Carbon::parse($tanggal)->format('d-m-Y') }}</p>
                <div class="mt-3 p-3 bg-warning-50 text-warning-700 rounded-lg text-sm dark:bg-warning-500/15 dark:text-warning-500">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Data yang dihapus akan kembali berstatus Alpha secara default.
                </div>
            </div>
            
            <div class="flex justify-end gap-3">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit" class="rounded-lg bg-error-500 px-4 py-2 text-white hover:bg-error-600">Hapus Data</button>
            </div>
        </form>
    </div>
</x-ui.modal>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('.btnEditStatus').on('click', function () {
                var studentId = $(this).data('id');
                var nama = $(this).data('nama');
                var status = $(this).data('status');
                var ket = $(this).data('keterangan');
                var masuk = $(this).data('masuk');
                var pulang = $(this).data('pulang');

                $('#edit_student_id').val(studentId);
                $('#edit_nama_label').text(nama);
                $('#edit_status').val(status);

                $('#edit_jam_masuk').val(masuk);
                $('#edit_jam_pulang').val(pulang);

                if (ket === '-') ket = '';
                $('#edit_keterangan').val(ket);
            });

            $('.btnHapus').on('click', function () {
                var studentId = $(this).data('id');
                var nama = $(this).data('nama');

                $('#hapus_student_id').val(studentId);
                $('#hapus_nama').text(nama);
            });
        });
    </script>
@endpush