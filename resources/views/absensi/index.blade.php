@extends('layouts.app')

@section('title', 'Data Absensi Harian')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Absensi Harian</h1>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filter Data</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('absensi.index') }}" method="GET" class="form-inline">
            <div class="form-group mb-2">
                <label for="tanggal" class="mr-2">Tanggal:</label>
                <input type="date" name="tanggal" id="tanggal" class="form-control" value="{{ $tanggal }}">
            </div>
            <div class="form-group mx-sm-3 mb-2">
                <label for="kelas_id" class="mr-2">Kelas:</label>
                <select name="kelas_id" id="kelas_id" class="form-control">
                    <option value="">Semua Kelas</option>
                    @foreach($allKelas as $k)
                        <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-primary mb-2">Tampilkan</button>
        </form>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Log Absensi: {{ \Carbon\Carbon::parse($tanggal)->format('d-m-Y') }}</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th>Kelas</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $row)
                    <tr>
                        <td>{{ $row->nama }}</td>
                        <td>{{ $row->kelas }}</td>
                        <td>{{ $row->jam_masuk != '-' ? \Carbon\Carbon::parse($row->jam_masuk)->format('H:i') : '-' }}</td>
                        <td>{{ $row->jam_pulang != '-' ? \Carbon\Carbon::parse($row->jam_pulang)->format('H:i') : '-' }}</td>
                        <td>
                            @if($row->status == 'H') <span class="badge badge-success">Hadir</span>
                            @elseif($row->status == 'I') <span class="badge badge-info">Izin</span>
                            @elseif($row->status == 'S') <span class="badge badge-warning">Sakit</span>
                            @elseif($row->status == 'T') <span class="badge badge-warning">Terlambat</span>
                            @elseif($row->status == 'B') <span class="badge badge-danger">Bolos</span>
                            @else <span class="badge badge-danger">Alpha</span>
                            @endif
                        </td>
                        <td>{{ $row->keterangan }}</td>
                        <td>
                            <button class="btn btn-sm btn-primary btnEditStatus"
                                data-id="{{ $row->id }}"
                                data-nama="{{ $row->nama }}"
                                data-status="{{ $row->status }}"
                                data-keterangan="{{ $row->keterangan }}"
                                data-masuk="{{ $row->jam_masuk }}"
                                data-pulang="{{ $row->jam_pulang }}"
                                data-toggle="modal" data-target="#modalEditStatus">
                                <i class="fas fa-edit"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Edit Status -->
<div class="modal fade" id="modalEditStatus" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('absensi.update') }}" method="POST">
            @csrf
            <!-- We send the date from filter, so it updates that specific day -->
            <input type="hidden" name="tanggal" value="{{ $tanggal }}">
            <input type="hidden" name="student_id" id="edit_student_id">

            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Update Status Absensi</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <h5 id="edit_nama_label" class="mb-3 font-weight-bold"></h5>
                    
                    <div class="form-group row">
                        <div class="col-6">
                            <label>Jam Masuk</label>
                            <input type="time" name="jam_masuk" id="edit_jam_masuk" class="form-control">
                        </div>
                        <div class="col-6">
                            <label>Jam Pulang</label>
                            <input type="time" name="jam_pulang" id="edit_jam_pulang" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option value="H">Hadir (H)</option>
                            <option value="T">Terlambat (T)</option>
                            <option value="I">Izin (I)</option>
                            <option value="S">Sakit (S)</option>
                            <option value="A">Alpha (A)</option>
                            <option value="B">Bolos (B)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea name="keterangan" id="edit_keterangan" class="form-control" rows="3"></textarea>
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

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable();

        $('.btnEditStatus').on('click', function() {
            var studentId = $(this).data('id');
            var nama = $(this).data('nama');
            var status = $(this).data('status');
            var ket = $(this).data('keterangan');
            var masuk = $(this).data('masuk');
            var pulang = $(this).data('pulang');

            $('#edit_student_id').val(studentId);
            $('#edit_nama_label').text(nama);
            $('#edit_status').val(status);
            
            // Handle time input value (needs HH:mm format)
            if(masuk === '-') masuk = '';
            if(pulang === '-') pulang = '';
            
            // Assuming time in DB is HH:mm:ss, input type=time needs HH:mm
            if(masuk.length > 5) masuk = masuk.substring(0, 5);
            if(pulang.length > 5) pulang = pulang.substring(0, 5);

            $('#edit_jam_masuk').val(masuk);
            $('#edit_jam_pulang').val(pulang);

            if(ket === '-') ket = '';
            $('#edit_keterangan').val(ket);
        });
    });
</script>
@endpush
