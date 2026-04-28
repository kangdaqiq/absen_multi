@extends('layouts.app')

@section('title', 'Edit Admin - ' . $school->name)

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            Edit Admin: {{ $admin->full_name }}
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sekolah: <strong class="text-black dark:text-white">{{ $school->name }}</strong></p>
    </div>
    <a href="{{ route('super-admin.schools.admins.index', $school) }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-gray-500 px-10 py-3 text-center font-medium text-white hover:bg-opacity-90 lg:px-8 xl:px-10 transition">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="grid grid-cols-1 gap-9 sm:grid-cols-3">
    <div class="flex flex-col gap-9 sm:col-span-2">
        <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
                <h3 class="font-medium text-black dark:text-white">
                    Form Edit Admin
                </h3>
            </div>
            
            <div class="p-6.5">
                <form action="{{ route('super-admin.schools.admins.update', [$school, $admin]) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Nama Lengkap <span class="text-meta-1">*</span>
                        </label>
                        <input type="text" name="full_name" value="{{ old('full_name', $admin->full_name) }}" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('full_name') border-danger @enderror" />
                        @error('full_name')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Username <span class="text-meta-1">*</span>
                        </label>
                        <input type="text" name="username" value="{{ old('username', $admin->username) }}" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('username') border-danger @enderror" />
                        @error('username')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Email <span class="text-meta-1">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email', $admin->email) }}" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('email') border-danger @enderror" />
                        @error('email')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mt-8 mb-5.5 border-t border-stroke pt-6 dark:border-strokedark">
                        <h4 class="mb-2 font-semibold text-brand-500">Ubah Password (Opsional)</h4>
                        <p class="mb-4 text-sm text-gray-500">Kosongkan jika tidak ingin mengubah password</p>
                        
                        <div class="flex flex-col gap-5 sm:flex-row">
                            <div class="w-full sm:w-1/2">
                                <label class="mb-2.5 block text-black dark:text-white">
                                    Password Baru
                                </label>
                                <input type="password" name="password" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('password') border-danger @enderror" />
                                <p class="mt-1.5 text-xs text-gray-500">Minimal 8 karakter</p>
                                @error('password')
                                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="w-full sm:w-1/2">
                                <label class="mb-2.5 block text-black dark:text-white">
                                    Konfirmasi Password Baru
                                </label>
                                <input type="password" name="password_confirmation" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4.5 border-t border-stroke pt-5 dark:border-strokedark">
                        <a href="{{ route('super-admin.schools.admins.index', $school) }}" class="flex justify-center rounded border border-stroke px-6 py-3 font-medium text-black hover:shadow-1 dark:border-strokedark dark:text-white transition">
                            Batal
                        </a>
                        <button type="submit" class="flex justify-center rounded bg-brand-500 px-6 py-3 font-medium text-white hover:bg-opacity-90 transition">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="flex flex-col gap-9 sm:col-span-1">
        <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
                <h3 class="font-medium text-black dark:text-white">
                    Informasi Admin
                </h3>
            </div>
            <div class="p-6.5 space-y-4">
                <div class="flex justify-between items-center pb-3 border-b border-stroke dark:border-strokedark">
                    <span class="text-gray-500 dark:text-gray-400">Sekolah</span>
                    <span class="font-medium text-black dark:text-white text-right">{{ $school->name }}</span>
                </div>
                <div class="flex justify-between items-center pb-3 border-b border-stroke dark:border-strokedark">
                    <span class="text-gray-500 dark:text-gray-400">Role</span>
                    <span class="inline-flex rounded-full bg-info/10 px-3 py-1 text-xs font-medium text-info">{{ ucfirst($admin->role) }}</span>
                </div>
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400 block mb-1">Dibuat:</span>
                    <span class="font-medium text-black dark:text-white">{{ $admin->created_at->format('d M Y H:i') }}</span>
                </div>
                <div>
                    <span class="text-sm text-gray-500 dark:text-gray-400 block mb-1">Terakhir diupdate:</span>
                    <span class="font-medium text-black dark:text-white">{{ $admin->updated_at->format('d M Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection