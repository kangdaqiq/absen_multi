@extends('layouts.app')

@section('title', 'Tambah Admin - ' . $school->name)

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            Tambah Admin Baru
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
                    Form Admin
                </h3>
            </div>
            
            <div class="p-6.5">
                <form action="{{ route('super-admin.schools.admins.store', $school) }}" method="POST">
                    @csrf
                    
                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Nama Lengkap <span class="text-meta-1">*</span>
                        </label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('full_name') border-danger @enderror" />
                        @error('full_name')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Username <span class="text-meta-1">*</span>
                        </label>
                        <input type="text" name="username" value="{{ old('username') }}" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('username') border-danger @enderror" />
                        <p class="mt-1.5 text-xs text-gray-500">Username untuk login</p>
                        @error('username')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Email <span class="text-meta-1">*</span>
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('email') border-danger @enderror" />
                        @error('email')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5 flex flex-col gap-5 sm:flex-row">
                        <div class="w-full sm:w-1/2">
                            <label class="mb-2.5 block text-black dark:text-white">
                                Password <span class="text-meta-1">*</span>
                            </label>
                            <input type="password" name="password" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('password') border-danger @enderror" />
                            <p class="mt-1.5 text-xs text-gray-500">Minimal 8 karakter</p>
                            @error('password')
                                <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="w-full sm:w-1/2">
                            <label class="mb-2.5 block text-black dark:text-white">
                                Konfirmasi Password <span class="text-meta-1">*</span>
                            </label>
                            <input type="password" name="password_confirmation" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-4.5 border-t border-stroke pt-5 dark:border-strokedark">
                        <a href="{{ route('super-admin.schools.admins.index', $school) }}" class="flex justify-center rounded border border-stroke px-6 py-3 font-medium text-black hover:shadow-1 dark:border-strokedark dark:text-white transition">
                            Batal
                        </a>
                        <button type="submit" class="flex justify-center rounded bg-brand-500 px-6 py-3 font-medium text-white hover:bg-opacity-90 transition">
                            Simpan
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
                    Informasi
                </h3>
            </div>
            <div class="p-6.5 space-y-6">
                <div>
                    <h5 class="mb-2 font-semibold text-black dark:text-white">
                        <i class="fas fa-info-circle text-info mr-2"></i> Akses Admin
                    </h5>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Admin yang dibuat akan memiliki akses penuh ke data sekolah <strong>{{ $school->name }}</strong> saja.</p>
                </div>
                
                <div>
                    <h5 class="mb-2 font-semibold text-black dark:text-white">
                        <i class="fas fa-lock text-info mr-2"></i> Password
                    </h5>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Pastikan password aman dan minimal 8 karakter. Admin dapat mengubah password setelah login.</p>
                </div>
                
                <div>
                    <h5 class="mb-2 font-semibold text-black dark:text-white">
                        <i class="fas fa-user-shield text-info mr-2"></i> Role
                    </h5>
                    <p class="text-sm text-gray-500 dark:text-gray-400">User ini akan dibuat dengan role <span class="inline-flex rounded-full bg-info/10 px-2.5 py-0.5 text-xs font-medium text-info">Admin</span> dan terikat ke sekolah ini.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection