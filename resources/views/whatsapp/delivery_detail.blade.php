@extends('layouts.app')

@section('title', 'Detail Delivery WA & Status Last Seen')

@section('content')
<div x-data="{
    showReminderModal: false,
    targetPhone: '',
    targetName: '',
    targetRole: '',
    openModal(phone, name, role) {
        this.targetPhone = phone;
        this.targetName = name;
        this.targetRole = role;
        this.showReminderModal = true;
    }
}">

    <!-- Page Header & Breadcrumbs -->
    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1">
                <a href="{{ route('whatsapp-logs.index') }}" class="hover:text-brand-500 flex items-center gap-1.5 transition font-medium">
                    <i class="fas fa-arrow-left text-[10px]"></i> Log WhatsApp
                </a>
                <span>/</span>
                <span class="text-gray-700 dark:text-gray-200 font-semibold">Detail Delivery & Last Seen</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                <span>Detail Delivery WhatsApp Absensi</span>
            </h2>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('whatsapp-logs.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 transition shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark dark:text-gray-200 dark:hover:bg-gray-800">
                <i class="fas fa-arrow-left text-gray-400"></i> Log Pesan
            </a>
            <a href="{{ route('settings.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-3.5 py-2 text-xs font-semibold text-white hover:bg-brand-600 transition shadow-theme-xs" style="background-color: #4f6bed; color: #ffffff;">
                <i class="fas fa-cog"></i> Atur Masa Aktif ({{ $expiryDays }} Hari)
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="mb-4 rounded-xl border border-success-200 bg-success-50 p-3.5 text-xs text-success-700 dark:border-success-500/30 dark:bg-success-500/15 dark:text-success-400 flex items-center gap-2.5">
            <i class="fas fa-check-circle text-sm text-success-500"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-xl border border-error-200 bg-error-50 p-3.5 text-xs text-error-700 dark:border-error-500/30 dark:bg-error-500/15 dark:text-error-400 flex items-center gap-2.5">
            <i class="fas fa-exclamation-triangle text-sm text-error-500"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <!-- Compact Info Banner (Clean, Thin & Professional) -->
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-brand-200 bg-brand-25 px-4 py-2.5 shadow-theme-xs dark:border-brand-500/30 dark:bg-brand-500/10 text-xs">
        <div>
            <span class="text-gray-700 dark:text-gray-300">
                <strong>Aturan Last Seen:</strong> Maksimal <strong class="text-brand-600 dark:text-brand-400">{{ $expiryDays }} Hari ({{ $expiryHours }} Jam)</strong>. Kontak &ge; {{ $expiryDays }} hari atau belum pernah interaksi otomatis diskip demi perlindungan nomor sekolah dari pemblokiran WhatsApp.
            </span>
        </div>
        <a href="{{ route('settings.index') }}" class="shrink-0 font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 flex items-center gap-1 transition">
            Ubah Aturan <i class="fas fa-chevron-right text-[10px]"></i>
        </a>
    </div>

    <!-- Tabs Navigation -->
    <div class="mb-5 border-b border-gray-200 dark:border-gray-800">
        <nav class="flex space-x-6" aria-label="Tabs">
            <a href="{{ route('whatsapp-logs.delivery-detail', ['tab' => 'delivery', 'date' => $date]) }}"
               class="inline-flex items-center gap-2 border-b-2 py-3 px-1 text-sm font-semibold transition {{ $activeTab === 'delivery' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <i class="fas fa-calendar-check text-base"></i>
                <span>Detail Delivery Absen</span>
                @if(isset($dailySummary))
                    <span class="ml-1.5 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        {{ $dailySummary['total_absen'] }}
                    </span>
                @endif
            </a>

            <a href="{{ route('whatsapp-logs.delivery-detail', ['tab' => 'contacts']) }}"
               class="inline-flex items-center gap-2 border-b-2 py-3 px-1 text-sm font-semibold transition {{ $activeTab === 'contacts' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                <i class="fas fa-address-book text-base"></i>
                <span>Status Last Seen Kontak</span>
                @if(isset($summaryStats))
                    <span class="ml-1.5 rounded-full bg-success-50 px-2 py-0.5 text-xs font-semibold text-success-700 dark:bg-success-500/20 dark:text-success-400">
                        {{ $summaryStats['active'] }}/{{ $summaryStats['total'] }}
                    </span>
                @endif
            </a>
        </nav>
    </div>

    @if($activeTab === 'delivery')
        <!-- ============================================================== -->
        <!-- TAB 1: DETAIL DELIVERY NOTIFIKASI VS ABSEN PADA TANGGAL INI     -->
        <!-- ============================================================== -->

        <!-- 4 Balanced Metric Cards (2x2 on Tablet, 4 across on Desktop) -->
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <!-- 1. Total Absensi -->
            <a href="{{ route('whatsapp-logs.delivery-detail', ['tab' => 'delivery', 'date' => $date, 'role' => $filterRole, 'status' => 'all', 'search' => $search]) }}"
               class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-brand-300 dark:border-gray-800 dark:bg-gray-dark">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Total Presensi
                        </span>
                        <div class="mt-2 flex items-baseline gap-2">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $dailySummary['total_absen'] }}
                            </h3>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">orang</span>
                        </div>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
                    <span>Tanggal: <strong>{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</strong></span>
                    <span class="text-brand-600 dark:text-brand-400 font-semibold">Semua Presensi &rarr;</span>
                </div>
            </a>

            <!-- 2. Masuk Antrean WA -->
            <a href="{{ route('whatsapp-logs.delivery-detail', ['tab' => 'delivery', 'date' => $date, 'role' => $filterRole, 'status' => 'queued', 'search' => $search]) }}"
               class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-emerald-300 dark:border-gray-800 dark:bg-gray-dark">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Masuk Antrean WA
                        </span>
                        <div class="mt-2 flex items-baseline gap-2">
                            <h3 class="text-2xl font-bold text-success-600 dark:text-success-400">
                                {{ $dailySummary['total_queued'] }}
                            </h3>
                            <span class="text-xs font-medium text-success-600 dark:text-success-400">
                                ({{ $dailySummary['queued_rate'] }}%)
                            </span>
                        </div>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                        <i class="fab fa-whatsapp text-2xl"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
                    <span>Last Seen &lt; {{ $expiryDays }} hari</span>
                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Filter Masuk &rarr;</span>
                </div>
            </a>

            <!-- 3. TIDAK Masuk Antrean WA (HIGHLIGHTED) -->
            <a href="{{ route('whatsapp-logs.delivery-detail', ['tab' => 'delivery', 'date' => $date, 'role' => $filterRole, 'status' => 'not_queued', 'search' => $search]) }}"
               class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-rose-300 dark:border-gray-800 dark:bg-gray-dark">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-error-600 dark:text-error-400">
                            Tidak Masuk Antrean
                        </span>
                        <div class="mt-2 flex items-baseline gap-2">
                            <h3 class="text-2xl font-bold text-error-600 dark:text-error-400">
                                {{ $dailySummary['total_not_queued'] }}
                            </h3>
                            <span class="text-xs font-medium text-error-600 dark:text-error-400">
                                orang
                            </span>
                        </div>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400">
                        <i class="fas fa-user-slash text-xl"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
                    <span>Diskip / No kosong</span>
                    <span class="text-rose-600 dark:text-rose-400 font-semibold">Lihat Daftar &rarr;</span>
                </div>
            </a>

            <!-- 4. Rincian Skip & Nomor Kosong -->
            <a href="{{ route('whatsapp-logs.delivery-detail', ['tab' => 'delivery', 'date' => $date, 'role' => $filterRole, 'status' => 'skipped', 'search' => $search]) }}"
               class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:border-amber-300 dark:border-gray-800 dark:bg-gray-dark">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Rincian Penyebab Skip
                        </span>
                        <div class="mt-2 flex items-baseline gap-2">
                            <h3 class="text-2xl font-bold text-warning-600 dark:text-warning-400">
                                {{ $dailySummary['total_skipped_last_seen'] }}
                            </h3>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">expired</span>
                        </div>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                        <i class="fas fa-user-clock text-xl"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
                    <span>Nomor Kosong: <strong>{{ $dailySummary['total_no_phone'] }}</strong></span>
                    <span class="text-amber-600 dark:text-amber-400 font-semibold">Filter Skip &rarr;</span>
                </div>
            </a>
        </div>

        <!-- Filter Toolbar Tab 1 (Clean Inline Row) -->
        <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-3.5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
            <form method="GET" action="{{ route('whatsapp-logs.delivery-detail') }}" class="flex flex-wrap items-center gap-2.5">
                <input type="hidden" name="tab" value="delivery">

                <!-- Tanggal -->
                <input type="date" name="date" value="{{ $date }}"
                       class="rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs text-gray-800 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">

                <!-- Filter Peran -->
                <select name="role" class="rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs text-gray-800 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="all" {{ $filterRole === 'all' ? 'selected' : '' }}>Semua Peran</option>
                    <option value="siswa" {{ $filterRole === 'siswa' ? 'selected' : '' }}>Siswa Saja</option>
                    <option value="guru" {{ $filterRole === 'guru' ? 'selected' : '' }}>Guru Saja</option>
                </select>

                <!-- Filter Status Delivery -->
                <select name="status" class="rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs text-gray-800 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="all" {{ $filterStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="not_queued" {{ $filterStatus === 'not_queued' ? 'selected' : '' }}>Tidak Masuk Antrean</option>
                    <option value="queued" {{ $filterStatus === 'queued' ? 'selected' : '' }}>Masuk Antrean WA</option>
                    <option value="skipped" {{ $filterStatus === 'skipped' ? 'selected' : '' }}>Diskip Last Seen</option>
                    <option value="no_phone" {{ $filterStatus === 'no_phone' ? 'selected' : '' }}>Tanpa Nomor WA</option>
                </select>

                <!-- Search Input (Bersih tanpa icon) -->
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, nomor WA, NIS..."
                       class="w-48 sm:w-60 rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs text-gray-800 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">

                <!-- Buttons -->
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-500 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-600 transition shadow-theme-xs" style="background-color: #4f6bed; color: #ffffff;">
                    Filter
                </button>

                <a href="{{ route('whatsapp-logs.delivery-detail', ['tab' => 'delivery']) }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50 transition dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800" title="Reset Filter">
                    <i class="fas fa-undo"></i>
                </a>
            </form>
        </div>

        <!-- Quick Filter Pills Navigation -->
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 mr-1">Tampilkan:</span>

            <!-- 1. Semua Presensi -->
            <a href="{{ route('whatsapp-logs.delivery-detail', ['tab' => 'delivery', 'date' => $date, 'role' => $filterRole, 'status' => 'all', 'search' => $search]) }}"
               class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-medium transition shadow-2xs {{ $filterStatus === 'all' ? 'text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' }}"
               @if($filterStatus === 'all') style="background-color: #1f2937; color: #ffffff;" @endif>
                <span>Semua Presensi</span>
                <span class="rounded-full px-1.5 py-0.2 text-[10px] font-bold"
                      style="{{ $filterStatus === 'all' ? 'background-color: rgba(255,255,255,0.25); color: #ffffff;' : 'background-color: #e5e7eb; color: #374151;' }}">
                    {{ $dailySummary['total_absen'] }}
                </span>
            </a>

            <!-- 2. TIDAK Masuk Antrean WA -->
            <a href="{{ route('whatsapp-logs.delivery-detail', ['tab' => 'delivery', 'date' => $date, 'role' => $filterRole, 'status' => 'not_queued', 'search' => $search]) }}"
               class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition shadow-2xs {{ $filterStatus === 'not_queued' ? 'text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-500/15 dark:text-rose-400' }}"
               @if($filterStatus === 'not_queued') style="background-color: #e11d48; color: #ffffff;" @endif>
                <i class="fas fa-times-circle"></i>
                <span>TIDAK Masuk Antrean WA</span>
                <span class="rounded-full px-1.5 py-0.2 text-[10px] font-bold"
                      style="{{ $filterStatus === 'not_queued' ? 'background-color: rgba(255,255,255,0.25); color: #ffffff;' : 'background-color: #fecdd3; color: #9f1239;' }}">
                    {{ $dailySummary['total_not_queued'] }}
                </span>
            </a>

            <!-- 3. Masuk Antrean WA -->
            <a href="{{ route('whatsapp-logs.delivery-detail', ['tab' => 'delivery', 'date' => $date, 'role' => $filterRole, 'status' => 'queued', 'search' => $search]) }}"
               class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition shadow-2xs {{ $filterStatus === 'queued' ? 'text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-500/15 dark:text-emerald-400' }}"
               @if($filterStatus === 'queued') style="background-color: #059669; color: #ffffff;" @endif>
                <i class="fas fa-check-circle"></i>
                <span>Masuk Antrean WA</span>
                <span class="rounded-full px-1.5 py-0.2 text-[10px] font-bold"
                      style="{{ $filterStatus === 'queued' ? 'background-color: rgba(255,255,255,0.25); color: #ffffff;' : 'background-color: #a7f3d0; color: #065f46;' }}">
                    {{ $dailySummary['total_queued'] }}
                </span>
            </a>

            <!-- 4. Diskip Last Seen -->
            <a href="{{ route('whatsapp-logs.delivery-detail', ['tab' => 'delivery', 'date' => $date, 'role' => $filterRole, 'status' => 'skipped', 'search' => $search]) }}"
               class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition shadow-2xs {{ $filterStatus === 'skipped' ? 'text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 dark:bg-amber-500/15 dark:text-amber-400' }}"
               @if($filterStatus === 'skipped') style="background-color: #d97706; color: #ffffff;" @endif>
                <i class="fas fa-user-clock"></i>
                <span>Diskip Last Seen</span>
                <span class="rounded-full px-1.5 py-0.2 text-[10px] font-bold"
                      style="{{ $filterStatus === 'skipped' ? 'background-color: rgba(255,255,255,0.25); color: #ffffff;' : 'background-color: #fde68a; color: #92400e;' }}">
                    {{ $dailySummary['total_skipped_last_seen'] }}
                </span>
            </a>
        </div>

        <!-- Table Data Absensi & Delivery Status -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark overflow-hidden">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800 gap-2">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>Daftar Kehadiran & Status Pengiriman WhatsApp</span>
                        @if($filterStatus === 'not_queued')
                            <span class="inline-flex items-center gap-1 rounded-md bg-error-50 px-2 py-0.5 text-xs font-bold text-error-700 dark:bg-error-500/20 dark:text-error-400">
                                Filter: Khusus Yang TIDAK Masuk Antrean WA
                            </span>
                        @endif
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Menampilkan hasil pengecekan absensi tanggal <strong class="text-gray-700 dark:text-gray-300">{{ \Carbon\Carbon::parse($date)->translatedFormat('d F Y') }}</strong>
                    </p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    Total: {{ count($deliveryRows) }} data
                </span>
            </div>

            <div class="max-w-full overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50/75 text-left text-xs uppercase tracking-wider text-gray-500 dark:bg-gray-800/40 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                            <th class="px-4 py-3.5 text-center w-12">No</th>
                            <th class="px-4 py-3.5">Warga Sekolah</th>
                            <th class="px-4 py-3.5">Presensi</th>
                            <th class="px-4 py-3.5">Penerima 1 (Siswa / Guru)</th>
                            <th class="px-4 py-3.5">Penerima 2 (Orang Tua)</th>
                            <th class="px-4 py-3.5 text-center w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
                        @forelse($deliveryRows as $index => $row)
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition {{ !$row['is_any_queued'] ? 'border-l-4 border-l-error-500 bg-error-25/50 dark:bg-error-950/10' : '' }}">
                                <!-- No -->
                                <td class="px-4 py-3.5 text-center text-xs text-gray-400 align-top">
                                    {{ $index + 1 }}
                                </td>

                                <!-- Warga Sekolah -->
                                <td class="px-4 py-3.5 align-top">
                                    <div class="flex items-start gap-2.5">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $row['type'] === 'Guru' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 'bg-brand-100 text-brand-700 dark:bg-brand-900/40 dark:text-brand-300' }} font-bold text-xs mt-0.5">
                                            {{ substr($row['nama'], 0, 2) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900 dark:text-white flex items-center gap-1.5 flex-wrap">
                                                <span>{{ $row['nama'] }}</span>
                                                @if(!$row['is_any_queued'])
                                                    <span class="inline-flex items-center gap-1 rounded bg-error-50 px-1.5 py-0.2 text-[10px] font-bold text-error-700 dark:bg-error-500/20 dark:text-error-400">
                                                        <i class="fas fa-times-circle text-[9px]"></i> Tidak Masuk Antrean
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-1.5 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                <span class="inline-flex items-center rounded px-1.5 py-0.2 text-[10px] font-semibold {{ $row['type'] === 'Guru' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' }}">
                                                    {{ $row['type'] }}
                                                </span>
                                                <span>&bull;</span>
                                                <span>{{ $row['sub_info'] }}</span>
                                            </div>

                                            @if(!$row['is_any_queued'])
                                                <div class="mt-1 text-[11px] text-error-600 dark:text-error-400 font-medium flex items-center gap-1">
                                                    <i class="fas fa-info-circle text-[10px]"></i>
                                                    <span>{{ $row['not_queued_summary'] }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Presensi -->
                                <td class="px-4 py-3.5 align-top">
                                    <div class="flex flex-col gap-1 text-xs">
                                        <div class="flex items-center gap-1 font-medium text-gray-700 dark:text-gray-200">
                                            <i class="fas fa-sign-in-alt text-emerald-500"></i>
                                            <span>Masuk: {{ $row['jam_masuk'] ? substr($row['jam_masuk'], 0, 5) : '-' }}</span>
                                        </div>
                                        @if($row['jam_pulang'])
                                            <div class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                                                <i class="fas fa-sign-out-alt text-blue-500"></i>
                                                <span>Pulang: {{ substr($row['jam_pulang'], 0, 5) }}</span>
                                            </div>
                                        @endif
                                        <div class="mt-0.5">
                                            @php
                                                $st = strtoupper($row['status_absen'] ?? 'H');
                                            @endphp
                                            @if($st === 'H' || $st === 'HADIR')
                                                <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">Hadir</span>
                                            @elseif($st === 'T' || $st === 'TERLAMBAT')
                                                <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">Terlambat</span>
                                            @elseif($st === 'I' || $st === 'IZIN')
                                                <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 dark:bg-blue-500/15 dark:text-blue-400">Izin</span>
                                            @elseif($st === 'S' || $st === 'SAKIT')
                                                <span class="inline-flex rounded-full bg-purple-50 px-2 py-0.5 text-[10px] font-semibold text-purple-700 dark:bg-purple-500/15 dark:text-purple-400">Sakit</span>
                                            @elseif($st === 'A' || $st === 'ALPHA')
                                                <span class="inline-flex rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-semibold text-rose-700 dark:bg-rose-500/15 dark:text-rose-400">Alpha</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-400">{{ $st }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Penerima 1 (Siswa/Guru) -->
                                <td class="px-4 py-3.5 align-top">
                                    <div class="text-xs">
                                        @if($row['siswa_phone'])
                                            <div class="font-mono font-medium text-gray-800 dark:text-white flex items-center gap-1.5">
                                                <i class="fab fa-whatsapp text-emerald-600"></i>
                                                <span>{{ $row['siswa_phone'] }}</span>
                                            </div>

                                            <div class="mt-1 flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                <span>Last Seen:</span>
                                                @if($row['siswa_last_seen'])
                                                    <span class="{{ $row['siswa_is_active'] ? 'text-emerald-600 dark:text-emerald-400 font-medium' : 'text-amber-600 dark:text-amber-400 font-medium' }}">
                                                        {{ \Carbon\Carbon::parse($row['siswa_last_seen'])->diffForHumans() }}
                                                    </span>
                                                @else
                                                    <span class="text-rose-600 dark:text-rose-400 italic">Belum pernah chat</span>
                                                @endif
                                            </div>

                                            <div class="mt-1.5">
                                                @if($row['siswa_status']['is_queued'])
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                                                        <i class="fas fa-check-circle text-[10px]"></i>
                                                        {{ $row['siswa_status']['label'] }}
                                                    </span>
                                                @elseif($row['siswa_status']['code'] === 'skipped_expired')
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-500/15 dark:text-amber-400" title="{{ $row['siswa_status']['detail'] }}">
                                                        <i class="fas fa-exclamation-circle text-[10px]"></i>
                                                        {{ $row['siswa_status']['label'] }}
                                                    </span>
                                                @elseif($row['siswa_status']['code'] === 'skipped_never')
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700 dark:bg-rose-500/15 dark:text-rose-400" title="{{ $row['siswa_status']['detail'] }}">
                                                        <i class="fas fa-comment-slash text-[10px]"></i>
                                                        {{ $row['siswa_status']['label'] }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                        {{ $row['siswa_status']['label'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                                <i class="fas fa-phone-slash text-[10px]"></i> Nomor Kosong
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Penerima 2 (Orang Tua) -->
                                <td class="px-4 py-3.5 align-top">
                                    <div class="text-xs">
                                        @if($row['type'] === 'Guru')
                                            <span class="text-gray-400 dark:text-gray-500 italic text-[11px]">- Bukan Siswa -</span>
                                        @elseif($row['ortu_phone'])
                                            <div class="font-mono font-medium text-gray-800 dark:text-white flex items-center gap-1.5">
                                                <i class="fab fa-whatsapp text-emerald-600"></i>
                                                <span>{{ $row['ortu_phone'] }}</span>
                                            </div>

                                            <div class="mt-1 flex items-center gap-1 text-[11px] text-gray-500 dark:text-gray-400">
                                                <span>Last Seen:</span>
                                                @if($row['ortu_last_seen'])
                                                    <span class="{{ $row['ortu_is_active'] ? 'text-emerald-600 dark:text-emerald-400 font-medium' : 'text-amber-600 dark:text-amber-400 font-medium' }}">
                                                        {{ \Carbon\Carbon::parse($row['ortu_last_seen'])->diffForHumans() }}
                                                    </span>
                                                @else
                                                    <span class="text-rose-600 dark:text-rose-400 italic">Belum pernah chat</span>
                                                @endif
                                            </div>

                                            <div class="mt-1.5">
                                                @if($row['ortu_status']['is_queued'])
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                                                        <i class="fas fa-check-circle text-[10px]"></i>
                                                        {{ $row['ortu_status']['label'] }}
                                                    </span>
                                                @elseif($row['ortu_status']['code'] === 'skipped_expired')
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:bg-amber-500/15 dark:text-amber-400" title="{{ $row['ortu_status']['detail'] }}">
                                                        <i class="fas fa-exclamation-circle text-[10px]"></i>
                                                        {{ $row['ortu_status']['label'] }}
                                                    </span>
                                                @elseif($row['ortu_status']['code'] === 'skipped_never')
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700 dark:bg-rose-500/15 dark:text-rose-400" title="{{ $row['ortu_status']['detail'] }}">
                                                        <i class="fas fa-comment-slash text-[10px]"></i>
                                                        {{ $row['ortu_status']['label'] }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                                        {{ $row['ortu_status']['label'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                                <i class="fas fa-phone-slash text-[10px]"></i> Nomor Ortu Kosong
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td class="px-4 py-3.5 text-center align-top">
                                    @php
                                        // Pancing aktivasi HANYA boleh jika nomor pernah berinteraksi (last_seen ada) dan saat ini sudah kadaluarsa (tidak aktif)
                                        $canPingSiswa = $row['siswa_phone'] && !$row['siswa_is_active'] && !empty($row['siswa_last_seen']);
                                        $isNeverSiswa = $row['siswa_phone'] && empty($row['siswa_last_seen']);

                                        $canPingOrtu  = ($row['type'] !== 'Guru') && $row['ortu_phone'] && !$row['ortu_is_active'] && !empty($row['ortu_last_seen']);
                                        $isNeverOrtu  = ($row['type'] !== 'Guru') && $row['ortu_phone'] && empty($row['ortu_last_seen']);
                                    @endphp

                                    @if($canPingSiswa || $isNeverSiswa || $canPingOrtu || $isNeverOrtu)
                                        <div class="flex flex-col gap-1.5 items-center">
                                            {{-- Tombol Pancing Siswa / Guru --}}
                                            @if($canPingSiswa)
                                                <button type="button"
                                                        @click="openModal('{{ $row['siswa_phone'] }}', '{{ addslashes($row['nama']) }}', '{{ $row['type'] }}')"
                                                        class="w-full inline-flex items-center justify-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-emerald-700 transition shadow-2xs" style="background-color: #059669; color: #ffffff;"
                                                        title="Kirim pancingan aktivasi ke nomor {{ $row['siswa_phone'] }}">
                                                    <i class="fab fa-whatsapp text-xs"></i>
                                                    <span>Pancing {{ $row['type'] }}</span>
                                                </button>
                                            @elseif($isNeverSiswa)
                                                <button type="button"
                                                        disabled
                                                        class="w-full inline-flex items-center justify-center gap-1 rounded-lg border border-gray-200 bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-400 cursor-not-allowed opacity-60 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-500"
                                                        title="Pancing aktivasi dinonaktifkan: {{ $row['type'] }} belum pernah aktivasi (harus kirim pesan ke bot terlebih dahulu)">
                                                    <i class="fab fa-whatsapp text-xs"></i>
                                                    <span>Pancing {{ $row['type'] }}</span>
                                                </button>
                                            @endif

                                            {{-- Tombol Pancing Ortu --}}
                                            @if($canPingOrtu)
                                                <button type="button"
                                                        @click="openModal('{{ $row['ortu_phone'] }}', 'Ortu {{ addslashes($row['nama']) }}', 'Orang Tua')"
                                                        class="w-full inline-flex items-center justify-center gap-1 rounded-lg bg-brand-500 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-brand-600 transition shadow-2xs" style="background-color: #4f6bed; color: #ffffff;"
                                                        title="Kirim pancingan aktivasi ke nomor Ortu {{ $row['ortu_phone'] }}">
                                                    <i class="fab fa-whatsapp text-xs"></i>
                                                    <span>Pancing Ortu</span>
                                                </button>
                                            @elseif($isNeverOrtu)
                                                <button type="button"
                                                        disabled
                                                        class="w-full inline-flex items-center justify-center gap-1 rounded-lg border border-gray-200 bg-gray-100 px-2.5 py-1 text-[11px] font-semibold text-gray-400 cursor-not-allowed opacity-60 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-500"
                                                        title="Pancing aktivasi dinonaktifkan: Ortu belum pernah aktivasi (harus kirim pesan ke bot terlebih dahulu)">
                                                    <i class="fab fa-whatsapp text-xs"></i>
                                                    <span>Pancing Ortu</span>
                                                </button>
                                            @endif
                                        </div>
                                    @elseif($row['siswa_phone'] || $row['ortu_phone'])
                                        <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 dark:text-emerald-400 font-medium">
                                            <i class="fas fa-check-circle"></i> Kontak Aktif
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                                            <i class="fas fa-calendar-times text-xl"></i>
                                        </div>
                                        <h4 class="mt-3 font-semibold text-gray-800 dark:text-gray-200">
                                            {{ $filterStatus === 'not_queued' ? 'Hebat! Semua absensi berhasil masuk antrean WA' : 'Tidak ada data presensi' }}
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ $filterStatus === 'not_queued' ? 'Tidak ada siswa atau guru yang terlewat / diskip pada tanggal ini.' : 'Tidak ditemukan data presensi untuk filter atau tanggal yang dipilih.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @else
        <!-- ============================================================== -->
        <!-- TAB 2: DATABASE STATUS LAST SEEN SELURUH KONTAK                -->
        <!-- ============================================================== -->

        <!-- 4 Balanced Metric Cards in Tab 2 -->
        <div class="mb-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <!-- 1. Total Kontak -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Total Kontak WA
                        </span>
                        <div class="mt-2 flex items-baseline gap-2">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $summaryStats['total'] }}
                            </h3>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">kontak</span>
                        </div>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-500/15 dark:text-purple-400">
                        <i class="fas fa-address-book text-xl"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
                    <span>Guru, Siswa & Ortu</span>
                    <span class="text-brand-600 dark:text-brand-400 font-semibold">Database Kontak</span>
                </div>
            </div>

            <!-- 2. Kontak Aktif -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Kontak Aktif
                        </span>
                        <div class="mt-2 flex items-baseline gap-2">
                            <h3 class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {{ $summaryStats['active'] }}
                            </h3>
                            <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                ({{ $summaryStats['active_rate'] }}%)
                            </span>
                        </div>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                        <i class="fas fa-user-check text-xl"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
                    <span>Last Seen &lt; {{ $expiryDays }} hari</span>
                    <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Siap Terima WA</span>
                </div>
            </div>

            <!-- 3. Kontak Kadaluarsa -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Kontak Kadaluarsa
                        </span>
                        <div class="mt-2 flex items-baseline gap-2">
                            <h3 class="text-2xl font-bold text-warning-600 dark:text-warning-400">
                                {{ $summaryStats['expired'] }}
                            </h3>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">kontak</span>
                        </div>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400">
                        <i class="fas fa-user-clock text-xl"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
                    <span>Pernah chat, kini &ge; {{ $expiryDays }} hari</span>
                    <span class="text-amber-600 dark:text-amber-400 font-semibold">Perlu Dipancing</span>
                </div>
            </div>

            <!-- 4. Belum Pernah Chat -->
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Belum Pernah Chat
                        </span>
                        <div class="mt-2 flex items-baseline gap-2">
                            <h3 class="text-2xl font-bold text-error-600 dark:text-error-400">
                                {{ $summaryStats['never'] }}
                            </h3>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">kontak</span>
                        </div>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400">
                        <i class="fas fa-comment-slash text-xl"></i>
                    </div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-800">
                    <span>Belum ada riwayat interaksi</span>
                    <span class="text-rose-600 dark:text-rose-400 font-semibold">Perlu Diaktifkan</span>
                </div>
            </div>
        </div>

        <!-- Filter Toolbar Tab 2 (Clean Inline Row) -->
        <div class="mb-4 rounded-2xl border border-gray-200 bg-white p-3.5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
            <form method="GET" action="{{ route('whatsapp-logs.delivery-detail') }}" class="flex flex-wrap items-center gap-2.5">
                <input type="hidden" name="tab" value="contacts">

                <!-- Filter Peran -->
                <select name="role" class="rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs text-gray-800 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="all" {{ $filterRole === 'all' ? 'selected' : '' }}>Semua Kontak</option>
                    <option value="siswa" {{ $filterRole === 'siswa' ? 'selected' : '' }}>Siswa Saja</option>
                    <option value="ortu" {{ $filterRole === 'ortu' ? 'selected' : '' }}>Orang Tua</option>
                    <option value="guru" {{ $filterRole === 'guru' ? 'selected' : '' }}>Guru / Staff</option>
                </select>

                <!-- Filter Status Keaktifan -->
                <select name="status" class="rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs text-gray-800 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="all" {{ $filterStatus === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="active" {{ $filterStatus === 'active' ? 'selected' : '' }}>Aktif (&lt; {{ $expiryDays }} Hari)</option>
                    <option value="expired" {{ $filterStatus === 'expired' ? 'selected' : '' }}>Kadaluarsa (&ge; {{ $expiryDays }} Hari)</option>
                    <option value="never" {{ $filterStatus === 'never' ? 'selected' : '' }}>Belum Pernah Chat</option>
                    <option value="no_phone" {{ $filterStatus === 'no_phone' ? 'selected' : '' }}>Nomor Kosong</option>
                </select>

                <!-- Filter Kelas -->
                <select name="kelas_id" class="rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs text-gray-800 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    <option value="all" {{ $filterKelas === 'all' ? 'selected' : '' }}>Semua Kelas</option>
                    @foreach($kelases as $k)
                        <option value="{{ $k->id }}" {{ $filterKelas == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>

                <!-- Search Input (Bersih tanpa icon) -->
                <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, nomor WA..."
                       class="w-48 sm:w-60 rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs text-gray-800 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">

                <!-- Buttons -->
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-brand-500 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-600 transition shadow-theme-xs" style="background-color: #4f6bed; color: #ffffff;">
                    Cari
                </button>

                <a href="{{ route('whatsapp-logs.delivery-detail', ['tab' => 'contacts']) }}" class="inline-flex items-center justify-center rounded-xl border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50 transition dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800" title="Reset Filter">
                    <i class="fas fa-undo"></i>
                </a>
            </form>
        </div>

        <!-- Table Database Status Last Seen Kontak -->
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark overflow-hidden">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800 gap-2">
                <div>
                    <h3 class="font-bold text-gray-900 dark:text-white">Daftar Status Kontak & Sisa Waktu Last Seen</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Urutan teratas: Kontak yang baru saja aktif berinteraksi dengan nomor bot WhatsApp sekolah.
                    </p>
                </div>
                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    Menampilkan: {{ $contactsList->total() }} kontak
                </span>
            </div>

            <div class="max-w-full overflow-x-auto">
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-gray-50/75 text-left text-xs uppercase tracking-wider text-gray-500 dark:bg-gray-800/40 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                            <th class="px-4 py-3.5 text-center w-12">No</th>
                            <th class="px-4 py-3.5">Nama & Identitas</th>
                            <th class="px-4 py-3.5">Peran</th>
                            <th class="px-4 py-3.5">Nomor WhatsApp</th>
                            <th class="px-4 py-3.5">Terakhir Aktif (Last Seen)</th>
                            <th class="px-4 py-3.5">Status Masa Berlaku</th>
                            <th class="px-4 py-3.5 text-center w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm dark:divide-gray-800">
                        @forelse($contactsList as $index => $contact)
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition">
                                <!-- No -->
                                <td class="px-4 py-3.5 text-center text-xs text-gray-400 align-middle">
                                    {{ $contactsList->firstItem() + $index }}
                                </td>

                                <!-- Nama & Identitas -->
                                <td class="px-4 py-3.5 align-middle">
                                    <div class="font-semibold text-gray-900 dark:text-white">{{ $contact['nama'] }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $contact['sub_info'] }}</div>
                                </td>

                                <!-- Peran -->
                                <td class="px-4 py-3.5 align-middle">
                                    @if($contact['role'] === 'Siswa')
                                        <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-500/15 dark:text-blue-400">Siswa</span>
                                    @elseif($contact['role'] === 'Orang Tua')
                                        <span class="inline-flex rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-500/15 dark:text-purple-400">Orang Tua</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">Guru / Staff</span>
                                    @endif
                                </td>

                                <!-- Nomor WhatsApp -->
                                <td class="px-4 py-3.5 align-middle font-mono text-xs">
                                    @if($contact['has_phone'])
                                        <div class="flex items-center gap-1.5 text-gray-800 dark:text-white">
                                            <i class="fab fa-whatsapp text-emerald-600"></i>
                                            <span>{{ $contact['phone'] }}</span>
                                            <a href="https://wa.me/{{ $contact['phone'] }}" target="_blank" rel="noopener noreferrer" class="text-gray-400 hover:text-emerald-600 transition" title="Buka WhatsApp Langsung">
                                                <i class="fas fa-external-link-alt text-[10px]"></i>
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-gray-400 italic">Nomor kosong</span>
                                    @endif
                                </td>

                                <!-- Terakhir Aktif -->
                                <td class="px-4 py-3.5 align-middle text-xs">
                                    @if($contact['last_seen'])
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $contact['last_seen']->format('d/m/Y H:i') }}
                                        </div>
                                        <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                            ({{ $contact['last_seen']->diffForHumans() }})
                                        </div>
                                    @else
                                        <span class="text-rose-600 dark:text-rose-400 italic font-medium">Belum pernah interaksi</span>
                                    @endif
                                </td>

                                <!-- Status Masa Berlaku -->
                                <td class="px-4 py-3.5 align-middle text-xs">
                                    @if($contact['status'] === 'active')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            {{ $contact['remaining_text'] }}
                                        </span>
                                    @elseif($contact['status'] === 'expired')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            {{ $contact['remaining_text'] }}
                                        </span>
                                    @elseif($contact['status'] === 'never')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 dark:bg-rose-500/15 dark:text-rose-400">
                                            <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                            Belum Pernah Chat
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                            Tanpa Nomor
                                        </span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="px-4 py-3.5 text-center align-middle">
                                    @if($contact['has_phone'])
                                        @if($contact['status'] === 'expired' && !empty($contact['last_seen']))
                                            <button type="button"
                                                    @click="openModal('{{ $contact['phone'] }}', '{{ addslashes($contact['nama']) }}', '{{ $contact['role'] }}')"
                                                    class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-600 hover:text-white transition shadow-theme-xs dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-400 dark:hover:bg-emerald-600 dark:hover:text-white"
                                                    title="Kirim pesan pancingan aktivasi ke nomor {{ $contact['phone'] }}">
                                                <i class="fab fa-whatsapp text-xs"></i>
                                                <span>Pancing Aktivasi</span>
                                            </button>
                                        @elseif($contact['status'] === 'never')
                                            <button type="button"
                                                    disabled
                                                    class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-400 cursor-not-allowed opacity-60 dark:border-gray-800 dark:bg-gray-800 dark:text-gray-500"
                                                    title="Pancing aktivasi dinonaktifkan: kontak belum pernah aktivasi (harus mengirim pesan ke bot terlebih dahulu)">
                                                <i class="fab fa-whatsapp text-xs"></i>
                                                <span>Pancing Aktivasi</span>
                                            </button>
                                        @elseif($contact['status'] === 'active')
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                                <i class="fas fa-check-circle"></i> Kontak Aktif
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400">-</span>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                                            <i class="fas fa-search text-xl"></i>
                                        </div>
                                        <h4 class="mt-3 font-semibold text-gray-800 dark:text-gray-200">Tidak ada kontak ditemukan</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Coba sesuaikan kata kunci atau filter pencarian Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginasi Kontak -->
            <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800">
                {{ $contactsList->links('vendor.pagination.tailwind') }}
            </div>
        </div>
    @endif

    <!-- ============================================================== -->
    <!-- MODAL POPUP: KONFIRMASI KIRIM PANCINGAN AKTIVASI               -->
    <!-- ============================================================== -->
    <div x-show="showReminderModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-99999 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-xs"
         style="display: none;">

        <div @click.away="showReminderModal = false"
             class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900">

            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400">
                        <i class="fab fa-whatsapp text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Kirim Pesan Pancingan Aktivasi</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pesan ini akan mem-bypass aturan last seen</p>
                    </div>
                </div>
                <button type="button" @click="showReminderModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form method="POST" action="{{ route('whatsapp-logs.send-reminder-single') }}" class="mt-4">
                @csrf
                <input type="hidden" name="phone" :value="targetPhone">
                <input type="hidden" name="name" :value="targetName">
                <input type="hidden" name="school_id" value="{{ $schoolId }}">

                <div class="rounded-xl bg-gray-50 p-4 text-xs dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                    <div class="flex justify-between py-1">
                        <span class="text-gray-500 dark:text-gray-400">Penerima:</span>
                        <span class="font-semibold text-gray-900 dark:text-white" x-text="targetName"></span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-gray-500 dark:text-gray-400">Peran:</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300" x-text="targetRole"></span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-gray-500 dark:text-gray-400">Nomor WhatsApp:</span>
                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="targetPhone"></span>
                    </div>
                </div>

                <!-- Preview Pesan -->
                <div class="mt-3">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Preview Format Pesan Reminder:
                    </label>
                    <div class="rounded-xl border border-gray-200 bg-gray-50/70 p-3 text-xs text-gray-700 font-mono whitespace-pre-wrap dark:border-gray-800 dark:bg-gray-800/40 dark:text-gray-300 max-h-40 overflow-y-auto leading-relaxed">
📌 *PEMBERITAHUAN AKTIVASI NOTIFIKASI ABSENSI* 📌

Halo Bapak/Ibu/Saudara *<span x-text="targetName"></span>* 👋,

Masa aktif interaksi WhatsApp Anda pada sistem absensi sekolah perlu diaktifkan agar notifikasi kehadiran dapat masuk dengan lancar.

⚠️ *PENTING:* Silakan *BALAS pesan ini* (contoh ketik: *OK* atau *Siap*) agar status nomor Anda terverifikasi aktif oleh server.

_Terima kasih atas kerja samanya._ 🙏
                    </div>
                </div>

                <div class="mt-4 rounded-xl bg-brand-50 p-3 text-[11px] text-brand-700 dark:bg-brand-500/10 dark:text-brand-300 border border-brand-100 dark:border-brand-500/20">
                    <i class="fas fa-info-circle mr-1"></i> Setelah penerima membalas pesan ini (misal mengetik "OK" atau pesan apapun), server akan otomatis memperbarui timestamp <strong>last_seen</strong> kontak tersebut dan notifikasi kehadiran harian akan otomatis aktif kembali.
                </div>

                <div class="mt-5 flex items-center justify-end gap-2.5">
                    <button type="button" @click="showReminderModal = false"
                            class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:text-gray-300 dark:hover:bg-gray-800">
                        Batal
                    </button>
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition shadow-theme-xs" style="background-color: #059669; color: #ffffff;">
                        <i class="fab fa-whatsapp"></i> Kirim Pesan Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
