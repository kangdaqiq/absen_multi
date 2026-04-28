@extends('layouts.app')

@section('title', 'Edit Profil')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Edit Profil
    </h2>
    <nav>
        <ol class="flex items-center gap-2">
            <li>
                <a class="font-medium text-gray-500 dark:text-gray-400" href="{{ route('dashboard') }}">Dashboard /</a>
            </li>
            <li class="font-medium text-brand-500">Edit Profil</li>
        </ol>
    </nav>
</div>

<div class="grid grid-cols-1 gap-9 sm:grid-cols-2">
    <div class="flex flex-col gap-9">
        <!-- Contact Form -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
            <div class="border-b border-gray-200 py-4 px-6 dark:border-gray-800">
                <h3 class="font-medium text-gray-800 dark:text-white/90">
                    Informasi Akun
                </h3>
            </div>
            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="p-6">
                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-gray-800 dark:text-white/90">
                            Nama Lengkap <span class="text-error-500">*</span>
                        </label>
                        <input type="text" name="name" placeholder="Masukkan nama lengkap Anda"
                            class="w-full rounded-lg border-[1.5px] border-gray-200 bg-transparent py-3 px-5 font-medium text-gray-800 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-500 {{ $errors->has('name') ? 'border-error-500' : '' }}"
                            value="{{ old('name', $user->name) }}" required>
                        @error('name')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-gray-800 dark:text-white/90">
                            Email Address <span class="text-error-500">*</span>
                        </label>
                        <input type="email" name="email" placeholder="Masukkan email Anda"
                            class="w-full rounded-lg border-[1.5px] border-gray-200 bg-transparent py-3 px-5 font-medium text-gray-800 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-500 {{ $errors->has('email') ? 'border-error-500' : '' }}"
                            value="{{ old('email', $user->email) }}" required>
                        @error('email')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    @if(auth()->user()->isSuperAdmin())
                        <div class="mb-4.5 mt-6 border-t border-gray-200 dark:border-gray-800 pt-6">
                            <label class="mb-2.5 block text-gray-800 dark:text-white/90">
                                Logo Global (Super Admin)
                            </label>
                            
                            @php
                                $globalLogo = \App\Models\Setting::where('school_id', 0)->where('setting_key', 'logo_filename')->value('setting_value');
                                if($globalLogo) {
                                    $isStorage = \Illuminate\Support\Str::startsWith($globalLogo, 'schools/');
                                    $logoUrl = $isStorage ? asset('storage/' . $globalLogo) : asset('img/' . $globalLogo);
                                }
                            @endphp

                            @if(isset($logoUrl))
                                <div class="mb-4 rounded-lg border border-gray-200 p-2 dark:border-gray-800 inline-block bg-white dark:bg-gray-800">
                                    <img src="{{ $logoUrl }}" alt="Global Logo" class="h-20 object-contain">
                                </div>
                            @endif

                            <input type="file" name="global_logo" accept="image/*"
                                class="w-full rounded-lg border-[1.5px] border-gray-200 bg-transparent px-5 py-3 font-medium text-gray-500 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400 file:mr-4 file:rounded-lg file:border-0 file:bg-brand-500 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-brand-600">
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Logo ini akan tampil di Halaman Login dan Dashboard Super Admin (Global).</p>
                            @error('global_logo')
                                <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div class="mb-4.5 mt-6 border-t border-gray-200 dark:border-gray-800 pt-6">
                        <h3 class="font-medium text-gray-800 dark:text-white/90 mb-4.5">
                            Ganti Password (Opsional)
                        </h3>

                        <label class="mb-2.5 block text-gray-800 dark:text-white/90">
                            Password Saat Ini
                        </label>
                        <input type="password" name="current_password" placeholder="Masukkan password saat ini"
                            class="mb-4.5 w-full rounded-lg border-[1.5px] border-gray-200 bg-transparent py-3 px-5 font-medium text-gray-800 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-500 {{ $errors->has('current_password') ? 'border-error-500' : '' }}">
                        @error('current_password')
                            <p class="mt-1 mb-4.5 text-xs text-error-500 -mt-3">{{ $message }}</p>
                        @enderror

                        <label class="mb-2.5 block text-gray-800 dark:text-white/90">
                            Password Baru
                        </label>
                        <input type="password" name="new_password" placeholder="Masukkan password baru"
                            class="mb-1 w-full rounded-lg border-[1.5px] border-gray-200 bg-transparent py-3 px-5 font-medium text-gray-800 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-500 {{ $errors->has('new_password') ? 'border-error-500' : '' }}">
                        <p class="mb-4.5 text-xs text-gray-500 dark:text-gray-400">Minimal 8 karakter. Kosongkan jika tidak ingin mengganti password.</p>
                        @error('new_password')
                            <p class="mt-1 mb-4.5 text-xs text-error-500 -mt-3">{{ $message }}</p>
                        @enderror

                        <label class="mb-2.5 block text-gray-800 dark:text-white/90">
                            Konfirmasi Password Baru
                        </label>
                        <input type="password" name="new_password_confirmation" placeholder="Konfirmasi password baru"
                            class="w-full rounded-lg border-[1.5px] border-gray-200 bg-transparent py-3 px-5 font-medium text-gray-800 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-500">
                    </div>

                    <button class="flex w-full justify-center rounded-lg bg-brand-500 p-3 font-medium text-white hover:bg-brand-600 transition mt-6">
                        <i class="fas fa-save mr-2 mt-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection