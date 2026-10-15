@extends('layouts.app')

@section('title', 'Tambah User')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Tambah User Baru
    </h2>
    <a href="{{ route('users.index') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-gray-500 px-10 py-3 text-center font-medium text-white hover:bg-opacity-90 lg:px-8 xl:px-10 transition">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
        <h3 class="font-medium text-black dark:text-white">
            Form User
        </h3>
    </div>
    
    <div class="p-6.5">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf
            
            <div class="mb-4.5">
                <label class="mb-2.5 block text-black dark:text-white">
                    Nama Lengkap <span class="text-meta-1">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('name') border-danger @enderror" />
                @error('name')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4.5">
                <label class="mb-2.5 block text-black dark:text-white">
                    Username <span class="text-meta-1">*</span>
                </label>
                <input type="text" name="username" value="{{ old('username') }}" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('username') border-danger @enderror" />
                @error('username')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4.5">
                <label class="mb-2.5 block text-black dark:text-white">
                    Email Address <span class="text-meta-1">*</span>
                </label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('email') border-danger @enderror" />
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
                        <option value="guru" {{ old('role', 'guru') == 'guru' ? 'selected' : '' }}>Guru Pengajar</option>
                        <option value="admin" {{ old('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        @if(auth()->user() && auth()->user()->isSuperAdmin())
                        <option value="super_admin" {{ old('role') == 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        @endif
                        <option value="wali_kelas" {{ old('role') == 'wali_kelas' ? 'selected' : '' }}>Wali Kelas</option>
                        <option value="waka_kurikulum" {{ old('role') == 'waka_kurikulum' ? 'selected' : '' }}>Waka Kurikulum</option>
                    </select>
                    <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                        <i class="fas fa-chevron-down text-sm"></i>
                    </span>
                </div>
                @error('role')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4.5" id="guru_select_container" style="display: {{ in_array(old('role', 'guru'), ['guru', 'wali_kelas', 'waka_kurikulum']) ? 'block' : 'none' }};">
                <label class="mb-2.5 block text-black dark:text-white">
                    Pilih Data Guru <span class="text-xs text-gray-500">(Khusus Akun Guru / Wali Kelas / Waka Kurikulum)</span>
                </label>
                <div class="relative z-20 bg-transparent dark:bg-form-input">
                    <select name="guru_id" class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                        <option value="">-- Pilih Guru (Opsional) --</option>
                        @foreach($gurus as $guru)
                            <option value="{{ $guru->id }}" {{ old('guru_id') == $guru->id ? 'selected' : '' }}>{{ $guru->nama }} {{ $guru->nip ? '('.$guru->nip.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-5.5 flex flex-col gap-5 sm:flex-row">
                <div class="w-full sm:w-1/2">
                    <label class="mb-2.5 block text-black dark:text-white">
                        Password <span class="text-meta-1">*</span>
                    </label>
                    <input type="password" name="password" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('password') border-danger @enderror" />
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

            <button type="submit" class="flex w-full justify-center rounded bg-brand-500 p-3 font-medium text-white hover:bg-opacity-90 transition">
                Simpan
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.querySelector('select[name="role"]');
        const guruContainer = document.getElementById('guru_select_container');
        
        roleSelect.addEventListener('change', function() {
            if (['guru', 'wali_kelas', 'waka_kurikulum'].includes(this.value)) {
                guruContainer.style.display = 'block';
            } else {
                guruContainer.style.display = 'none';
            }
        });
    });
</script>
@endsection
