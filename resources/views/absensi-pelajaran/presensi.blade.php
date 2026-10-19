@extends('layouts.app')

@section('title', 'Presensi Siswa & Jurnal Pembelajaran - ' . ($jadwal->mapel?->nama_mapel ?? 'Mapel') . ' ' . ($jadwal->kelas?->nama_kelas ?? ''))

@push('styles')
<style>
    /* Styling Radio Pill Status Kehadiran (Hadir, Izin, Sakit, Alpha) */
    .status-pill-group {
        display: inline-flex;
        align-items: center;
        background-color: #f1f5f9;
        padding: 3px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        user-select: none;
        gap: 3px;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.04);
    }
    .dark .status-pill-group {
        background-color: #1e293b;
        border-color: #334155;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.2);
    }

    .status-pill-label {
        margin: 0;
        cursor: pointer;
        display: inline-flex;
        position: relative;
    }

    .status-pill-label input[type="radio"].radio-status {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
        margin: 0;
        pointer-events: none;
    }

    .status-btn-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 14px;
        font-size: 12px;
        font-weight: 700;
        border-radius: 8px;
        color: #64748b;
        background: transparent;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid transparent;
        cursor: pointer;
        line-height: 1.25;
        white-space: nowrap;
        -webkit-tap-highlight-color: transparent;
    }
    .dark .status-btn-pill {
        color: #94a3b8;
    }

    .status-btn-pill:hover {
        background-color: rgba(0, 0, 0, 0.05);
        color: #1e293b;
    }
    .dark .status-btn-pill:hover {
        background-color: rgba(255, 255, 255, 0.08);
        color: #f8fafc;
    }

    /* 4 STATUS COLORS: HADIR (H), IZIN (I), SAKIT (S), ALPHA (A) */
    .radio-status[value="H"]:checked + .status-btn-pill.pill-hadir {
        background-color: #10b981 !important;
        color: #ffffff !important;
        box-shadow: 0 2px 8px -1px rgba(16, 185, 129, 0.55);
        border-color: #059669 !important;
        transform: translateY(-1px);
    }

    .radio-status[value="I"]:checked + .status-btn-pill.pill-izin {
        background-color: #3b82f6 !important;
        color: #ffffff !important;
        box-shadow: 0 2px 8px -1px rgba(59, 130, 246, 0.55);
        border-color: #2563eb !important;
        transform: translateY(-1px);
    }

    .radio-status[value="S"]:checked + .status-btn-pill.pill-sakit {
        background-color: #f59e0b !important;
        color: #ffffff !important;
        box-shadow: 0 2px 8px -1px rgba(245, 158, 11, 0.55);
        border-color: #d97706 !important;
        transform: translateY(-1px);
    }

    .radio-status[value="A"]:checked + .status-btn-pill.pill-alpa {
        background-color: #ef4444 !important;
        color: #ffffff !important;
        box-shadow: 0 2px 8px -1px rgba(239, 68, 68, 0.55);
        border-color: #dc2626 !important;
        transform: translateY(-1px);
    }

    /* Soft Row Highlight based on status on Desktop */
    tr.student-row {
        transition: background-color 0.2s ease, border-color 0.2s ease;
    }
    tr.student-row.row-status-A {
        background-color: rgba(239, 68, 68, 0.06) !important;
    }
    tr.student-row.row-status-S {
        background-color: rgba(245, 158, 11, 0.07) !important;
    }
    tr.student-row.row-status-I {
        background-color: rgba(59, 130, 246, 0.07) !important;
    }
    tr.student-row.row-status-H {
        background-color: transparent !important;
    }

    .dark tr.student-row.row-status-A {
        background-color: rgba(239, 68, 68, 0.12) !important;
    }
    .dark tr.student-row.row-status-S {
        background-color: rgba(245, 158, 11, 0.12) !important;
    }
    .dark tr.student-row.row-status-I {
        background-color: rgba(59, 130, 246, 0.12) !important;
    }

    /* Mobile Responsive Cards Transformation */
    @media (max-width: 767px) {
        .presensi-table thead {
            display: none !important;
        }
        .presensi-table,
        .presensi-table tbody {
            display: block !important;
            width: 100% !important;
        }
        .presensi-table tbody {
            padding: 0.875rem !important;
        }
        .presensi-table tr.student-row {
            display: block !important;
            width: 100% !important;
            margin-bottom: 0.875rem !important;
            padding: 0.875rem !important;
            border-radius: 1rem !important;
            border: 1.5px solid #e2e8f0 !important;
            background-color: #ffffff !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04) !important;
        }
        .dark .presensi-table tr.student-row {
            border-color: #334155 !important;
            background-color: #1e293b !important;
        }
        .presensi-table td {
            display: block !important;
            width: 100% !important;
            padding: 0 !important;
            border: none !important;
        }
        .presensi-table td.td-no {
            display: none !important;
        }
        .presensi-table td.td-student {
            margin-bottom: 0.75rem !important;
        }
        .presensi-table td.td-status {
            margin-bottom: 0.625rem !important;
        }
        .presensi-table td.td-notes {
            margin-top: 0.25rem !important;
        }

        /* Pill group full width on mobile with 4 equal buttons */
        .status-pill-group {
            display: flex !important;
            width: 100% !important;
            justify-content: stretch !important;
            gap: 4px !important;
            padding: 4px !important;
            border-radius: 12px !important;
        }
        .status-pill-label {
            flex: 1 !important;
            display: flex !important;
        }
        .status-btn-pill {
            width: 100% !important;
            padding: 9px 2px !important;
            min-height: 44px !important;
            font-size: 13px !important;
            font-weight: 800 !important;
            border-radius: 8px !important;
        }

        /* Mobile Card Border Accents */
        tr.student-row.row-status-A {
            border-color: #ef4444 !important;
            background-color: rgba(239, 68, 68, 0.05) !important;
        }
        tr.student-row.row-status-S {
            border-color: #f59e0b !important;
            background-color: rgba(245, 158, 11, 0.05) !important;
        }
        tr.student-row.row-status-I {
            border-color: #3b82f6 !important;
            background-color: rgba(59, 130, 246, 0.05) !important;
        }
        tr.student-row.row-status-H {
            border-color: #10b981 !important;
            background-color: rgba(16, 185, 129, 0.03) !important;
        }
    }
