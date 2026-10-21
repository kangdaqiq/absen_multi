@extends('layouts.app')

@section('title', 'Manajemen Storage Server & Kuota Client')

@section('content')
<div class="mb-6">
    <x-page-header 
        title="Cloudflare R2 Storage & Kuota Client" 
        subtitle="Monitoring kapasitas cloud storage, pembatasan kuota tiap sekolah, dan CDN distribusi berkas media" 
        icon="fas fa-cloud" 
    />
</div>

{{-- ── Toast Notification ── --}}
<div id="toastBox" class="fixed top-5 right-5 z-[9999] flex flex-col gap-2 pointer-events-none" style="min-width:300px;max-width:380px;"></div>

{{-- ── Top Metrics ── --}}
<div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
    {{-- Status Cloud Storage --}}
    <div class="rounded-2xl border {{ $stats['is_online'] ? 'border-success-200 dark:border-success-800/40 bg-success-50/30 dark:bg-success-500/5' : 'border-error-200 dark:border-error-800/40 bg-error-50/30 dark:bg-error-500/5' }} p-5 shadow-theme-sm md:p-6">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Cloud Storage</span>
            @if($stats['is_online'])
                <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-500">
                    <span class="h-2 w-2 rounded-full bg-success-500 animate-pulse"></span> R2 Aktif
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-error-50 px-2.5 py-1 text-xs font-semibold text-error-600 dark:bg-error-500/15 dark:text-error-500">
                    <span class="h-2 w-2 rounded-full bg-error-500"></span> Terputus
                </span>
            @endif
        </div>
        <div class="mt-4">
            <h4 class="text-lg font-bold text-gray-800 dark:text-white/90 truncate" title="Bucket: {{ $stats['bucket'] ?? 'jagatabsen' }}">
                <i class="fas fa-box text-brand-500 mr-1 text-sm"></i> {{ $stats['bucket'] ?? 'jagatabsen' }}
            </h4>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 truncate" title="{{ $stats['server_url'] }}">
                <i class="fas fa-globe text-info-500 mr-1 text-[11px]"></i> {{ parse_url($stats['server_url'], PHP_URL_HOST) ?: $stats['server_url'] }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Egress Bandwidth: <span class="font-semibold text-success-600 dark:text-success-400">Rp 0 (Free)</span></p>
        </div>
    </div>

    {{-- Total Pemakaian Global --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Pemakaian</span>
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15">
                <i class="fas fa-hdd fa-lg"></i>
            </div>
        </div>
        <div class="mt-4">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">{{ $stats['total_used_human'] }}</h4>
            <div class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>Alokasi: {{ $stats['total_quota_human'] }}</span>
                <span class="font-bold {{ $stats['global_usage_percent'] >= 90 ? 'text-error-500' : ($stats['global_usage_percent'] >= 70 ? 'text-warning-500' : 'text-brand-500') }}">
                    {{ $stats['global_usage_percent'] }}%
                </span>
            </div>
            <div class="mt-1.5 h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-700 {{ $stats['global_usage_percent'] >= 90 ? 'bg-error-500' : ($stats['global_usage_percent'] >= 70 ? 'bg-warning-500' : 'bg-brand-500') }}"
                     style="width: {{ $stats['global_usage_percent'] }}%"></div>
            </div>
        </div>
    </div>

    {{-- Client Sekolah --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Client Sekolah</span>
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-info-50 text-info-500 dark:bg-info-500/15">
                <i class="fas fa-school fa-lg"></i>
            </div>
        </div>
        <div class="mt-4">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">{{ $stats['total_schools'] }}</h4>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                <span class="font-semibold text-success-600 dark:text-success-400">{{ $stats['total_dok_kbm_active'] }}</span> aktifkan Dok KBM
                &bull; <span class="font-semibold">{{ $stats['total_licenses'] }}</span> lisensi self-hosted
            </p>
        </div>
    </div>

    {{-- Total Berkas --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Berkas</span>
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-warning-50 text-warning-500 dark:bg-warning-500/15">
                <i class="fas fa-file-video fa-lg"></i>
            </div>
        </div>
        <div class="mt-4">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">{{ number_format($stats['total_files']) }}</h4>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Foto & video KBM tersimpan</p>
        </div>
    </div>
</div>

{{-- ── Action Toolbar ── --}}
<div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="flex items-center gap-2">
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
            <i class="fas fa-tools text-brand-500 mr-1.5"></i> Operasi Cepat:
        </span>
        @if(!$stats['is_online'])
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-error-50 px-2.5 py-1 text-xs font-semibold text-error-600 dark:bg-error-500/15 dark:text-error-400">
                <i class="fas fa-exclamation-triangle"></i> Storage server tidak dapat dijangkau
            </span>
        @endif
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
        <button type="button" id="btnTestConn"
            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition">
            <i class="fas fa-plug text-info-500"></i> Tes Koneksi
        </button>
        <button type="button" id="btnRecalculate"
            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition">
            <i class="fas fa-sync-alt text-warning-500"></i> Hitung Ulang Kapasitas
        </button>
        <button type="button" id="btnSyncAll"
            class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2 text-xs font-semibold text-white shadow hover:bg-brand-600 transition">
            <i class="fas fa-user-plus"></i> Auto-Sync Semua Client
        </button>
    </div>
</div>

{{-- ── Tabs ── --}}
<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark" x-data="{ tab: 'schools' }">
    <div class="flex border-b border-gray-200 px-6 pt-4 dark:border-gray-800 gap-1">
        <button type="button" @click="tab = 'schools'"
            :class="tab === 'schools' ? 'border-brand-500 text-brand-600 dark:text-brand-400 font-bold bg-brand-50/50 dark:bg-brand-500/10' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 font-medium hover:bg-gray-50 dark:hover:bg-gray-800/50'"
            class="flex items-center gap-2 border-b-2 rounded-t-xl py-3 px-4 text-sm transition">
            <i class="fas fa-school"></i>
            <span>Sekolah Tenant (SaaS)</span>
            <span class="rounded-full bg-brand-100 dark:bg-brand-500/20 px-2 py-0.5 text-xs font-bold text-brand-600 dark:text-brand-400">{{ $schools->count() }}</span>
        </button>
        <button type="button" @click="tab = 'licenses'"
            :class="tab === 'licenses' ? 'border-brand-500 text-brand-600 dark:text-brand-400 font-bold bg-brand-50/50 dark:bg-brand-500/10' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 font-medium hover:bg-gray-50 dark:hover:bg-gray-800/50'"
            class="flex items-center gap-2 border-b-2 rounded-t-xl py-3 px-4 text-sm transition">
            <i class="fas fa-key"></i>
            <span>Client Lisensi (Self-Hosted)</span>
            <span class="rounded-full bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-xs font-bold text-gray-600 dark:text-gray-300">{{ $licenses->count() }}</span>
        </button>
    </div>

    {{-- Tab Sekolah --}}
    <div x-show="tab === 'schools'" class="p-5 sm:p-6">
        <div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h4 class="text-base font-bold text-gray-800 dark:text-white/90">Daftar Tenant Sekolah</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Atur kuota individual tiap sekolah. Kuota ini membatasi kapasitas unggahan foto & video KBM dari aplikasi Android guru.</p>
            </div>
            <div class="relative flex-shrink-0">
                <input type="text" id="searchSchools" placeholder="Cari sekolah..."
                    class="w-56 rounded-xl border border-gray-200 bg-gray-50 py-2 pl-9 pr-4 text-xs outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white transition" />
                <i class="fas fa-search absolute left-3 top-2.5 text-xs text-gray-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-100 dark:ring-gray-800">
            <table class="w-full table-auto text-left" id="tableSchools">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/75 dark:border-gray-800 dark:bg-gray-800/50 text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <th class="py-3 px-4">Sekolah</th>
                        <th class="py-3 px-4">Dok KBM</th>
                        <th class="py-3 px-4">Storage API Key</th>
                        <th class="py-3 px-4 min-w-[220px]">Kuota & Pemakaian</th>
                        <th class="py-3 px-4 text-center">Berkas</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
                    @forelse($schools as $school)
                    @php
                        $pct = $school->usage_percentage;
                        $barColor = $pct >= 90 ? 'bg-error-500' : ($pct >= 70 ? 'bg-warning-500' : 'bg-brand-500');
                        $pctText  = $pct >= 90 ? 'text-error-600' : ($pct >= 70 ? 'text-warning-600' : 'text-gray-500 dark:text-gray-400');
                    @endphp
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition school-row"
                        data-name="{{ strtolower($school->name . ' ' . $school->code) }}">
                        <td class="py-3.5 px-4 align-middle">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15 text-xs font-bold">
                                    {{ strtoupper(substr($school->name, 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-800 dark:text-white/90 truncate max-w-[180px]" title="{{ $school->name }}">{{ $school->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $school->code }} &bull; {{ $school->siswa_count ?? 0 }} Siswa</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 align-middle">
                            @if($school->dok_kbm_enabled)
                                <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-400">
                                    <i class="fas fa-check-circle text-[10px]"></i> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    <i class="fas fa-minus-circle text-[10px]"></i> Off
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 align-middle">
                            @if($school->storage_api_key)
                                <div class="flex items-center gap-1.5">
                                    <code class="rounded bg-gray-100 dark:bg-gray-800 px-2 py-1 text-[11px] text-gray-600 dark:text-gray-300 max-w-[120px] truncate block"
                                          title="{{ $school->storage_api_key }}">{{ substr($school->storage_api_key, 0, 14) }}…</code>
                                    <button type="button"
                                        onclick="copyToClipboard('{{ $school->storage_api_key }}')"
                                        class="rounded p-1.5 text-gray-400 hover:text-brand-500 hover:bg-brand-50 dark:hover:bg-brand-500/10 transition"
                                        title="Salin API Key">
                                        <i class="fas fa-copy text-xs"></i>
                                    </button>
                                </div>
                                @if($school->storage_user_id)
                                    <p class="text-[10px] text-gray-400 mt-0.5 font-mono truncate max-w-[140px]" title="{{ $school->storage_user_id }}">ID: {{ $school->storage_user_id }}</p>
                                @endif
                            @else
                                <span class="text-xs text-gray-400 italic">Belum dibuat</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 align-middle min-w-[220px]">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-200 quota-badge-school-{{ $school->id }}">
                                    {{ $school->used_human ?? '0 B' }}
                                    <span class="font-normal text-gray-400">/ {{ $school->storage_quota_human }}</span>
                                </span>
                                <span class="text-[11px] font-bold {{ $pctText }}">{{ $pct }}%</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 {{ $barColor }}" style="width: {{ $pct }}%"></div>
                            </div>
                            @if($pct >= 90)
                            <p class="mt-0.5 text-[10px] text-error-500 flex items-center gap-0.5">
                                <i class="fas fa-exclamation-triangle"></i> Kuota hampir penuh!
                            </p>
                            @elseif($pct >= 70)
                            <p class="mt-0.5 text-[10px] text-warning-500 flex items-center gap-0.5">
                                <i class="fas fa-exclamation-circle"></i> Penggunaan &ge;70%
                            </p>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 align-middle text-center">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-file-image text-brand-400 text-[10px]"></i>
                                {{ number_format($school->total_files) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 align-middle text-right">
                            <div class="inline-flex items-center gap-1.5">
                                <button type="button"
                                    class="btn-edit-quota inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-600 transition"
                                    data-type="school"
                                    data-id="{{ $school->id }}"
                                    data-name="{{ $school->name }}"
                                    data-quota="{{ $school->storage_quota_mb ?? 1000 }}"
                                    data-quota-human="{{ $school->storage_quota_human }}"
                                    data-used="{{ $school->used_human ?? '0 B' }}"
                                    data-percent="{{ $pct }}">
                                    <i class="fas fa-sliders-h text-[11px]"></i> Kuota
                                </button>
                                <button type="button"
                                    class="btn-sync-single inline-flex items-center justify-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 w-8 h-8 text-gray-500 hover:text-brand-500 hover:border-brand-300 transition"
                                    data-url="{{ route('super-admin.storage.sync-school', $school) }}"
                                    title="Sinkronkan ke Storage Server">
                                    <i class="fas fa-sync text-xs"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <i class="fas fa-school text-3xl text-gray-200 dark:text-gray-700"></i>
                                <p class="text-sm text-gray-400">Belum ada sekolah terdaftar.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tab Lisensi --}}
    <div x-show="tab === 'licenses'" class="p-5 sm:p-6" style="display:none;">
        <div class="mb-4">
            <h4 class="text-base font-bold text-gray-800 dark:text-white/90">Client Lisensi (Self-Hosted)</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Kuota penyimpanan untuk instansi yang memasang sistem absen secara mandiri.</p>
        </div>

        <div class="overflow-x-auto rounded-xl ring-1 ring-gray-100 dark:ring-gray-800">
            <table class="w-full table-auto text-left">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/75 dark:border-gray-800 dark:bg-gray-800/50 text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <th class="py-3 px-4">Client</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Storage API Key</th>
                        <th class="py-3 px-4 min-w-[220px]">Kuota & Pemakaian</th>
                        <th class="py-3 px-4 text-center">Berkas</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
                    @forelse($licenses as $license)
                    @php
                        $quotaMb = $license->storage_quota_mb ?? 1000;
                        $quotaHuman = ($quotaMb >= 1024) ? round($quotaMb / 1024, 2).' GB' : ($quotaMb > 0 ? "{$quotaMb} MB" : 'Unlimited');
                        $pct = $license->usage_percentage;
                        $barColor = $pct >= 90 ? 'bg-error-500' : ($pct >= 70 ? 'bg-warning-500' : 'bg-info-500');
                        $pctText  = $pct >= 90 ? 'text-error-600' : ($pct >= 70 ? 'text-warning-600' : 'text-gray-500 dark:text-gray-400');
                    @endphp
                    <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/30 transition">
                        <td class="py-3.5 px-4 align-middle">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-info-50 text-info-500 dark:bg-info-500/15 text-xs font-bold">
                                    {{ strtoupper(substr($license->client_name, 0, 2)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-800 dark:text-white/90 truncate max-w-[160px]" title="{{ $license->client_name }}">{{ $license->client_name }}</p>
                                    <p class="text-[10px] text-gray-400 font-mono truncate max-w-[160px]">{{ $license->license_key }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3.5 px-4 align-middle">
                            @if($license->is_active)
                                <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-400">
                                    <i class="fas fa-check-circle text-[10px]"></i> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-error-50 px-2.5 py-1 text-xs font-semibold text-error-600 dark:bg-error-500/15 dark:text-error-400">
                                    <i class="fas fa-ban text-[10px]"></i> Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 align-middle">
                            @if($license->storage_api_key)
                                <div class="flex items-center gap-1.5">
                                    <code class="rounded bg-gray-100 dark:bg-gray-800 px-2 py-1 text-[11px] text-gray-600 dark:text-gray-300 max-w-[120px] truncate block"
                                          title="{{ $license->storage_api_key }}">{{ substr($license->storage_api_key, 0, 14) }}…</code>
                                    <button type="button"
                                        onclick="copyToClipboard('{{ $license->storage_api_key }}')"
                                        class="rounded p-1.5 text-gray-400 hover:text-brand-500 hover:bg-brand-50 dark:hover:bg-brand-500/10 transition"
                                        title="Salin API Key">
                                        <i class="fas fa-copy text-xs"></i>
                                    </button>
                                </div>
                                @if($license->storage_user_id)
                                    <p class="text-[10px] text-gray-400 mt-0.5 font-mono truncate max-w-[140px]" title="{{ $license->storage_user_id }}">ID: {{ $license->storage_user_id }}</p>
                                @endif
                            @else
                                <span class="text-xs text-gray-400 italic">Belum dibuat</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 align-middle min-w-[220px]">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-semibold text-gray-700 dark:text-gray-200 quota-badge-license-{{ $license->id }}">
                                    {{ $license->used_human ?? '0 B' }}
                                    <span class="font-normal text-gray-400">/ {{ $quotaHuman }}</span>
                                </span>
                                <span class="text-[11px] font-bold {{ $pctText }}">{{ $pct }}%</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 {{ $barColor }}" style="width: {{ $pct }}%"></div>
                            </div>
                            @if($pct >= 90)
                            <p class="mt-0.5 text-[10px] text-error-500 flex items-center gap-0.5">
                                <i class="fas fa-exclamation-triangle"></i> Kuota hampir penuh!
                            </p>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 align-middle text-center">
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-gray-700 dark:text-gray-300">
                                <i class="fas fa-file text-info-400 text-[10px]"></i>
                                {{ number_format($license->total_files) }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 align-middle text-right">
                            <button type="button"
                                class="btn-edit-quota inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-600 transition"
                                data-type="license"
                                data-id="{{ $license->id }}"
                                data-name="{{ $license->client_name }}"
                                data-quota="{{ $license->storage_quota_mb ?? 1000 }}"
                                data-quota-human="{{ $quotaHuman }}"
                                data-used="{{ $license->used_human ?? '0 B' }}"
                                data-percent="{{ $pct }}">
                                <i class="fas fa-sliders-h text-[11px]"></i> Kuota
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <i class="fas fa-key text-3xl text-gray-200 dark:text-gray-700"></i>
                                <p class="text-sm text-gray-400">Belum ada lisensi client terdaftar.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── Modal Ubah Kuota ── --}}
<div id="modalEditQuota" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-dark" @click.stop>
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-800">
            <div class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15">
                    <i class="fas fa-database"></i>
                </div>
                <div>
                    <h5 class="text-base font-bold text-gray-800 dark:text-white">Atur Batas Kuota Storage</h5>
                    <p class="text-xs text-gray-500 dark:text-gray-400" id="modalTargetName">-</p>
                </div>
            </div>
            <button type="button" id="btnCloseModal"
                class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Current usage mini widget --}}
        <div id="modalUsageWidget" class="mt-4 rounded-xl bg-gray-50 dark:bg-gray-800/50 p-3.5">
            <div class="flex items-center justify-between text-xs mb-1.5">
                <span class="text-gray-500 dark:text-gray-400">Pemakaian saat ini</span>
                <span class="font-bold" id="modalPctLabel">0%</span>
            </div>
            <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <div id="modalUsageBar" class="h-full rounded-full bg-brand-500 transition-all duration-500" style="width:0%"></div>
            </div>
            <div class="mt-1.5 flex justify-between text-xs">
                <span class="font-semibold text-gray-700 dark:text-gray-200" id="modalCurrentUsed">0 B</span>
                <span class="text-gray-400">dari <span id="modalCurrentQuota">-</span></span>
            </div>
        </div>

        <form id="formUpdateQuota" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="type" id="modalTargetType" value="school" />
            <input type="hidden" name="id" id="modalTargetId" value="" />

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                    Batas Kuota Baru <span class="font-normal text-gray-400">(Megabytes)</span>
                </label>
                <div class="relative">
                    <input type="number" name="quota_mb" id="modalQuotaInput"
                        min="0" step="100" required
                        class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pr-12 pl-4 text-sm font-semibold text-gray-800 outline-none focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white transition"
                        placeholder="Contoh: 1000" />
                    <span class="absolute right-3.5 top-2.5 text-xs font-bold text-gray-400">MB</span>
                </div>
                <p class="mt-1 text-xs text-gray-400">
                    <span id="quotaPreviewLabel" class="text-brand-500 font-semibold"></span>
                    — Masukkan <span class="font-bold">0</span> untuk Unlimited.
                </p>
            </div>

            {{-- Quick Presets --}}
            <div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 block mb-1.5">Pilihan Cepat:</span>
                <div class="flex flex-wrap gap-2">
                    @foreach([['val'=>500,'label'=>'500 MB'],['val'=>1000,'label'=>'1 GB'],['val'=>2000,'label'=>'2 GB'],['val'=>5000,'label'=>'5 GB'],['val'=>10000,'label'=>'10 GB'],['val'=>0,'label'=>'∞ Unlimited']] as $p)
                    <button type="button"
                        class="btn-preset rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-brand-50 hover:border-brand-300 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-brand-500/10 dark:hover:text-brand-400 transition"
                        data-val="{{ $p['val'] }}">{{ $p['label'] }}</button>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 border-t border-gray-100 pt-4 dark:border-gray-800">
                <button type="button" id="btnCancelModal"
                    class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition">
                    Batal
                </button>
                <button type="submit" id="btnSaveQuota"
                    class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-5 py-2 text-xs font-semibold text-white shadow hover:bg-brand-600 transition">
                    <i class="fas fa-save"></i> Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Toast Notification ──
    function showToast(msg, type = 'success') {
        const box = document.getElementById('toastBox');
        const colors = {
            success: 'bg-success-50 border-success-200 text-success-700 dark:bg-success-500/10 dark:border-success-500/30 dark:text-success-400',
            error:   'bg-error-50 border-error-200 text-error-700 dark:bg-error-500/10 dark:border-error-500/30 dark:text-error-400',
            info:    'bg-info-50 border-info-200 text-info-700 dark:bg-info-500/10 dark:border-info-500/30 dark:text-info-400',
            warning: 'bg-warning-50 border-warning-200 text-warning-700 dark:bg-warning-500/10 dark:border-warning-500/30 dark:text-warning-400',
        };
        const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' };
        const el = document.createElement('div');
        el.className = `pointer-events-auto flex items-start gap-3 rounded-xl border px-4 py-3 shadow-lg text-sm font-medium transition-all duration-300 translate-x-8 opacity-0 ${colors[type] || colors.info}`;
        el.innerHTML = `<i class="fas ${icons[type] || icons.info} mt-0.5 shrink-0"></i><span class="flex-1">${msg}</span>`;
        box.appendChild(el);
        setTimeout(() => { el.classList.remove('translate-x-8', 'opacity-0'); }, 20);
        setTimeout(() => {
            el.classList.add('opacity-0', 'translate-x-8');
            setTimeout(() => el.remove(), 350);
        }, 4000);
    }

    // ── Clipboard Copy ──
    window.copyToClipboard = function(text) {
        navigator.clipboard.writeText(text).then(() => showToast('API Key disalin ke clipboard!', 'info'));
    };

    // ── Search Filter ──
    const searchInput = document.getElementById('searchSchools');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            document.querySelectorAll('.school-row').forEach(row => {
                row.style.display = (row.dataset.name || '').includes(q) ? '' : 'none';
            });
        });
    }

    // ── Modal ──
    const modal       = document.getElementById('modalEditQuota');
    const form        = document.getElementById('formUpdateQuota');
    const inputQuota  = document.getElementById('modalQuotaInput');
    const targetType  = document.getElementById('modalTargetType');
    const targetId    = document.getElementById('modalTargetId');
    const targetName  = document.getElementById('modalTargetName');
    const currentUsed = document.getElementById('modalCurrentUsed');
    const currentQuota = document.getElementById('modalCurrentQuota');
    const pctLabel    = document.getElementById('modalPctLabel');
    const usageBar    = document.getElementById('modalUsageBar');
    const previewLabel = document.getElementById('quotaPreviewLabel');
    const btnSave     = document.getElementById('btnSaveQuota');

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function updateBarColor(pct) {
        usageBar.className = usageBar.className.replace(/bg-\w+-500/, '');
        if (pct >= 90) usageBar.classList.add('bg-error-500');
        else if (pct >= 70) usageBar.classList.add('bg-warning-500');
        else usageBar.classList.add('bg-brand-500');
    }

    function updateQuotaPreview(mb) {
        const v = parseInt(mb);
        if (isNaN(v) || v <= 0) { previewLabel.textContent = '∞ Unlimited'; return; }
        previewLabel.textContent = v >= 1024 ? (v / 1024).toFixed(2) + ' GB' : v + ' MB';
    }

    document.querySelectorAll('.btn-edit-quota').forEach(btn => {
        btn.addEventListener('click', function () {
            targetType.value  = this.dataset.type;
            targetId.value    = this.dataset.id;
            targetName.textContent = this.dataset.name + ' · ' + (this.dataset.type === 'school' ? 'Sekolah SaaS' : 'Lisensi Self-Hosted');
            inputQuota.value  = this.dataset.quota || 1000;
            currentUsed.textContent = this.dataset.used || '0 B';
            currentQuota.textContent = this.dataset.quotaHuman || '-';
            const pct = parseFloat(this.dataset.percent) || 0;
            usageBar.style.width = pct + '%';
            pctLabel.textContent = pct + '%';
            updateBarColor(pct);
            updateQuotaPreview(this.dataset.quota);
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => inputQuota.focus(), 100);
        });
    });

    inputQuota.addEventListener('input', function () { updateQuotaPreview(this.value); });

    document.getElementById('btnCloseModal').addEventListener('click', closeModal);
    document.getElementById('btnCancelModal').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

    document.querySelectorAll('.btn-preset').forEach(btn => {
        btn.addEventListener('click', function () {
            inputQuota.value = this.dataset.val;
            updateQuotaPreview(this.dataset.val);
        });
    });

    // ── Submit Kuota ──
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        btnSave.disabled = true;
        btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        try {
            const res = await fetch("{{ route('super-admin.storage.update-quota') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    type: targetType.value,
                    id: targetId.value,
                    quota_mb: inputQuota.value
                })
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message, 'success');
                // Update badge di tabel tanpa reload
                const badge = document.querySelector(`.quota-badge-${targetType.value}-${targetId.value}`);
                if (badge) {
                    badge.innerHTML = `0 B <span class="font-normal text-gray-400">/ ${data.quota_human}</span>`;
                }
                closeModal();
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showToast('Gagal: ' + (data.message || 'Terjadi kesalahan.'), 'error');
            }
        } catch (err) {
            showToast('Kesalahan jaringan: ' + err.message, 'error');
        } finally {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="fas fa-save"></i> Simpan';
        }
    });

    // ── Tes Koneksi ──
    document.getElementById('btnTestConn')?.addEventListener('click', async function () {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memeriksa...';
        try {
            const res = await fetch("{{ route('super-admin.storage.test-connection') }}");
            const data = await res.json();
            if (data.success) {
                showToast(`✅ Storage Server online! v${data.data?.version || '?'} · Uptime: ${data.data?.uptime || '-'}`, 'success');
            } else {
                showToast('❌ Koneksi gagal: ' + data.message, 'error');
            }
        } catch (err) {
            showToast('Error: ' + err.message, 'error');
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-plug text-info-500"></i> Tes Koneksi';
        }
    });

    // ── Recalculate ──
    document.getElementById('btnRecalculate')?.addEventListener('click', async function () {
        if (!confirm('Jalankan kalkulasi ulang kapasitas disk seluruh user?')) return;
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghitung...';
        try {
            const res = await fetch("{{ route('super-admin.storage.recalculate') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            const data = await res.json();
            showToast(data.message || 'Kalkulasi selesai.', data.success ? 'success' : 'warning');
            if (data.success) setTimeout(() => window.location.reload(), 1500);
        } catch (err) {
            showToast('Error: ' + err.message, 'error');
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-sync-alt text-warning-500"></i> Hitung Ulang Kapasitas';
        }
    });

    // ── Sync All ──
    document.getElementById('btnSyncAll')?.addEventListener('click', async function () {
        if (!confirm('Daftarkan / perbarui seluruh akun ke Storage Server sekarang?')) return;
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyinkronkan...';
        try {
            const res = await fetch("{{ route('super-admin.storage.sync-all') }}", {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            const data = await res.json();
            showToast(data.message, data.success ? 'success' : 'warning');
            if (data.success) setTimeout(() => window.location.reload(), 1500);
        } catch (err) {
            showToast('Error: ' + err.message, 'error');
        } finally {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-user-plus"></i> Auto-Sync Semua Client';
        }
    });

    // ── Single Sync ──
    document.querySelectorAll('.btn-sync-single').forEach(btn => {
        btn.addEventListener('click', async function () {
            const url = this.dataset.url;
            const original = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin text-brand-500 text-xs"></i>';
            this.disabled = true;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    showToast('Akun storage berhasil disinkronkan!', 'success');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('Gagal: ' + (data.message || 'Error'), 'error');
                }
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
            } finally {
                this.innerHTML = original;
                this.disabled = false;
            }
        });
    });
});
</script>
@endsection
