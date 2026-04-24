@extends('layouts.app')

@section('title', 'Kelola Pengumuman')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fas fa-bullhorn text-primary mr-2"></i> Kelola Pengumuman
    </h1>
    <a href="{{ route('super-admin.announcements.create') }}" class="btn btn-sm btn-primary shadow-sm">
        <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Pengumuman
    </a>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Pengumuman</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Judul</th>
                        <th>Status</th>
                        <th>Dibuat Pada</th>
                        <th width="15%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($announcements as $announcement)
                    <tr>
                        <td>{{ $loop->iteration + $announcements->firstItem() - 1 }}</td>
                        <td>{{ $announcement->title }}</td>
                        <td>
                            @if($announcement->is_active)
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-secondary">Tidak Aktif</span>
                            @endif
                        </td>
                        <td>{{ $announcement->created_at->format('d M Y H:i') }}</td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="{{ route('super-admin.announcements.edit', $announcement) }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('super-admin.announcements.destroy', $announcement) }}" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus pengumuman ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center">Belum ada pengumuman.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            {{ $announcements->links() }}
        </div>
    </div>
</div>
@endsection
