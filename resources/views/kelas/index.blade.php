@extends('layouts.app')

@section('title', 'Manajemen Kelas')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manajemen Kelas</h1>
        <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTambahKelas">
            <i class="fas fa-plus fa-sm"></i> Tambah Kelas
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Kelas</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Kelas</th>
                            <th>Wali Kelas</th>
                            <th>ID Grup WA</th>
                            <th>Status Absen</th>
                            <th>Status Report</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kelas as $k)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $k->nama_kelas }}</td>
                                <td>
                                    @if($k->waliKelas)
                                        <a href="{{ route('guru.index') }}">{{ $k->waliKelas->nama }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($k->wa_group_id)
                                        <span class="badge badge-success">{{ $k->wa_group_id }}</span>
                                    @else
                                        <span class="badge badge-secondary">-</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('kelas.toggle-status', $k->id) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="btn btn-sm btn-{{ $k->is_active_attendance ? 'success' : 'secondary' }}"
                                            title="{{ $k->is_active_attendance ? 'Nonaktifkan Absensi' : 'Aktifkan Absensi' }}">
                                            <i class="fas fa-{{ $k->is_active_attendance ? 'toggle-on' : 'toggle-off' }}"></i>
                                            {{ $k->is_active_attendance ? 'Aktif' : 'Nonaktif' }}
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    @if(!$k->is_active_attendance)
                                        <span class="badge badge-secondary">Nonaktif</span>
                                    @else
                                        <form action="{{ route('kelas.toggle-report', $k->id) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-sm btn-{{ $k->is_active_report ? 'info' : 'secondary' }}"
                                                title="{{ $k->is_active_report ? 'Nonaktifkan Report WA' : 'Aktifkan Report WA' }}">
                                                <i class="fas fa-{{ $k->is_active_report ? 'bell' : 'bell-slash' }}"></i>
                                                {{ $k->is_active_report ? 'Aktif' : 'Nonaktif' }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning btnEdit" data-id="{{ $k->id }}"
                                        data-nama="{{ $k->nama_kelas }}" data-wali="{{ $k->wali_kelas_id }}"
                                        data-wa-group-id="{{ $k->wa_group_id }}" data-toggle="modal"
                                        data-target="#modalEditKelas">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btnHapus" data-id="{{ $k->id }}"
                                        data-nama="{{ $k->nama_kelas }}" data-toggle="modal" data-target="#modalHapusKelas">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal fade" id="modalTambahKelas" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="{{ route('kelas.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Kelas</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Kelas</label>
                            <input type="text" name="nama_kelas" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Wali Kelas</label>
                            <select name="wali_kelas_id" class="form-control select2">
                                <option value="">-- Pilih Wali Kelas --</option>
                                @foreach($gurus as $g)
                                    <option value="{{ $g->id }}">{{ $g->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>ID Grup WhatsApp</label>
                            <div class="input-group">
                                <input type="text" name="wa_group_id" id="tambah_wa_group_id" class="form-control"
                                    placeholder="Contoh: 120363XXXXXX@g.us">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="button"
                                        onclick="loadWaGroups('tambah_wa_group_id')">
                                        <i class="fab fa-whatsapp"></i> Pilih
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Opsional. ID Grup WhatsApp untuk broadcast ke kelas
                                ini.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal fade" id="modalEditKelas" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="#" method="POST" id="formEditKelas">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Kelas</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Kelas</label>
                            <input type="text" name="nama_kelas" id="edit_nama_kelas" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Wali Kelas</label>
                            <select name="wali_kelas_id" id="edit_wali_kelas" class="form-control select2">
                                <option value="">-- Pilih Wali Kelas --</option>
                                @foreach($gurus as $g)
                                    <option value="{{ $g->id }}">{{ $g->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>ID Grup WhatsApp</label>
                            <div class="input-group">
                                <input type="text" name="wa_group_id" id="edit_wa_group_id" class="form-control"
                                    placeholder="Contoh: 120363XXXXXX@g.us">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="button"
                                        onclick="loadWaGroups('edit_wa_group_id')">
                                        <i class="fab fa-whatsapp"></i> Pilih
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Opsional. ID Grup WhatsApp untuk broadcast ke kelas
                                ini.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Hapus -->
    <div class="modal fade" id="modalHapusKelas" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="#" method="POST" id="formHapusKelas">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Hapus Kelas</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Yakin ingin menghapus kelas: <strong id="hapus_nama_kelas"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

<!-- WA Group Modal -->
<div class="modal fade" id="waGroupModal" tabindex="-1" role="dialog" aria-labelledby="waGroupModalLabel"
    aria-hidden="true" style="z-index: 100000;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="waGroupModalLabel">Pilih Grup WhatsApp</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="waGroupLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Mengambil daftar grup...</p>
                </div>
                <div id="waGroupError" class="alert alert-danger d-none"></div>
                <div class="list-group" id="waGroupList">
                    <!-- Groups will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#dataTable').DataTable();

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
            $('#dataTable').on('click', '.btnHapus', function () {
                var id = $(this).data('id');
                $('#hapus_nama_kelas').text($(this).data('nama'));
                $('#formHapusKelas').attr('action', '{{ url('kelas') }}/' + id);
            });
        });

        // WA Group Selector Logic
        let targetInputId = '';

        function loadWaGroups(inputId) {
            targetInputId = inputId;
            $('#waGroupModal').modal('show');

            // Reset state
            $('#waGroupLoading').removeClass('d-none');
            $('#waGroupList').html('');
            $('#waGroupError').addClass('d-none');

            // Fetch groups
            fetch('{{ route("api.whatsapp.groups") }}')
                .then(response => response.json())
                .then(data => {
                    $('#waGroupLoading').addClass('d-none');
                    console.log("WA Groups Data:", data); // Debug

                    if (data.success) {
                        if (data.groups.length === 0) {
                            $('#waGroupList').html('<div class="text-center text-muted p-3">Tidak ada grup ditemukan.</div>');
                            return;
                        }

                        let html = '';
                        data.groups.forEach(group => {
                            html += `
                                    <button type="button" class="list-group-item list-group-item-action" 
                                        onclick="selectWaGroup('${group.jid}')">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1 font-weight-bold">${group.name}</h6>
                                            <small class="text-muted">ID: ${group.jid.split('@')[0]}</small> 
                                        </div>
                                        <small class="text-muted d-block text-truncate">${group.jid}</small>
                                    </button>
                                `;
                        });
                        $('#waGroupList').html(html);
                    } else {
                        $('#waGroupError').text(data.message || 'Gagal mengambil data grup.').removeClass('d-none');
                    }
                })
                .catch(error => {
                    $('#waGroupLoading').addClass('d-none');
                    $('#waGroupError').text('Terjadi kesalahan koneksi.').removeClass('d-none');
                    console.error('Error:', error);
                });
        }

        function selectWaGroup(jid) {
            if (targetInputId) {
                document.getElementById(targetInputId).value = jid;
            }
            $('#waGroupModal').modal('hide');
        }
    </script>
@endpush