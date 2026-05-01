@extends('layouts.app')

@section('title', 'Status WhatsApp Device')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        <i class="fab fa-whatsapp text-success-500 mr-2"></i> Status WhatsApp Device
    </h2>
    <button onclick="refreshAll()" class="inline-flex items-center justify-center gap-2.5 rounded-md bg-brand-500 px-10 py-3 text-center font-medium text-white hover:bg-opacity-90 lg:px-8 xl:px-10 transition">
        <i class="fas fa-sync-alt"></i> Refresh Semua
    </button>
</div>

<div class="rounded-sm border border-stroke bg-white shadow-default dark:border-strokedark dark:bg-boxdark mb-6">
    <div class="border-b border-stroke py-4 px-6.5 dark:border-strokedark">
        <h3 class="font-medium text-black dark:text-white">
            Daftar Device WA per Sekolah
        </h3>
    </div>
    
    <div class="max-w-full overflow-x-auto">
        <table class="w-full table-auto">
            <thead>
                <tr class="bg-gray-2 text-left dark:bg-meta-4">
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center xl:pl-11" style="width:50px">#</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white">Nama Sekolah</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center" style="width:150px">Device ID</th>
                    <th class="py-4 px-4 font-medium text-black dark:text-white text-center" style="width:180px">Status WA</th>
                </tr>
            </thead>
            <tbody>
                @forelse($schools as $i => $school)
                    <tr>
                        <td class="border-b border-[#eee] py-5 px-4 pl-9 dark:border-strokedark xl:pl-11 text-center align-middle">
                            <p class="text-black dark:text-white">{{ $i + 1 }}</p>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark align-middle">
                            <p class="text-black dark:text-white font-medium inline-block">{{ $school->name }}</p>
                            @if(!$school->is_active)
                                <span class="ml-2 inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">Nonaktif</span>
                            @endif
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-middle">
                            <code class="rounded bg-gray-100 px-2 py-1 text-xs text-brand-500 dark:bg-gray-800">school_{{ $school->id }}</code>
                        </td>
                        <td class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center align-middle">
                            <span class="wa-status" data-school-id="{{ $school->id }}">
                                <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                    <i class="fas fa-circle-notch fa-spin mr-1"></i> Mengecek...
                                </span>
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="border-b border-[#eee] py-5 px-4 dark:border-strokedark text-center text-gray-500">
                            Belum ada sekolah terdaftar.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="rounded-md border-l-6 border-info bg-info/10 px-7 py-3 shadow-md">
    <p class="text-sm text-info">
        <i class="fas fa-info-circle mr-1"></i> Status diambil langsung dari server <code class="bg-white/50 px-1 py-0.5 rounded">{{ env('WA_API_BASE_URL', 'http://localhost:3000') }}</code>. Klik <strong>Refresh Semua</strong> untuk memperbarui.
    </p>
</div>
@endsection

@push('scripts')
@php
    $statusUrlsData = $schools->pluck('id')->map(function($id) {
        return [
            'school_id' => $id,
            'url'       => route('super-admin.whatsapp-devices.status', $id),
        ];
    })->values()->toArray();
@endphp
<script>
    const statusUrls = @json($statusUrlsData);

    function setStatus(schoolId, connected) {
        const el = document.querySelector(`.wa-status[data-school-id="${schoolId}"]`);
        if (!el) return;
        if (connected) {
            el.innerHTML = `<span class="inline-flex rounded-full bg-success-500/10 px-3 py-1 text-xs font-medium text-success-500"><i class="fab fa-whatsapp mr-1 mt-0.5"></i> Terhubung</span>`;
        } else {
            el.innerHTML = `<span class="inline-flex rounded-full bg-error-500/10 px-3 py-1 text-xs font-medium text-error-500"><i class="fas fa-times-circle mr-1 mt-0.5"></i> Tidak Terhubung</span>`;
        }
    }

    function checkOne(schoolId, url) {
        // Show loading
        const el = document.querySelector(`.wa-status[data-school-id="${schoolId}"]`);
        if (el) el.innerHTML = `<span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300"><i class="fas fa-circle-notch fa-spin mr-1 mt-0.5"></i> Mengecek...</span>`;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => setStatus(schoolId, data.connected))
            .catch(() => {
                const el = document.querySelector(`.wa-status[data-school-id="${schoolId}"]`);
                if (el) el.innerHTML = `<span class="inline-flex rounded-full bg-warning/10 px-3 py-1 text-xs font-medium text-warning"><i class="fas fa-exclamation-triangle mr-1 mt-0.5"></i> Error</span>`;
            });
    }

    function refreshAll() {
        statusUrls.forEach(item => checkOne(item.school_id, item.url));
    }

    // Auto check on load, staggered by 300ms per row to avoid flooding the API
    document.addEventListener('DOMContentLoaded', () => {
        statusUrls.forEach((item, index) => {
            setTimeout(() => checkOne(item.school_id, item.url), index * 300);
        });
    });
</script>
@endpush
