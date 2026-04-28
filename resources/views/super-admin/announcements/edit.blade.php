@extends('layouts.app')

@section('title', 'Edit Pengumuman')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Edit Pengumuman
    </h2>
    <a href="{{ route('super-admin.announcements.index') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-gray-500 px-10 py-3 text-center font-medium text-white hover:bg-opacity-90 lg:px-8 xl:px-10 transition">
        <i class="fas fa-arrow-left"></i> Kembali
    </a>
</div>

<div class="grid grid-cols-1 gap-9 sm:grid-cols-2">
    <div class="flex flex-col gap-9">
        <div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
            <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
                <h3 class="font-medium text-black dark:text-white">
                    Form Edit Pengumuman
                </h3>
            </div>
            
            <div class="p-6.5">
                <form action="{{ route('super-admin.announcements.update', $announcement) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Judul Pengumuman <span class="text-meta-1">*</span>
                        </label>
                        <input type="text" name="title" value="{{ old('title', $announcement->title) }}" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('title') border-danger @enderror" />
                        @error('title')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4.5">
                        <label class="mb-2.5 block text-black dark:text-white">
                            Isi Pengumuman <span class="text-meta-1">*</span>
                        </label>
                        <textarea name="content" rows="6" required class="w-full rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500 @error('content') border-danger @enderror">{{ old('content', $announcement->content) }}</textarea>
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Isi pengumuman ini akan ditampilkan di dashboard Admin Sekolah.</p>
                        @error('content')
                            <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-5.5">
                        <label for="is_active" class="flex cursor-pointer select-none items-center">
                            <div class="relative">
                                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $announcement->is_active) ? 'checked' : '' }} class="sr-only" />
                                <div class="box mr-4 flex h-5 w-5 items-center justify-center rounded border border-stroke dark:border-strokedark bg-white dark:bg-boxdark">
                                    <span class="opacity-0">
                                        <i class="fas fa-check text-xs text-brand-500"></i>
                                    </span>
                                </div>
                            </div>
                            <span class="text-sm font-medium text-black dark:text-white">Aktif (Tampilkan di Dashboard)</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-4.5 border-t border-stroke pt-5 dark:border-strokedark">
                        <a href="{{ route('super-admin.announcements.index') }}" class="flex justify-center rounded border border-stroke px-6 py-3 font-medium text-black hover:shadow-1 dark:border-strokedark dark:text-white transition">
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
</div>

<style>
    /* Custom Checkbox styles for Tailadmin */
    input[type="checkbox"]:checked ~ .box {
        border-color: #3C50E0;
    }
    input[type="checkbox"]:checked ~ .box span {
        opacity: 1;
    }
    .dark input[type="checkbox"]:checked ~ .box {
        border-color: #3C50E0;
    }
</style>
@endsection
