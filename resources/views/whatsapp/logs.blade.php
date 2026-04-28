@extends('layouts.app')

@section('title', 'Log WhatsApp')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Log Pengiriman WhatsApp
    </h2>
</div>

<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <!-- Header & Search -->
    <div class="flex flex-col sm:flex-row justify-between items-center px-5 py-4 border-b border-gray-200 dark:border-gray-800 gap-4">
        <h4 class="font-semibold text-gray-800 dark:text-white/90">Riwayat Pesan</h4>
        
        <form method="GET" action="{{ route('whatsapp-logs.index') }}" class="w-full sm:w-auto flex items-center">
            <div class="relative w-full sm:w-64">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nomor atau pesan..." 
                    class="w-full rounded-lg border border-gray-200 bg-transparent py-2 pl-4 pr-10 text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:focus:border-brand-500 text-gray-800 dark:text-white/90">
                <button type="submit" class="absolute right-0 top-0 h-full px-3 text-gray-500 hover:text-brand-500 dark:text-gray-400">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-medium text-sm">
                    <th class="px-4 py-4 xl:pl-6 w-16">ID</th>
                    <th class="px-4 py-4 w-40">No Tujuan</th>
                    <th class="px-4 py-4">Pesan</th>
                    <th class="px-4 py-4 text-center w-32">Status</th>
                    <th class="px-4 py-4 w-48">Waktu</th>
                </tr>
            </thead>
            <tbody class="text-sm">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors border-b border-gray-100 dark:border-gray-800 last:border-b-0">
                        <td class="px-4 py-4 xl:pl-6 align-top">
                            <p class="text-gray-500 dark:text-gray-400">#{{ $log->id }}</p>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <p class="font-medium text-gray-800 dark:text-white/90 font-mono">{{ $log->phone_number }}</p>
                        </td>
                        <td class="px-4 py-4 align-top">
                            <p class="text-gray-600 dark:text-gray-400 whitespace-pre-wrap text-xs">{{ \Illuminate\Support\Str::limit($log->message, 150) }}</p>
                        </td>
                        <td class="px-4 py-4 text-center align-top">
                            @if($log->status == 'sent')
                                <span class="inline-flex rounded-full bg-success-50 px-2.5 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">Terkirim</span>
                            @elseif($log->status == 'pending')
                                <span class="inline-flex rounded-full bg-warning-50 px-2.5 py-1 text-xs font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">Pending</span>
                            @elseif($log->status == 'failed')
                                <span class="inline-flex rounded-full bg-error-50 px-2.5 py-1 text-xs font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">Gagal</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ $log->status }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-4 align-top">
                            <p class="text-gray-500 dark:text-gray-400 text-xs">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</p>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Belum ada log pesan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800">
        {{ $logs->links('vendor.pagination.tailwind') }}
    </div>
</div>
@endsection
