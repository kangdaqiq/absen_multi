@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Edit User
    </h2>
    <a href="{{ route('users.index') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-gray-500 px-10 py-3 text-center font-medium text-white hover:bg-opacity-90 lg:px-8 xl:px-10 transition">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
        <h3 class="font-medium text-black dark:text-white">
            Form Edit User
        </h3>
    </div>
    
    <div class="p-6.5">
        <form action="{{ route('users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-4.5">
                <label class="mb-2.5 block text-black dark:text-white">
                    Nama Lengkap <span class="text-meta-1">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $user->full_name) }}" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('name') border-danger @enderror" />
                @error('name')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4.5">
                <label class="mb-2.5 block text-black dark:text-white">
                    Username <span class="text-meta-1">*</span>
                </label>
                <input type="text" name="username" value="{{ old('username', $user->username) }}" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('username') border-danger @enderror" />
                @error('username')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4.5">
                <label class="mb-2.5 block text-black dark:text-white">
                    Email Address <span class="text-meta-1">*</span>
                </label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('email') border-danger @enderror" />
                @error('email')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4.5">
                <label class="mb-2.5 block text-black dark:text-white">
                    Role <span class="text-meta-1">*</span>
                </label>
                <div class="relative z-20 bg-transparent dark:bg-form-input">
                    <select name="role" required class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('role') border-danger @enderror">
                        <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                    </select>
                    <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                        <i class="fas fa-chevron-down text-sm"></i>
                    </span>
                </div>
                @error('role')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="mt-8 mb-5.5 border-t border-stroke pt-6 dark:border-strokedark">
                <h4 class="mb-4 font-semibold text-brand-500">Ganti Password (Opsional)</h4>
                
                <div class="flex flex-col gap-5 sm:flex-row">
                    <div class="w-full sm:w-1/2">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Password Baru
                        </label>
                        <input type="password" name="password" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('password') border-danger @enderror" />
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Kosongkan jika tidak ingin mengubah password.</p>
                        @error('password')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="w-full sm:w-1/2">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Konfirmasi Password
                        </label>
                        <input type="password" name="password_confirmation" class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                    </div>
                </div>
            </div>

            <button type="submit" class="flex w-full justify-center rounded bg-brand-500 p-3 font-medium text-white hover:bg-opacity-90 transition">
                Update
            </button>
        </form>
    </div>
</div>
@endsection
