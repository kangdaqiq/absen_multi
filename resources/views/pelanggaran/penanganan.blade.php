@extends('layouts.app')

@section('title', 'Riwayat Penanganan & Konseling BK')

@section('content')
<div class="space-y-6" x-data="penangananBk()">
    <!-- Header -->
    <x-page-header
        title="Riwayat Penanganan & Konseling BK"
        subtitle="Rekam jejak tindakan pembinaan, sanksi SP 1 - SP 4, kesepakatan siswa, dan arsip berkas digital Google Drive"
        icon="fas fa-user-shield">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('pelanggaran.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition">
                <i class="fas fa-exclamation-triangle text-amber-500"></i> Catatan Kasus
            </a>
            <a href="{{ route('pelanggaran.rekap') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition">
                <i class="fas fa-file-invoice text-emerald-500"></i> Rekap Pelanggaran
            </a>
            <a href="{{ route('pelanggaran.penanganan.export-excel', request()->all()) }}"
               class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white shadow-theme-xs hover:bg-emerald-700 transition">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            <button type="button" @click="openCreateModal = true"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-theme-xs hover:bg-indigo-700 transition">
                <i class="fas fa-plus"></i> Catat Penanganan BK
            </button>
        </div>
    </x-page-header>

    @if(session('success'))
        <div class="rounded-xl border border-success-200 bg-success-50 px-4 py-3 text-sm text-success-700 dark:border-success-500/30 dark:bg-success-500/10 dark:text-success-400 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
            <div class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                <i class="fas fa-user-shield text-indigo-500"></i> Total Tindakan
            </div>
            <div class="mt-2 text-2xl font-bold text-gray-800 dark:text-white/90">{{ number_format($totalPenanganan) }}</div>
            <span class="text-[11px] text-gray-400">Seluruh riwayat BK</span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
            <div class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                <i class="fas fa-certificate text-rose-500"></i> SP 1 & SP 2
            </div>
            <div class="mt-2 text-2xl font-bold text-rose-600 dark:text-rose-400">{{ number_format($totalSp1 + $totalSp2) }}</div>
            <span class="text-[11px] text-gray-400">SP 1: {{ $totalSp1 }} | SP 2: {{ $totalSp2 }}</span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
            <div class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                <i class="fas fa-exclamation-circle text-red-600"></i> SP 3 & SP 4
            </div>
            <div class="mt-2 text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($totalSp3 + $totalSp4) }}</div>
            <span class="text-[11px] text-gray-400">SP 3: {{ $totalSp3 }} | SP 4: {{ $totalSp4 }}</span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
            <div class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                <i class="fas fa-hands-helping text-blue-500"></i> Pembinaan / Teguran
            </div>
            <div class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($totalPembinaan) }}</div>
            <span class="text-[11px] text-gray-400">Bimbingan konseling</span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
            <div class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                <i class="fas fa-check-double text-emerald-500"></i> Selesai
            </div>
            <div class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ number_format($totalSelesai) }}</div>
            <span class="text-[11px] text-gray-400">Kasus tuntas dibina</span>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
            <div class="flex items-center gap-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                <i class="fas fa-spinner text-amber-500"></i> Dalam Proses
            </div>
            <div class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">{{ number_format($totalDalamProses) }}</div>
            <span class="text-[11px] text-gray-400">Sedang dipantau BK</span>
        </div>
    </div>

    @if($selectedStudent)
    <!-- Banner Filter Siswa Aktif -->
    <div class="rounded-2xl border border-indigo-200 bg-indigo-50/70 p-4 dark:border-indigo-900/40 dark:bg-indigo-950/20 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-white font-bold text-base shadow-sm">
                {{ strtoupper(substr($selectedStudent->nama, 0, 2)) }}
            </div>
            <div>
                <div class="text-xs font-semibold text-indigo-700 dark:text-indigo-400 uppercase tracking-wide">Fokus Riwayat Siswa</div>
                <div class="text-base font-bold text-gray-900 dark:text-white">{{ $selectedStudent->nama }} <span class="text-sm font-normal text-gray-500">(NIS: {{ $selectedStudent->nis ?? '-' }} • Kelas: {{ $selectedStudent->kelas?->nama_kelas ?? '-' }})</span></div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="showStudentDossier({{ $selectedStudent->id }})" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 transition shadow-xs">
                <i class="fas fa-id-card mr-1"></i> Buka Rekam Jejak Utuh
            </button>
            <a href="{{ route('pelanggaran.penanganan.index') }}" class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 transition">
                <i class="fas fa-times mr-1"></i> Tampilkan Semua Siswa
            </a>
        </div>
    </div>
    @endif

    <!-- Filter Bar -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
        <form method="GET" action="{{ route('pelanggaran.penanganan.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6 items-end">
            @if(request('student_id'))
                <input type="hidden" name="student_id" value="{{ request('student_id') }}">
            @endif

            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Cari Siswa / Kasus / GDrive</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Nama siswa, NIS, kode kasus, catatan..."
                           class="w-full rounded-xl border border-gray-200 bg-transparent py-2 pl-9 pr-3 text-xs outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>
            </div>

            <!-- Kelas -->
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Kelas</label>
                <select name="kelas_id" class="w-full rounded-xl border border-gray-200 bg-transparent py-2 px-3 text-xs outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="all">Semua Kelas</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" {{ request('kelas_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->nama_kelas }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Tindakan / Tingkat SP -->
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Tindakan / Sanksi SP</label>
                <select name="tindakan_sp" class="w-full rounded-xl border border-gray-200 bg-transparent py-2 px-3 text-xs outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="all">Semua Tindakan</option>
                    <option value="pembinaan" {{ request('tindakan_sp') === 'pembinaan' ? 'selected' : '' }}>Pembinaan BK</option>
                    <option value="teguran_lisan" {{ request('tindakan_sp') === 'teguran_lisan' ? 'selected' : '' }}>Teguran Lisan</option>
                    <option value="teguran_tertulis" {{ request('tindakan_sp') === 'teguran_tertulis' ? 'selected' : '' }}>Teguran Tertulis</option>
                    <option value="sp_1" {{ request('tindakan_sp') === 'sp_1' ? 'selected' : '' }}>Surat Peringatan 1 (SP 1)</option>
                    <option value="sp_2" {{ request('tindakan_sp') === 'sp_2' ? 'selected' : '' }}>Surat Peringatan 2 (SP 2)</option>
                    <option value="sp_3" {{ request('tindakan_sp') === 'sp_3' ? 'selected' : '' }}>Surat Peringatan 3 (SP 3)</option>
                    <option value="sp_4" {{ request('tindakan_sp') === 'sp_4' ? 'selected' : '' }}>Surat Peringatan 4 (SP 4)</option>
                    <option value="skorsing" {{ request('tindakan_sp') === 'skorsing' ? 'selected' : '' }}>Skorsing</option>
                    <option value="dikembalikan_ortu" {{ request('tindakan_sp') === 'dikembalikan_ortu' ? 'selected' : '' }}>Dikembalikan ke Ortu</option>
                </select>
            </div>

            <!-- Status Penanganan -->
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">Status Penanganan</label>
                <select name="status_penanganan" class="w-full rounded-xl border border-gray-200 bg-transparent py-2 px-3 text-xs outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="all">Semua Status</option>
                    <option value="dalam_proses" {{ request('status_penanganan') === 'dalam_proses' ? 'selected' : '' }}>Dalam Proses</option>
                    <option value="selesai" {{ request('status_penanganan') === 'selesai' ? 'selected' : '' }}>Selesai</option>
                    <option value="evaluasi_lanjutan" {{ request('status_penanganan') === 'evaluasi_lanjutan' ? 'selected' : '' }}>Evaluasi Lanjutan</option>
                </select>
            </div>

            <!-- Tombol Filter -->
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('pelanggaran.penanganan.index') }}" class="rounded-xl border border-gray-200 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition" title="Reset filter">
                    <i class="fas fa-undo"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Data Table -->
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03] shadow-theme-xs">
        <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-800 dark:text-white/90 flex items-center gap-2">
                <i class="fas fa-list-check text-indigo-500"></i> Riwayat Tindakan Konseling & Sanksi SP
                <span class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400">
                    {{ $items->total() }} catatan
                </span>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800 text-left text-xs">
                <thead class="bg-gray-50/75 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 w-12 text-center">No</th>
                        <th class="py-3.5 px-4">Siswa & Kelas</th>
                        <th class="py-3.5 px-4">Kasus Pelanggaran</th>
                        <th class="py-3.5 px-4 text-center">Tindakan / Sanksi</th>
                        <th class="py-3.5 px-4">Catatan & Hasil Kesepakatan</th>
                        <th class="py-3.5 px-4 text-center">Berkas & GDrive</th>
                        <th class="py-3.5 px-4">Petugas BK</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-center w-24">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                    @forelse($items as $idx => $p)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-white/[0.02] transition">
                            <td class="py-3.5 px-4 text-center font-medium text-gray-400">
                                {{ $items->firstItem() + $idx }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-xs font-bold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">
                                        {{ strtoupper(substr($p->student?->nama ?? 'S', 0, 2)) }}
                                    </div>
                                    <div>
                                        <button type="button" @click="showStudentDossier({{ $p->student_id }})" class="font-bold text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 text-left transition flex items-center gap-1 group">
                                            <span>{{ $p->student?->nama ?? '-' }}</span>
                                            <i class="fas fa-external-link-alt text-[10px] opacity-0 group-hover:opacity-100 text-indigo-500 transition"></i>
                                        </button>
                                        <div class="text-[11px] text-gray-500">
                                            NIS: {{ $p->student?->nis ?? '-' }} • <span class="font-medium text-gray-600 dark:text-gray-300">{{ $p->student?->kelas?->nama_kelas ?? '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($p->pelanggaran)
                                    <div>
                                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-mono font-bold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                            {{ $p->pelanggaran->code }}
                                        </span>
                                        <div class="font-semibold text-gray-800 dark:text-gray-200 mt-0.5">
                                            {{ $p->pelanggaran->judul_pelanggaran }}
                                        </div>
                                        <div class="text-[10px] text-gray-500 flex items-center gap-2 mt-0.5">
                                            <span><i class="fas fa-calendar-alt mr-1"></i>{{ $p->pelanggaran->tanggal?->format('d/m/Y') }}</span>
                                            <span>•</span>
                                            <span class="font-bold text-rose-600 dark:text-rose-400">{{ $p->pelanggaran->poin }} Poin</span>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400 italic">Kasus umum / terpisah</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @php
                                    $spBadge = match($p->tindakan_sp) {
                                        'sp_1' => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/30',
                                        'sp_2' => 'bg-orange-100 text-orange-800 border-orange-300 dark:bg-orange-500/10 dark:text-orange-400 dark:border-orange-500/30',
                                        'sp_3' => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-500/10 dark:text-rose-400 dark:border-rose-500/30',
                                        'sp_4' => 'bg-red-100 text-red-900 border-red-400 dark:bg-red-500/20 dark:text-red-300 dark:border-red-500/40 font-black',
                                        'skorsing', 'dikembalikan_ortu' => 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/30',
                                        default => 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-500/10 dark:text-indigo-300 dark:border-indigo-500/20',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $spBadge }} shadow-2xs">
                                    {{ $p->tindakan_sp_label }}
                                </span>
                                <div class="text-[10px] text-gray-400 mt-1">
                                    <i class="fas fa-clock mr-1"></i>{{ $p->tanggal?->translatedFormat('d M Y') }}
                                </div>
                            </td>
                            <td class="py-3.5 px-4 max-w-xs">
                                <p class="text-gray-800 dark:text-gray-200 line-clamp-2 leading-relaxed">
                                    {{ $p->catatan_penanganan }}
                                </p>
                                @if($p->hasil_kesepakatan)
                                    <div class="mt-1 text-[11px] text-emerald-700 dark:text-emerald-400 flex items-start gap-1">
                                        <i class="fas fa-handshake text-xs mt-0.5 shrink-0"></i>
                                        <span class="line-clamp-1 italic">{{ $p->hasil_kesepakatan }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex flex-col items-center gap-1.5">
                                    @if($p->link_gdrive)
                                        <a href="{{ $p->link_gdrive }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100 dark:border-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 transition shadow-2xs">
                                            <i class="fab fa-google-drive text-emerald-600"></i> Buka GDrive
                                        </a>
                                    @endif
                                    @if($p->lampiran_file)
                                        <a href="{{ $p->lampiran_file_url }}" target="_blank"
                                           class="inline-flex items-center gap-1 text-[11px] text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 hover:underline">
                                            <i class="fas fa-paperclip"></i> File Surat
                                        </a>
                                    @endif
                                    @if(!$p->link_gdrive && !$p->lampiran_file)
                                        <span class="text-gray-400 text-[11px]">-</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="text-xs font-semibold text-gray-800 dark:text-gray-200">
                                    {{ $p->handler?->full_name ?? $p->handler?->username ?? 'Guru BK' }}
                                </div>
                                <div class="text-[10px] text-gray-400">Guru BK / Konseling</div>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($p->status_penanganan === 'selesai')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20">
                                        <i class="fas fa-check-circle text-[9px]"></i> Selesai
                                    </span>
                                @elseif($p->status_penanganan === 'evaluasi_lanjutan')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-500/10 dark:text-purple-400 dark:border-purple-500/20">
                                        <i class="fas fa-sync-alt text-[9px]"></i> Evaluasi
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20">
                                        <i class="fas fa-hourglass-half text-[9px]"></i> Dalam Proses
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Detail Modal Trigger -->
                                    <button type="button" @click="showDetail({{ json_encode($p) }})"
                                            class="rounded-lg border border-gray-200 p-1.5 text-gray-600 hover:bg-gray-100 hover:text-indigo-600 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition"
                                            title="Lihat Detail Penanganan">
                                        <i class="fas fa-eye text-xs"></i>
                                    </button>

                                    <!-- Dossier Siswa -->
                                    <button type="button" @click="showStudentDossier({{ $p->student_id }})"
                                            class="rounded-lg border border-indigo-200 p-1.5 text-indigo-600 hover:bg-indigo-50 dark:border-indigo-800 dark:text-indigo-400 dark:hover:bg-indigo-950/40 transition"
                                            title="Histori Utuh Siswa">
                                        <i class="fas fa-id-badge text-xs"></i>
                                    </button>

                                    <!-- Delete -->
                                    <form method="POST" action="{{ route('pelanggaran.penanganan.destroy', $p->id) }}"
                                          onsubmit="return confirm('Hapus catatan penanganan ini? Tindakan ini tidak dapat dibatalkan.')"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="rounded-lg border border-gray-200 p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:border-gray-700 dark:hover:bg-rose-950/40 transition"
                                                title="Hapus Penanganan">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
                                        <i class="fas fa-user-shield text-xl"></i>
                                    </div>
                                    <p class="mt-3 text-sm font-semibold text-gray-700 dark:text-gray-300">Belum Ada Riwayat Penanganan BK</p>
                                    <p class="mt-1 text-xs text-gray-400 max-w-sm">
                                        Seluruh tindakan bimbingan konseling, surat peringatan (SP 1, 2, 3, 4), dan berkas surat Google Drive siswa akan tercatat dan ditampilkan di sini.
                                    </p>
                                    <button type="button" @click="openCreateModal = true" class="mt-4 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition">
                                        <i class="fas fa-plus mr-1"></i> Catat Penanganan Pertama
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-800">
                {{ $items->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL 1: Rekam Jejak / Dossier Perilaku Siswa (Kronologis) -->
    <div x-show="openDossierModal" style="display: none;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         class="fixed inset-0 z-99999 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
        <div @click.away="openDossierModal = false"
             class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            
            <!-- Dossier Header -->
            <div class="flex items-start justify-between border-b border-gray-100 pb-4 dark:border-gray-800">
                <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-white font-bold text-lg shadow-md">
                        <span x-text="dossierStudent?.nama ? dossierStudent.nama.substring(0,2).toUpperCase() : 'S'"></span>
                    </div>
                    <div>
                        <div class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                            Dossier Kedisiplinan & Penanganan BK
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white" x-text="dossierStudent?.nama"></h4>
                        <p class="text-xs text-gray-500">
                            NIS: <span class="font-medium text-gray-700 dark:text-gray-300" x-text="dossierStudent?.nis"></span> •
                            Kelas: <span class="font-medium text-gray-700 dark:text-gray-300" x-text="dossierStudent?.kelas"></span>
                        </p>
                    </div>
                </div>
                <button type="button" @click="openDossierModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <!-- Dossier Stats -->
            <div class="mt-4 grid grid-cols-3 gap-3">
                <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-3 text-center dark:border-gray-800 dark:bg-gray-800/40">
                    <div class="text-xs font-medium text-gray-500">Total Kasus</div>
                    <div class="mt-1 text-xl font-bold text-gray-800 dark:text-white" x-text="dossierStudent?.total_kasus ?? 0"></div>
                </div>
                <div class="rounded-xl border border-rose-100 bg-rose-50/60 p-3 text-center dark:border-rose-900/30 dark:bg-rose-950/20">
                    <div class="text-xs font-medium text-rose-600 dark:text-rose-400">Akumulasi Poin</div>
                    <div class="mt-1 text-xl font-bold text-rose-700 dark:text-rose-300" x-text="dossierStudent?.total_poin ?? 0"></div>
                </div>
                <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-3 text-center dark:border-indigo-900/30 dark:bg-indigo-950/20">
                    <div class="text-xs font-medium text-indigo-600 dark:text-indigo-400">Tindakan BK</div>
                    <div class="mt-1 text-xl font-bold text-indigo-700 dark:text-indigo-300" x-text="dossierStudent?.total_penanganan ?? 0"></div>
                </div>
            </div>

            <!-- Loading State -->
            <div x-show="loadingDossier" class="py-12 text-center text-gray-400">
                <i class="fas fa-spinner fa-spin text-2xl text-indigo-600"></i>
                <p class="mt-2 text-xs">Memuat rekam jejak siswa...</p>
            </div>

            <!-- Dossier Timeline -->
            <div x-show="!loadingDossier" class="mt-6">
                <h5 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3 flex items-center gap-2">
                    <i class="fas fa-history text-indigo-500"></i> Kronologi Penanganan & Tindakan SP
                </h5>

                <template x-if="dossierPenanganans && dossierPenanganans.length > 0">
                    <div class="relative pl-6 space-y-4 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-indigo-100 dark:before:bg-gray-800">
                        <template x-for="(pen, pIdx) in dossierPenanganans" :key="pen.id">
                            <div class="relative rounded-xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-800/60">
                                <!-- Dot Marker -->
                                <span class="absolute -left-[27px] top-4 flex h-3 w-3 items-center justify-center rounded-full bg-indigo-600 ring-4 ring-white dark:ring-gray-900"></span>

                                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 pb-2.5 dark:border-gray-800">
                                    <div class="flex items-center gap-2">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                                              :class="{
                                                  'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300': pen.tindakan_sp === 'sp_1',
                                                  'bg-orange-100 text-orange-800 dark:bg-orange-500/20 dark:text-orange-300': pen.tindakan_sp === 'sp_2',
                                                  'bg-rose-100 text-rose-800 dark:bg-rose-500/20 dark:text-rose-300': pen.tindakan_sp === 'sp_3',
                                                  'bg-red-100 text-red-900 dark:bg-red-500/30 dark:text-red-200': pen.tindakan_sp === 'sp_4',
                                                  'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/20 dark:text-indigo-300': !pen.tindakan_sp.startsWith('sp_')
                                              }"
                                              x-text="pen.tindakan_sp_label"></span>
                                        <span class="text-xs text-gray-500" x-text="pen.tanggal"></span>
                                    </div>
                                    <span class="text-xs font-medium text-gray-500" x-text="'Oleh: ' + pen.handler_name"></span>
                                </div>

                                <div class="mt-2.5 text-xs">
                                    <div class="text-gray-500 font-medium">Kasus Terkait:</div>
                                    <div class="font-semibold text-gray-800 dark:text-gray-200" x-text="'[' + pen.kasus_kode + '] ' + pen.kasus_judul"></div>
                                </div>

                                <div class="mt-2 text-xs">
                                    <div class="text-gray-500 font-medium">Catatan Pembinaan BK:</div>
                                    <p class="text-gray-800 dark:text-gray-300 leading-relaxed mt-0.5" x-text="pen.catatan_penanganan"></p>
                                </div>

                                <template x-if="pen.hasil_kesepakatan">
                                    <div class="mt-2 rounded-lg bg-emerald-50/70 p-2 text-xs text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300">
                                        <strong>Hasil Kesepakatan:</strong> <span x-text="pen.hasil_kesepakatan"></span>
                                    </div>
                                </template>

                                <!-- GDrive & Attachment Links -->
                                <div class="mt-3 pt-2.5 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <template x-if="pen.link_gdrive">
                                            <a :href="pen.link_gdrive" target="_blank" rel="noopener noreferrer"
                                               class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700 transition">
                                                <i class="fab fa-google-drive"></i> Buka Google Drive
                                            </a>
                                        </template>
                                        <template x-if="pen.lampiran_file_url">
                                            <a :href="pen.lampiran_file_url" target="_blank"
                                               class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                                                <i class="fas fa-paperclip"></i> Unduh Lampiran File
                                            </a>
                                        </template>
                                    </div>
                                    <span class="text-[11px] font-semibold uppercase tracking-wider"
                                          :class="pen.status_penanganan === 'selesai' ? 'text-emerald-600' : 'text-amber-600'"
                                          x-text="pen.status_penanganan_label"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!dossierPenanganans || dossierPenanganans.length === 0">
                    <div class="rounded-xl border border-gray-100 bg-gray-50 p-6 text-center text-xs text-gray-500 dark:border-gray-800 dark:bg-gray-800/40">
                        <i class="fas fa-info-circle text-base text-gray-400 mb-1"></i>
                        <p>Belum ada tindakan/catatan penanganan khusus dari Guru BK untuk siswa ini.</p>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <a :href="'{{ route('pelanggaran.penanganan.index') }}?student_id=' + (dossierStudent?.id ?? '')"
                   class="text-xs font-semibold text-indigo-600 hover:underline dark:text-indigo-400">
                    <i class="fas fa-filter mr-1"></i> Filter tabel dengan siswa ini
                </a>
                <button type="button" @click="openDossierModal = false" class="rounded-xl border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: Detail Satu Penanganan -->
    <div x-show="openDetailModal" style="display: none;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-99999 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
        <div @click.away="openDetailModal = false"
             class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                <h4 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-file-shield text-indigo-600"></i> Detail Tindakan Penanganan BK
                </h4>
                <button type="button" @click="openDetailModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="mt-4 space-y-3.5 text-xs text-gray-700 dark:text-gray-300" x-show="activeDetail">
                <div class="grid grid-cols-2 gap-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/40">
                    <div>
                        <span class="text-gray-400 font-medium">Nama Siswa:</span>
                        <div class="font-bold text-gray-900 dark:text-white text-sm" x-text="activeDetail?.student?.nama"></div>
                        <div class="text-[11px] text-gray-500" x-text="'NIS: ' + (activeDetail?.student?.nis ?? '-') + ' • Kelas: ' + (activeDetail?.student?.kelas?.nama_kelas ?? '-')"></div>
                    </div>
                    <div>
                        <span class="text-gray-400 font-medium">Tindakan / Sanksi:</span>
                        <div class="font-bold text-indigo-600 dark:text-indigo-400 text-sm" x-text="activeDetail?.tindakan_sp_label ?? activeDetail?.tindakan_sp"></div>
                        <div class="text-[11px] text-gray-500" x-text="'Tanggal: ' + activeDetail?.tanggal"></div>
                    </div>
                </div>

                <div>
                    <span class="text-gray-500 font-medium">Kasus Pelanggaran Terkait:</span>
                    <div class="font-semibold text-gray-900 dark:text-white mt-0.5" x-text="activeDetail?.pelanggaran ? ('[' + activeDetail.pelanggaran.code + '] ' + activeDetail.pelanggaran.judul_pelanggaran) : '-'"></div>
                </div>

                <div>
                    <span class="text-gray-500 font-medium">Catatan Pembinaan & Hasil Konseling:</span>
                    <div class="p-3 rounded-xl border border-gray-100 bg-white dark:border-gray-800 dark:bg-gray-800/60 mt-1 leading-relaxed whitespace-pre-wrap" x-text="activeDetail?.catatan_penanganan"></div>
                </div>

                <template x-if="activeDetail?.hasil_kesepakatan">
                    <div>
                        <span class="text-gray-500 font-medium">Hasil Kesepakatan / Pernyataan Siswa:</span>
                        <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-100 text-emerald-900 dark:bg-emerald-950/30 dark:border-emerald-900/30 dark:text-emerald-300 mt-1 leading-relaxed" x-text="activeDetail?.hasil_kesepakatan"></div>
                    </div>
                </template>

                <div class="pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <template x-if="activeDetail?.link_gdrive">
                            <a :href="activeDetail.link_gdrive" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-emerald-700 transition">
                                <i class="fab fa-google-drive"></i> Buka Berkas Google Drive
                            </a>
                        </template>
                    </div>
                    <button type="button" @click="openDetailModal = false" class="rounded-xl border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 3: Form Catat Penanganan BK Langsung -->
    <div x-show="openCreateModal" style="display: none;"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 z-99999 flex items-center justify-center bg-black/60 p-4 backdrop-blur-xs">
        <div @click.away="openCreateModal = false"
             class="w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-200 dark:border-gray-800">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                <h4 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-plus-circle text-indigo-600"></i> Catat Penanganan BK / Sanksi SP
                </h4>
                <button type="button" @click="openCreateModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form action="{{ route('pelanggaran.penanganan.storeDirect') }}"
                  method="POST" enctype="multipart/form-data" class="mt-4 space-y-4 text-xs">
                @csrf

                <!-- Pilih Siswa (dari Siswa Berpoin di Rekap) -->
                <div>
                    <label class="mb-1 block font-semibold text-gray-700 dark:text-gray-300">
                        Pilih Siswa (Berdasarkan Rekap Poin) <span class="text-rose-500">*</span>
                    </label>
                    <select name="student_id" x-model="selectedStudentId" @change="onStudentSelect()" required
                            class="w-full rounded-xl border border-indigo-200 bg-transparent py-2.5 px-3 text-xs outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white font-medium">
                        <option value="">-- Pilih Siswa yang Memiliki Poin Pelanggaran --</option>
                        @foreach($studentsWithPoints as $swp)
                            <option value="{{ $swp->id }}">
                                {{ $swp->nama }} ({{ $swp->total_poin }} Poin) - {{ $swp->kelas?->nama_kelas ?? '-' }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-[11px] text-gray-400">
                        <i class="fas fa-info-circle mr-0.5"></i> Hanya menampilkan siswa yang memiliki akumulasi poin pelanggaran (siswa tanpa poin tidak ditampilkan).
                    </p>
                </div>

                <!-- Pilih Kasus Pelanggaran Terkait (Opsional) -->
                <div x-show="currentStudentViolations && currentStudentViolations.length > 0">
                    <label class="mb-1 block font-semibold text-gray-700 dark:text-gray-300">
                        Kasus Pelanggaran Terkait <span class="text-gray-400 font-normal">(Opsional)</span>
                    </label>
                    <select name="pelanggaran_id" x-model="selectedViolationId"
                            class="w-full rounded-xl border border-gray-200 bg-transparent py-2 px-3 text-xs outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">-- Otomatis Kaitkan ke Kasus Terbaru Siswa --</option>
                        <template x-for="v in currentStudentViolations" :key="v.id">
                            <option :value="v.id" x-text="'[' + v.code + '] ' + v.tanggal + ' - ' + v.judul_pelanggaran + ' (' + v.poin + ' Poin)'"></option>
                        </template>
                    </select>
                    <p class="mt-1 text-[11px] text-gray-400">Bisa memilih kasus spesifik atau biarkan default mengaitkan ke insiden terbaru.</p>
                </div>

                <!-- Tanggal & Tindakan SP -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block font-semibold text-gray-700 dark:text-gray-300">Tanggal Tindakan <span class="text-rose-500">*</span></label>
                        <input type="date" name="tanggal" value="{{ now()->format('Y-m-d') }}" required
                               class="w-full rounded-xl border border-gray-200 bg-transparent py-2 px-3 text-xs outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold text-gray-700 dark:text-gray-300">Tindakan / Sanksi SP <span class="text-rose-500">*</span></label>
                        <select name="tindakan_sp" required
                                class="w-full rounded-xl border border-gray-200 bg-transparent py-2 px-3 text-xs outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white font-medium">
                            <option value="pembinaan">Pembinaan / Konseling BK</option>
                            <option value="teguran_lisan">Teguran Lisan</option>
                            <option value="teguran_tertulis">Teguran Tertulis</option>
                            <option value="sp_1">Surat Peringatan 1 (SP 1)</option>
                            <option value="sp_2">Surat Peringatan 2 (SP 2)</option>
                            <option value="sp_3">Surat Peringatan 3 (SP 3)</option>
                            <option value="sp_4">Surat Peringatan 4 (SP 4 / Terakhir)</option>
                            <option value="skorsing">Skorsing Sementara</option>
                            <option value="dikembalikan_ortu">Dikembalikan ke Orang Tua</option>
                        </select>
                    </div>
                </div>

                <!-- Catatan Penanganan -->
                <div>
                    <label class="mb-1 block font-semibold text-gray-700 dark:text-gray-300">Catatan Penanganan & Pembinaan <span class="text-rose-500">*</span></label>
                    <textarea name="catatan_penanganan" rows="3" required
                              placeholder="Uraikan proses bimbingan, pemanggilan orang tua, atau hasil konseling..."
                              class="w-full rounded-xl border border-gray-200 bg-transparent p-3 text-xs outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                </div>

                <!-- Hasil Kesepakatan -->
                <div>
                    <label class="mb-1 block font-semibold text-gray-700 dark:text-gray-300">Hasil Kesepakatan / Pernyataan Siswa</label>
                    <textarea name="hasil_kesepakatan" rows="2"
                              placeholder="Contoh: Siswa menandatangani surat pernyataan dan berjanji tidak mengulangi perbuatannya..."
                              class="w-full rounded-xl border border-gray-200 bg-transparent p-3 text-xs outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                </div>

                <!-- Link Google Drive -->
                <div>
                    <label class="mb-1 block font-semibold text-gray-700 dark:text-gray-300 flex items-center justify-between">
                        <span><i class="fab fa-google-drive text-emerald-600 mr-1"></i> Link Google Drive Berkas Surat</span>
                        <span class="text-[10px] text-emerald-600 font-normal">Rekomendasi (hemat storage)</span>
                    </label>
                    <input type="url" name="link_gdrive"
                           placeholder="https://drive.google.com/file/d/.../view?usp=sharing"
                           class="w-full rounded-xl border border-emerald-200 bg-transparent py-2 px-3 text-xs outline-none focus:border-emerald-500 dark:border-emerald-900/50 dark:bg-gray-900 dark:text-white">
                    <p class="mt-1 text-[11px] text-gray-400">Tempelkan link folder/dokumen Google Drive surat peringatan resmi siswa.</p>
                </div>

                <!-- Status Kasus Pelanggaran -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block font-semibold text-gray-700 dark:text-gray-300">Status Penanganan</label>
                        <select name="status_penanganan"
                                class="w-full rounded-xl border border-gray-200 bg-transparent py-2 px-3 text-xs outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="dalam_proses">Dalam Proses Bimbingan</option>
                            <option value="selesai">Selesai / Dituntaskan</option>
                            <option value="evaluasi_lanjutan">Evaluasi Lanjutan</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block font-semibold text-gray-700 dark:text-gray-300">Upload Berkas Fisik (Opsional)</label>
                        <input type="file" name="lampiran_file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                               class="w-full rounded-xl border border-gray-200 bg-transparent py-1.5 px-3 text-xs outline-none focus:border-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2">
                    <button type="button" @click="openCreateModal = false"
                            class="rounded-xl border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                        Batal
                    </button>
                    <button type="submit" :disabled="!selectedStudentId"
                            class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 transition shadow-sm">
                        <i class="fas fa-save mr-1"></i> Simpan Penanganan BK
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function penangananBk() {
    const studentsMap = @json($studentsMap ?? []);
    const defaultStudentId = '{{ $selectedStudent?->id ?? '' }}';

    return {
        openDetailModal: false,
        activeDetail: null,
        openDossierModal: false,
        dossierStudent: null,
        dossierPenanganans: [],
        loadingDossier: false,
        openCreateModal: false,
        selectedStudentId: defaultStudentId,
        selectedViolationId: '',
        currentStudentViolations: (defaultStudentId && studentsMap[defaultStudentId]) ? (studentsMap[defaultStudentId].violations || []) : [],

        init() {
            if (this.selectedStudentId && studentsMap[this.selectedStudentId]) {
                this.currentStudentViolations = studentsMap[this.selectedStudentId].violations || [];
            }
        },

        onStudentSelect() {
            const st = studentsMap[this.selectedStudentId];
            if (st && st.violations) {
                this.currentStudentViolations = st.violations;
                this.selectedViolationId = st.violations.length > 0 ? String(st.violations[0].id) : '';
            } else {
                this.currentStudentViolations = [];
                this.selectedViolationId = '';
            }
        },

        showDetail(item) {
            this.activeDetail = item;
            this.openDetailModal = true;
        },

        async showStudentDossier(studentId) {
            if (!studentId) return;
            this.openDossierModal = true;
            this.loadingDossier = true;
            this.dossierStudent = null;
            this.dossierPenanganans = [];

            try {
                const response = await fetch(`/pelanggaran/penanganan/student/${studentId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const res = await response.json();
                if (res.success) {
                    this.dossierStudent = res.student;
                    this.dossierPenanganans = res.penanganans;
                }
            } catch (e) {
                console.error("Gagal memuat rekam jejak siswa:", e);
            } finally {
                this.loadingDossier = false;
            }
        }
    };
}
</script>
@endpush

