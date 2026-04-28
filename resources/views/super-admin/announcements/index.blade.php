@extends('layouts.app')

@section('title', 'Kelola Pengumuman')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        <i class="fas fa-bullhorn text-brand-500 mr-2"></i> Kelola Pengumuman
    </h2>
    <a href="{{ route('super-admin.announcements.create') }}" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-brand-500 px-10 py-3 text-center font-medium text-white hover:bg-opacity-90 lg:px-8 xl:px-10 transition">
        <i class="fas fa-plus"></i> Tambah Pengumuman
    </a>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
        <h3 class="font-medium text-black dark:text-white">
            Daftar Pengumuman
        </h3>
    </div>
    
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-2 text-left dark:bg-meta-4">
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center xl:pl-11" width="5%">No</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white">Judul</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center">Status</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white">Dibuat Pada</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center" width="15%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($announcements as $announcement)
                    <tr>
                        <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-strokedark xl:pl-11 text-center align-middle">
                            <p class="text-black dark:text-white">{{ $loop->iteration + $announcements->firstItem() - 1 }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-middle">
                            <p class="text-black dark:text-white font-medium">{{ $announcement->title }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-middle">
                            @if($announcement->is_active)
                                <span class="inline-flex rounded-full bg-success/10 px-3 py-1 text-xs font-medium text-success">Aktif</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">Tidak Aktif</span>
                            @endif
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-middle">
                            <p class="text-gray-500 dark:text-gray-400 text-sm">{{ $announcement->created_at->format('d M Y H:i') }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-middle">
                            <div class="flex items-center justify-center space-x-3">
                                <a href="{{ route('super-admin.announcements.edit', $announcement) }}" class="hover:text-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('super-admin.announcements.destroy', $announcement) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus pengumuman ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="hover:text-danger" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center text-gray-500">
                            Belum ada pengumuman.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($announcements->hasPages())
        <div class="px-5 py-4 border-t border-stroke dark:border-strokedark">
            {{ $announcements->links('vendor.pagination.tailwind') }}
        </div>
    @endif
</div>
@endsection
