@extends('layouts.app')

@section('title', 'Data Siswa')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Data Siswa</h1>
        <div>
            <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTambahSiswa">
                <i class="fas fa-plus fa-sm"></i> Tambah Siswa
            </button>
            <button class="btn btn-success shadow-sm ml-2" data-toggle="modal" data-target="#modalImportSiswa">
                <i class="fas fa-file-excel fa-sm"></i> Import Excel
            </button>
            <form action="{{ route('siswa.generateAccounts') }}" method="POST" class="d-inline ml-2"
                onsubmit="return confirm('PERHATIAN: Semua akun siswa akan dihapus dan dibuat ulang! Username=NIS, Password=NIS. Lanjutkan?');">
                @csrf
                <button type="submit" class="btn btn-warning shadow-sm">
                    <i class="fas fa-key fa-sm"></i> Generate Akun
                </button>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Tabel Data Siswa</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama</th>
                            <th>NIS</th>
                            <th>Tgl Lahir</th>
                            <th>Kelas</th>
                            <th>No WhatsApp</th>
                            <th>No WA Ortu</th>
                            <th>Akun User</th>
                            <th>UID RFID</th>
                            <th>ID Finger</th>
                            <th width="20%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($siswa as $s)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $s->nama }}</td>
                                <td>{{ $s->nis }}</td>
                                <td>{{ $s->tgl_lahir ? \Carbon\Carbon::parse($s->tgl_lahir)->format('d-m-Y') : '-' }}</td>
                                <td>{{ $s->kelas->nama_kelas ?? '-' }}</td>
                                <td>{{ $s->no_wa }}</td>
                                <td>{{ $s->wa_ortu }}</td>
                                <td>
                                    @if($s->user)
                                        <span class="badge badge-success">{{ $s->user->username }}</span>
                                    @else
                                        <span class="badge badge-secondary">Belum Terhubung</span>
                                    @endif
                                </td>
                                <td>{{ $s->uid_rfid }}</td>
                                <td>
                                    @if($s->id_finger)
                                        <span class="badge badge-success">{{ $s->id_finger }}</span>
                                    @else
                                        <span class="badge badge-secondary">-</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info btnEnroll" data-id="{{ $s->id }}"
                                        data-nama="{{ $s->nama }}" data-uid="{{ $s->uid_rfid }}" data-toggle="modal"
                                        data-target="#modalEnrollRFID">
                                        <i class="fas fa-rss"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success btnEnrollFinger" data-id="{{ $s->id }}"
                                        data-nama="{{ $s->nama }}" data-finger="{{ $s->id_finger }}" data-toggle="modal"
                                        data-target="#modalEnrollFinger">
                                        <i class="fas fa-fingerprint"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning btnEdit" data-id="{{ $s->id }}"
                                        data-nama="{{ $s->nama }}" data-nis="{{ $s->nis }}" data-tgl_lahir="{{ $s->tgl_lahir }}"
                                        data-kelas="{{ $s->kelas_id }}" data-wa="{{ $s->no_wa }}"
                                        data-wa_ortu="{{ $s->wa_ortu }}" data-uid="{{ $s->uid_rfid }}" data-toggle="modal"
                                        data-target="#modalEditSiswa">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btnHapus" data-id="{{ $s->id }}"
                                        data-nama="{{ $s->nama }}" data-toggle="modal" data-target="#modalHapusSiswa">
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
    <div class="modal fade" id="modalTambahSiswa" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="{{ route('siswa.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Siswa</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Siswa</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>NIS</label>
                            <input type="text" name="nis" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Lahir</label>
                            <input type="date" name="tgl_lahir" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Kelas</label>
                            <select name="kelas_id" class="form-control" required>
                                <option value="">Pilih Kelas</option>
                                @foreach ($kelas as $k)
                                    <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>No WhatsApp Siswa</label>
                            <input type="text" name="no_wa" class="form-control" placeholder="08xxx">
                        </div>
                        <div class="form-group">
                            <label>No WhatsApp Ortu</label>
                            <input type="text" name="wa_ortu" class="form-control" placeholder="08xxx">
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
    <div class="modal fade" id="modalEditSiswa" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="#" method="POST" id="formEditSiswa">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Siswa</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Siswa</label>
                            <input type="text" name="nama" id="edit_nama" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>NIS</label>
                            <input type="text" name="nis" id="edit_nis" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Lahir</label>
                            <input type="date" name="tgl_lahir" id="edit_tgl_lahir" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Kelas</label>
                            <select name="kelas_id" id="edit_kelas_id" class="form-control" required>
                                <option value="">Pilih Kelas</option>
                                @foreach ($kelas as $k)
                                    <option value="{{ $k->id }}">{{ $k->nama_kelas }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>No WhatsApp Siswa</label>
                            <input type="text" name="no_wa" id="edit_no_wa" class="form-control" placeholder="08xxx">
                        </div>
                        <div class="form-group">
                            <label>No WhatsApp Ortu</label>
                            <input type="text" name="wa_ortu" id="edit_wa_ortu" class="form-control" placeholder="08xxx">
                        </div>
                        <div class="form-group">
                            <label>UID RFID</label>
                            <input type="text" name="uid_rfid" id="edit_uid_rfid" class="form-control" readonly>
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
    <div class="modal fade" id="modalHapusSiswa" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="#" method="POST" id="formHapusSiswa">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Hapus Siswa</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Yakin ingin menghapus siswa: <strong id="hapus_nama"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Import -->
    <div class="modal fade" id="modalImportSiswa" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="{{ route('siswa.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Import Data Siswa</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Gunakan file Excel (.xlsx) dengan format kolom: <strong>Nama,
                                NIS, Tgl Lahir, Kelas, WA Siswa, WA Ortu</strong>.
                        </div>
                        <div class="form-group">
                            <label>Pilih File Excel</label>
                            <input type="file" name="fileExcel" class="form-control-file" required>
                        </div>
                        <div class="mt-2">
                            <a href="{{ route('siswa.template') }}" class="small font-weight-bold"><i
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

    <!-- Modal Enroll RFID -->
    <div class="modal fade" id="modalEnrollRFID" tabindex="-1" role="dialog" data-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-id-card"></i> Registrasi RFID</h5>
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
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-fingerprint"></i> Registrasi Sidik Jari</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body text-center">
                    <h5 id="enroll_finger_nama" class="font-weight-bold mb-3"></h5>

                    <div id="finger_id_wrapper" class="d-none mb-3">
                        <div class="alert alert-success">
                            ID Sidik Jari: <strong id="enroll_finger_id" class="h4"></strong>
                        </div>
                    </div>

                    <div id="enroll_finger_status" class="mb-3"></div>

                    <div class="form-group">
                        <label>Pilih Device</label>
                        <select id="finger_device_id" class="form-control">
                            <option value="">-- Pilih Device --</option>
                            @foreach($devices as $dev)
                                <option value="{{ $dev->id }}">{{ $dev->name }} ({{ ucfirst($dev->type) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-success btn-lg btn-block" id="btnMulaiEnrollFinger">
                            <i class="fas fa-fingerprint"></i> Mulai Scan Sidik Jari
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
            var table = $('#dataTable').DataTable();

            // Custom file input
            $(".custom-file-input").on("change", function () {
                var fileName = $(this).val().split("\\").pop();
                $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
            });

            // Edit
            $('#dataTable').on('click', '.btnEdit', function () {
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                var nis = $(this).data('nis');
                var tgl_lahir = $(this).data('tgl_lahir');
                var kelas = $(this).data('kelas');
                var wa = $(this).data('wa');
                var wa_ortu = $(this).data('wa_ortu');
                var uid = $(this).data('uid');

                $('#edit_nama').val(nama);
                $('#edit_nis').val(nis);
                $('#edit_tgl_lahir').val(tgl_lahir);
                $('#edit_kelas_id').val(kelas);
                $('#edit_no_wa').val(wa);
                $('#edit_wa_ortu').val(wa_ortu);
                $('#edit_uid_rfid').val(uid);

                $('#formEditSiswa').attr('action', '{{ secure_url('siswa') }}/' + id);
            });


            // Hapus
            $('#dataTable').on('click', '.btnHapus', function () {
                var id = $(this).data('id');
                var nama = $(this).data('nama');
                $('#hapus_nama').text(nama);
                $('#formHapusSiswa').attr('action', '{{ secure_url('siswa') }}/' + id);
            });

            // ==========================
            //  ENROLL LOGIC
            // ==========================
            let enrollSiswaId = null;
            let enrollInterval = null;

            $('#dataTable').on('click', '.btnEnroll', function () {
                enrollSiswaId = $(this).data('id');
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
                if (!enrollSiswaId) return;

                $('#enroll_status').html('<div class="spinner-border text-primary" role="status"></div><br><span class="text-info blink">Silakan tempelkan kartu RFID...</span>');

                // Request Enroll
                $.post('{{ secure_url('siswa') }}/' + enrollSiswaId + '/enroll', {
                    _token: '{{ csrf_token() }}'
                }, function (res) {
                    if (res.ok) {
                        startEnrollPolling(enrollSiswaId);
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

                    $.get('{{ secure_url('siswa') }}/' + id + '/enroll-check', function (res) {
                        if (res.ok && res.uid) {
                            clearInterval(enrollInterval);
                            $('#enroll_uid').text(res.uid);
                            $('#uid_wrapper').removeClass('d-none');
                            $('#btnHapusUID').prop('disabled', false);
                            $('#enroll_status').html('<span class="text-success font-weight-bold"><i class="fas fa-check-circle"></i> Berhasil! Refresh halaman untuk update tabel.</span>');

                            // Optional: reload table or update row
                            setTimeout(function () { location.reload(); }, 1500);
                        }
                    });
                }, 1500);
            }

            $('#btnHapusUID').on('click', function () {
                if (!enrollSiswaId) return;
                if (!confirm('Hapus UID RFID siswa ini?')) return;

                $.post('{{ secure_url('siswa') }}/' + enrollSiswaId + '/delete-uid', {
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
                if (enrollSiswaId) {
                    $.post('{{ secure_url('siswa') }}/' + enrollSiswaId + '/enroll-cancel', {
                        _token: '{{ csrf_token() }}'
                    });
                }

                enrollSiswaId = null;
            });

            // ==========================
            //  FINGERPRINT ENROLL LOGIC
            // ==========================
            let enrollFingerSiswaId = null;
            let enrollFingerInterval = null;

            $('#dataTable').on('click', '.btnEnrollFinger', function () {
                enrollFingerSiswaId = $(this).data('id');
                $('#enroll_finger_nama').text($(this).data('nama'));

                // Reset UI
                $('#enroll_finger_status').html('');
                $('#finger_id_wrapper').addClass('d-none');
                $('#enroll_finger_id').text('');
                $('#btnHapusFinger').prop('disabled', true);
                $('#finger_device_id').val('');

                // Check existing Finger ID
                var fingerId = $(this).data('finger');
                if (fingerId) {
                    $('#enroll_finger_id').text(fingerId);
                    $('#finger_id_wrapper').removeClass('d-none');
                    $('#btnHapusFinger').prop('disabled', false);
                }
            });

            $('#btnMulaiEnrollFinger').on('click', function () {
                if (!enrollFingerSiswaId) return;

                var deviceId = $('#finger_device_id').val();
                if (!deviceId) {
                    alert('Pilih device terlebih dahulu!');
                    return;
                }

                $('#enroll_finger_status').html('<div class="spinner-border text-success" role="status"></div><br><span class="text-info blink">Silakan tempelkan jari pada sensor...</span>');

                // Request Enroll with device_id
                $.post('{{ secure_url('siswa') }}/' + enrollFingerSiswaId + '/enroll-finger', {
                    _token: '{{ csrf_token() }}',
                    device_id: deviceId
                }, function (res) {
                    if (res.ok) {
                        startFingerEnrollPolling(enrollFingerSiswaId);
                    } else {
                        $('#enroll_finger_status').html('<span class="text-danger">Gagal request enrollment.</span>');
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
                        $('#enroll_finger_status').html('<span class="text-warning">Waktu habis. Coba lagi.</span>');
                        return;
                    }

                    $.get('{{ secure_url('siswa') }}/' + id + '/enroll-finger-check', function (res) {
                        if (res.ok && res.id_finger && res.status === 'done') {
                            clearInterval(enrollFingerInterval);
                            $('#enroll_finger_id').text(res.id_finger);
                            $('#finger_id_wrapper').removeClass('d-none');
                            $('#btnHapusFinger').prop('disabled', false);
                            $('#enroll_finger_status').html('<span class="text-success font-weight-bold"><i class="fas fa-check-circle"></i> Berhasil! Refresh halaman untuk update tabel.</span>');

                            setTimeout(function () { location.reload(); }, 1500);
                        }
                    });
                }, 1500);
            }

            $('#btnHapusFinger').on('click', function () {
                if (!enrollFingerSiswaId) return;
                if (!confirm('Hapus sidik jari siswa ini dari semua device?')) return;

                $.post('{{ secure_url('siswa') }}/' + enrollFingerSiswaId + '/delete-finger', {
                    _token: '{{ csrf_token() }}'
                }, function (res) {
                    if (res.ok) {
                        $('#finger_id_wrapper').addClass('d-none');
                        $('#enroll_finger_id').text('');
                        $('#btnHapusFinger').prop('disabled', true);
                        $('#enroll_finger_status').html('<span class="text-warning">Sidik jari dihapus.</span>');
                        setTimeout(function () { location.reload(); }, 1000);
                    }
                });
            });

            $('#modalEnrollFinger').on('hidden.bs.modal', function () {
                if (enrollFingerInterval) clearInterval(enrollFingerInterval);

                // Cancel request if pending
                if (enrollFingerSiswaId) {
                    $.post('{{ secure_url('siswa') }}/' + enrollFingerSiswaId + '/enroll-finger-cancel', {
                        _token: '{{ csrf_token() }}'
                    });
                }

                enrollFingerSiswaId = null;
            });
        });
    </script>
@endpush