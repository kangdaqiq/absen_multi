@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Manajemen User</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Pengguna</h6>
            <div>
                <button type="button" id="bulkDeleteBtn" class="btn btn-sm btn-danger shadow-sm mr-2" style="display: none;">
                    <i class="fas fa-trash fa-sm text-white-50"></i> Hapus Terpilih (<span id="selectedCount">0</span>)
                </button>
                <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary shadow-sm">
                    <i class="fas fa-plus fa-sm text-white-50"></i> Tambah User
                </a>
            </div>
        </div>
        <div class="card-body">
            


            <form id="bulkDeleteForm" action="{{ route('users.bulk-destroy') }}" method="POST">
                @csrf
                @method('DELETE')
                
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="30">
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>

                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>
                                    @if(auth()->id() !== $user->id)
                                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="user-checkbox">
                                    @endif
                                </td>
                                <td>{{ $user->full_name }}</td>
                                <td>{{ $user->email }}</td>

                                <td>
                                    <a href="{{ route('users.edit', $user->id) }}" class="btn btn-sm btn-info">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if(auth()->id() !== $user->id)
                                    <form action="{{ route('users.destroy', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus user ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Select All functionality
    $('#selectAll').on('change', function() {
        $('.user-checkbox').prop('checked', this.checked);
        updateBulkDeleteButton();
    });

    // Individual checkbox change
    $('.user-checkbox').on('change', function() {
        updateBulkDeleteButton();
        
        // Update "Select All" checkbox state
        const totalCheckboxes = $('.user-checkbox').length;
        const checkedCheckboxes = $('.user-checkbox:checked').length;
        $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
    });

    // Bulk delete button click
    $('#bulkDeleteBtn').on('click', function() {
        const selectedCount = $('.user-checkbox:checked').length;
        
        if (selectedCount === 0) {
            alert('Pilih minimal 1 user untuk dihapus.');
            return;
        }

        if (confirm(`Apakah Anda yakin ingin menghapus ${selectedCount} user yang dipilih?`)) {
            $('#bulkDeleteForm').submit();
        }
    });

    function updateBulkDeleteButton() {
        const checkedCount = $('.user-checkbox:checked').length;
        $('#selectedCount').text(checkedCount);
        
        if (checkedCount > 0) {
            $('#bulkDeleteBtn').show();
        } else {
            $('#bulkDeleteBtn').hide();
        }
    }
});
</script>
@endpush
