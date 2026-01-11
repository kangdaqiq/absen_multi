@extends('layouts.app')

@section('title', 'Kelola Admin - ' . $school->name)

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-users-cog"></i> Kelola Admin
            </h1>
            <p class="text-muted mb-0">Sekolah: <strong>{{ $school->name }}</strong></p>
        </div>
        <div>
            <a href="{{ route('super-admin.schools.admins.create', $school) }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Admin
            </a>
            <a href="{{ route('super-admin.schools.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama Lengkap</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($admins as $admin)
                            <tr>
                                <td>{{ $admin->full_name }}</td>
                                <td><span class="badge badge-info">{{ $admin->username }}</span></td>
                                <td>{{ $admin->email }}</td>
                                <td>{{ $admin->created_at->format('d M Y') }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('super-admin.schools.admins.edit', [$school, $admin]) }}"
                                            class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            onclick="confirmDelete({{ $admin->id }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <form id="delete-form-{{ $admin->id }}"
                                        action="{{ route('super-admin.schools.admins.destroy', [$school, $admin]) }}"
                                        method="POST" style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">
                                    Belum ada admin untuk sekolah ini.
                                    <a href="{{ route('super-admin.schools.admins.create', $school) }}">Tambah admin pertama</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($admins->hasPages())
                <div class="mt-3">
                    {{ $admins->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function confirmDelete(adminId) {
            if (confirm('Apakah Anda yakin ingin menghapus admin ini?')) {
                document.getElementById('delete-form-' + adminId).submit();
            }
        }
    </script>
@endpush