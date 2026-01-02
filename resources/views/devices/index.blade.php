@extends('layouts.app')

@section('title', 'Manajemen Device')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manajemen Device (ESP32)</h1>
        <button class="btn btn-primary shadow-sm" data-toggle="modal" data-target="#modalTambahDevice">
            <i class="fas fa-plus fa-sm"></i> Tambah Device
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Device</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Device</th>
                            <th>Token (api_key)</th>
                            <th>Tipe</th>
                            <th>Status</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($devices as $d)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $d->name }}</td>
                                <td><code>{{ $d->api_key }}</code></td>
                                <td>
                                    @if($d->type == 'rfid')
                                        <span class="badge badge-info"><i class="fas fa-id-card"></i> RFID Only</span>
                                    @elseif($d->type == 'fingerprint')
                                        <span class="badge badge-warning"><i class="fas fa-fingerprint"></i> Fingerprint Only</span>
                                    @else
                                        <span class="badge badge-secondary"><i class="fas fa-microchip"></i> RFID + Finger</span>
                                    @endif
                                </td>
                                <td>
                                    @if($d->active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning btnEdit"
                                        data-id="{{ $d->id }}"
                                        data-name="{{ $d->name }}"
                                        data-key="{{ $d->api_key }}"
                                        data-type="{{ $d->type }}"
                                        data-active="{{ $d->active }}"
                                        data-toggle="modal" data-target="#modalEditDevice">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger btnHapus"
                                        data-id="{{ $d->id }}"
                                        data-name="{{ $d->name }}"
                                        data-toggle="modal" data-target="#modalHapusDevice">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="small text-muted mt-2">Note: Token digunakan oleh ESP32 (header <code>X-API-Key</code>).</div>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal fade" id="modalTambahDevice" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="{{ route('devices.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Device</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Device</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Token (api_key)</label>
                            <div class="input-group">
                                <input type="text" name="api_key" id="create_api_key" class="form-control" required>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="generateToken('create_api_key')">Generate</button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Tipe Device</label>
                            <select name="type" class="form-control">
                                <option value="rfid_fingerprint">RFID + Fingerprint (Default)</option>
                                <option value="rfid">RFID Only</option>
                                <option value="fingerprint">Fingerprint Only</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="active" class="form-control">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
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
    <div class="modal fade" id="modalEditDevice" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="#" method="POST" id="formEditDevice">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Device</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Device</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Token (api_key)</label>
                            <div class="input-group">
                                <input type="text" name="api_key" id="edit_api_key" class="form-control" required>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="generateToken('edit_api_key')">Generate</button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Tipe Device</label>
                            <select name="type" id="edit_type" class="form-control">
                                <option value="rfid_fingerprint">RFID + Fingerprint</option>
                                <option value="rfid">RFID Only</option>
                                <option value="fingerprint">Fingerprint Only</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="active" id="edit_active" class="form-control">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
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
    <div class="modal fade" id="modalHapusDevice" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <form action="#" method="POST" id="formHapusDevice">
                @csrf
                @method('DELETE')
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Hapus Device</h5>
                        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Yakin ingin menghapus device: <strong id="hapus_name"></strong>?</p>
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
    function generateToken(targetId) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let out = '';
        for(let i=0;i<60;i++) out += chars.charAt(Math.floor(Math.random()*chars.length));
        document.getElementById(targetId).value = out;
    }

    $(document).ready(function() {
        $('#dataTable').DataTable();

        $('.btnEdit').on('click', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var key = $(this).data('key');
            var type = $(this).data('type');
            var active = $(this).data('active');
            
            $('#edit_name').val(name);
            $('#edit_api_key').val(key);
            $('#edit_type').val(type);
            $('#edit_active').val(active);
            
            $('#formEditDevice').attr('action', '{{ url('devices') }}/' + id);
        });

        $('.btnHapus').on('click', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            $('#hapus_name').text(name);
            $('#formHapusDevice').attr('action', '{{ url('devices') }}/' + id);
        });
    });
</script>
@endpush
