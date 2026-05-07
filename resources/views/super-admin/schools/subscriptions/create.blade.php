@extends('layouts.app')

@section('title', 'Tambah / Perpanjang Langganan')

@section('content')
<div class="mb-6">
    <a href="{{ route('super-admin.schools.subscriptions.index', $school) }}" class="text-brand-500 hover:text-brand-600 transition flex items-center gap-2 mb-4">
        <i class="fas fa-arrow-left"></i> Kembali ke Riwayat Langganan
    </a>
    <h2 class="text-title-md2 font-semibold text-black dark:text-white">
        Tambah / Perpanjang Langganan: {{ $school->name }}
    </h2>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark max-w-3xl">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
        <h3 class="font-medium text-black dark:text-white">
            Form Langganan
        </h3>
    </div>
    <form action="{{ route('super-admin.schools.subscriptions.store', $school) }}" method="POST">
        @csrf
        
        <div class="p-6.5 space-y-5">
            <div>
                <label class="mb-2.5 block text-black dark:text-white">
                    Paket Langganan <span class="text-error-500">*</span>
                </label>
                <select name="package_id" required class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                    <option value="">-- Pilih Paket --</option>
                    @foreach($packages as $pkg)
                        <option value="{{ $pkg->id }}" {{ old('package_id') == $pkg->id ? 'selected' : '' }}>
                            {{ $pkg->name }} - Rp {{ number_format($pkg->price_monthly, 0, ',', '.') }}/bln
                        </option>
                    @endforeach
                </select>
                @error('package_id')
                    <span class="text-sm text-error-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="mb-2.5 block text-black dark:text-white">
                    Siklus Tagihan <span class="text-error-500">*</span>
                </label>
                <select name="billing_cycle" required class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                    <option value="monthly" {{ old('billing_cycle') == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                    <option value="yearly" {{ old('billing_cycle') == 'yearly' ? 'selected' : '' }}>Tahunan</option>
                </select>
                @error('billing_cycle')
                    <span class="text-sm text-error-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="mb-2.5 block text-black dark:text-white">
                    Status <span class="text-error-500">*</span>
                </label>
                <select name="status" required class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                    <option value="paid" {{ old('status', 'paid') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ old('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
                @error('status')
                    <span class="text-sm text-error-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="mb-2.5 block text-black dark:text-white">
                    Tanggal Mulai
                </label>
                <input type="date" name="started_at" value="{{ old('started_at', date('Y-m-d')) }}" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                @error('started_at')
                    <span class="text-sm text-error-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="mb-2.5 block text-black dark:text-white">
                    Tanggal Berakhir (Masa Aktif)
                </label>
                <input type="date" name="expired_at" value="{{ old('expired_at', date('Y-m-d', strtotime('+1 month'))) }}" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                <p class="text-sm text-gray-500 mt-1">Jika status Paid, maka tanggal berakhir ini otomatis memperpanjang masa aktif Sekolah.</p>
                @error('expired_at')
                    <span class="text-sm text-error-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="mb-2.5 block text-black dark:text-white">
                    Tanggal Dibayar
                </label>
                <input type="date" name="paid_at" value="{{ old('paid_at', date('Y-m-d')) }}" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                @error('paid_at')
                    <span class="text-sm text-error-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>
            
            <div>
                <label class="mb-2.5 block text-black dark:text-white">
                    Total Harga (Rp)
                </label>
                <input type="number" name="amount" value="{{ old('amount') }}" placeholder="Kosongkan jika mengikuti harga otomatis paket" class="w-full rounded border-[1.5px] border-stroke bg-transparent py-3 px-5 font-medium outline-none transition focus:border-brand-500 active:border-brand-500 disabled:cursor-default disabled:bg-whiter dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500" />
                <p class="text-sm text-gray-500 mt-1">Jika dikosongkan, nominal tagihan otomatis mengikuti Siklus (Bulanan/Tahunan) dari paket.</p>
                @error('amount')
                    <span class="text-sm text-error-500 mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="flex w-full justify-center rounded bg-brand-500 p-3 font-medium text-white hover:bg-opacity-90 transition">
                Simpan Langganan
            </button>
        </div>
    </form>
</div>
@endsection
