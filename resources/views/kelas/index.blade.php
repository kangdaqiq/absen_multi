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
                            <th>Status Absen</th>
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
                                    <button class="btn btn-sm btn-warning btnEdit" data-id="{{ $k->id }}"
                                        data-nama="{{ $k->nama_kelas }}" data-wali="{{ $k->wali_kelas_id }}" data-toggle="modal"
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

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#dataTable').DataTable();

            $('.btnEdit').on('click', function () {
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                var wali = $(this).data('wali');

                $('#edit_nama_kelas').val(nama);
                $('#edit_wali_kelas').val(wali).trigger('change');
                $('#formEditKelas').attr('action', '{{ secure_url('kelas') }}/' + id);
            });

            // Hapus
            $('#dataTable').on('click', '.btnHapus', function () {
                var id = $(this).data('id');
                $('#hapus_nama_kelas').text($(this).data('nama')); // Changed to match original target element
                $('#formHapusKelas').attr('action', '{{ secure_url('kelas') }}/' + id);
            });
        });
    </script>
@endpush