</style>
@endpush

@section('content')
    <!-- ========================= TOP BREADCRUMB / HEADER ========================= -->
    <div class="mb-5 sm:mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('absensi-pelajaran.index', ['tanggal' => $tanggal]) }}"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 transition shadow-sm"
                title="Kembali ke Daftar Jadwal">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div class="min-w-0">
                <h2 class="text-lg sm:text-title-md2 font-bold text-gray-900 dark:text-white truncate">
                    Presensi Siswa & Jurnal Pembelajaran
                </h2>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                    {{ $jadwal->mapel?->nama_mapel ?? 'Mapel' }} &bull; 
                    @if(!empty($isMultiKelas))
                        <span class="inline-flex items-center gap-1 font-bold text-brand-600 dark:text-brand-400">
                            <i class="fas fa-layer-group text-xs text-brand-500"></i> Kelas Gabungan: {{ $parallelJadwals->pluck('kelas.nama_kelas')->filter()->unique()->join(' & ') }}
                        </span>
                    @else
                        Kelas {{ $jadwal->kelas?->nama_kelas ?? '-' }}
                    @endif
                </p>
            </div>
        </div>

        <!-- Date indicator & change -->
        <form action="{{ route('absensi-pelajaran.presensi', $jadwal->id) }}" method="GET" class="flex items-center justify-between sm:justify-start gap-2 pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100 dark:border-gray-800">
            <label class="text-xs text-gray-500 dark:text-gray-400 font-medium whitespace-nowrap">Tanggal:</label>
            <input type="date" name="tanggal" value="{{ $tanggal }}" onchange="this.form.submit()"
                class="rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs sm:text-sm font-semibold text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-white shadow-sm focus:border-brand-500 outline-none">
        </form>
    </div>

    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-success-200 bg-success-50 p-4 dark:border-success-500/20 dark:bg-success-500/10 text-success-800 dark:text-success-400 text-sm shadow-sm">
            <i class="fas fa-check-circle text-lg shrink-0"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if (isset($errors) && $errors->any())
        <div class="mb-5 rounded-2xl border border-error-200 bg-error-50 p-4 dark:border-error-500/20 dark:bg-error-500/10 text-error-800 dark:text-error-400 text-sm shadow-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- ========================= HERO DETAIL JADWAL PEMBELAJARAN ========================= -->
    <div class="mb-5 sm:mb-6 rounded-2xl border border-gray-200 bg-gradient-to-r from-brand-50/50 to-white dark:from-gray-800/60 dark:to-gray-dark p-4 sm:p-6 shadow-theme-sm dark:border-gray-800">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
            <div>
                <span class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 block">Mata Pelajaran</span>
                <span class="font-bold text-gray-800 dark:text-white text-sm sm:text-base block truncate">{{ $jadwal->mapel?->nama_mapel ?? '-' }}</span>
            </div>
            <div>
                <span class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 block">Kelas</span>
                @if(!empty($isMultiKelas))
                    <span class="font-bold text-brand-600 dark:text-brand-400 text-sm sm:text-base block truncate" title="{{ $parallelJadwals->pluck('kelas.nama_kelas')->filter()->unique()->join(' & ') }}">
                        {{ $parallelJadwals->pluck('kelas.nama_kelas')->filter()->unique()->join(' & ') }}
                    </span>
                    <span class="text-[10px] font-semibold text-brand-500 block">(Kelas Gabungan)</span>
                @else
                    <span class="font-bold text-brand-600 dark:text-brand-400 text-sm sm:text-base block truncate">{{ $jadwal->kelas?->nama_kelas ?? '-' }}</span>
                @endif
            </div>
            <div>
                <span class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 block">Hari & Sesi</span>
                <span class="font-semibold text-gray-800 dark:text-white text-xs sm:text-sm block">
                    {{ $jadwal->hari }} {{ $jadwal->jam_ke ? '(Jam ke ' . $jadwal->jam_ke . ')' : '' }}
                </span>
            </div>
            <div>
                <span class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 block">Waktu Pelajaran</span>
                <span class="font-semibold text-gray-800 dark:text-white text-xs sm:text-sm font-mono block">
                    {{ substr($jadwal->jam_mulai, 0, 5) }} - {{ substr($jadwal->jam_selesai, 0, 5) }}
                </span>
                @if(!empty($jadwal->jeda_istirahat_info['has_istirahat']))
                    <span class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold block mt-0.5">
                        <i class="fas fa-coffee text-[9px]"></i> {{ $jadwal->jeda_istirahat_info['istirahat_label'] }}
                    </span>
                @endif
            </div>
            <div>
                <span class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 block">Ruangan</span>
                <span class="font-semibold text-gray-800 dark:text-white text-xs sm:text-sm block truncate">{{ $jadwal->ruangan ?: '-' }}</span>
            </div>
            <div>
                <span class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 block">Guru Pengajar</span>
                <span class="font-semibold text-gray-800 dark:text-white text-xs sm:text-sm block truncate">{{ $jadwal->guru?->nama ?? '-' }}</span>
            </div>
        </div>
    </div>

    <!-- ========================= MULTI-CLASS / KELAS GABUNGAN BANNER ========================= -->
    @if(!empty($isMultiKelas))
        <div class="mb-5 sm:mb-6 rounded-2xl border border-brand-200 bg-brand-50/50 p-4 sm:p-5 dark:border-brand-500/20 dark:bg-brand-500/10 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="flex items-start sm:items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl font-bold text-lg shadow-sm" style="background-color: #4f6bed; color: #ffffff;">
                    <i class="fas fa-layer-group text-white text-base"></i>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h4 class="font-bold text-sm sm:text-base text-gray-900 dark:text-white">Sesi Mengajar Kelas Gabungan</h4>
                        <span class="inline-flex rounded-full bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300 px-2.5 py-0.5 text-[11px] font-bold border border-brand-200 dark:border-brand-500/30">
                            {{ $parallelJadwals->count() }} Kelas Paralel
                        </span>
                    </div>
                    <p class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                        Anda mengajar mata pelajaran ini di jam yang sama untuk kelas: 
                        <strong class="text-brand-600 dark:text-brand-400 font-bold">{{ $parallelJadwals->pluck('kelas.nama_kelas')->filter()->unique()->join(' & ') }}</strong>.
                        Jurnal KBM otomatis disinkronkan ke seluruh kelas, dan absensi siswa dikelompokkan per kelas di bawah.
                    </p>
                </div>
            </div>
            <div class="shrink-0 flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-xl border border-brand-200 bg-white dark:border-gray-800 dark:bg-gray-800 px-3 py-1.5 text-xs font-semibold text-brand-600 dark:text-brand-400 shadow-2xs">
                    <i class="fas fa-sync-alt text-brand-500"></i> Jurnal Otomatis Terhubung
                </span>
            </div>
        </div>
    @endif

    <!-- ========================= LIVE COUNTERS RIBBON (4 STATUS: H, I, S, A) ========================= -->
    <div class="mb-5 sm:mb-6 grid grid-cols-2 sm:grid-cols-5 gap-2.5 sm:gap-3">
        <!-- 1. Total Siswa -->
        <div class="col-span-2 sm:col-span-1 rounded-xl border border-gray-200 bg-white p-3 sm:p-3.5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark flex sm:flex-col items-center justify-between sm:justify-center text-center">
            <span class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 font-medium">Total Siswa</span>
            <span class="text-lg sm:text-xl font-extrabold text-gray-800 dark:text-white" id="counter_total">{{ $stats['total'] }}</span>
        </div>
        <!-- 2. Hadir (H) -->
        <div class="rounded-xl border border-success-200 bg-success-50/50 p-2.5 sm:p-3.5 shadow-theme-xs dark:border-success-500/20 dark:bg-success-500/5 text-center">
            <span class="text-[10px] sm:text-xs text-success-700 dark:text-success-400 font-bold block truncate">Hadir (H)</span>
            <span class="text-base sm:text-xl font-extrabold text-success-700 dark:text-success-400 mt-0.5 sm:mt-1 block" id="counter_H">{{ $stats['H'] }}</span>
        </div>
        <!-- 3. Izin (I) -->
        <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-2.5 sm:p-3.5 shadow-theme-xs dark:border-blue-500/20 dark:bg-blue-500/5 text-center">
            <span class="text-[10px] sm:text-xs text-blue-700 dark:text-blue-400 font-bold block truncate">Izin (I)</span>
            <span class="text-base sm:text-xl font-extrabold text-blue-700 dark:text-blue-400 mt-0.5 sm:mt-1 block" id="counter_I">{{ $stats['I'] }}</span>
        </div>
        <!-- 4. Sakit (S) -->
        <div class="rounded-xl border border-warning-200 bg-warning-50/50 p-2.5 sm:p-3.5 shadow-theme-xs dark:border-warning-500/20 dark:bg-warning-500/5 text-center">
            <span class="text-[10px] sm:text-xs text-warning-700 dark:text-warning-400 font-bold block truncate">Sakit (S)</span>
            <span class="text-base sm:text-xl font-extrabold text-warning-700 dark:text-warning-400 mt-0.5 sm:mt-1 block" id="counter_S">{{ $stats['S'] }}</span>
        </div>
        <!-- 5. Alpha (A) - DEFAULT -->
        <div class="rounded-xl border border-error-200 bg-error-50/50 p-2.5 sm:p-3.5 shadow-theme-xs dark:border-error-500/20 dark:bg-error-500/5 text-center">
            <span class="text-[10px] sm:text-xs text-error-700 dark:text-error-400 font-bold block truncate">Alpha (A)</span>
            <span class="text-base sm:text-xl font-extrabold text-error-700 dark:text-error-400 mt-0.5 sm:mt-1 block" id="counter_A">{{ $stats['A'] }}</span>
        </div>
    </div>

    <!-- ========================= FORM JURNAL PEMBELAJARAN (KBM) MANDIRI ========================= -->
    <div class="mb-5 sm:mb-6 rounded-2xl border border-gray-200 bg-white p-4 sm:p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <form action="{{ route('absensi-pelajaran.jurnal', $jadwal->id) }}" method="POST" id="formJurnal">
            @csrf
            <input type="hidden" name="tanggal" value="{{ $tanggal }}">

            <div class="flex items-center justify-between mb-3.5 sm:mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-500 text-white font-bold text-sm shrink-0 shadow-xs">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-bold text-gray-800 dark:text-white/90">Jurnal Pembelajaran</h3>
                            @if(!empty($isMultiKelas))
                                <span class="inline-flex items-center gap-1 rounded-md bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300 px-2 py-0.5 text-[11px] font-bold border border-brand-200 dark:border-brand-500/20">
                                    <i class="fas fa-sync-alt text-[10px] text-brand-500"></i> Tersinkron ke {{ $parallelJadwals->count() }} Kelas
                                </span>
                            @endif
                        </div>
                        <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400">
                            @if(!empty($isMultiKelas))
                                Cukup isi sekali, sistem otomatis menyinkronkan materi ke seluruh kelas gabungan: {{ $parallelJadwals->pluck('kelas.nama_kelas')->filter()->unique()->join(' & ') }}
                            @else
                                Catat materi kompetensi dasar dan evaluasi proses sesi ini
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Tombol Simpan Jurnal di Header Card (Desktop) -->
                <button type="submit" id="btnSaveJurnalTop"
                    class="hidden sm:inline-flex items-center gap-1.5 rounded-xl bg-brand-500 hover:bg-brand-600 px-4 py-2 text-xs font-bold text-white transition shadow-sm">
                    <i class="fas fa-save"></i>
                    <span>Simpan Jurnal</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5 sm:gap-4">
                <div>
                    <label class="mb-1 block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Materi Pokok / Pembahasan <span class="text-error-500">*</span>
                    </label>
                    <input type="text" name="materi" required
                        value="{{ old('materi', $jurnal?->materi ?? '') }}"
                        placeholder="Contoh: Bab 3 - Persamaan Linier Dua Variabel dan Latihan Soal"
                        class="w-full rounded-xl border border-gray-200 bg-transparent px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white shadow-xs">
                </div>

                <div>
                    <label class="mb-1 block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Catatan / Tugas 
                    </label>
                    <input type="text" name="catatan"
                        value="{{ old('catatan', $jurnal?->catatan ?? '') }}"
                        placeholder="Contoh: Latihan hal 45 dikumpulkan minggu depan, siswa aktif"
                        class="w-full rounded-xl border border-gray-200 bg-transparent px-3.5 py-2 sm:py-2.5 text-xs sm:text-sm outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white shadow-xs">
                </div>
            </div>

            @if($jurnal && $jurnal->has_dokumentasi)
                <div class="mt-4 pt-3.5 border-t border-gray-100 dark:border-gray-800">
                    <label class="mb-2.5 block text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center justify-between">
                        <span class="flex items-center gap-2">
                            <i class="fas fa-photo-video text-brand-500"></i>
                            <span>Dokumentasi Kegiatan Belajar ({{ count($jurnal->dokumentasi_list) }} berkas)</span>
                        </span>
                        <span class="text-[11px] text-gray-400 font-normal">Tersimpan di Cloud Storage</span>
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                        @foreach($jurnal->dokumentasi_list as $dok)
                            <div class="group relative rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 shadow-xs hover:shadow-md transition">
                                @if($dok['is_video'])
                                    <div class="aspect-video w-full bg-black flex items-center justify-center relative">
                                        <video src="{{ $dok['view_url'] }}" controls preload="metadata" class="w-full h-full object-cover"></video>
                                    </div>
                                @else
                                    <a href="{{ $dok['view_url'] }}" target="_blank" class="block aspect-square w-full overflow-hidden bg-gray-100 dark:bg-gray-900">
                                        <img src="{{ $dok['view_url'] }}" alt="{{ $dok['name'] }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    </a>
                                @endif
                                <div class="p-2 text-[10px] text-gray-600 dark:text-gray-400 flex items-center justify-between gap-1 border-t border-gray-100 dark:border-gray-700/60">
                                    <span class="truncate" title="{{ $dok['name'] }}">{{ $dok['name'] }}</span>
                                    <a href="{{ $dok['download_url'] }}" target="_blank" download class="text-brand-500 hover:text-brand-600 ml-1 shrink-0" title="Unduh Berkas">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Bottom action row jurnal -->
            <div class="mt-3.5 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3">
                <div id="jurnalSaveStatus" class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1.5">
                    @if($jurnal?->updated_at)
                        <i class="fas fa-check-circle text-emerald-500"></i>
                        <span>Terakhir disimpan: {{ $jurnal->updated_at->format('H:i') }} WIB</span>
                    @else
                        <i class="fas fa-info-circle text-brand-500"></i>
                        <span>Silakan isi jurnal lalu klik simpan.</span>
                    @endif
                </div>

                <button type="submit" id="btnSaveJurnalBottom"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-600 px-5 py-2.5 text-xs font-bold text-white transition shadow-sm">
                    <i class="fas fa-save"></i>
                    <span>Simpan Jurnal</span>
                </button>
            </div>
        </form>
    </div>

    <!-- ========================= DAFTAR KEHADIRAN SISWA (LIVE ACTION) ========================= -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark overflow-hidden mb-16 md:mb-8">
        <!-- Table Header Bar -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center px-4 sm:px-5 py-3.5 sm:py-4 border-b border-gray-200 dark:border-gray-800 gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h4 class="font-bold text-gray-800 dark:text-white/90 text-sm sm:text-base">Daftar Kehadiran Siswa</h4>
                    <!-- Live indicator status -->
                    <span id="liveStatusBadge" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] sm:text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                        <i class="fas fa-check-circle text-emerald-500"></i> <span>Tersimpan Otomatis</span>
                    </span>
                </div>
                <p class="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                    <strong>Live Action:</strong> Setiap klik status langsung tersimpan otomatis. Default adalah <strong>Alpha (A)</strong>.
                </p>
            </div>

            <!-- Fast Actions -->
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="button" id="btnSetSemuaHadir"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 rounded-xl bg-success-500 hover:bg-success-600 px-4 py-2 text-xs font-bold text-white transition shadow-sm">
                    <i class="fas fa-check-double"></i> <span id="btnSetSemuaHadirText">Setel Semua Hadir</span>
                </button>
            </div>
        </div>

        @if(!empty($isMultiKelas))
            <!-- Class Tabs Navigation for Multi-Class Session -->
            <div class="px-4 sm:px-5 pt-3 pb-3 border-b border-gray-200 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/20">
                <div class="flex items-center gap-2 overflow-x-auto pb-1 no-scrollbar" id="classTabsBar">
                    <!-- Tab 1: Semua Kelas (DEFAULT ACTIVE) -->
                    <button type="button"
                        class="class-tab-btn inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition-all shadow-2xs bg-brand-500 text-white shadow-brand-500/25 active-tab"
                        data-filter-kelas="all"
                        data-jadwal-id="all"
                        data-kelas-name="Semua Kelas">
                        <i class="fas fa-users text-xs"></i>
                        <span>Semua Kelas</span>
                        <span class="tab-badge ml-1 rounded-full px-2 py-0.5 text-[10px] font-extrabold bg-white/25 text-white">
                            {{ $globalStats['total'] }} Siswa
                        </span>
                    </button>

                    <!-- Tab per Kelas -->
                    @foreach($classesData as $idx => $cd)
                        <button type="button"
                            class="class-tab-btn inline-flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-bold transition-all shadow-2xs bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700"
                            data-filter-kelas="{{ $cd->jadwal->id }}"
                            data-jadwal-id="{{ $cd->jadwal->id }}"
                            data-kelas-name="Kelas {{ $cd->kelas?->nama_kelas }}">
                            <i class="fas fa-chalkboard text-xs"></i>
                            <span>Kelas {{ $cd->kelas?->nama_kelas }}</span>
                            <span class="tab-badge ml-1 rounded-full px-2 py-0.5 text-[10px] font-extrabold bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                {{ $cd->stats['total'] }} Siswa
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="max-w-full overflow-x-auto">
            <table class="w-full table-auto presensi-table">
                <thead>
                    <tr class="bg-gray-50 text-left dark:bg-gray-800/50 text-gray-800 dark:text-white/90 font-semibold text-xs">
                        <th class="px-4 py-3.5 w-12 text-center">No</th>
                        <th class="px-4 py-3.5">Nama Siswa</th>
                        <th class="px-4 py-3.5 text-center min-w-[280px]">Status Kehadiran</th>
                        <th class="px-4 py-3.5 min-w-[200px]">Catatan / Keterangan</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($students as $idx => $s)
                        @php
                            $currStatus = $s->presensi_status ?? 'A'; // DEFAULT ALPHA
                        @endphp
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors student-row row-status-{{ $currStatus }}"
                            data-student-id="{{ $s->id }}"
                            data-jadwal-id="{{ $s->jadwal_id ?? $jadwal->id }}"
                            data-kelas-id="{{ $s->jadwal_id ?? $jadwal->id }}">
                            <td class="px-4 py-3 text-center text-xs text-gray-500 dark:text-gray-400 td-no">
                                <span class="student-idx">{{ $idx + 1 }}</span>
                            </td>
                            <td class="px-4 py-3 td-student">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                                        @if($s->foto)
                                            <img src="{{ $s->foto_url }}" alt="{{ $s->nama }}" class="h-9 w-9 rounded-full object-cover shrink-0 border border-gray-200 dark:border-gray-700">
                                        @else
                                            <div class="h-9 w-9 rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 font-bold flex items-center justify-center text-xs shrink-0">
                                                {{ substr($s->nama, 0, 1) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <p class="font-semibold text-gray-900 dark:text-white text-xs sm:text-sm leading-snug truncate">{{ $s->nama }}</p>
                                                @if(!empty($isMultiKelas))
                                                    <span class="inline-flex rounded bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300 px-1.5 py-0.5 text-[10px] font-bold border border-brand-200 dark:border-brand-500/20 shrink-0">
                                                        Kelas {{ $s->nama_kelas }}
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="text-[11px] text-gray-400 font-mono">NIS: {{ $s->nis }}</span>
                                        </div>
                                    </div>
                                    <!-- Mobile Student Number Badge -->
                                    <span class="md:hidden inline-flex items-center justify-center h-6 w-6 rounded-full bg-gray-100 dark:bg-gray-800 text-[10px] font-bold text-gray-600 dark:text-gray-300 font-mono shrink-0">
                                        #<span class="student-idx-mobile">{{ $idx + 1 }}</span>
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center td-status">
                                <!-- Radio Pill Group: H, I, S, A -->
                                <div class="status-pill-group">
                                    <!-- 1. HADIR (H) -->
                                    <label class="status-pill-label" title="Hadir (H)">
                                        <input type="radio" name="attendance_{{ $s->id }}" value="H" {{ $currStatus == 'H' ? 'checked' : '' }} class="radio-status" data-student-id="{{ $s->id }}">
                                        <span class="status-btn-pill pill-hadir">
                                            <span class="hidden sm:inline">Hadir</span>
                                            <span class="sm:hidden font-black text-xs">H</span>
                                        </span>
                                    </label>

                                    <!-- 2. IZIN (I) -->
                                    <label class="status-pill-label" title="Izin (I)">
                                        <input type="radio" name="attendance_{{ $s->id }}" value="I" {{ $currStatus == 'I' ? 'checked' : '' }} class="radio-status" data-student-id="{{ $s->id }}">
                                        <span class="status-btn-pill pill-izin">
                                            <span class="hidden sm:inline">Izin</span>
                                            <span class="sm:hidden font-black text-xs">I</span>
                                        </span>
                                    </label>

                                    <!-- 3. SAKIT (S) -->
                                    <label class="status-pill-label" title="Sakit (S)">
                                        <input type="radio" name="attendance_{{ $s->id }}" value="S" {{ $currStatus == 'S' ? 'checked' : '' }} class="radio-status" data-student-id="{{ $s->id }}">
                                        <span class="status-btn-pill pill-sakit">
                                            <span class="hidden sm:inline">Sakit</span>
                                            <span class="sm:hidden font-black text-xs">S</span>
                                        </span>
                                    </label>

                                    <!-- 4. ALPHA (A) - DEFAULT -->
                                    <label class="status-pill-label" title="Alpha (A) - Default">
                                        <input type="radio" name="attendance_{{ $s->id }}" value="A" {{ $currStatus == 'A' ? 'checked' : '' }} class="radio-status" data-student-id="{{ $s->id }}">
                                        <span class="status-btn-pill pill-alpa">
                                            <span class="hidden sm:inline">Alpha</span>
                                            <span class="sm:hidden font-black text-xs">A</span>
                                        </span>
                                    </label>
                                </div>
                            </td>
                            <td class="px-4 py-3 td-notes">
                                <div class="relative">
                                    <input type="text" name="keterangan_{{ $s->id }}" value="{{ $s->presensi_keterangan }}"
                                        data-student-id="{{ $s->id }}"
                                        placeholder="Keterangan / alasan jika izin/sakit..."
                                        class="input-keterangan w-full rounded-xl border border-gray-200 bg-gray-50/50 sm:bg-transparent px-3 py-2 sm:py-1.5 text-xs outline-none focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-900 dark:text-white transition">
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                Tidak ada data siswa di kelas ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Bottom Informational Footer (No Submit Button Needed for Attendance!) -->
        <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/40 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="flex items-center gap-2">

            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script>
        $(document).ready(function () {
            // Setup CSRF header for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            var jadwalId = {{ $jadwal->id }};
            var tanggal = '{{ $tanggal }}';
            var isMultiKelas = {{ !empty($isMultiKelas) ? 'true' : 'false' }};
            var activeFilter = 'all';
            var activeJadwalId = 'all';

            // Function to update visual card highlight (A = rose, S = amber, I = blue, H = neutral/green)
            function updateRowHighlight() {
                $('.student-row:visible').each(function () {
                    var status = $(this).find('.radio-status:checked').val();
                    $(this).removeClass('row-status-H row-status-I row-status-S row-status-A');
                    if (status) {
                        $(this).addClass('row-status-' + status);
                    }
                });
            }

            // Function to recalculate local counters based on VISIBLE rows
            function recalculateLocalCounters() {
                var counts = { H: 0, I: 0, S: 0, A: 0, total: 0 };
                $('.student-row:visible').each(function () {
                    counts.total++;
                    var val = $(this).find('.radio-status:checked').val();
                    if (counts.hasOwnProperty(val)) {
                        counts[val]++;
                    }
                });

                $('#counter_total').text(counts.total);
                $('#counter_H').text(counts.H);
                $('#counter_I').text(counts.I);
                $('#counter_S').text(counts.S);
                $('#counter_A').text(counts.A);

                updateRowHighlight();
            }

            // Function to renumber index numbers for visible rows
            function renumberVisibleRows() {
                var idx = 1;
                $('.student-row:visible').each(function () {
                    $(this).find('.student-idx').text(idx);
                    $(this).find('.student-idx-mobile').text(idx);
                    idx++;
                });
            }

            // Function to filter students by class
            function filterStudentsByClass(targetKelas) {
                activeFilter = targetKelas;
                if (targetKelas === 'all') {
                    $('.student-row').show();
                    $('#btnSetSemuaHadirText').text('Setel Semua Hadir');
                } else {
                    $('.student-row').each(function () {
                        var rKelas = $(this).data('kelas-id');
                        if (String(rKelas) === String(targetKelas)) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                    var $activeBtn = $('.class-tab-btn[data-filter-kelas="' + targetKelas + '"]');
                    var kName = $activeBtn.data('kelas-name') || 'Kelas Ini';
                    $('#btnSetSemuaHadirText').text('Setel Semua Hadir (' + kName + ')');
                }
                renumberVisibleRows();
                recalculateLocalCounters();
            }

            // If multi-class session, initialize tab filter
            if (isMultiKelas) {
                filterStudentsByClass(activeFilter);
            } else {
                recalculateLocalCounters();
            }

            // Handle Tab Click
            $(document).on('click', '.class-tab-btn', function () {
                var target = $(this).data('filter-kelas');
                var jId = $(this).data('jadwal-id');
                activeJadwalId = (jId === 'all') ? jadwalId : jId;

                // Toggle active styling
                $('.class-tab-btn').removeClass('bg-brand-500 text-white shadow-brand-500/25 active-tab')
                    .addClass('bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700');
                $('.class-tab-btn .tab-badge').removeClass('bg-white/25 text-white')
                    .addClass('bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300');

                $(this).removeClass('bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700')
                    .addClass('bg-brand-500 text-white shadow-brand-500/25 active-tab');
                $(this).find('.tab-badge').removeClass('bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300')
                    .addClass('bg-white/25 text-white');

                filterStudentsByClass(target);
            });

            // Function to set live indicator UI
            function setLiveStatus(state, msg) {
                var $badge = $('#liveStatusBadge');
                if (state === 'saving') {
                    $badge.removeClass('bg-emerald-50 text-emerald-700 bg-rose-50 text-rose-700 border-emerald-200 border-rose-200')
                          .addClass('bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/15 dark:text-amber-400')
                          .html('<i class="fas fa-spinner fa-spin text-amber-500"></i> <span>' + (msg || 'Menyimpan...') + '</span>');
                } else if (state === 'success') {
                    $badge.removeClass('bg-amber-50 text-amber-700 bg-rose-50 text-rose-700 border-amber-200 border-rose-200')
                          .addClass('bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400')
                          .html('<i class="fas fa-check-circle text-emerald-500"></i> <span>' + (msg || 'Tersimpan') + '</span>');
                } else if (state === 'error') {
                    $badge.removeClass('bg-emerald-50 text-emerald-700 bg-amber-50 text-amber-700 border-emerald-200 border-amber-200')
                          .addClass('bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-500/15 dark:text-rose-400')
                          .html('<i class="fas fa-exclamation-triangle text-rose-500"></i> <span>' + (msg || 'Gagal menyimpan') + '</span>');
                }
            }

            // AJAX: Save Single Student Attendance
            function saveStudentAttendance(studentId, status, keterangan, studentJadwalId) {
                setLiveStatus('saving', 'Menyimpan...');
                var targetJadwal = studentJadwalId || (activeFilter !== 'all' ? activeJadwalId : jadwalId);

                $.ajax({
                    url: '{{ route("absensi-pelajaran.update-single") }}',
                    type: 'POST',
                    data: {
                        jadwal_pelajaran_id: targetJadwal,
                        student_id: studentId,
                        tanggal: tanggal,
                        status: status,
                        keterangan: keterangan
                    },
                    success: function (res) {
                        setLiveStatus('success', 'Tersimpan Otomatis');
                        recalculateLocalCounters();
                    },
                    error: function (xhr) {
                        setLiveStatus('error', 'Gagal tersimpan');
                    }
                });
            }

            // 1. Listen to any status radio change -> LIVE ACTION SAVE!
            $(document).on('change', '.radio-status', function () {
                var studentId = $(this).data('student-id');
                var status = $(this).val();
                var $row = $(this).closest('.student-row');
                var studentJadwalId = $row.data('jadwal-id') || jadwalId;
                var keterangan = $row.find('.input-keterangan').val();

                recalculateLocalCounters();
                saveStudentAttendance(studentId, status, keterangan, studentJadwalId);
            });

            // 2. Listen to notes input change or blur -> LIVE ACTION SAVE!
            $(document).on('change blur', '.input-keterangan', function () {
                var studentId = $(this).data('student-id');
                var keterangan = $(this).val();
                var $row = $(this).closest('.student-row');
                var studentJadwalId = $row.data('jadwal-id') || jadwalId;
                var status = $row.find('.radio-status:checked').val() || 'A';

                saveStudentAttendance(studentId, status, keterangan, studentJadwalId);
            });

            // 3. Fast 1-Click "Set Semua Hadir" -> LIVE ACTION SAVE ALL!
            $('#btnSetSemuaHadir').on('click', function () {
                var $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
                setLiveStatus('saving', 'Menyetel semua hadir...');

                var isAllParallel = (activeFilter === 'all');
                var targetJadwal = isAllParallel ? jadwalId : activeJadwalId;

                $.ajax({
                    url: '{{ route("absensi-pelajaran.set-all") }}',
                    type: 'POST',
                    data: {
                        jadwal_pelajaran_id: targetJadwal,
                        all_parallel: isAllParallel ? 1 : 0,
                        tanggal: tanggal,
                        status: 'H'
                    },
                    success: function (res) {
                        if (isAllParallel) {
                            $('.student-row .radio-status[value="H"]').prop('checked', true);
                        } else {
                            $('.student-row:visible .radio-status[value="H"]').prop('checked', true);
                        }
                        recalculateLocalCounters();
                        setLiveStatus('success', 'Semua Hadir Tersimpan');
                        var btnLabel = isAllParallel ? 'Setel Semua Hadir' : ($('.class-tab-btn.active-tab').data('kelas-name') ? 'Setel Semua Hadir (' + $('.class-tab-btn.active-tab').data('kelas-name') + ')' : 'Setel Semua Hadir');
                        $btn.prop('disabled', false).html('<i class="fas fa-check-double"></i> <span id="btnSetSemuaHadirText">' + btnLabel + '</span>');
                    },
                    error: function () {
                        setLiveStatus('error', 'Gagal setel semua hadir');
                        $btn.prop('disabled', false).html('<i class="fas fa-check-double"></i> <span id="btnSetSemuaHadirText">Setel Semua Hadir</span>');
                    }
                });
            });

            // 4. Form Jurnal KBM Submit Handler (Independent Save with Automatic Parallel Sync)
            $('#formJurnal').on('submit', function (e) {
                e.preventDefault();
                var $form = $(this);
                var $btnTop = $('#btnSaveJurnalTop');
                var $btnBottom = $('#btnSaveJurnalBottom');
                var $status = $('#jurnalSaveStatus');

                $btnTop.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
                $btnBottom.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan Jurnal...');
                $status.html('<span class="text-brand-600"><i class="fas fa-spinner fa-spin"></i> Sedang menyimpan materi...</span>');

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    success: function (res) {
                        $btnTop.prop('disabled', false).html('<i class="fas fa-check"></i> <span>Tersimpan!</span>');
                        $btnBottom.prop('disabled', false).html('<i class="fas fa-check"></i> <span>Jurnal Tersimpan!</span>');
                        $status.html('<span class="text-emerald-600 font-bold"><i class="fas fa-check-circle"></i> ' + (res.message || 'Jurnal berhasil disimpan!') + '</span>');

                        setTimeout(function () {
                            $btnTop.html('<i class="fas fa-save"></i> <span>Simpan Jurnal</span>');
                            $btnBottom.html('<i class="fas fa-save"></i> <span>Simpan Jurnal</span>');
                        }, 3000);
                    },
                    error: function (xhr) {
                        var err = 'Gagal menyimpan jurnal.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            err = xhr.responseJSON.message;
                        }
                        $status.html('<span class="text-rose-600 font-bold"><i class="fas fa-exclamation-circle"></i> ' + err + '</span>');
                        $btnTop.prop('disabled', false).html('<i class="fas fa-save"></i> <span>Simpan Jurnal</span>');
                        $btnBottom.prop('disabled', false).html('<i class="fas fa-save"></i> <span>Simpan Jurnal</span>');
                    }
                });
            });
        });
    </script>
@endpush
