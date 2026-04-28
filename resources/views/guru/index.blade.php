@extends('layouts.app')

@php
    $school = auth()->user()->school ?? null;
    $labelKaryawan = $school?->employeeLabel() ?? 'Guru';
    $labelNIP = $school?->nipLabel() ?? 'NIP';
@endphp

@section('title', 'Data ' . $labelKaryawan)

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" x-data="{}">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Data {{ $labelKaryawan }}
    </h2>
    <div class="flex flex-wrap gap-2">
        <button @click="$dispatch('open-modal', 'modalImportGuru')" class="inline-flex items-center justify-center gap-2.5 rounded-lg bg-success-500 px-4 py-2 text-center font-medium text-white hover:bg-success-600 transition">
            <i class="fas fa-file-excel"></i> Import Excel
        </button>
        <button @click="$dispatch('open-modal', 'modalTambahGuru')" class="inline-flex items-center justify-center gap-2.5 rounded-lg bg-brand-500 px-4 py-2 text-center font-medium text-white hover:bg-brand-600 transition">
            <i class="fas fa-plus"></i> Tambah {{ $labelKaryawan }}
        </button>
    </div>
</div>

<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <!-- Header & Search -->
    <div class="flex flex-col sm:flex-row justify-between items-center px-5 py-4 border-b border-gray-200 dark:border-gray-800 gap-4">
        <h4 class="font-semibold text-gray-800 dark:text-white/90">Tabel Data {{ $labelKaryawan }}</h4>
        
        <div class="relative w-full sm:w-64">
            <input type="text" id="clientSearch" placeholder="Cari di halaman ini..." 
                class="client-search w-full rounded-lg border border-gray-200 bg-transparent py-2 pl-4 pr-10 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:focus:border-brand-500 text-gray-800 dark:text-white/90">
            <button type="button" class="absolute right-0 top-0 h-full px-3 text-gray-500 hover:text-brand-500 dark:text-gray-400">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>

    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                    <th class="px-4 py-4 xl:pl-6">No</th>
                    <th class="px-4 py-4">Nama</th>
                    <th class="px-4 py-4">{{ $labelNIP }}</th>
                    <th class="px-4 py-4">No WhatsApp</th>
                    <th class="px-4 py-4 text-center">UID RFID</th>
                    <th class="px-4 py-4 text-center">ID Finger</th>
                    <th class="px-4 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse ($guru as $g)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 xl:pl-6">
                            <p class="text-gray-500 dark:text-gray-400">{{ $loop->iteration + $guru->firstItem() - 1 }}</p>
                        </td>
                        <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800">
                            <p class="font-medium text-gray-800 dark:text-white/90">{{ $g->nama }}</p>
                        </td>
                        <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800">
                            <p class="text-gray-500 dark:text-gray-400">{{ $g->nip ?: '-' }}</p>
                        </td>
                        <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800">
                            <p class="text-gray-500 dark:text-gray-400">{{ $g->no_wa ?: '-' }}</p>
                        </td>
                        <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 text-center">
                            @if($g->uid_rfid)
                                <span class="inline-flex rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-500">{{ $g->uid_rfid }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 text-center">
                            @if($g->id_finger)
                                <span class="inline-flex rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">{{ $g->id_finger }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800">
                            <div class="flex items-center justify-center gap-2" x-data="{}">
                                <!-- Enroll RFID -->
                                <button class="btnEnroll text-info-500 hover:text-info-700 hover:bg-info-50 p-2 rounded-lg transition" 
                                    data-id="{{ $g->id }}" data-nama="{{ $g->nama }}" data-uid="{{ $g->uid_rfid }}"
                                    @click="$dispatch('open-modal', 'modalEnrollRFID')" title="Registrasi RFID">
                                    <i class="fas fa-rss"></i>
                                </button>
                                
                                <!-- Enroll Fingerprint -->
                                <button class="btnFinger text-success-500 hover:text-success-700 hover:bg-success-50 p-2 rounded-lg transition" 
                                    data-id="{{ $g->id }}" data-nama="{{ $g->nama }}" data-finger="{{ $g->id_finger }}"
                                    @click="$dispatch('open-modal', 'modalEnrollFinger')" title="Registrasi Sidik Jari">
                                    <i class="fas fa-fingerprint"></i>
                                </button>
                                
                                <!-- Edit -->
                                <button class="btnEdit text-warning-500 hover:text-warning-700 hover:bg-warning-50 p-2 rounded-lg transition" 
                                    data-id="{{ $g->id }}" data-nama="{{ $g->nama }}" data-nip="{{ $g->nip }}" data-wa="{{ $g->no_wa }}" data-rfid="{{ $g->uid_rfid }}"
                                    @click="$dispatch('open-modal', 'modalEditGuru')" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <!-- Delete -->
                                <button class="btnHapus text-error-500 hover:text-error-700 hover:bg-error-50 p-2 rounded-lg transition" 
                                    data-id="{{ $g->id }}" data-nama="{{ $g->nama }}"
                                    @click="$dispatch('open-modal', 'modalHapusGuru')" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="border-b border-gray-100 px-4 py-8 dark:border-gray-800 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data {{ strtolower($labelKaryawan) }} ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
        {{ $guru->links('vendor.pagination.tailwind') }}
    </div>
</div>

<!-- ========================= MODALS ========================= -->

<!-- Modal Tambah -->
<x-ui.modal id="modalTambahGuru" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Tambah {{ $labelKaryawan }}</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('guru.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Lengkap</label>
                    <input type="text" name="nama" required class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $labelNIP }}</label>
                    <input type="text" name="nip" placeholder="{{ $school && $school->isOffice() ? 'ID Pegawai (opsional)' : 'NIP' }}" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">No WhatsApp</label>
                    <input type="text" name="no_wa" placeholder="08xxx atau 628xxx" pattern="^(08|628)[0-9]{8,13}$" required class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500">Format: 08xxx atau 628xxx (8-13 digit)</p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">Simpan</button>
            </div>
        </form>
    </div>
</x-ui.modal>

<!-- Modal Edit -->
<x-ui.modal id="modalEditGuru" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Edit {{ $labelKaryawan }}</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="#" method="POST" id="formEditGuru">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Lengkap</label>
                    <input type="text" name="nama" id="edit_nama" required class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $labelNIP }}</label>
                    <input type="text" name="nip" id="edit_nip" placeholder="{{ $school && $school->isOffice() ? 'ID Pegawai (opsional)' : 'NIP' }}" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">No WhatsApp</label>
                    <input type="text" name="no_wa" id="edit_wa" placeholder="08xxx atau 628xxx" pattern="^(08|628)[0-9]{8,13}$" required class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">UID RFID (readonly)</label>
                    <input type="text" name="uid_rfid" id="edit_rfid" readonly class="w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2 outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-gray-400">
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">Update</button>
            </div>
        </form>
    </div>
