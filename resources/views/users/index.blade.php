@extends('layouts.app')

@section('title', 'Manajemen User')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        <i class="fas fa-users-cog text-brand-500 mr-2"></i> Manajemen User
    </h2>
</div>

@if (session('success'))
    <div class="mb-4 rounded-lg bg-success/10 border border-success/20 px-4 py-3 text-sm font-medium text-success flex items-center gap-2">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-lg bg-error-500/10 border border-error-500/20 px-4 py-3 text-sm font-medium text-error-500 flex items-center gap-2">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
@endif

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark flex justify-between items-center">
        <h3 class="font-medium text-black dark:text-white">
            Daftar Pengguna
        </h3>
        <div class="flex items-center gap-2">
            <button type="button" id="bulkDeleteBtn" class="hidden inline-flex items-center justify-center gap-2.5 rounded-md bg-error-500 px-4 py-2 text-center font-medium text-white hover:bg-error-600 transition">
                <i class="fas fa-trash"></i> Hapus Terpilih (<span id="selectedCount">0</span>)
            </button>
            <a href="{{ route('users.create') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-brand-500 px-4 py-2 text-center font-medium text-white hover:bg-opacity-90 transition">
                <i class="fas fa-plus"></i> Tambah User
            </a>
        </div>
    </div>
    
    <div class="p-0">
        <form id="bulkDeleteForm" action="{{ route('users.bulk-destroy') }}" method="POST">
            @csrf
            @method('DELETE')
            
            <div class="max-w-full overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-2 text-left dark:bg-meta-4">
                            <th class="py-4 px-4 xl:pl-11" width="30">
                                <label for="selectAll" class="flex cursor-pointer select-none items-center">
                                    <div class="relative">
                                        <input type="checkbox" id="selectAll" class="sr-only peer" />
                                        <div class="box flex h-5 w-5 items-center justify-center rounded border border-stroke dark:border-strokedark bg-white dark:bg-boxdark peer-checked:border-brand-500 peer-checked:bg-brand-500 transition">
                                            <span class="opacity-0 peer-checked:opacity-100">
                                                <i class="fas fa-check text-xs text-white"></i>
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            </th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Nama Lengkap</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Username</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white">Email</th>
                            <th class="py-4 px-4 font-medium text-black dark:text-white text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                            <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-strokedark xl:pl-11">
                                @if(auth()->id() !== $user->id)
                                <label for="user_{{ $user->id }}" class="flex cursor-pointer select-none items-center">
                                    <div class="relative">
                                        <input type="checkbox" id="user_{{ $user->id }}" name="user_ids[]" value="{{ $user->id }}" class="user-checkbox sr-only peer" />
                                        <div class="box flex h-5 w-5 items-center justify-center rounded border border-stroke dark:border-strokedark bg-white dark:bg-boxdark peer-checked:border-brand-500 peer-checked:bg-brand-500 transition">
                                            <span class="opacity-0 peer-checked:opacity-100">
                                                <i class="fas fa-check text-xs text-white"></i>
                                            </span>
                                        </div>
                                    </div>
                                </label>
                                @endif
                            </td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                <p class="text-black dark:text-white font-medium">{{ $user->full_name }}</p>
                            </td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                <span class="inline-flex rounded-full bg-info/10 px-3 py-1 text-xs font-medium text-info">{{ $user->username }}</span>
                            </td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark">
                                <p class="text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            </td>
                            <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Edit --}}
                                    <button class="btnEditUser text-orange-500 hover:text-orange-700 hover:bg-orange-50 dark:hover:bg-orange-500/10 p-2 rounded-lg transition"
                                        data-id="{{ $user->id }}"
                                        data-name="{{ $user->full_name }}"
                                        data-username="{{ $user->username }}"
                                        data-email="{{ $user->email }}"
                                        data-role="{{ $user->role }}"
                                        data-update-url="{{ route('users.update', $user->id) }}"
                                        @click="$dispatch('open-modal', 'modalEditUser')"
                                        title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    {{-- Delete --}}
                                    @if(auth()->id() !== $user->id)
                                    <button type="button" class="btnDeleteUser text-error-500 hover:text-error-700 hover:bg-error-50 dark:hover:bg-error-500/10 p-2 rounded-lg transition"
                                        data-id="{{ $user->id }}" data-name="{{ $user->full_name }}" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

