@extends('layouts.app')

@section('title', 'Kelola Langganan Sekolah')

@section('content')
<div class="mb-6">
    <a href="{{ route('super-admin.schools.index') }}" class="text-brand-500 hover:text-brand-600 transition flex items-center gap-2 mb-4">
        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Sekolah
    </a>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            <i class="fas fa-file-invoice-dollar text-brand-500 mr-2"></i> Langganan: {{ $school->name }}
        </h2>
        <a href="{{ route('super-admin.schools.subscriptions.create', $school) }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-gray-100 dark:bg-meta-4 px-6 py-2.5 text-center font-medium text-gray-700 dark:text-white hover:bg-opacity-90 transition">
            <i class="fas fa-cog"></i> Form Manual Lanjutan
        </a>
    </div>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark mb-6">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark bg-brand-50/50 dark:bg-brand-500/10">
        <h3 class="font-medium text-brand-700 dark:text-brand-300 flex items-center gap-2">
            <i class="fas fa-bolt text-brand-500"></i> Perpanjang Cepat
        </h3>
    </div>
    <form action="{{ route('super-admin.schools.subscriptions.quick-renew', $school) }}" method="POST" class="p-6.5">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-5 items-end">
            <div class="sm:col-span-5">
                <label class="mb-2 block text-sm font-medium text-black dark:text-white">
                    Pilih Paket
                </label>
                <div class="relative z-20 bg-transparent dark:bg-form-input">
                    <select name="package_id" required class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                        @foreach($packages as $pkg)
                            <option value="{{ $pkg->id }}" {{ $activePackageId == $pkg->id ? 'selected' : '' }}>
                                {{ $pkg->name }} (Rp {{ number_format($pkg->price_monthly, 0, ',', '.') }}/bln)
                            </option>
                        @endforeach
                    </select>
                    <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                        <i class="fas fa-chevron-down text-sm"></i>
                    </span>
                </div>
            </div>
            <div class="sm:col-span-4">
                <label class="mb-2 block text-sm font-medium text-black dark:text-white">
                    Durasi Langganan
                </label>
                <div class="relative z-20 bg-transparent dark:bg-form-input">
                    <select name="duration_months" required class="relative z-20 w-full appearance-none rounded border border-stroke bg-transparent py-3 px-5 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-form-strokedark dark:bg-form-input dark:focus:border-brand-500">
                        <option value="1">1 Bulan</option>
                        <option value="3">3 Bulan</option>
                        <option value="6">6 Bulan</option>
                        <option value="12" selected>1 Tahun (12 Bln)</option>
                        <option value="24">2 Tahun (24 Bln)</option>
                    </select>
                    <span class="absolute top-1/2 right-4 z-30 -translate-y-1/2">
                        <i class="fas fa-chevron-down text-sm"></i>
                    </span>
                </div>
            </div>
            <div class="sm:col-span-3">
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded bg-brand-500 py-3 px-4 font-medium text-white hover:bg-opacity-90 transition">
                    <i class="fas fa-check-circle"></i> Perpanjang Sekarang
                </button>
            </div>
        </div>
        <div class="mt-4 p-3 bg-brand-50 dark:bg-brand-500/10 rounded-md text-sm text-brand-800 dark:text-brand-300 flex items-start gap-2">
            <i class="fas fa-info-circle mt-0.5"></i>
            <p>
                Status langganan akan langsung ditandai <strong>Paid</strong>. Masa aktif akan disambung dari 
                {{ $school->expired_at && $school->expired_at > now() ? 'akhir masa aktif saat ini (' . $school->expired_at->format('d M Y') . ')' : 'hari ini' }}.
            </p>
        </div>
    </form>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark flex justify-between items-center">
        <h3 class="font-medium text-black dark:text-white">
            Riwayat Langganan
        </h3>
    </div>
    
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                    <th class="py-4 px-4 xl:pl-11 border-b border-stroke dark:border-strokedark">Paket</th>
                    <th class="py-4 px-4 text-center border-b border-stroke dark:border-strokedark">Siklus</th>
                    <th class="py-4 px-4 border-b border-stroke dark:border-strokedark text-right">Total (Rp)</th>
                    <th class="py-4 px-4 border-b border-stroke dark:border-strokedark">Mulai</th>
                    <th class="py-4 px-4 border-b border-stroke dark:border-strokedark">Berakhir</th>
                    <th class="py-4 px-4 border-b border-stroke dark:border-strokedark text-center">Status</th>
                    <th class="py-4 px-4 border-b border-stroke dark:border-strokedark text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($subscriptions as $sub)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-strokedark xl:pl-11 align-top">
                            <p class="text-black dark:text-white font-medium">{{ $sub->package->name ?? '-' }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                            {{ ucfirst($sub->billing_cycle) }}
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-right align-top">
                            {{ number_format($sub->amount, 0, ',', '.') }}
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                            {{ $sub->started_at ? $sub->started_at->format('d M Y') : '-' }}
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                            {{ $sub->expired_at ? $sub->expired_at->format('d M Y') : '-' }}
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                            @if($sub->status === 'paid')
                                <span class="inline-flex rounded-full bg-success-500/10 px-3 py-1 text-xs font-medium text-success-500">Paid</span>
                            @elseif($sub->status === 'unpaid')
                                <span class="inline-flex rounded-full bg-warning-500/10 px-3 py-1 text-xs font-medium text-warning-500">Unpaid</span>
                            @elseif($sub->status === 'expired')
                                <span class="inline-flex rounded-full bg-gray-500/10 px-3 py-1 text-xs font-medium text-gray-500">Expired</span>
                            @else
                                <span class="inline-flex rounded-full bg-error-500/10 px-3 py-1 text-xs font-medium text-error-500">{{ ucfirst($sub->status) }}</span>
                            @endif
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                            <div class="flex items-center justify-center space-x-3">
                                @if($sub->status === 'unpaid')
                                    <form action="{{ route('super-admin.schools.subscriptions.confirm', [$school, $sub]) }}" method="POST" class="inline-block" onsubmit="return confirm('Konfirmasi pembayaran ini dan aktifkan langganan?')">
                                        @csrf
                                        <button type="submit" class="text-success-500 hover:text-success-700 hover:bg-success-50 p-2 rounded-lg transition" title="Konfirmasi Pembayaran">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('super-admin.schools.subscriptions.edit', [$school, $sub]) }}" class="text-warning-500 hover:text-warning-700 hover:bg-warning-50 p-2 rounded-lg transition" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="text-error-500 hover:text-error-700 hover:bg-error-50 p-2 rounded-lg transition btnDelete" data-id="{{ $sub->id }}" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <form id="delete-form-{{ $sub->id }}" action="{{ route('super-admin.schools.subscriptions.destroy', [$school, $sub]) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center">
                            Belum ada data langganan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($subscriptions->hasPages())
        <div class="border-t border-stroke py-4 px-6.5 dark:border-strokedark">
            {{ $subscriptions->links('vendor.pagination.tailwind') }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btnDelete').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    if (confirm('Apakah Anda yakin ingin menghapus data langganan ini?')) {
                        document.getElementById('delete-form-' + id).submit();
                    }
                });
            });
        });
    </script>
@endpush
