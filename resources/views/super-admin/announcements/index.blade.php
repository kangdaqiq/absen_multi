@extends('layouts.app')

@section('title', 'Kelola Pengumuman & Update Rilis')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            <i class="fas fa-bullhorn text-brand-500 mr-2"></i> Pengumuman & Update Rilis
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Kelola kabar pembaruan, informasi rilis aplikasi, dan notifikasi pop-up untuk admin dashboard.
        </p>
    </div>
    <a href="{{ route('super-admin.announcements.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-2.5 text-center text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 transition">
        <i class="fas fa-plus"></i> Tambah Pengumuman
    </a>
</div>

@if(session('success'))
    <div class="mb-6 flex items-center justify-between rounded-xl border border-success-200 bg-success-50 p-4 text-success-800 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400">
        <div class="flex items-center gap-3">
            <i class="fas fa-check-circle text-lg"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    </div>
@endif

<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="border-b border-gray-200 py-4 px-6 dark:border-gray-800 flex items-center justify-between">
        <h3 class="font-semibold text-gray-800 dark:text-white">
            Daftar Pengumuman & Informasi
        </h3>
        <span class="text-xs text-gray-400">Total: {{ $announcements->total() }} data</span>
    </div>
    
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50">
                    <th class="py-3.5 px-4 font-medium text-gray-700 dark:text-gray-300 text-center" width="5%">No</th>
                    <th class="py-3.5 px-4 font-medium text-gray-700 dark:text-gray-300" width="18%">Tipe / Versi</th>
                    <th class="py-3.5 px-4 font-medium text-gray-700 dark:text-gray-300">Judul & Isi Singkat</th>
                    <th class="py-3.5 px-4 font-medium text-gray-700 dark:text-gray-300 text-center" width="12%">Pop-up</th>
                    <th class="py-3.5 px-4 font-medium text-gray-700 dark:text-gray-300 text-center" width="10%">Status</th>
                    <th class="py-3.5 px-4 font-medium text-gray-700 dark:text-gray-300" width="15%">Tanggal</th>
                    <th class="py-3.5 px-4 font-medium text-gray-700 dark:text-gray-300 text-center" width="12%">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($announcements as $announcement)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition">
                        <td class="py-4 px-4 text-center align-middle text-sm text-gray-500 dark:text-gray-400">
                            {{ $loop->iteration + $announcements->firstItem() - 1 }}
                        </td>
                        <td class="py-4 px-4 align-middle">
                            <div class="flex flex-col gap-1 items-start">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold border {{ $announcement->type_badge_class }}">
                                    <i class="fas {{ $announcement->type_icon }}"></i>
                                    {{ $announcement->type_label }}
                                </span>
                                @if($announcement->version)
                                    <span class="inline-flex items-center gap-1 font-mono text-[11px] font-bold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">
                                        <i class="fas fa-tag text-[9px] text-gray-400"></i> {{ $announcement->version }}
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="py-4 px-4 align-middle">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $announcement->title }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ Str::limit($announcement->content, 90) }}</p>
                            @if($announcement->action_url)
                                <a href="{{ $announcement->action_url }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-brand-500 hover:underline mt-1">
                                    <i class="fas fa-link"></i> {{ $announcement->action_text ?: 'Link Aksi' }}
                                </a>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-center align-middle">
                            @if($announcement->is_popup)
                                <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                    <i class="fas fa-window-restore text-[10px]"></i> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    Tidak
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-4 text-center align-middle">
                            @if($announcement->is_active)
                                <span class="inline-flex rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-400">
                                    Aktif
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="py-4 px-4 align-middle">
                            <p class="text-xs text-gray-700 dark:text-gray-300 font-medium">{{ $announcement->created_at->format('d M Y') }}</p>
                            <p class="text-[11px] text-gray-400">{{ $announcement->created_at->format('H:i') }} WIB</p>
                        </td>
                        <td class="py-4 px-4 text-center align-middle">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('super-admin.announcements.edit', $announcement) }}" class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-brand-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition" title="Edit">
                                    <i class="fas fa-edit text-xs"></i>
                                </a>
                                <form action="{{ route('super-admin.announcements.destroy', $announcement) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus pengumuman ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:bg-red-50 hover:text-error-500 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-red-500/10 transition" title="Hapus">
                                        <i class="fas fa-trash text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-gray-500 dark:text-gray-400 text-sm">
                            <i class="fas fa-bullhorn text-2xl text-gray-300 dark:text-gray-600 mb-2 block"></i>
                            Belum ada pengumuman yang dibuat.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($announcements->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800">
            {{ $announcements->links() }}
        </div>
    @endif
</div>
@endsection
