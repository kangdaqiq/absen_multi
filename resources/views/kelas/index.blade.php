@extends('layouts.app')

@section('title', 'Manajemen Kelas')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between" x-data="{}">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Manajemen Kelas
    </h2>
    <div class="flex flex-wrap gap-2">
        <button @click="$dispatch('open-modal', 'modalTambahKelas')" class="inline-flex items-center justify-center gap-2.5 rounded-lg bg-brand-500 px-4 py-2 text-center font-medium text-white hover:bg-brand-600 transition">
            <i class="fas fa-plus"></i> Tambah Kelas
        </button>
    </div>
</div>

<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <!-- Header & Search -->
    <div class="flex flex-col sm:flex-row justify-between items-center px-5 py-4 border-b border-gray-200 dark:border-gray-800 gap-4">
        <h4 class="font-semibold text-gray-800 dark:text-white/90">Daftar Kelas</h4>
        
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
                    <th class="px-4 py-4 xl:pl-6 w-16">No</th>
                    <th class="px-4 py-4">Nama Kelas</th>
                    <th class="px-4 py-4">Wali Kelas</th>
                    @if(auth()->user()->school && auth()->user()->school->wa_enabled)
                    <th class="px-4 py-4">ID Grup WA</th>
                    @endif
                    <th class="px-4 py-4 text-center">Status Absen</th>
                    <th class="px-4 py-4 text-center">Status Report</th>
                    <th class="px-4 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse ($kelas as $k)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 xl:pl-6">
                            <p class="text-gray-500 dark:text-gray-400">{{ $loop->iteration + $kelas->firstItem() - 1 }}</p>
                        </td>
                        <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800">
                            <p class="font-medium text-gray-800 dark:text-white/90">{{ $k->nama_kelas }}</p>
                        </td>
                        <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800">
                            @if($k->waliKelas)
                                <a href="{{ route('guru.index') }}" class="text-brand-500 hover:underline">{{ $k->waliKelas->nama }}</a>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        @if(auth()->user()->school && auth()->user()->school->wa_enabled)
                        <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800">
                            @if($k->wa_group_id)
                                <span class="inline-flex rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500" title="{{ $k->wa_group_id }}">
                                    {{ Str::limit($k->wa_group_id, 15) }}
                                </span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        @endif
                        <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 text-center">
                            <form action="{{ route('kelas.toggle-status', $k->id) }}" method="POST">
                                @csrf
                                <button type="submit"
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-medium transition {{ $k->is_active_attendance ? 'bg-success-50 text-success-600 hover:bg-success-100 dark:bg-success-500/15 dark:text-success-500' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400' }}"
                                    title="{{ $k->is_active_attendance ? 'Nonaktifkan Absensi' : 'Aktifkan Absensi' }}">
                                    <i class="fas fa-{{ $k->is_active_attendance ? 'toggle-on' : 'toggle-off' }} mr-1"></i>
                                    {{ $k->is_active_attendance ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </form>
                        </td>
                        <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800 text-center">
                            @if(!$k->is_active_attendance)
                                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">Nonaktif</span>
                            @else
                                <form action="{{ route('kelas.toggle-report', $k->id) }}" method="POST">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-medium transition {{ $k->is_active_report ? 'bg-info-50 text-info-600 hover:bg-info-100 dark:bg-info-500/15 dark:text-info-500' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400' }}"
                                        title="{{ $k->is_active_report ? 'Nonaktifkan Report WA' : 'Aktifkan Report WA' }}">
                                        <i class="fas fa-{{ $k->is_active_report ? 'bell' : 'bell-slash' }} mr-1"></i>
                                        {{ $k->is_active_report ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </form>
                            @endif
                        </td>
                        <td class="border-b border-gray-100 px-4 py-4 dark:border-gray-800">
                            <div class="flex items-center justify-center gap-2" x-data="{}">
                                <!-- Edit -->
                                <button class="btnEdit text-warning-500 hover:text-warning-700 hover:bg-warning-50 p-2 rounded-lg transition" 
                                    data-id="{{ $k->id }}" data-nama="{{ $k->nama_kelas }}" data-wali="{{ $k->wali_kelas_id }}"
                                    data-wa-group-id="{{ $k->wa_group_id }}"
                                    @click="$dispatch('open-modal', 'modalEditKelas')" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                
                                <!-- Delete -->
                                <button class="btnHapus text-error-500 hover:text-error-700 hover:bg-error-50 p-2 rounded-lg transition" 
                                    data-id="{{ $k->id }}" data-nama="{{ $k->nama_kelas }}"
                                    @click="$dispatch('open-modal', 'modalHapusKelas')" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->school && auth()->user()->school->wa_enabled ? '7' : '6' }}" class="border-b border-gray-100 px-4 py-8 dark:border-gray-800 text-center text-gray-500 dark:text-gray-400">
                            Tidak ada data kelas ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
        {{ $kelas->links('vendor.pagination.tailwind') }}
    </div>
</div>

<!-- ========================= MODALS ========================= -->

<!-- Modal Tambah -->
<x-ui.modal id="modalTambahKelas" :is-open="false" x-data="{}">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Tambah Kelas</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('kelas.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Kelas</label>
                    <input type="text" name="nama_kelas" required class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Wali Kelas</label>
                    <select name="wali_kelas_id" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white select2">
                        <option value="">-- Pilih Wali Kelas --</option>
                        @foreach($gurus as $g)
                            <option value="{{ $g->id }}">{{ $g->nama }}</option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->school && auth()->user()->school->wa_enabled)
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">ID Grup WhatsApp</label>
                    <div class="relative">
                        <input type="text" name="wa_group_id" id="tambah_wa_group_id" placeholder="Contoh: 120363XXXXXX@g.us" class="w-full rounded-lg border border-gray-200 bg-transparent py-2 pl-4 pr-24 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <button type="button" onclick="loadWaGroups('tambah_wa_group_id')" class="absolute right-1 top-1 bottom-1 rounded-md bg-brand-500 px-3 py-1 text-sm font-medium text-white hover:bg-brand-600 transition">
                            <i class="fab fa-whatsapp"></i> Pilih
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Opsional. ID Grup WhatsApp untuk broadcast ke kelas ini.</p>
                </div>
                @endif
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">Simpan</button>
            </div>
        </form>
    </div>
</x-ui.modal>

<!-- Modal Edit -->
<x-ui.modal id="modalEditKelas" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Edit Kelas</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="#" method="POST" id="formEditKelas">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Kelas</label>
                    <input type="text" name="nama_kelas" id="edit_nama_kelas" required class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Wali Kelas</label>
                    <select name="wali_kelas_id" id="edit_wali_kelas" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white select2">
                        <option value="">-- Pilih Wali Kelas --</option>
                        @foreach($gurus as $g)
                            <option value="{{ $g->id }}">{{ $g->nama }}</option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->school && auth()->user()->school->wa_enabled)
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">ID Grup WhatsApp</label>
                    <div class="relative">
                        <input type="text" name="wa_group_id" id="edit_wa_group_id" placeholder="Contoh: 120363XXXXXX@g.us" class="w-full rounded-lg border border-gray-200 bg-transparent py-2 pl-4 pr-24 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <button type="button" onclick="loadWaGroups('edit_wa_group_id')" class="absolute right-1 top-1 bottom-1 rounded-md bg-brand-500 px-3 py-1 text-sm font-medium text-white hover:bg-brand-600 transition">
                            <i class="fab fa-whatsapp"></i> Pilih
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Opsional. ID Grup WhatsApp untuk broadcast ke kelas ini.</p>
                </div>
                @endif
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600">Update</button>
            </div>
        </form>
    </div>
</x-ui.modal>

<!-- Modal Hapus -->
<x-ui.modal id="modalHapusKelas" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-error-500">Hapus Kelas</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        <form action="#" method="POST" id="formHapusKelas">
            @csrf
            @method('DELETE')
            <p class="text-gray-700 dark:text-gray-300 mb-6">Yakin ingin menghapus kelas: <strong id="hapus_nama_kelas" class="text-gray-900 dark:text-white"></strong>?</p>
            <div class="flex justify-end gap-3">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">Batal</button>
                <button type="submit" class="rounded-lg bg-error-500 px-4 py-2 text-white hover:bg-error-600">Hapus</button>
            </div>
        </form>
    </div>
</x-ui.modal>

<!-- WA Group Modal -->
<x-ui.modal id="waGroupModal" :is-open="false" class="max-w-2xl">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Pilih Grup WhatsApp</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        
        <div id="waGroupLoading" class="text-center py-8">
            <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-brand-500 border-r-transparent align-[-0.125em]" role="status">
                <span class="!absolute !-m-px !h-px !w-px !overflow-hidden !whitespace-nowrap !border-0 !p-0 ![clip:rect(0,0,0,0)]">Loading...</span>
            </div>
            <p class="mt-3 text-gray-500 dark:text-gray-400">Mengambil daftar grup dari WhatsApp...</p>
        </div>
        
        <div id="waGroupError" class="hidden mb-4 rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/15 dark:text-error-500"></div>
        
        <div id="waGroupList" class="flex flex-col gap-2 max-h-96 overflow-y-auto pr-2">
            <!-- Groups will be loaded here -->
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="button" @click="open = false" class="rounded-lg bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Tutup</button>
        </div>
    </div>
</x-ui.modal>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('.btnEdit').on('click', function () {
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                var wali = $(this).data('wali');
                var waGroupId = $(this).data('wa-group-id');

                $('#edit_nama_kelas').val(nama);
                $('#edit_wali_kelas').val(wali).trigger('change');
                $('#edit_wa_group_id').val(waGroupId);
                $('#formEditKelas').attr('action', '{{ url('kelas') }}/' + id);
            });

            // Hapus
            $('.btnHapus').on('click', function () {
                var id = $(this).data('id');
                $('#hapus_nama_kelas').text($(this).data('nama'));
                $('#formHapusKelas').attr('action', '{{ url('kelas') }}/' + id);
            });
        });

        // WA Group Selector Logic
        let targetInputId = '';

        function loadWaGroups(inputId) {
            targetInputId = inputId;
            // dispatch open-modal event for Alpine modal
            window.dispatchEvent(new CustomEvent('open-modal', { detail: 'waGroupModal' }));

            // Reset state
            $('#waGroupLoading').removeClass('hidden');
            $('#waGroupList').html('');
            $('#waGroupError').addClass('hidden');

            // Fetch groups
            fetch('{{ route("api.whatsapp.groups") }}')
                .then(response => response.json())
                .then(data => {
                    $('#waGroupLoading').addClass('hidden');
                    console.log("WA Groups Data:", data); // Debug

                    if (data.success) {
                        if (data.groups.length === 0) {
                            $('#waGroupList').html('<div class="text-center text-gray-500 py-4">Tidak ada grup ditemukan.</div>');
                            return;
                        }

                        let html = '';
                        data.groups.forEach(group => {
                            html += `
                                <button type="button" class="flex flex-col items-start w-full rounded-lg border border-gray-200 p-4 hover:border-brand-500 hover:bg-brand-50 dark:border-gray-700 dark:hover:border-brand-500 dark:hover:bg-brand-500/10 transition text-left" 
                                    onclick="selectWaGroup('${group.jid}')">
                                    <div class="flex w-full justify-between items-center mb-1">
                                        <h6 class="font-semibold text-gray-800 dark:text-white/90">${group.name}</h6>
                                        <span class="text-xs text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">ID: ${group.jid.split('@')[0]}</span> 
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 break-all">${group.jid}</span>
                                </button>
                            `;
                        });
                        $('#waGroupList').html(html);
                    } else {
                        $('#waGroupError').text(data.message || 'Gagal mengambil data grup.').removeClass('hidden');
                    }
                })
                .catch(error => {
                    $('#waGroupLoading').addClass('hidden');
                    $('#waGroupError').text('Terjadi kesalahan koneksi ke server.').removeClass('hidden');
                    console.error('Error:', error);
                });
        }

        function selectWaGroup(jid) {
            if (targetInputId) {
                document.getElementById(targetInputId).value = jid;
            }
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'waGroupModal' }));
        }
    </script>
@endpush