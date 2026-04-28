@extends('layouts.app')

@section('title', 'Kelola Sekolah')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        <i class="fas fa-school text-brand-500 mr-2"></i> Kelola Sekolah
    </h2>
    <a href="{{ route('super-admin.schools.create') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-brand-500 px-10 py-3 text-center font-medium text-white hover:bg-opacity-90 lg:px-8 xl:px-10 transition">
        <i class="fas fa-plus"></i> Tambah Sekolah
    </a>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
        <h3 class="font-medium text-black dark:text-white">
            Daftar Sekolah & Perkantoran
        </h3>
    </div>
    
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-2 text-left dark:bg-meta-4">
                    <th class="py-4 px-4 font-medium text-black dark:text-white xl:pl-11">Nama Sekolah</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white">Kode</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white">Kontak</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center">Siswa</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center">Karyawan/Guru</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center">Admin</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white">Status</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schools as $school)
                    <tr>
                        <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-strokedark xl:pl-11 align-top">
                            <p class="text-black dark:text-white font-medium">{{ $school->name }}</p>
                            @if($school->address)
                                <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">{{ Str::limit($school->address, 50) }}</p>
                            @endif
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                            <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $school->code }}</span>
                            <div class="mt-2">
                                @if($school->isOffice())
                                    <span class="inline-flex rounded-full bg-brand-500/10 px-3 py-1 text-xs font-medium text-brand-500"><i class="fas fa-building mr-1 mt-0.5"></i> Perkantoran</span>
                                @else
                                    <span class="inline-flex rounded-full bg-info/10 px-3 py-1 text-xs font-medium text-info"><i class="fas fa-school mr-1 mt-0.5"></i> Sekolah</span>
                                @endif
                            </div>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top text-sm">
                            <div class="flex flex-col gap-1 text-gray-600 dark:text-gray-400">
                                @if($school->phone)
                                    <span><i class="fas fa-phone mr-1"></i> {{ $school->phone }}</span>
                                @endif
                                @if($school->email)
                                    <span><i class="fas fa-envelope mr-1"></i> {{ $school->email }}</span>
                                @endif
                                @if($school->operator_phone)
                                    <span class="mt-1 text-brand-500"><i class="fas fa-headset mr-1"></i> <strong>Operator:</strong> {{ $school->operator_phone }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                            <p class="text-black dark:text-white font-medium">{{ $school->siswa_count }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                            <p class="text-black dark:text-white font-medium">{{ $school->guru_count }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                            <a href="{{ route('super-admin.schools.admins.index', $school) }}" class="inline-flex rounded-full bg-info/10 px-3 py-1 text-xs font-medium text-info hover:bg-info/20 transition">
                                {{ $school->admins_count }} admin
                            </a>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                            @if($school->is_active)
                                <span class="inline-flex rounded-full bg-success/10 px-3 py-1 text-xs font-medium text-success">Aktif</span>
                            @else
                                <span class="inline-flex rounded-full bg-danger/10 px-3 py-1 text-xs font-medium text-danger">Nonaktif</span>
                            @endif
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('super-admin.schools.edit', $school) }}" class="hover:text-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('super-admin.schools.admins.index', $school) }}" class="hover:text-info" title="Kelola Admin">
                                    <i class="fas fa-users-cog"></i>
                                </a>
                                <a href="{{ route('super-admin.schools.devices.index', $school) }}" class="hover:text-meta-6" title="Kelola Device">
                                    <i class="fas fa-microchip"></i>
                                </a>
                                <button type="button" class="hover:text-danger btnDelete" data-id="{{ $school->id }}" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <form id="delete-form-{{ $school->id }}" action="{{ route('super-admin.schools.destroy', $school) }}" method="POST" class="hidden">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center text-gray-500">
                            Belum ada sekolah
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($schools->hasPages())
        <div class="px-5 py-4 border-t border-stroke dark:border-strokedark">
            {{ $schools->links('vendor.pagination.tailwind') }}
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
                    if (confirm('Apakah Anda yakin ingin menghapus sekolah ini? Semua data terkait akan ikut terhapus!')) {
                        document.getElementById('delete-form-' + id).submit();
                    }
                });
            });
        });
    </script>
@endpush