</x-ui.modal>

<!-- Modal Hapus -->
<x-ui.modal id="modalHapusGuru" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-error-500">Hapus {{ $labelKaryawan }}</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="#" method="POST" id="formHapusGuru">
            @csrf
            @method('DELETE')
            <p class="text-gray-700 dark:text-gray-300 mb-6">Yakin ingin menghapus {{ strtolower($labelKaryawan) }}: <strong id="hapus_nama" class="text-gray-900 dark:text-white"></strong>?</p>
            <div class="flex justify-end gap-3">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit" class="rounded-lg bg-error-500 px-4 py-2 text-white hover:bg-error-600">Hapus</button>
            </div>
        </form>
    </div>
</x-ui.modal>

<!-- Modal Import -->
<x-ui.modal id="modalImportGuru" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Import Data {{ $labelKaryawan }}</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('guru.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-4 rounded-lg bg-info-50 p-4 text-sm text-info-700 dark:bg-info-500/15 dark:text-info-500">
                <i class="fas fa-info-circle mr-1"></i> Gunakan file Excel (.xlsx) dengan format kolom: <strong>Nama, {{ $labelNIP }}, No WA</strong>.
            </div>
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih File Excel</label>
                    <input type="file" name="fileExcel" required accept=".xlsx,.xls" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <a href="{{ route('guru.template') }}" class="text-sm font-medium text-brand-500 hover:underline"><i class="fas fa-download mr-1"></i> Download Template Excel</a>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit" class="rounded-lg bg-success-500 px-4 py-2 text-white hover:bg-success-600">Import Data</button>
            </div>
        </form>
    </div>
</x-ui.modal>

<!-- Modal Enroll RFID -->
<x-ui.modal id="modalEnrollRFID" :is-open="false">
    <div class="p-6 text-center">
        <div class="flex justify-end mb-2">
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <div class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/15">
            <i class="fas fa-id-card fa-2x"></i>
        </div>
        <h3 class="mb-2 text-2xl font-bold text-gray-800 dark:text-white/90">Registrasi RFID</h3>
        <h5 id="enroll_nama" class="font-medium text-gray-600 dark:text-gray-400 mb-6"></h5>

        <div id="uid_wrapper" class="hidden mb-4 rounded-lg bg-success-50 p-3 text-success-700 dark:bg-success-500/15 dark:text-success-500">
            UID Terdaftar: <strong id="enroll_uid" class="text-lg"></strong>
        </div>

        <div id="enroll_status" class="mb-6 h-10 flex items-center justify-center"></div>

        <div class="flex flex-col gap-3">
            <button type="button" class="rounded-lg bg-brand-500 p-3 font-medium text-white hover:bg-brand-600 transition" id="btnMulaiEnroll">
                <i class="fas fa-rss mr-1"></i> Mulai Scan Kartu
            </button>
            <button type="button" class="rounded-lg bg-error-500 p-3 font-medium text-white hover:bg-error-600 transition disabled:opacity-50 disabled:cursor-not-allowed" id="btnHapusUID" disabled>
                <i class="fas fa-trash mr-1"></i> Hapus UID
            </button>
        </div>
    </div>
</x-ui.modal>

<!-- Modal Enroll Fingerprint -->
<x-ui.modal id="modalEnrollFinger" :is-open="false">
    <div class="p-6 text-center">
        <div class="flex justify-end mb-2">
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <div class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-success-50 text-success-500 dark:bg-success-500/15">
            <i class="fas fa-fingerprint fa-2x"></i>
        </div>
        <h3 class="mb-2 text-2xl font-bold text-gray-800 dark:text-white/90">Registrasi Sidik Jari</h3>
        <h5 id="enroll_finger_nama" class="font-medium text-gray-600 dark:text-gray-400 mb-6"></h5>

        <div class="mb-4 text-left">
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Device</label>
            <select id="finger_device_id" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                <option value="">-- Pilih Device --</option>
                @foreach($devices as $dev)
                    <option value="{{ $dev->id }}">{{ $dev->name }} ({{ ucfirst($dev->type) }})</option>
                @endforeach
            </select>
        </div>

        <div id="finger_wrapper" class="hidden mb-4 rounded-lg bg-success-50 p-3 text-success-700 dark:bg-success-500/15 dark:text-success-500">
            ID Sidik Jari: <strong id="enroll_finger_id" class="text-lg"></strong>
        </div>

        <div id="enroll_finger_status" class="mb-6 h-10 flex items-center justify-center"></div>

        <div class="flex flex-col gap-3">
            <button type="button" class="rounded-lg bg-success-500 p-3 font-medium text-white hover:bg-success-600 transition" id="btnMulaiEnrollFinger">
                <i class="fas fa-fingerprint mr-1"></i> Mulai Scan Sidik Jari
            </button>
            <button type="button" class="rounded-lg bg-error-500 p-3 font-medium text-white hover:bg-error-600 transition disabled:opacity-50 disabled:cursor-not-allowed" id="btnHapusFinger" disabled>
                <i class="fas fa-trash mr-1"></i> Hapus Sidik Jari
            </button>
        </div>
    </div>
</x-ui.modal>

@endsection

@push('scripts')
    <!-- Minimal jQuery for legacy AJAX functionality, consider refactoring to Alpine/Fetch later -->
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            // Edit
            $('.btnEdit').on('click', function () {
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                var nip = $(this).data('nip');
                var wa = $(this).data('wa');
                var rfid = $(this).data('rfid');

                $('#edit_nama').val(nama);
                $('#edit_nip').val(nip);
                $('#edit_wa').val(wa);
                $('#edit_rfid').val(rfid);

                $('#formEditGuru').attr('action', '{{ url('guru') }}/' + id);
            });

            // Hapus
            $('.btnHapus').on('click', function () {
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                $('#hapus_nama').text(nama);
                $('#formHapusGuru').attr('action', '{{ url('guru') }}/' + id);
            });

            // ==========================
            //  ENROLL LOGIC
            // ==========================
            let enrollGuruId = null;
            let enrollInterval = null;

            $('.btnEnroll').on('click', function () {
                enrollGuruId = $(this).data('id');
                $('#enroll_nama').text($(this).data('nama'));

                // Reset UI
                $('#enroll_status').html('');
                $('#uid_wrapper').addClass('hidden');
                $('#enroll_uid').text('');
                $('#btnHapusUID').prop('disabled', true);

                // Check existing UID
                var uid = $(this).data('uid');
                if (uid) {
                    $('#enroll_uid').text(uid);
                    $('#uid_wrapper').removeClass('hidden');
                    $('#btnHapusUID').prop('disabled', false);
                }
            });

            $('#btnMulaiEnroll').on('click', function () {
                if (!enrollGuruId) return;

                $('#enroll_status').html('<span class="text-brand-500 animate-pulse"><i class="fas fa-spinner fa-spin mr-2"></i>Silakan tempelkan kartu RFID...</span>');

                // Request Enroll
                $.post('{{ url('guru') }}/' + enrollGuruId + '/enroll', {
                    _token: '{{ csrf_token() }}'
                }, function (res) {
                    if (res.ok) {
                        startEnrollPolling(enrollGuruId);
                    } else {
                        $('#enroll_status').html('<span class="text-error-500"><i class="fas fa-times-circle mr-1"></i>Gagal request enrollment.</span>');
                    }
                });
            });

            function startEnrollPolling(id) {
                if (enrollInterval) clearInterval(enrollInterval);
                let counter = 0;

                enrollInterval = setInterval(function () {
                    counter++;
                    if (counter > 20) { // 30 sec timeout
                        clearInterval(enrollInterval);
                        $('#enroll_status').html('<span class="text-warning-500"><i class="fas fa-exclamation-triangle mr-1"></i>Waktu habis. Coba lagi.</span>');
                        return;
                    }

                    $.get('{{ url('guru') }}/' + id + '/enroll-check', function (res) {
                        if (res.ok && res.uid) {
                            clearInterval(enrollInterval);
                            $('#enroll_uid').text(res.uid);
                            $('#uid_wrapper').removeClass('hidden');
                            $('#btnHapusUID').prop('disabled', false);
                            $('#enroll_status').html('<span class="text-success-500 font-bold"><i class="fas fa-check-circle mr-1"></i> Berhasil! Menyegarkan...</span>');

                            setTimeout(function () { location.reload(); }, 1500);
                        }
                    });
                }, 1500);
            }

            $('#btnHapusUID').on('click', function () {
                if (!enrollGuruId) return;
                if (!confirm('Hapus UID RFID guru ini?')) return;

                $.post('{{ url('guru') }}/' + enrollGuruId + '/delete-uid', {
                    _token: '{{ csrf_token() }}'
                }, function (res) {
                    if (res.ok) {
                        $('#uid_wrapper').addClass('hidden');
                        $('#enroll_uid').text('');
                        $('#btnHapusUID').prop('disabled', true);
                        $('#enroll_status').html('<span class="text-warning-500">UID dihapus.</span>');
                        setTimeout(function () { location.reload(); }, 1000);
                    }
                });
            });

            // Handle modal close to cancel enrollment
            window.addEventListener('close-modal', function(e) {
                if(e.detail === 'modalEnrollRFID') {
                    if (enrollInterval) clearInterval(enrollInterval);
                    if (enrollGuruId) {
                        $.post('{{ url('guru') }}/' + enrollGuruId + '/enroll-cancel', {
                            _token: '{{ csrf_token() }}'
                        });
                    }
                    enrollGuruId = null;
                }
                
                if(e.detail === 'modalEnrollFinger') {
                    if (enrollFingerInterval) clearInterval(enrollFingerInterval);
                    if (enrollFingerId) {
                        $.post('{{ url('guru') }}/' + enrollFingerId + '/enroll-finger-cancel', {
                            _token: '{{ csrf_token() }}'
                        });
                    }
                    enrollFingerId = null;
                }
            });

            // ==========================
            //  FINGERPRINT ENROLL LOGIC
            // ==========================
            let enrollFingerId = null;
            let enrollFingerInterval = null;

            $('.btnFinger').on('click', function () {
                enrollFingerId = $(this).data('id');
                $('#enroll_finger_nama').text($(this).data('nama'));

                // Reset UI
                $('#enroll_finger_status').html('');
                $('#finger_wrapper').addClass('hidden');
                $('#enroll_finger_id').text('');
                $('#btnHapusFinger').prop('disabled', true);
                $('#finger_device_id').val('');

                // Check existing Finger ID
                var fingerId = $(this).data('finger');
                if (fingerId) {
                    $('#enroll_finger_id').text(fingerId);
                    $('#finger_wrapper').removeClass('hidden');
                    $('#btnHapusFinger').prop('disabled', false);
                }
            });

            $('#btnMulaiEnrollFinger').on('click', function () {
                if (!enrollFingerId) return;

                var deviceId = $('#finger_device_id').val();
                if (!deviceId) {
                    alert('Pilih device terlebih dahulu!');
                    return;
                }

                $('#enroll_finger_status').html('<span class="text-success-500 animate-pulse"><i class="fas fa-spinner fa-spin mr-2"></i>Silakan tempelkan jari pada sensor...</span>');

                // Request Enroll with device_id
                $.post('{{ url('guru') }}/' + enrollFingerId + '/enroll-finger', {
                    _token: '{{ csrf_token() }}',
                    device_id: deviceId
                }, function (res) {
                    if (res.ok) {
                        startFingerEnrollPolling(enrollFingerId);
                    } else {
                        $('#enroll_finger_status').html('<span class="text-error-500"><i class="fas fa-times-circle mr-1"></i>Gagal request enrollment.</span>');
                    }
                });
            });

            function startFingerEnrollPolling(id) {
                if (enrollFingerInterval) clearInterval(enrollFingerInterval);
                let counter = 0;

                enrollFingerInterval = setInterval(function () {
                    counter++;
                    if (counter > 40) { // 60 sec timeout
                        clearInterval(enrollFingerInterval);
                        $('#enroll_finger_status').html('<span class="text-warning-500"><i class="fas fa-exclamation-triangle mr-1"></i>Waktu habis. Coba lagi.</span>');
                        return;
                    }

                    $.get('{{ url('guru') }}/' + id + '/enroll-finger-check', function (res) {
                        if (res.ok && res.id_finger) {
                            clearInterval(enrollFingerInterval);
                            $('#enroll_finger_id').text(res.id_finger);
                            $('#finger_wrapper').removeClass('hidden');
                            $('#btnHapusFinger').prop('disabled', false);
                            $('#enroll_finger_status').html('<span class="text-success-500 font-bold"><i class="fas fa-check-circle mr-1"></i> Berhasil! Menyegarkan...</span>');

                            setTimeout(function () { location.reload(); }, 1500);
                        }
                    });
                }, 1500);
            }

            $('#btnHapusFinger').on('click', function () {
                if (!enrollFingerId) return;
                if (!confirm('Hapus sidik jari guru ini dari semua device?')) return;

                $.post('{{ url('guru') }}/' + enrollFingerId + '/delete-finger', {
                    _token: '{{ csrf_token() }}'
                }, function (res) {
                    if (res.ok) {
                        $('#finger_wrapper').addClass('hidden');
                        $('#enroll_finger_id').text('');
                        $('#btnHapusFinger').prop('disabled', true);
                        $('#enroll_finger_status').html('<span class="text-warning-500">Sidik jari dihapus.</span>');
                        setTimeout(function () { location.reload(); }, 1000);
                    }
                });
            });
        });
    </script>
@endpush