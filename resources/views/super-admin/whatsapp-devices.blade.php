@extends('layouts.app')

@section('title', 'Status WhatsApp Device')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">
        <i class="fab fa-whatsapp text-success mr-2"></i> Status WhatsApp Device
    </h1>
    <button class="btn btn-sm btn-outline-primary" onclick="refreshAll()">
        <i class="fas fa-sync-alt mr-1"></i> Refresh Semua
    </button>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Daftar Device WA per Sekolah</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th class="text-center" style="width:50px">#</th>
                        <th>Nama Sekolah</th>
                        <th class="text-center" style="width:150px">Device ID</th>
                        <th class="text-center" style="width:180px">Status WA</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($schools as $i => $school)
                    <tr>
                        <td class="text-center align-middle">{{ $i + 1 }}</td>
                        <td class="align-middle">
                            <strong>{{ $school->name }}</strong>
                            @if(!$school->is_active)
                                <span class="badge badge-secondary ml-1">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-center align-middle"><code>school_{{ $school->id }}</code></td>
                        <td class="text-center align-middle">
                            <span class="wa-status" data-school-id="{{ $school->id }}">
                                <span class="badge badge-secondary">
                                    <i class="fas fa-circle-notch fa-spin mr-1"></i> Mengecek...
                                </span>
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-4">Belum ada sekolah terdaftar.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card shadow">
    <div class="card-body py-2 px-3">
        <small class="text-muted">
            <i class="fas fa-info-circle mr-1"></i>
            Status diambil langsung dari server <code>{{ env('WA_API_BASE_URL', 'http://localhost:3000') }}</code>.
            Klik <strong>Refresh Semua</strong> untuk memperbarui.
        </small>
    </div>
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
            el.innerHTML = `<span class="badge badge-success"><i class="fab fa-whatsapp mr-1"></i> Terhubung</span>`;
        } else {
            el.innerHTML = `<span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i> Tidak Terhubung</span>`;
        }
    }

    function checkOne(schoolId, url) {
        // Show loading
        const el = document.querySelector(`.wa-status[data-school-id="${schoolId}"]`);
        if (el) el.innerHTML = `<span class="badge badge-secondary"><i class="fas fa-circle-notch fa-spin mr-1"></i> Mengecek...</span>`;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => setStatus(schoolId, data.connected))
            .catch(() => {
                const el = document.querySelector(`.wa-status[data-school-id="${schoolId}"]`);
                if (el) el.innerHTML = `<span class="badge badge-warning"><i class="fas fa-exclamation-triangle mr-1"></i> Error</span>`;
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
