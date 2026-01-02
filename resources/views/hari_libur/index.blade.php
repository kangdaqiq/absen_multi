@extends('layouts.app')

@section('title', 'Manajemen Hari Libur')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Tambah Hari Libur</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('hari-libur.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Keterangan</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Contoh: HUT RI" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Daftar Hari Libur</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Hari Minggu secara otomatis dianggap libur oleh sistem, tidak perlu diinput di sini.
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Hari</th>
                                <th>Keterangan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($libur as $l)
                            <tr>
                                <td>{{ $l->tanggal->format('d/m/Y') }}</td>
                                <td>{{ $l->tanggal->translatedFormat('l') }}</td>
                                <td>{{ $l->keterangan }}</td>
                                <td>
                                    <form action="{{ route('hari-libur.destroy', $l->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus hari libur ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
