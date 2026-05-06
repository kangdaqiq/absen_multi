@extends('layouts.app')

@section('title', 'Kelola Paket Langganan')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        <i class="fas fa-box text-brand-500 mr-2"></i> Kelola Paket Langganan
    </h2>
    <a href="{{ route('super-admin.packages.create') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-brand-500 px-10 py-3 text-center font-medium text-white hover:bg-opacity-90 lg:px-8 xl:px-10 transition">
        <i class="fas fa-plus"></i> Tambah Paket
    </a>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
        <h3 class="font-medium text-black dark:text-white">
            Daftar Paket Langganan
        </h3>
    </div>
    
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                    <th class="py-4 px-4 xl:pl-11 border-b border-stroke dark:border-strokedark">Nama Paket</th>
                    <th class="py-4 px-4 border-b border-stroke dark:border-strokedark text-right">Harga Bulanan</th>
                    <th class="py-4 px-4 border-b border-stroke dark:border-strokedark text-right">Harga Tahunan</th>
                    <th class="py-4 px-4 text-center border-b border-stroke dark:border-strokedark">Limit Siswa</th>
                    <th class="py-4 px-4 text-center border-b border-stroke dark:border-strokedark">Limit Guru</th>
                    <th class="py-4 px-4 text-center border-b border-stroke dark:border-strokedark">Limit Bot</th>
                    <th class="py-4 px-4 text-center border-b border-stroke dark:border-strokedark">Kuota Riwayat</th>
                    <th class="py-4 px-4 text-center border-b border-stroke dark:border-strokedark">Fitur WA</th>
                    <th class="py-4 px-4 text-center border-b border-stroke dark:border-strokedark">Fitur Bot</th>
                    <th class="py-4 px-4 border-b border-stroke dark:border-strokedark">Status</th>
                    <th class="py-4 px-4 border-b border-stroke dark:border-strokedark">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($packages as $package)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-strokedark xl:pl-11 align-top">
                            <p class="text-black dark:text-white font-medium">{{ $package->name }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top text-right">
                            Rp {{ number_format($package->price_monthly, 0, ',', '.') }}
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top text-right">
                            Rp {{ number_format($package->price_yearly, 0, ',', '.') }}
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                            {{ $package->student_limit == 0 ? 'Unlimited' : $package->student_limit }}
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                            {{ $package->teacher_limit == 0 ? 'Unlimited' : $package->teacher_limit }}
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                            {{ $package->bot_user_limit == 0 ? 'Unlimited' : $package->bot_user_limit }}
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                            {{ is_null($package->history_quota_months) ? 'Unlimited' : $package->history_quota_months . ' Bulan' }}
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                            @if($package->wa_enabled)
                                <i class="fas fa-check text-success-500"></i>
                            @else
                                <i class="fas fa-times text-error-500"></i>
                            @endif
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                            @if($package->bot_enabled)
                                <i class="fas fa-check text-success-500"></i>
                            @else
                                <i class="fas fa-times text-error-500"></i>
                            @endif
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                            @if($package->is_active)
                                <span class="inline-flex rounded-full bg-success-500/10 px-3 py-1 text-xs font-medium text-success-500">Aktif</span>
                            @else
                                <span class="inline-flex rounded-full bg-error-500/10 px-3 py-1 text-xs font-medium text-error-500">Nonaktif</span>
                            @endif
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('super-admin.packages.edit', $package) }}" class="text-warning-500 hover:text-warning-700 hover:bg-warning-50 p-2 rounded-lg transition" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="text-error-500 hover:text-error-700 hover:bg-error-50 p-2 rounded-lg transition btnDelete" data-id="{{ $package->id }}" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <form id="delete-form-{{ $package->id }}" action="{{ route('super-admin.packages.destroy', $package) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center">
                            Belum ada paket
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btnDelete').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    if (confirm('Apakah Anda yakin ingin menghapus paket ini?')) {
                        document.getElementById('delete-form-' + id).submit();
                    }
                });
            });
        });
    </script>
@endpush
