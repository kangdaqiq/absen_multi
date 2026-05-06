@extends('layouts.app')

@section('title', 'Edit Paket Langganan')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-black dark:text-white">
        Edit Paket Langganan
    </h2>
    <nav>
        <ol class="flex items-center gap-2">
            <li><a class="font-medium hover:text-brand-500" href="{{ route('super-admin.dashboard') }}">Dashboard /</a></li>
            <li><a class="font-medium hover:text-brand-500" href="{{ route('super-admin.packages.index') }}">Paket /</a></li>
            <li class="font-medium text-brand-500">Edit</li>
        </ol>
    </nav>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark max-w-4xl mx-auto">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
        <h3 class="font-medium text-black dark:text-white">
            Form Edit Paket: {{ $package->name }}
        </h3>
    </div>
    
    <form action="{{ route('super-admin.packages.update', $package) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="p-6.5">
            
            <div class="mb-4.5">
                <label class="mb-2.5 block text-black dark:text-white">
                    Nama Paket <span class="text-error-500">*</span>
                </label>
                <input type="text" name="name" required value="{{ old('name', $package->name) }}" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                @error('name') <span class="text-error-500 text-sm mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="mb-4.5 flex flex-col gap-6 xl:flex-row">
                <div class="w-full xl:w-1/2">
                    <label class="mb-2.5 block text-black dark:text-white">
                        Harga Bulanan (Rp) <span class="text-error-500">*</span>
                    </label>
                    <input type="number" name="price_monthly" required value="{{ old('price_monthly', (int)$package->price_monthly) }}" min="0" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                    @error('price_monthly') <span class="text-error-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div class="w-full xl:w-1/2">
                    <label class="mb-2.5 block text-black dark:text-white">
                        Harga Tahunan (Rp) <span class="text-error-500">*</span>
                    </label>
                    <input type="number" name="price_yearly" required value="{{ old('price_yearly', (int)$package->price_yearly) }}" min="0" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                    @error('price_yearly') <span class="text-error-500 text-sm mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mb-4.5 flex flex-col gap-6 xl:flex-row">
                <div class="w-full xl:w-1/4">
                    <label class="mb-2.5 block text-black dark:text-white">
                        Limit Siswa <span class="text-xs text-gray-500">(0 = Unlimited)</span>
                    </label>
                    <input type="number" name="student_limit" value="{{ old('student_limit', $package->student_limit) }}" min="0" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                </div>

                <div class="w-full xl:w-1/4">
                    <label class="mb-2.5 block text-black dark:text-white">
                        Limit Guru <span class="text-xs text-gray-500">(0 = Unlimited)</span>
                    </label>
                    <input type="number" name="teacher_limit" value="{{ old('teacher_limit', $package->teacher_limit) }}" min="0" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                </div>

                <div class="w-full xl:w-1/4">
                    <label class="mb-2.5 block text-black dark:text-white">
                        Limit Akun Bot <span class="text-xs text-gray-500">(0 = Unlimited)</span>
                    </label>
                    <input type="number" name="bot_user_limit" value="{{ old('bot_user_limit', $package->bot_user_limit) }}" min="0" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                </div>

                <div class="w-full xl:w-1/4">
                    <label class="mb-2.5 block text-black dark:text-white">
                        Kuota Riwayat <span class="text-xs text-gray-500">(Bulan)</span>
                    </label>
                    <input type="number" name="history_quota_months" value="{{ old('history_quota_months', $package->history_quota_months) }}" min="0" placeholder="Kosong = Unlimited" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                </div>
            </div>

            <div class="mb-5.5 flex flex-wrap gap-6 border-t border-stroke pt-4 dark:border-strokedark">
                <label class="flex items-center cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" name="wa_enabled" value="1" class="sr-only custom-toggle" {{ old('wa_enabled', $package->wa_enabled) ? 'checked' : '' }} />
                        <div class="w-11 h-6 bg-gray-300 rounded-full dark:bg-gray-700 transition-colors duration-300 track"></div>
                        <div class="absolute top-0.5 left-0.5 h-5 w-5 bg-white rounded-full transition-transform duration-300 shadow thumb"></div>
                    </div>
                    <span class="ml-3 font-medium text-black dark:text-white">Fitur WA Notif Aktif</span>
                </label>

                <label class="flex items-center cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" name="bot_enabled" value="1" class="sr-only custom-toggle" {{ old('bot_enabled', $package->bot_enabled) ? 'checked' : '' }} />
                        <div class="w-11 h-6 bg-gray-300 rounded-full dark:bg-gray-700 transition-colors duration-300 track"></div>
                        <div class="absolute top-0.5 left-0.5 h-5 w-5 bg-white rounded-full transition-transform duration-300 shadow thumb"></div>
                    </div>
                    <span class="ml-3 font-medium text-black dark:text-white">Fitur Bot WA Aktif</span>
                </label>

                <label class="flex items-center cursor-pointer">
                    <div class="relative">
                        <input type="checkbox" name="is_active" value="1" class="sr-only custom-toggle" {{ old('is_active', $package->is_active) ? 'checked' : '' }} />
                        <div class="w-11 h-6 bg-gray-300 rounded-full dark:bg-gray-700 transition-colors duration-300 track"></div>
                        <div class="absolute top-0.5 left-0.5 h-5 w-5 bg-white rounded-full transition-transform duration-300 shadow thumb"></div>
                    </div>
                    <span class="ml-3 font-medium text-black dark:text-white">Paket Aktif (Tersedia)</span>
                </label>
            </div>

            <button type="submit" class="flex w-full justify-center rounded bg-brand-500 p-3 font-medium text-gray hover:bg-opacity-90 transition">
                Simpan Perubahan
            </button>
        </div>
    </form>
</div>
@endsection

@push('styles')
<style>
    .custom-toggle:checked ~ .track {
        background-color: #3C50E0 !important;
    }
    .custom-toggle:checked ~ .thumb {
        transform: translateX(20px) !important;
    }
</style>
@endpush