{{-- ========================= MODAL EDIT USER ========================= --}}
<x-ui.modal id="modalEditUser" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Edit User</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formEditUser" action="" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nama Lengkap <span class="text-error-500">*</span>
                </label>
                <input type="text" name="name" id="edit_user_name" required
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Username <span class="text-error-500">*</span>
                </label>
                <input type="text" name="username" id="edit_user_username" required
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Email <span class="text-error-500">*</span>
                </label>
                <input type="email" name="email" id="edit_user_email" required
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Role <span class="text-error-500">*</span>
                </label>
                <div class="relative">
                    <select name="role" id="edit_user_role" required
                        class="w-full appearance-none rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="admin">Admin</option>
                    </select>
                    <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-gray-400">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </span>
                </div>
            </div>

            <div class="pt-3 border-t border-gray-200 dark:border-gray-800">
                <p class="text-sm font-medium text-brand-500 mb-1">Ganti Password <span class="text-gray-400 font-normal">(Opsional)</span></p>
                <p class="text-xs text-gray-500 mb-3">Kosongkan jika tidak ingin mengubah password</p>
                <div class="flex gap-3">
                    <div class="w-1/2">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Password Baru</label>
                        <input type="password" name="password"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
                        <p class="mt-1 text-xs text-gray-400">Min. 8 karakter</p>
                    </div>
                    <div class="w-1/2">
                        <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" @click="open = false"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                    Batal
                </button>
                <button type="submit" class="rounded-lg bg-brand-500 px-4 py-2 text-white hover:bg-brand-600 transition">
                    <i class="fas fa-save mr-1"></i> Update
                </button>
            </div>
        </form>
    </div>
</x-ui.modal>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll       = document.getElementById('selectAll');
    const userCheckboxes  = document.querySelectorAll('.user-checkbox');
    const bulkDeleteBtn   = document.getElementById('bulkDeleteBtn');
    const selectedCount   = document.getElementById('selectedCount');
    const bulkDeleteForm  = document.getElementById('bulkDeleteForm');

    function updateBulkDeleteButton() {
        const checkedCount = document.querySelectorAll('.user-checkbox:checked').length;
        selectedCount.textContent = checkedCount;
        bulkDeleteBtn.classList.toggle('hidden', checkedCount === 0);
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            userCheckboxes.forEach(cb => { cb.checked = this.checked; });
            updateBulkDeleteButton();
        });
    }

    userCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateBulkDeleteButton();
            if (selectAll) {
                selectAll.checked = document.querySelectorAll('.user-checkbox:checked').length === userCheckboxes.length;
            }
        });
    });

    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', function() {
            const count = document.querySelectorAll('.user-checkbox:checked').length;
            if (count === 0) { alert('Pilih minimal 1 user untuk dihapus.'); return; }
            if (confirm(`Hapus ${count} user yang dipilih?`)) bulkDeleteForm.submit();
        });
    }

    // ===== Modal Edit User =====
    document.querySelectorAll('.btnEditUser').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.getElementById('formEditUser').action = this.dataset.updateUrl;
            document.getElementById('edit_user_name').value     = this.dataset.name;
            document.getElementById('edit_user_username').value = this.dataset.username;
            document.getElementById('edit_user_email').value    = this.dataset.email;
            document.getElementById('edit_user_role').value     = this.dataset.role;
            // Clear password fields
            document.querySelectorAll('#formEditUser input[type=password]').forEach(i => i.value = '');
        });
    });

    // ===== Individual Delete =====
    document.querySelectorAll('.btnDeleteUser').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const userId   = this.getAttribute('data-id');
            const userName = this.getAttribute('data-name');

            if (confirm(`Hapus user ${userName}?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `{{ url('users') }}/${userId}`;

                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden'; csrfInput.name = '_token';
                csrfInput.value = '{{ csrf_token() }}';

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden'; methodInput.name = '_method';
                methodInput.value = 'DELETE';

                form.appendChild(csrfInput);
                form.appendChild(methodInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    });
});
</script>
@endpush
