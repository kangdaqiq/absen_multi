@extends('layouts.app')

@section('title', 'Backup & Restore Database')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Backup Database</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Cadangan Database</h6>
            <form action="{{ route('backups.create') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-sm btn-primary shadow-sm"><i
                        class="fas fa-download fa-sm text-white-50"></i> Backup Sekarang</button>
            </form>
        </div>
        <div class="card-body">
            <div class="alert alert-info alert-static">
                <strong>Info:</strong> Backup otomatis berjalan setiap hari pukul 02:00 AM. File disimpan di folder server
                <code>storage/app/backups</code>.
            </div>

            <div class="table-responsive">
                <table class="table table-bordered text-center" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama File</th>
                            <th>Tanggal Backup</th>
                            <th>Ukuran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($backups as $index => $file)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="text-left font-weight-bold">{{ $file['name'] }}</td>
                                <td>{{ $file['date'] }}</td>
                                <td>{{ $file['size'] }}</td>
                                <td>
                                    <a href="{{ route('backups.download', $file['name']) }}" class="btn btn-sm btn-success"
                                        title="Download">
                                        <i class="fas fa-file-download"></i> Download
                                    </a>
                                    <form action="{{ route('backups.delete', $file['name']) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Hapus backup ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-warning"
                                        onclick="alert('Untuk restore, silakan import file .sql ini via phpMyAdmin atau HeidiSQL.')">
                                        <i class="fas fa-undo"></i> Restore Info
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">Belum ada file backup.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection