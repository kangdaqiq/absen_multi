@extends('layouts.app')

@section('title', 'Manajemen Jadwal')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Manajemen Jadwal</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Jadwal Per Hari</h6>
            <button class="btn btn-primary btn-sm float-right" data-toggle="modal" data-target="#modalAddJadwal">
                <i class="fas fa-plus"></i> Tambah Jadwal
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Hari</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Toleransi (Menit)</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jadwal as $j)
                            <tr>
                                <td>{{ $days[$j->index_hari] ?? 'Hari Ke-' . $j->index_hari }}</td>
                                <td>{{ substr($j->jam_masuk, 0, 5) }}</td>
                                <td>{{ substr($j->jam_pulang, 0, 5) }}</td>
                                <td>{{ $j->toleransi }}</td>
                                <td>
                                    @if($j->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-secondary">Non-Aktif</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning btnEdit" data-id="{{ $j->id }}"
                                        data-hari="{{ $j->index_hari }}" data-masuk="{{ $j->jam_masuk }}"
                                        data-pulang="{{ $j->jam_pulang }}" data-toleransi="{{ $j->toleransi }}"
                                        data-active="{{ $j->is_active }}" data-toggle="modal" data-target="#modalEditJadwal">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btnHapus" data-id="{{ $j->id }}"
                                        data-hari="{{ $days[$j->index_hari] ?? $j->index_hari }}" data-toggle="modal"
                                        data-target="#modalHapusJadwal">
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

    <!-- Modal Add -->
    <div class="modal fade" id="modalAddJadwal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('jadwal.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Tambah Jadwal</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Hari</label>
                            <select name="index_hari" class="form-control" required>
                                @foreach($days as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group row">
                            <div class="col-6">
                                <label>Jam Masuk</label>
                                <input type="time" name="jam_masuk" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label>Jam Pulang</label>
                                <input type="time" name="jam_pulang" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Toleransi Keterlambatan (Menit)</label>
                            <input type="number" name="toleransi" class="form-control" value="15" required>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="checkActiveAdd" checked>
                            <label class="form-check-label" for="checkActiveAdd">Aktifkan Jadwal?</label>
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
    <div class="modal fade" id="modalEditJadwal" tabindex="-1">
        <div class="modal-dialog">
            <form action="" method="POST" id="formEditJadwal">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title">Edit Jadwal</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <!-- Hari cannot be changed easily because logic assumes one per day. Simple lock -->
                        <div class="form-group">
                            <label>Hari (Tidak dapat diubah)</label>
                            <input type="text" id="edit_hari_label" class="form-control" readonly>
                        </div>
                        <div class="form-group row">
                            <div class="col-6">
                                <label>Jam Masuk</label>
                                <input type="time" name="jam_masuk" id="edit_masuk" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label>Jam Pulang</label>
                                <input type="time" name="jam_pulang" id="edit_pulang" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Toleransi (Menit)</label>
                            <input type="number" name="toleransi" id="edit_toleransi" class="form-control" required>
                        </div>
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="edit_active">
                            <label class="form-check-label" for="edit_active">Aktif?</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Hapus -->
    <div class="modal fade" id="modalHapusJadwal" tabindex="-1">
        <div class="modal-dialog">
            <form action="" method="POST" id="formHapusJadwal">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Hapus Jadwal</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Yakin ingin menghapus jadwal hari <strong id="hapus_hari"></strong>?</p>
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
                var hariIndex = $(this).data('hari');
                var masuk = $(this).data('masuk');
                var pulang = $(this).data('pulang');
                var tol = $(this).data('toleransi');
                var active = $(this).data('active');

                // Map index to name manually or just use index
                var days = { 1: 'Senin', 2: 'Selasa', 3: 'Rabu', 4: 'Kamis', 5: 'Jumat', 6: 'Sabtu', 7: 'Minggu' };

                $('#edit_hari_label').val(days[hariIndex]);
                $('#edit_masuk').val(masuk);
                $('#edit_pulang').val(pulang);
                $('#edit_toleransi').val(tol);
                $('#edit_active').prop('checked', active == 1);

                $('#formEditJadwal').attr('action', '{{ secure_url('jadwal') }}/' + id);
            });

            $('.btnHapus').on('click', function () {
                var id = $(this).data('id');
                var hari = $(this).data('hari');
                $('#hapus_hari').text(hari);
                $('#formHapusJadwal').attr('action', '{{ secure_url('jadwal') }}/' + id);
            });
        });
    </script>
@endpush