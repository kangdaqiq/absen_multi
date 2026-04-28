@extends('layouts.app')

@section('title', 'Kelola Admin - ' . $school->name)

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            <i class="fas fa-users-cog text-brand-500 mr-2"></i> Kelola Admin
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sekolah: <strong class="text-black dark:text-white">{{ $school->name }}</strong></p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('super-admin.schools.index') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md border border-stroke bg-transparent px-6 py-3 text-center font-medium text-black hover:bg-gray-100 dark:border-strokedark dark:text-white dark:hover:bg-boxdark transition">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <a href="{{ route('super-admin.schools.admins.create', $school) }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-brand-500 px-6 py-3 text-center font-medium text-white hover:bg-opacity-90 transition">
            <i class="fas fa-plus"></i> Tambah Admin
        </a>
    </div>
</div>

@if (session('success'))
    <div class="mb-4 rounded-lg bg-success/10 border border-success/20 px-4 py-3 text-sm font-medium text-success flex items-center gap-2">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
@endif

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
        <h3 class="font-medium text-black dark:text-white">
            Daftar Admin Sekolah
        </h3>
    </div>
    
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-2 text-left dark:bg-meta-4">
                    <th class="py-4 px-4 font-medium text-black dark:text-white xl:pl-11">Nama Lengkap</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white">Username</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white">Email</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white">Dibuat</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admins as $admin)
                    <tr>
                        <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-strokedark xl:pl-11 align-middle">
                            <p class="text-black dark:text-white font-medium">{{ $admin->full_name }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-middle">
                            <span class="inline-flex rounded-full bg-info/10 px-3 py-1 text-xs font-medium text-info">{{ $admin->username }}</span>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-middle">
                            <p class="text-gray-500 dark:text-gray-400">{{ $admin->email }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-middle">
                            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ $admin->created_at->format('d M Y') }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-middle">
                            <div class="flex items-center justify-center gap-2">
                                {{-- Edit --}}
                                <button class="btnEditAdmin text-orange-500 hover:text-orange-700 hover:bg-orange-50 dark:hover:bg-orange-500/10 p-2 rounded-lg transition"
                                    data-id="{{ $admin->id }}"
                                    data-full-name="{{ $admin->full_name }}"
                                    data-username="{{ $admin->username }}"
                                    data-email="{{ $admin->email }}"
                                    data-role="{{ ucfirst($admin->role) }}"
                                    data-created="{{ $admin->created_at->format('d M Y H:i') }}"
                                    data-update-url="{{ route('super-admin.schools.admins.update', [$school, $admin]) }}"
                                    @click="$dispatch('open-modal', 'modalEditAdmin')"
                                    title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                {{-- Delete --}}
                                <button type="button" class="btnDelete text-error-500 hover:text-error-700 hover:bg-error-50 dark:hover:bg-error-500/10 p-2 rounded-lg transition"
                                    data-id="{{ $admin->id }}" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <form id="delete-form-{{ $admin->id }}" action="{{ route('super-admin.schools.admins.destroy', [$school, $admin]) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center text-gray-500">
                            Belum ada admin untuk sekolah ini.
                            <a href="{{ route('super-admin.schools.admins.create', $school) }}" class="text-brand-500 hover:underline">Tambah admin pertama</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($admins->hasPages())
        <div class="px-5 py-4 border-t border-stroke dark:border-strokedark">
            {{ $admins->links('vendor.pagination.tailwind') }}
        </div>
    @endif
</div>

{{-- ========================= MODAL EDIT ADMIN ========================= --}}
<x-ui.modal id="modalEditAdmin" :is-open="false">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Edit Admin</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Info badge --}}
        <div class="mb-5 p-3 rounded-lg bg-gray-50 dark:bg-gray-800/50 flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/15">
                <i class="fas fa-user-shield"></i>
            </div>
            <div>
                <p id="modal_admin_role" class="text-xs font-medium text-info"></p>
                <p id="modal_admin_created" class="text-xs text-gray-400 mt-0.5"></p>
            </div>
        </div>

        <form id="formEditAdmin" action="" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Nama Lengkap <span class="text-error-500">*</span>
                </label>
                <input type="text" name="full_name" id="edit_admin_full_name" required
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Username <span class="text-error-500">*</span>
                </label>
                <input type="text" name="username" id="edit_admin_username" required
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Email <span class="text-error-500">*</span>
                </label>
                <input type="email" name="email" id="edit_admin_email" required
                    class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2.5 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white" />
            </div>

            <div class="pt-2 border-t border-gray-200 dark:border-gray-800">
                <p class="text-sm font-medium text-brand-500 mb-1">Ubah Password <span class="text-gray-400 font-normal">(Opsional)</span></p>
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
        // ===== Edit Admin Modal =====
        document.querySelectorAll('.btnEditAdmin').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.getElementById('formEditAdmin').action = this.dataset.updateUrl;
                document.getElementById('edit_admin_full_name').value = this.dataset.fullName;
                document.getElementById('edit_admin_username').value  = this.dataset.username;
                document.getElementById('edit_admin_email').value     = this.dataset.email;
                document.getElementById('modal_admin_role').textContent    = 'Role: ' + this.dataset.role;
                document.getElementById('modal_admin_created').textContent = 'Dibuat: ' + this.dataset.created;
                // Clear password fields on open
                document.querySelectorAll('#formEditAdmin input[type=password]').forEach(i => i.value = '');
            });
        });

        // ===== Delete Admin =====
        document.querySelectorAll('.btnDelete').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                if (confirm('Apakah Anda yakin ingin menghapus admin ini?')) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        });
    });
</script>
@endpush