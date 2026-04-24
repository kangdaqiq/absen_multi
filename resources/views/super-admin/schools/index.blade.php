@extends('layouts.app')

@section('title', 'Kelola Sekolah')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-school"></i> Kelola Sekolah
        </h1>
        <a href="{{ route('super-admin.schools.create') }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> Tambah Sekolah
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nama Sekolah</th>
                            <th>Kode</th>
                            <th>Kontak</th>
                            <th>Siswa</th>
                            <th>Guru</th>
                            <th>Admin</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schools as $school)
                            <tr>
                                <td>
                                    <strong>{{ $school->name }}</strong>
                                    @if($school->address)
                                        <br><small class="text-muted">{{ Str::limit($school->address, 50) }}</small>
                                    @endif
                                </td>
                                <td><span class="badge badge-secondary">{{ $school->code }}</span></td>
                                <td>
                                    @if($school->phone)
                                        <i class="fas fa-phone"></i> {{ $school->phone }}<br>
                                    @endif
                                    @if($school->email)
                                        <i class="fas fa-envelope"></i> {{ $school->email }}<br>
                                    @endif
                                    @if($school->operator_phone)
                                        <i class="fas fa-headset text-primary"></i>
                                        <small><strong>Operator:</strong> {{ $school->operator_phone }}</small>
                                    @endif
                                </td>
                                <td class="text-center">{{ $school->siswa_count }}</td>
                                <td class="text-center">{{ $school->guru_count }}</td>
                                <td class="text-center">
                                    <a href="{{ route('super-admin.schools.admins.index', $school) }}" class="badge badge-info">
                                        {{ $school->admins_count }} admin
                                    </a>
                                </td>
                                <td>
                                    @if($school->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-danger">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('super-admin.schools.edit', $school) }}"
                                            class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="{{ route('super-admin.schools.admins.index', $school) }}"
                                            class="btn btn-sm btn-info" title="Kelola Admin">
                                            <i class="fas fa-users-cog"></i>
                                        </a>
                                        <a href="{{ route('super-admin.schools.devices.index', $school) }}"
                                            class="btn btn-sm btn-indigo text-white" style="background-color: #6610f2;" title="Kelola Device">
                                            <i class="fas fa-microchip"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger"
                                            onclick="confirmDelete({{ $school->id }})" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                    <form id="delete-form-{{ $school->id }}"
                                        action="{{ route('super-admin.schools.destroy', $school) }}" method="POST"
                                        style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">Belum ada sekolah</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($schools->hasPages())
                <div class="mt-3">
                    {{ $schools->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function confirmDelete(schoolId) {
            if (confirm('Apakah Anda yakin ingin menghapus sekolah ini? Semua data terkait akan ikut terhapus!')) {
                document.getElementById('delete-form-' + schoolId).submit();
            }
        }

        $(document).ready(function () {
            $('#dataTable').DataTable({
                "paging": false,
                "searching": true,
                "ordering": true,
                "info": false
            });
        });
    </script>
@endpush