@extends('layouts.app')

@section('title', 'Data Guru')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Data Guru</h1>
        <div>
            <button class="btn btn-success shadow-sm mr-2" data-toggle="modal" data-target="#modalImportGuru">
                <i class="fas fa-file-excel fa-sm"></i> Import Excel
            </button>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTambahGuru">
                <i class="fas fa-plus fa-sm"></i> Tambah Guru
            </button>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Tabel Data Guru</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama</th>
                            <th>NIP</th>
                            <th>No WhatsApp</th>
                            <th>UID RFID</th>
                            <th>ID Finger</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($guru as $g)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $g->nama }}</td>
                                <td>{{ $g->nip }}</td>
                                <td>{{ $g->no_wa }}</td>
                                <td>{{ $g->uid_rfid }}</td>
                                <td>{{ $g->id_finger }}</td>
                                <td>
                                    <button class="btn btn-sm btn-info btnEnroll" data-id="{{ $g->id }}"
                                        data-nama="{{ $g->nama }}" data-uid="{{ $g->uid_rfid }}" data-toggle="modal"
                                        data-target="#modalEnrollRFID">
                                        <i class="fas fa-rss"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning btnEdit" data-id="{{ $g->id }}"
                                        data-nama="{{ $g->nama }}" data-nip="{{ $g->nip }}" data-wa="{{ $g->no_wa }}"
                                        data-rfid="{{ $g->uid_rfid }}" data-toggle="modal" data-target="#modalEditGuru">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-dark btnFinger" data-id="{{ $g->id }}"
                                        data-nama="{{ $g->nama }}" data-finger="{{ $g->id_finger }}" data-toggle="modal"
                                        data-target="#modalEnrollFinger">
                                        <i class="fas fa-fingerprint"></i>
                                    </button>

                                    <button class="btn btn-sm btn-danger btnHapus" data-id="{{ $g->id }}"
                                        data-nama="{{ $g->nama }}" data-toggle="modal" data-target="#modalHapusGuru">
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
    <div class="modal fade" id="modalTambahGuru" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="{{ route('guru.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Guru</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Guru</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>NIP</label>
                            <input type="text" name="nip" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>No WhatsApp</label>
                            <input type="text" name="no_wa" class="form-control" placeholder="08xxx atau 628xxx"
                                pattern="^(08|628)[0-9]{8,13}$" required>
                            <small class="form-text text-muted">Format: 08xxx atau 628xxx (8-13 digit)</small>
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
    <div class="modal fade" id="modalEditGuru" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="#" method="POST" id="formEditGuru">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Guru</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Guru</label>
                            <input type="text" name="nama" id="edit_nama" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>NIP</label>
                            <input type="text" name="nip" id="edit_nip" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>No WhatsApp</label>
                            <input type="text" name="no_wa" id="edit_wa" class="form-control"
                                placeholder="08xxx atau 628xxx" pattern="^(08|628)[0-9]{8,13}$" required>
                            <small class="form-text text-muted">Format: 08xxx atau 628xxx (8-13 digit)</small>
                        </div>
                        <div class="form-group">
                            <label>UID RFID</label>
                            <input type="text" name="uid_rfid" id="edit_rfid" class="form-control">
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

    <!-- Modal Import -->
    <div class="modal fade" id="modalImportGuru" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="{{ route('guru.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Import Data Guru</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Gunakan file Excel (.xlsx) dengan format kolom: <strong>Nama,
                                NIP, No WA</strong>.
                        </div>
                        <div class="form-group">
                            <label>Pilih File Excel</label>
                            <input type="file" name="fileExcel" class="form-control-file" required>
                        </div>
                        <div class="mt-2">
                            <a href="{{ route('guru.template') }}" class="small font-weight-bold"><i
                                    class="fas fa-download"></i> Download Template</a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">Import</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Hapus -->
    <div class="modal fade" id="modalHapusGuru" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="#" method="POST" id="formHapusGuru">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Hapus Guru</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Yakin ingin menghapus guru: <strong id="hapus_nama"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Modal Enroll RFID -->
    <div class="modal fade" id="modalEnrollRFID" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-id-card"></i> Registrasi RFID Guru</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center">
                    <h5 id="enroll_nama" class="font-weight-bold mb-3"></h5>

                    <div id="uid_wrapper" class="d-none mb-3">
                        <div class="alert alert-success">
                            UID Terdaftar: <strong id="enroll_uid" class="h4"></strong>
                        </div>
                    </div>

                    <div id="enroll_status" class="mb-3"></div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-lg btn-block" id="btnMulaiEnroll">
                            <i class="fas fa-rss"></i> Mulai Scan Kartu
                        </button>
                        <button type="button" class="btn btn-danger btn-block mt-2" id="btnHapusUID" disabled>
                            <i class="fas fa-trash"></i> Hapus UID
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Enroll Fingerprint -->
    <div class="modal fade" id="modalEnrollFinger" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-fingerprint"></i> Registrasi Sidik Jari Guru</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center">
                    <h5 id="enroll_finger_nama" class="font-weight-bold mb-3"></h5>

                    <div class="form-group text-left">
                        <label>Pilih Device (Target):</label>
                        <select id="finger_device_id" class="form-control">
                            <option value="">-- Pilih Device --</option>
                            @foreach($devices as $d)
                                @if($d->type == 'fingerprint' || $d->type == 'rfid_fingerprint')
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>

                    <div id="finger_wrapper" class="d-none mb-3">
                        <div class="alert alert-success">
                            ID Finger Terdaftar: <strong id="enroll_finger_id" class="h4"></strong>
                        </div>
                    </div>

                    <div id="enroll_finger_status" class="mb-3"></div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-primary btn-lg btn-block" id="btnMulaiEnrollFinger">
                            <i class="fas fa-fingerprint"></i> Mulai Scan Jari
                        </button>
                        <button type="button" class="btn btn-danger btn-block mt-2" id="btnHapusFinger" disabled>
                            <i class="fas fa-trash"></i> Hapus Sidik Jari
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        $(document).ready(function () {
            $('#dataTable').DataTable();

            // EDIT LOGIC
            $('#dataTable').on('click', '.btnEdit', function () {
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                var nip = $(this).data('nip');
                var wa = $(this).data('wa');
                var rfid = $(this).data('rfid');

                $('#edit_nama').val(nama);
                $('#edit_nip').val(nip);
                $('#edit_wa').val(wa);
                $('#edit_rfid').val(rfid);

                $('#formEditGuru').attr('action', '{{ secure_url('guru') }}/' + id);
            });

            // HAPUS LOGIC
            $('#dataTable').on('click', '.btnHapus', function () {
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                $('#hapus_nama').text(nama);
                $('#formHapusGuru').attr('action', '{{ secure_url('guru') }}/' + id);
            });

            // ==========================
            //  ENROLL LOGIC GURU
            // ==========================
            let enrollGuruId = null;
            let enrollInterval = null;

            $('#dataTable').on('click', '.btnEnroll', function () {
                enrollGuruId = $(this).data('id');
                $('#enroll_nama').text($(this).data('nama'));

                // Reset UI
                $('#enroll_status').html('');
                $('#uid_wrapper').addClass('d-none');
                $('#enroll_uid').text('');
                $('#btnHapusUID').prop('disabled', true);

                // Check existing UID
                var uid = $(this).data('uid');
                if (uid) {
                    $('#enroll_uid').text(uid);
                    $('#uid_wrapper').removeClass('d-none');
                    $('#btnHapusUID').prop('disabled', false);
                }
            });

            $('#btnMulaiEnroll').on('click', function () {
                if (!enrollGuruId) return;

                $('#enroll_status').html('<div class="spinner-border text-primary" role="status"></div><br><span class="text-info blink">Silakan tempelkan kartu RFID...</span>');

                // Request Enroll
                $.post('{{ secure_url('guru') }}/' + enrollGuruId + '/enroll', {
                    _token: '{{ csrf_token() }}'
                }, function (res) {
                    if (res.ok) {
                        startEnrollPolling(enrollGuruId);
                    } else {
                        $('#enroll_status').html('<span class="text-danger">Gagal request enrollment.</span>');
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
                        $('#enroll_status').html('<span class="text-warning">Waktu habis. Coba lagi.</span>');
                        return;
                    }

                    $.get('{{ secure_url('guru') }}/' + id + '/enroll-check', function (res) {
                        if (res.ok && res.uid) {
                            clearInterval(enrollInterval);
                            $('#enroll_uid').text(res.uid);
                            $('#uid_wrapper').removeClass('d-none');
                            $('#btnHapusUID').prop('disabled', false);
                            $('#enroll_status').html('<span class="text-success font-weight-bold"><i class="fas fa-check-circle"></i> Berhasil! Refresh halaman untuk update tabel.</span>');

                            setTimeout(function () { location.reload(); }, 1500);
                        }
                    });
                }, 1500);
            }

            $('#btnHapusUID').on('click', function () {
                if (!enrollGuruId) return;
                if (!confirm('Hapus UID RFID guru ini?')) return;

                $.post('{{ secure_url('guru') }}/' + enrollGuruId + '/delete-uid', {
                    _token: '{{ csrf_token() }}'
                }, function (res) {
                    if (res.ok) {
                        $('#uid_wrapper').addClass('d-none');
                        $('#enroll_uid').text('');
                        $('#btnHapusUID').prop('disabled', true);
                        $('#enroll_status').html('<span class="text-warning">UID dihapus.</span>');
                        setTimeout(function () { location.reload(); }, 1000);
                    }
                });
            });

            $('#modalEnrollRFID').on('hidden.bs.modal', function () {
                if (enrollInterval) clearInterval(enrollInterval);

                // Cancel request if pending
                if (enrollGuruId) {
                    $.post('{{ secure_url('guru') }}/' + enrollGuruId + '/enroll-cancel', {
                        _token: '{{ csrf_token() }}'
                    });
                }

                enrollGuruId = null;
            });

            // ==========================
            //  ENROLL LOGIC FINGER
            // ==========================
            let enrollFingerId = null;
            let enrollFingerInterval = null;

            $('#dataTable').on('click', '.btnFinger', function () {
                enrollFingerId = $(this).data('id');
                $('#enroll_finger_nama').text($(this).data('nama'));

                // Reset UI
                $('#enroll_finger_status').html('');
                $('#finger_wrapper').addClass('d-none');
                $('#enroll_finger_id').text('');
                $('#btnHapusFinger').prop('disabled', true);

                // Check existing ID
                var fid = $(this).data('finger');
                if (fid) {
                    $('#enroll_finger_id').text(fid);
                    $('#finger_wrapper').removeClass('d-none');
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

                $('#enroll_finger_status').html('<div class="spinner-border text-primary" role="status"></div><br><span class="text-info blink">Silakan tempelkan jari di sensor device...</span>');

                // Request Enroll
                $.post('{{ secure_url('guru') }}/' + enrollFingerId + '/enroll-finger', {
                    _token: '{{ csrf_token() }}',
                    device_id: deviceId
                }, function (res) {
                    if (res.ok) {
                        startFingerPolling(enrollFingerId);
                    } else {
                        $('#enroll_finger_status').html('<span class="text-danger">Gagal request enrollment.</span>');
                    }
                });
            });

            function startFingerPolling(id) {
                if (enrollFingerInterval) clearInterval(enrollFingerInterval);
                let counter = 0;

                enrollFingerInterval = setInterval(function () {
                    counter++;
                    if (counter > 40) { // 60 sec timeout
                        clearInterval(enrollFingerInterval);
                        $('#enroll_finger_status').html('<span class="text-warning">Waktu habis. Coba lagi.</span>');
                        return;
                    }

                    $.get('{{ secure_url('guru') }}/' + id + '/enroll-finger-check', function (res) {
                        if (res.ok && res.id_finger) {
                            clearInterval(enrollFingerInterval);
                            $('#enroll_finger_id').text(res.id_finger);
                            $('#finger_wrapper').removeClass('d-none');
                            $('#btnHapusFinger').prop('disabled', false);
                            $('#enroll_finger_status').html('<span class="text-success font-weight-bold"><i class="fas fa-check-circle"></i> Berhasil! Refresh halaman untuk update tabel.</span>');

                            setTimeout(function () { location.reload(); }, 1500);

                        }
                    });
                }, 1500);
            }

            $('#btnHapusFinger').on('click', function () {
                if (!enrollFingerId) return;
                if (!confirm('Hapus Sidik Jari guru ini?')) return;

                $.post('{{ secure_url('guru') }}/' + enrollFingerId + '/delete-finger', {
                    _token: '{{ csrf_token() }}'
                }, function (res) {
                    if (res.ok) {
                        $('#finger_wrapper').addClass('d-none');
                        $('#enroll_finger_id').text('');
                        $('#btnHapusFinger').prop('disabled', true);
                        $('#enroll_finger_status').html('<span class="text-warning">Sidik Jari dihapus.</span>');
                        setTimeout(function () { location.reload(); }, 1000);
                    } else {
                        alert('Gagal menghapus: ' + (res.message || 'Unknown error'));
                    }
                }).fail(function (xhr) {
                    alert('Error: ' + xhr.status + ' ' + xhr.statusText + '\n' + xhr.responseText);
                });
            });

            $('#modalEnrollFinger').on('hidden.bs.modal', function () {
                if (enrollFingerInterval) clearInterval(enrollFingerInterval);

                // Cancel request if pending
                if (enrollFingerId) {
                    $.post('{{ secure_url('guru') }}/' + enrollFingerId + '/enroll-finger-cancel', {
                        _token: '{{ csrf_token() }}'
                    });
                }

                enrollFingerId = null;
            });
        });
    </script>
@endpush