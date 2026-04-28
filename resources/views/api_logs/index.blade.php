@extends('layouts.app')

@section('title', 'Log API')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        <i class="fas fa-network-wired text-brand-500 mr-2"></i> Log Penggunaan API System
    </h2>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
        <h3 class="font-medium text-black dark:text-white">
            Riwayat Request API
        </h3>
    </div>
    
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-2 text-left dark:bg-meta-4">
                    <th class="py-4 px-4 font-medium text-black dark:text-white xl:pl-11" width="15%">Waktu</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white" width="10%">Action</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white" width="10%">UID</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center" width="10%">Status</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white" width="35%">Pesan</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white" width="10%">API Key</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white" width="10%">IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-strokedark xl:pl-11 align-top">
                        <p class="text-black dark:text-white text-sm">{{ $log->created_at }}</p>
                    </td>
                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                        <p class="text-black dark:text-white font-medium">{{ $log->action }}</p>
                    </td>
                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                        <p class="text-gray-500 dark:text-gray-400 font-mono text-xs">{{ $log->uid }}</p>
                    </td>
                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-top">
                        @if($log->success)
                            <span class="inline-flex rounded-full bg-success/10 px-3 py-1 text-xs font-medium text-success">Sukses</span>
                        @else
                            <span class="inline-flex rounded-full bg-danger/10 px-3 py-1 text-xs font-medium text-danger">Gagal</span>
                        @endif
                    </td>
                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                        <p class="text-gray-600 dark:text-gray-400 text-sm whitespace-pre-wrap">{{ \Illuminate\Support\Str::limit($log->message, 80) }}</p>
                    </td>
                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                        <code class="rounded bg-gray-100 px-1 py-0.5 text-xs text-brand-500 dark:bg-gray-800">{{ substr($log->api_key, 0, 8) }}...</code>
                    </td>
                    <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-top">
                        <p class="text-gray-500 dark:text-gray-400 text-sm font-mono">{{ $log->ip_address }}</p>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center text-gray-500">
                        Belum ada log API.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($logs->hasPages())
        <div class="px-5 py-4 border-t border-stroke dark:border-strokedark">
            {{ $logs->links('vendor.pagination.tailwind') }}
        </div>
    @endif
</div>
@endsection
