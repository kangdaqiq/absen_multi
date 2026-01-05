@extends('layouts.app')

@section('title', 'Manajemen Mata Pelajaran')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manajemen Mata Pelajaran</h1>
        <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTambahMapel">
            <i class="fas fa-plus fa-sm"></i> Tambah Mapel
        </button>
    </div>

    @if (session('success'))
        <div class="alert alert-success border-left-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger border-left-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger border-left-danger" role="alert">
            <ul class="pl-4 my-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Mata Pelajaran</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Mata Pelajaran</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mapel as $m)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $m->nama_mapel }}</td>
                                <td>
                                    <button class="btn btn-sm btn-warning btnEdit" data-id="{{ $m->id }}"
                                        data-nama="{{ $m->nama_mapel }}" data-toggle="modal" data-target="#modalEditMapel">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btnHapus" data-id="{{ $m->id }}"
                                        data-nama="{{ $m->nama_mapel }}" data-toggle="modal" data-target="#modalHapusMapel">
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
    <div class="modal fade" id="modalTambahMapel" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="{{ route('mapel.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Mata Pelajaran</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Mata Pelajaran</label>
                            <input type="text" name="nama_mapel" class="form-control" placeholder="Contoh: Matematika"
                                required>
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
    <div class="modal fade" id="modalEditMapel" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="#" method="POST" id="formEditMapel">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Mata Pelajaran</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Mata Pelajaran</label>
                            <input type="text" name="nama_mapel" id="edit_nama_mapel" class="form-control" required>
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
    <div class="modal fade" id="modalHapusMapel" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="#" method="POST" id="formHapusMapel">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Hapus Mata Pelajaran</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Yakin ingin menghapus mapel: <strong id="hapus_nama_mapel"></strong>?</p>
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
                $('#edit_nama_mapel').val(nama);
                $('#formEditMapel').attr('action', '{{ secure_url('mapel') }}/' + id);
            });

            // Hapus
            $('#dataTable').on('click', '.btnHapus', function () {
                var id = $(this).data('id');
                $('#hapus_nama_mapel').text($(this).data('nama'));
                $('#formHapusMapel').attr('action', '{{ secure_url('mapel') }}/' + id);
            });
        });
    </script>
@endpush