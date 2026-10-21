@extends('layouts.app')

@section('title', 'Manajemen Storage Server & Kuota Client')

@section('content')
<div class="mb-6">
    <x-page-header 
        title="Storage Server & Kuota Client" 
        subtitle="Monitoring kapasitas disk, pembatasan kuota multi-user, dan manajemen API key tiap client sekolah" 
        icon="fas fa-server" 
    />
</div>

{{-- Status Banner & Top Metrics --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
    {{-- Status Server --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Status Server</span>
            @if($stats['is_online'])
                <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-500">
                    <span class="h-2 w-2 rounded-full bg-success-500 animate-pulse"></span> Online
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-full bg-error-50 px-2.5 py-1 text-xs font-semibold text-error-600 dark:bg-error-500/15 dark:text-error-500">
                    <span class="h-2 w-2 rounded-full bg-error-500"></span> Offline / Unreachable
                </span>
            @endif
        </div>
        <div class="mt-4">
            <h4 class="text-lg font-bold text-gray-800 dark:text-white/90 truncate" title="{{ $stats['server_url'] }}">
                {{ parse_url($stats['server_url'], PHP_URL_HOST) ?: $stats['server_url'] }}
            </h4>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Versi: <span class="font-semibold text-brand-500">{{ $stats['server_version'] }}</span> &bull; Max Upload: {{ $stats['max_upload_size'] }}
            </p>
        </div>
    </div>

    {{-- Total Kapasitas Terpakai --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Pemakaian Storage</span>
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15">
                <i class="fas fa-hdd fa-lg"></i>
            </div>
        </div>
        <div class="mt-4">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">
                {{ $stats['total_used_human'] }}
            </h4>
            <div class="mt-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>Total Alokasi: {{ $stats['total_quota_human'] }}</span>
                <span class="font-semibold text-brand-500">{{ $stats['global_usage_percent'] }}%</span>
            </div>
            <div class="mt-1.5 h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                <div class="h-full rounded-full bg-brand-500 transition-all duration-500" style="width: {{ $stats['global_usage_percent'] }}%"></div>
            </div>
        </div>
    </div>

    {{-- Tenant / Sekolah Dok KBM --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Client Sekolah</span>
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-info-50 text-info-500 dark:bg-info-500/15">
                <i class="fas fa-school fa-lg"></i>
            </div>
        </div>
        <div class="mt-4">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">
                {{ $stats['total_schools'] }}
            </h4>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                <span class="font-semibold text-success-600 dark:text-success-400">{{ $stats['total_dok_kbm_active'] }} sekolah</span> mengaktifkan Dok KBM
            </p>
        </div>
    </div>

    {{-- Total Berkas Tersimpan --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
        <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Berkas</span>
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-warning-50 text-warning-500 dark:bg-warning-500/15">
                <i class="fas fa-file-video fa-lg"></i>
            </div>
        </div>
        <div class="mt-4">
            <h4 class="text-2xl font-bold text-gray-800 dark:text-white/90">
                {{ number_format($stats['total_files']) }}
            </h4>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Dokumentasi foto & video KBM
            </p>
        </div>
    </div>
</div>

{{-- Action Toolbar --}}
<div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
    <div class="flex items-center gap-2">
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
            <i class="fas fa-tools text-brand-500 mr-1.5"></i> Operasi Cepat:
        </span>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
        <button type="button" id="btnTestConn" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition">
            <i class="fas fa-plug text-info-500"></i> Tes Koneksi
        </button>
        <button type="button" id="btnRecalculate" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition">
            <i class="fas fa-sync-alt text-warning-500"></i> Hitung Ulang Kapasitas (Recalculate)
        </button>
        <button type="button" id="btnSyncAll" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-4 py-2 text-xs font-semibold text-white shadow hover:bg-brand-600 transition">
            <i class="fas fa-user-plus"></i> Auto-Sync Semua Client ke Storage
        </button>
    </div>
</div>

{{-- Tabs & Clients Table --}}
<div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark" x-data="{ tab: 'schools' }">
    {{-- Tab Headers --}}
    <div class="flex border-b border-gray-200 px-6 pt-4 dark:border-gray-800">
        <button 
            type="button"
            @click="tab = 'schools'" 
            :class="tab === 'schools' ? 'border-brand-500 text-brand-500 dark:text-brand-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 font-medium'"
            class="flex items-center gap-2 border-b-2 py-3 px-4 text-sm transition">
            <i class="fas fa-school"></i>
            <span>Sekolah Tenant (SaaS)</span>
            <span class="rounded-full bg-brand-50 px-2 py-0.5 text-xs text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">{{ $schools->count() }}</span>
        </button>
        <button 
            type="button"
            @click="tab = 'licenses'" 
            :class="tab === 'licenses' ? 'border-brand-500 text-brand-500 dark:text-brand-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 font-medium'"
            class="flex items-center gap-2 border-b-2 py-3 px-4 text-sm transition">
            <i class="fas fa-key"></i>
            <span>Client Lisensi (Self-Hosted)</span>
            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ $licenses->count() }}</span>
        </button>
    </div>

    {{-- Tab 1: Sekolah --}}
    <div x-show="tab === 'schools'" class="p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h4 class="text-base font-bold text-gray-800 dark:text-white/90">Daftar Tenant Sekolah</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">Atur kuota individual tiap sekolah. Kuota ini membatasi kapasitas unggahan foto & video KBM dari aplikasi Android guru.</p>
            </div>
            <div class="relative">
                <input type="text" id="searchSchools" placeholder="Cari sekolah..." class="w-64 rounded-xl border border-gray-200 bg-gray-50 py-2 pl-9 pr-4 text-xs outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                <i class="fas fa-search absolute left-3 top-2.5 text-xs text-gray-400"></i>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full table-auto text-left" id="tableSchools">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/75 dark:border-gray-800 dark:bg-gray-800/50 text-xs font-semibold text-gray-600 dark:text-gray-300">
                        <th class="py-3.5 px-4">Sekolah</th>
                        <th class="py-3.5 px-4">Status Dok KBM</th>
                        <th class="py-3.5 px-4">Storage API Key</th>
                        <th class="py-3.5 px-4">Batas Kuota</th>
                        <th class="py-3.5 px-4 min-w-[180px]">Pemakaian Disk</th>
                        <th class="py-3.5 px-4 text-center">Berkas</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
                    @forelse($schools as $school)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition school-row" data-name="{{ strtolower($school->name . ' ' . $school->code) }}">
                            <td class="py-4 px-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15 font-bold">
                                        <i class="fas fa-school"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-white/90">{{ $school->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $school->code }} &bull; {{ $school->siswa_count }} Siswa</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 align-middle">
                                @if($school->dok_kbm_enabled)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-400">
                                        <i class="fas fa-check-circle"></i> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                        <i class="fas fa-minus-circle"></i> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-4 align-middle">
                                @if($school->storage_api_key)
                                    <div class="flex items-center gap-1.5 font-mono text-xs text-gray-600 dark:text-gray-300">
                                        <span class="rounded bg-gray-100 dark:bg-gray-800 px-2 py-1 max-w-[140px] truncate" title="{{ $school->storage_api_key }}">
                                            {{ substr($school->storage_api_key, 0, 12) }}...
                                        </span>
                                        <button 
                                            type="button" 
                                            onclick="navigator.clipboard.writeText('{{ $school->storage_api_key }}'); alert('API Key disalin ke clipboard!');" 
                                            class="rounded p-1 text-gray-400 hover:text-brand-500 transition" 
                                            title="Salin API Key">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Belum dibuat</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 align-middle font-medium text-gray-800 dark:text-white/90">
                                <span class="quota-badge-school-{{ $school->id }} inline-flex items-center gap-1 rounded-lg bg-brand-50 dark:bg-brand-500/15 px-2.5 py-1 text-xs font-bold text-brand-600 dark:text-brand-400">
                                    <i class="fas fa-database text-[10px]"></i> {{ $school->storage_quota_human }}
                                </span>
                            </td>
                            <td class="py-4 px-4 align-middle">
                                <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-300 mb-1">
                                    <span class="font-medium">{{ $school->used_human }}</span>
                                    <span class="font-bold {{ $school->usage_percentage >= 90 ? 'text-error-500' : ($school->usage_percentage >= 75 ? 'text-warning-500' : 'text-gray-500') }}">
                                        {{ $school->usage_percentage }}%
                                    </span>
                                </div>
                                <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-300 {{ $school->usage_percentage >= 90 ? 'bg-error-500' : ($school->usage_percentage >= 75 ? 'bg-warning-500' : 'bg-brand-500') }}" style="width: {{ $school->usage_percentage }}%"></div>
                                </div>
                            </td>
                            <td class="py-4 px-4 align-middle text-center font-semibold text-gray-700 dark:text-gray-300">
                                {{ number_format($school->total_files) }}
                            </td>
                            <td class="py-4 px-4 align-middle text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button 
                                        type="button"
                                        class="btn-edit-quota inline-flex items-center gap-1 rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-600 transition"
                                        data-type="school"
                                        data-id="{{ $school->id }}"
                                        data-name="{{ $school->name }}"
                                        data-quota="{{ $school->storage_quota_mb ?? 1000 }}"
                                        data-quota-human="{{ $school->storage_quota_human }}"
                                        data-used="{{ $school->used_human }}">
                                        <i class="fas fa-edit"></i> Atur Kuota
                                    </button>
                                    <button 
                                        type="button"
                                        class="btn-sync-single rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-1.5 text-gray-500 hover:text-brand-500 transition"
                                        data-url="{{ route('super-admin.storage.sync-school', $school) }}"
                                        title="Sinkronkan Akun Storage Server">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                Belum ada sekolah terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tab 2: Lisensi Client Self-Hosted --}}
    <div x-show="tab === 'licenses'" class="p-6" style="display: none;">
        <div class="mb-4">
            <h4 class="text-base font-bold text-gray-800 dark:text-white/90">Daftar Client Lisensi (Self-Hosted)</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400">Kuota penyimpanan untuk instansi yang memasang sistem absen secara mandiri dan mengarah ke central storage server.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full table-auto text-left">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/75 dark:border-gray-800 dark:bg-gray-800/50 text-xs font-semibold text-gray-600 dark:text-gray-300">
                        <th class="py-3.5 px-4">Nama Client</th>
                        <th class="py-3.5 px-4">Status Lisensi</th>
                        <th class="py-3.5 px-4">Storage API Key</th>
                        <th class="py-3.5 px-4">Batas Kuota</th>
                        <th class="py-3.5 px-4 min-w-[180px]">Pemakaian Disk</th>
                        <th class="py-3.5 px-4 text-center">Berkas</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
                    @forelse($licenses as $license)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                            <td class="py-4 px-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-info-50 text-info-500 dark:bg-info-500/15 font-bold">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800 dark:text-white/90">{{ $license->client_name }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ $license->license_key }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4 align-middle">
                                @if($license->is_active)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-400">
                                        <i class="fas fa-check-circle"></i> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-error-50 px-2.5 py-1 text-xs font-semibold text-error-600 dark:bg-error-500/15 dark:text-error-400">
                                        <i class="fas fa-ban"></i> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-4 align-middle">
                                @if($license->storage_api_key)
                                    <div class="flex items-center gap-1.5 font-mono text-xs text-gray-600 dark:text-gray-300">
                                        <span class="rounded bg-gray-100 dark:bg-gray-800 px-2 py-1 max-w-[140px] truncate">
                                            {{ substr($license->storage_api_key, 0, 12) }}...
                                        </span>
                                        <button 
                                            type="button" 
                                            onclick="navigator.clipboard.writeText('{{ $license->storage_api_key }}'); alert('API Key disalin ke clipboard!');" 
                                            class="rounded p-1 text-gray-400 hover:text-brand-500 transition">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Belum dibuat</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 align-middle font-medium text-gray-800 dark:text-white/90">
                                @php
                                    $quotaMb = $license->storage_quota_mb ?? 1000;
                                    $quotaHuman = ($quotaMb >= 1024) ? round($quotaMb / 1024, 2) . ' GB' : ($quotaMb > 0 ? "{$quotaMb} MB" : 'Unlimited');
                                @endphp
                                <span class="quota-badge-license-{{ $license->id }} inline-flex items-center gap-1 rounded-lg bg-info-50 dark:bg-info-500/15 px-2.5 py-1 text-xs font-bold text-info-600 dark:text-info-400">
                                    <i class="fas fa-database text-[10px]"></i> {{ $quotaHuman }}
                                </span>
                            </td>
                            <td class="py-4 px-4 align-middle">
                                <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-300 mb-1">
                                    <span class="font-medium">{{ $license->used_human }}</span>
                                    <span class="font-bold {{ $license->usage_percentage >= 90 ? 'text-error-500' : ($license->usage_percentage >= 75 ? 'text-warning-500' : 'text-gray-500') }}">
                                        {{ $license->usage_percentage }}%
                                    </span>
                                </div>
                                <div class="h-2 w-full rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-300 {{ $license->usage_percentage >= 90 ? 'bg-error-500' : ($license->usage_percentage >= 75 ? 'bg-warning-500' : 'bg-info-500') }}" style="width: {{ $license->usage_percentage }}%"></div>
                                </div>
                            </td>
                            <td class="py-4 px-4 align-middle text-center font-semibold text-gray-700 dark:text-gray-300">
                                {{ number_format($license->total_files) }}
                            </td>
                            <td class="py-4 px-4 align-middle text-right">
                                <button 
                                    type="button"
                                    class="btn-edit-quota inline-flex items-center gap-1 rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-600 transition"
                                    data-type="license"
                                    data-id="{{ $license->id }}"
                                    data-name="{{ $license->client_name }}"
                                    data-quota="{{ $license->storage_quota_mb ?? 1000 }}"
                                    data-quota-human="{{ $quotaHuman }}"
                                    data-used="{{ $license->used_human }}">
                                    <i class="fas fa-edit"></i> Atur Kuota
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                Belum ada lisensi client terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Ubah Kuota Storage --}}
<div id="modalEditQuota" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 backdrop-blur-sm p-4">
    <div class="relative w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-dark">
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
            <button type="button" id="btnCloseModal" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="formUpdateQuota" class="mt-5 space-y-4">
            @csrf
            <input type="hidden" name="type" id="modalTargetType" value="school" />
            <input type="hidden" name="id" id="modalTargetId" value="" />

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                    Batas Kuota Baru (dalam Megabytes / MB)
                </label>
                <div class="relative">
                    <input 
                        type="number" 
                        name="quota_mb" 
                        id="modalQuotaInput" 
                        min="0" 
                        step="100" 
                        required 
                        class="w-full rounded-xl border border-gray-200 bg-white py-2.5 px-4 text-sm font-semibold text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        placeholder="Contoh: 1000"
                    />
                    <span class="absolute right-3.5 top-2.5 text-xs font-bold text-gray-400">MB</span>
                </div>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Masukkan <span class="font-bold text-brand-500">0</span> untuk kuota tanpa batas (Unlimited).
                </p>
            </div>

            {{-- Quick Presets --}}
            <div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Pilihan Cepat:</span>
                <div class="mt-1.5 flex flex-wrap gap-2">
                    <button type="button" class="btn-preset rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-brand-50 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition" data-val="500">500 MB</button>
                    <button type="button" class="btn-preset rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-brand-50 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition" data-val="1000">1 GB (1000 MB)</button>
                    <button type="button" class="btn-preset rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-brand-50 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition" data-val="2000">2 GB (2000 MB)</button>
                    <button type="button" class="btn-preset rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-brand-50 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition" data-val="5000">5 GB (5000 MB)</button>
                    <button type="button" class="btn-preset rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-brand-50 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition" data-val="10000">10 GB</button>
                    <button type="button" class="btn-preset rounded-lg border border-gray-200 bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-brand-50 hover:text-brand-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition" data-val="0">Unlimited</button>
                </div>
            </div>

            <div class="rounded-xl bg-gray-50 dark:bg-gray-800/60 p-3 text-xs text-gray-500 dark:text-gray-400 space-y-1">
                <div class="flex justify-between">
                    <span>Pemakaian Saat Ini:</span>
                    <span class="font-bold text-gray-700 dark:text-gray-200" id="modalCurrentUsed">-</span>
                </div>
                <div class="flex justify-between">
                    <span>Sinkronisasi Storage:</span>
                    <span class="font-medium text-success-600 dark:text-success-400"><i class="fas fa-bolt"></i> Real-time via API</span>
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-2.5 border-t border-gray-100 pt-4 dark:border-gray-800">
                <button type="button" id="btnCancelModal" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    Batal
                </button>
                <button type="submit" id="btnSaveQuota" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-5 py-2 text-xs font-semibold text-white shadow hover:bg-brand-600 transition">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalEditQuota');
    const form = document.getElementById('formUpdateQuota');
    const inputQuota = document.getElementById('modalQuotaInput');
    const targetType = document.getElementById('modalTargetType');
    const targetId = document.getElementById('modalTargetId');
    const targetName = document.getElementById('modalTargetName');
    const currentUsed = document.getElementById('modalCurrentUsed');
    const btnSave = document.getElementById('btnSaveQuota');

    // Search filter tabel sekolah
    const searchInput = document.getElementById('searchSchools');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            document.querySelectorAll('.school-row').forEach(row => {
                const name = row.getAttribute('data-name') || '';
                row.style.display = name.includes(query) ? '' : 'none';
            });
        });
    }

    // Modal open
    document.querySelectorAll('.btn-edit-quota').forEach(btn => {
        btn.addEventListener('click', function () {
            targetType.value = this.dataset.type;
            targetId.value = this.dataset.id;
            targetName.textContent = this.dataset.name + ' (' + (this.dataset.type === 'school' ? 'Sekolah' : 'Lisensi') + ')';
            inputQuota.value = this.dataset.quota || 1000;
            currentUsed.textContent = this.dataset.used || '0 B';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            inputQuota.focus();
        });
    });

    // Close modal
    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    document.getElementById('btnCloseModal').addEventListener('click', closeModal);
    document.getElementById('btnCancelModal').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    // Preset buttons
    document.querySelectorAll('.btn-preset').forEach(btn => {
        btn.addEventListener('click', function () {
            inputQuota.value = this.dataset.val;
        });
    });

    // Submit form update kuota
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        btnSave.disabled = true;
        btnSave.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

        try {
            const formData = new FormData(form);
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
                alert(data.message);
                // Update badge di table langsung
                const badgeSelector = `.quota-badge-${targetType.value}-${targetId.value}`;
                const badge = document.querySelector(badgeSelector);
                if (badge) {
                    badge.innerHTML = `<i class="fas fa-database text-[10px]"></i> ${data.quota_human}`;
                }
                closeModal();
                window.location.reload();
            } else {
                alert('Gagal: ' + (data.message || 'Terjadi kesalahan saat menyimpan kuota.'));
            }
        } catch (err) {
            alert('Terjadi kesalahan jaringan: ' + err.message);
        } finally {
            btnSave.disabled = false;
            btnSave.innerHTML = '<i class="fas fa-save"></i> Simpan Perubahan';
        }
    });

    // Tes Koneksi
    const btnTest = document.getElementById('btnTestConn');
    if (btnTest) {
        btnTest.addEventListener('click', async function () {
            btnTest.disabled = true;
            btnTest.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memeriksa...';
            try {
                const res = await fetch("{{ route('super-admin.storage.test-connection') }}");
                const data = await res.json();
                if (data.success) {
                    alert('SUKSES: Koneksi ke Storage Server terhubung dengan baik!\n\nStatus: ' + (data.data.status || 'healthy') + '\nVersi: ' + (data.data.version || '1.1.0') + '\nUptime: ' + (data.data.uptime || '-'));
                } else {
                    alert('GAGAL: ' + data.message);
                }
            } catch (err) {
                alert('Error: ' + err.message);
            } finally {
                btnTest.disabled = false;
                btnTest.innerHTML = '<i class="fas fa-plug text-info-500"></i> Tes Koneksi';
            }
        });
    }

    // Recalculate usage
    const btnRecalc = document.getElementById('btnRecalculate');
    if (btnRecalc) {
        btnRecalc.addEventListener('click', async function () {
            if (!confirm('Jalankan kalkulasi ulang kapasitas disk seluruh user di storage server?')) return;
            btnRecalc.disabled = true;
            btnRecalc.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghitung...';
            try {
                const res = await fetch("{{ route('super-admin.storage.recalculate') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                alert(data.message || 'Kalkulasi ulang selesai.');
                window.location.reload();
            } catch (err) {
                alert('Error: ' + err.message);
            } finally {
                btnRecalc.disabled = false;
                btnRecalc.innerHTML = '<i class="fas fa-sync-alt text-warning-500"></i> Hitung Ulang Kapasitas (Recalculate)';
            }
        });
    }

    // Auto-sync all
    const btnSyncAll = document.getElementById('btnSyncAll');
    if (btnSyncAll) {
        btnSyncAll.addEventListener('click', async function () {
            if (!confirm('Daftarkan / perbarui seluruh akun sekolah ke Storage Server sekarang?')) return;
            btnSyncAll.disabled = true;
            btnSyncAll.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyinkronkan...';
            try {
                const res = await fetch("{{ route('super-admin.storage.sync-all') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                alert(data.message);
                window.location.reload();
            } catch (err) {
                alert('Error: ' + err.message);
            } finally {
                btnSyncAll.disabled = false;
                btnSyncAll.innerHTML = '<i class="fas fa-user-plus"></i> Auto-Sync Semua Client ke Storage';
            }
        });
    }

    // Single School Sync
    document.querySelectorAll('.btn-sync-single').forEach(btn => {
        btn.addEventListener('click', async function () {
            const url = this.dataset.url;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin text-brand-500"></i>';
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();
                if (data.success) {
                    alert('Sukses: Akun storage berhasil disinkronkan!');
                    window.location.reload();
                } else {
                    alert('Gagal: ' + (data.message || 'Gagal sinkron'));
                }
            } catch (err) {
                alert('Error: ' + err.message);
            } finally {
                btn.innerHTML = '<i class="fas fa-sync"></i>';
            }
        });
    });
});
</script>
@endsection
