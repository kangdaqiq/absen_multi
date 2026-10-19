@extends('layouts.app')

@section('title', 'Dashboard Guru')

@section('content')
<div x-data="{
    activeTab: 'jadwal',
    showQuickJurnalModal: false,
    selectedJadwalId: null,
    selectedJadwalNama: '',
    selectedJadwalKelas: '',
    selectedJadwalMateri: '',
    selectedJadwalCatatan: '',
    selectedTanggal: '{{ $tanggal }}',
    openJurnalModal(id, mapel, kelas, materi, catatan) {
        this.selectedJadwalId = id;
        this.selectedJadwalNama = mapel;
        this.selectedJadwalKelas = kelas;
        this.selectedJadwalMateri = materi || '';
        this.selectedJadwalCatatan = catatan || '';
        this.showQuickJurnalModal = true;
    }
}">
    <!-- ========================= ALERT MESSAGES ========================= -->
    @if(session('success'))
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-emerald-500/20 bg-emerald-50 dark:bg-emerald-950/30 p-4 text-emerald-800 dark:text-emerald-300 text-sm shadow-sm">
            <i class="fas fa-check-circle text-lg text-emerald-600 dark:text-emerald-400"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 flex items-center gap-3 rounded-2xl border border-rose-500/20 bg-rose-50 dark:bg-rose-950/30 p-4 text-rose-800 dark:text-rose-300 text-sm shadow-sm">
            <i class="fas fa-exclamation-circle text-lg text-rose-600 dark:text-rose-400"></i>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <!-- ========================= HERO HEADER GURU ========================= -->
    <div class="mb-5 sm:mb-6 rounded-2xl border border-gray-200 bg-white p-4 sm:p-6 shadow-sm dark:border-gray-800 dark:bg-boxdark">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 sm:gap-6">
            <!-- Profil Singkat Guru -->
            <div class="flex items-start sm:items-center gap-3 sm:gap-4">
                <div class="relative shrink-0">
                    <img src="{{ $guru?->foto_url ?? 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()?->name ?? 'Guru') . '&background=4f6bed&color=fff&size=160&bold=true' }}"
                        alt="{{ $guru?->nama ?? auth()->user()?->name ?? 'Guru' }}"
                        class="h-14 w-14 sm:h-20 sm:w-20 rounded-2xl object-cover border-2 border-brand-500/30 shadow-md">
                    @if($todayGuruAttendance)
                        <span class="absolute -bottom-1 -right-1 flex h-4 w-4" title="Sudah Presensi Masuk Hari Ini">
                            <span class="relative inline-flex h-4 w-4 rounded-full bg-emerald-500 ring-2 ring-white dark:ring-gray-900"></span>
                        </span>
                    @endif
                </div>

                <div class="space-y-1 sm:space-y-1.5 min-w-0">
                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                        <h2 class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white truncate">
                            {{ $guru?->nama ?? auth()->user()?->name ?? 'Bapak/Ibu Guru' }}
                        </h2>
                        @if($guru?->nip)
                            <span class="inline-flex items-center rounded-lg bg-gray-100 px-2 py-0.5 text-[10px] sm:text-xs font-mono font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                                NIP: {{ $guru->nip }}
                            </span>
                        @endif
                        <span class="inline-flex items-center rounded-full bg-brand-500/10 px-2.5 py-0.5 text-[10px] sm:text-xs font-semibold text-brand-600 dark:bg-brand-500/20 dark:text-brand-300 border border-brand-500/20">
                            {{ auth()->user()?->role === 'wali_kelas' ? 'Wali Kelas & Guru Pengajar' : 'Guru Pengajar' }}
                        </span>
                    </div>

                    <!-- Meta Tags / Info Tanggal & Sesi -->
                    <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 pt-1 text-[11px] sm:text-xs">
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 px-2.5 py-1 font-medium text-gray-700 dark:text-gray-300">
                            <i class="far fa-calendar-alt text-brand-500"></i>
                            <span>{{ $namaHari }}, {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d M Y') }}</span>
                        </span>

                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-brand-50 dark:bg-brand-500/15 px-2.5 py-1 font-semibold text-brand-700 dark:text-brand-300 border border-brand-200 dark:border-brand-500/20">
                            <i class="far fa-clock text-brand-500"></i>
                            <span>{{ $totalSesi }} Sesi Hari Ini</span>
                        </span>

                        @if($todayGuruAttendance)
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                                <i class="fas fa-check-circle text-emerald-500"></i>
                                Masuk: <strong>{{ $todayGuruAttendance->jam_masuk ? substr($todayGuruAttendance->jam_masuk, 0, 5) : '-' }}</strong>
                                @if($todayGuruAttendance->jam_pulang)
                                    | Pulang: <strong>{{ substr($todayGuruAttendance->jam_pulang, 0, 5) }}</strong>
                                @endif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-2.5 py-1 font-medium text-amber-700 dark:bg-amber-500/15 dark:text-amber-400 border border-amber-200 dark:border-amber-500/20">
                                <i class="fas fa-exclamation-triangle text-amber-500"></i> Belum presensi
                            </span>
                        @endif

                        @if($shiftGuru)
                            <span class="hidden sm:inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                <i class="fas fa-business-time text-gray-400"></i> Shift: {{ $shiftGuru->nama_shift }} ({{ substr($shiftGuru->jam_masuk, 0, 5) }} - {{ substr($shiftGuru->jam_pulang, 0, 5) }})
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Controls Kanan: Switcher Guru & Tombol Navigasi -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex items-center gap-2 lg:self-center w-full lg:w-auto">
                @if($canSwitchGuru && $allGurus->isNotEmpty())
                    <form action="{{ route('dashboard.guru') }}" method="GET" class="flex items-center w-full sm:w-auto">
                        <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                        <input type="hidden" name="hari" value="{{ $filterHari }}">
                        <div class="relative w-full sm:w-auto">
                            <select name="guru_id" onchange="this.form.submit()"
                                class="w-full sm:w-auto rounded-xl border border-gray-300 bg-gray-50 px-3 py-2 text-xs font-semibold text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 shadow-xs cursor-pointer">
                                @foreach($allGurus as $g)
                                    <option value="{{ $g->id }}" {{ ($guru && $guru->id == $g->id) ? 'selected' : '' }}>
                                        👨‍🏫 {{ $g->nama }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                @endif

                @if(in_array(auth()->user()?->role, ['admin', 'super_admin', 'waka_kurikulum', 'wali_kelas']))
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition shadow-xs text-center">
                        <i class="fas fa-th-large text-brand-500"></i>
                        <span>Dashboard Utama</span>
                    </a>
                @endif

                <a href="{{ route('absensi-pelajaran.index', ['tanggal' => $tanggal]) }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-600 px-4 py-2 text-xs font-bold text-white transition shadow-sm text-center">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Semua Jadwal Mapel</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ========================= KPI METRIK KBM HARI INI ========================= -->
    <div class="mb-5 sm:mb-6 grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-5">
        <!-- 1. Total Sesi Hari Ini -->
        <div class="rounded-2xl border border-gray-200 bg-white p-3.5 sm:p-5 shadow-sm dark:border-gray-800 dark:bg-boxdark transition hover:border-brand-400">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block truncate">Total Sesi</span>
                    <h4 class="mt-1 text-2xl sm:text-3xl font-extrabold text-gray-900 dark:text-white">
                        {{ $totalSesi }} <span class="text-xs sm:text-sm font-normal text-gray-400">Sesi</span>
                    </h4>
                </div>
                <div class="flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-xl sm:rounded-2xl bg-brand-500 text-white shadow-md shadow-brand-500/20 shrink-0">
                    <i class="fas fa-chalkboard-teacher text-base sm:text-xl"></i>
                </div>
            </div>
            <p class="mt-2 sm:mt-3 text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 truncate">
                <i class="fas fa-calendar-day text-brand-500"></i> Jadwal hari <strong>{{ $filterHari }}</strong>
            </p>
        </div>

        <!-- 2. Selesai Diabsen -->
        <div class="rounded-2xl border border-gray-200 bg-white p-3.5 sm:p-5 shadow-sm dark:border-gray-800 dark:bg-boxdark transition hover:border-emerald-400">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block truncate">Selesai Diabsen</span>
                    <h4 class="mt-1 text-2xl sm:text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">
                        {{ $sudahDiabsen }} <span class="text-xs sm:text-sm font-normal text-gray-400">Sesi</span>
                    </h4>
                </div>
                <div class="flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-xl sm:rounded-2xl bg-emerald-500 text-white shadow-md shadow-emerald-500/20 shrink-0">
                    <i class="fas fa-user-check text-base sm:text-xl"></i>
                </div>
            </div>
            <div class="mt-2 sm:mt-3 flex items-center justify-between text-[11px] sm:text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <span>{{ $totalSesi > 0 ? round(($sudahDiabsen / $totalSesi) * 100) : 0 }}% Selesai</span>
                <span class="text-gray-400 font-normal hidden sm:inline">Telah Diinput</span>
            </div>
        </div>

        <!-- 3. Belum Diabsen -->
        <div class="rounded-2xl border border-gray-200 bg-white p-3.5 sm:p-5 shadow-sm dark:border-gray-800 dark:bg-boxdark transition hover:border-amber-400">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block truncate">Belum Diabsen</span>
                    <h4 class="mt-1 text-2xl sm:text-3xl font-extrabold {{ $belumDiabsen > 0 ? 'text-amber-500 dark:text-amber-400' : 'text-gray-800 dark:text-gray-200' }}">
                        {{ $belumDiabsen }} <span class="text-xs sm:text-sm font-normal text-gray-400">Sesi</span>
                    </h4>
                </div>
                <div class="flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-xl sm:rounded-2xl shrink-0 {{ $belumDiabsen > 0 ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400' }}">
                    <i class="fas fa-clock text-base sm:text-xl"></i>
                </div>
            </div>
            <p class="mt-2 sm:mt-3 text-[11px] sm:text-xs truncate {{ $belumDiabsen > 0 ? 'text-amber-600 dark:text-amber-400 font-semibold' : 'text-gray-400' }}">
                {{ $belumDiabsen > 0 ? '⚠️ Perlu diinput' : '✅ Lengkap' }}
            </p>
        </div>

        <!-- 4. Jurnal Terisi -->
        <div class="rounded-2xl border border-gray-200 bg-white p-3.5 sm:p-5 shadow-sm dark:border-gray-800 dark:bg-boxdark transition hover:border-purple-400">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[10px] sm:text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 block truncate">Jurnal Terisi</span>
                    <h4 class="mt-1 text-2xl sm:text-3xl font-extrabold text-purple-600 dark:text-purple-400">
                        {{ $jurnalTerisi }}/{{ $totalSesi }} <span class="text-xs sm:text-sm font-normal text-gray-400">Materi</span>
                    </h4>
                </div>
                <div class="flex h-10 w-10 sm:h-12 sm:w-12 items-center justify-center rounded-xl sm:rounded-2xl bg-purple-500 text-white shadow-md shadow-purple-500/20 shrink-0">
                    <i class="fas fa-book-open text-base sm:text-xl"></i>
                </div>
            </div>
            <p class="mt-2 sm:mt-3 text-[11px] sm:text-xs text-gray-500 dark:text-gray-400 truncate">
                Materi tercatat hari ini
            </p>
        </div>
    </div>

    <!-- ========================= SELECTOR HARI & TANGGAL ========================= -->
    <div class="mb-5 sm:mb-6 rounded-2xl border border-gray-200 bg-white p-3.5 sm:p-5 shadow-sm dark:border-gray-800 dark:bg-boxdark">
        <form action="{{ route('dashboard.guru') }}" method="GET" class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 sm:gap-4">
            @if($canSwitchGuru && $guru)
                <input type="hidden" name="guru_id" value="{{ $guru->id }}">
            @endif

            <!-- Pill Selector Hari dengan Scroll Horizontal Halus di Mobile -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 lg:pb-0 -mx-1 px-1 no-scrollbar touch-pan-x">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 shrink-0 mr-1 flex items-center gap-1">
                    <i class="fas fa-calendar-week text-brand-500"></i> <span class="hidden sm:inline">Hari:</span>
                </span>
                @foreach($hariList as $h)
                    @php
                        $isSelectedHari = ($filterHari === $h);
                        $countH = $sesiPerHari[$h] ?? 0;
                    @endphp
                    <button type="submit" name="hari" value="{{ $h }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl text-xs font-bold transition shadow-xs shrink-0 {{ $isSelectedHari ? 'bg-brand-500 text-white shadow-md' : 'bg-gray-100 hover:bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <span>{{ $h }}</span>
                        @if($countH > 0)
                            <span class="inline-flex items-center justify-center px-1.5 py-0.2 rounded-full text-[10px] font-extrabold {{ $isSelectedHari ? 'bg-white text-brand-600' : 'bg-brand-500 text-white' }}">
                                {{ $countH }}
                            </span>
                        @else
                            <span class="text-[10px] opacity-60">0</span>
                        @endif
                    </button>
                @endforeach
            </div>

            <!-- Date Picker Tanggal KBM -->
            <div class="flex items-center justify-between sm:justify-start gap-2 pt-2 lg:pt-0 border-t lg:border-t-0 border-gray-100 dark:border-gray-800">
                <label class="text-xs font-bold text-gray-600 dark:text-gray-300 whitespace-nowrap flex items-center gap-1">
                    <i class="far fa-calendar-alt text-brand-500"></i> Tanggal:
                </label>
                <div class="flex items-center gap-2">
                    <input type="date" name="tanggal" value="{{ $tanggal }}" onchange="this.form.submit()"
                        class="rounded-xl border border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-900 px-2.5 py-1.5 sm:px-3 sm:py-2 text-xs font-medium text-gray-800 dark:text-white outline-none focus:border-brand-500 shadow-xs">
                    @if($tanggal !== \Carbon\Carbon::today()->format('Y-m-d'))
                        <a href="{{ route('dashboard.guru') }}" class="rounded-xl border border-gray-300 bg-white px-2.5 py-1.5 sm:px-3 sm:py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition shadow-xs shrink-0" title="Kembali ke Hari Ini">
                            Hari Ini
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- ========================= MAIN CONTENT: DAFTAR SESI MENGAJAR HARI INI ========================= -->
    <div class="space-y-4 mb-8">
        <div class="flex items-center justify-between pb-2">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span>📚 Jadwal Mengajar Hari {{ $filterHari }}</span>
                <span class="rounded-full bg-brand-500/10 px-3 py-0.5 text-xs font-bold text-brand-600 dark:text-brand-300 border border-brand-500/20">
                    {{ $jadwals->count() }} Sesi
                </span>
            </h3>
            <span class="text-xs text-gray-500 hidden sm:inline">Pilih jadwal untuk input absensi siswa dan jurnal KBM</span>
        </div>

        @forelse($jadwals as $j)
            @php
                $isLive = $j->is_live;
                $isAttended = $j->is_attended;
                $isJurnalFilled = $j->is_jurnal_filled;
                $abs = $j->absensi_summary;
                $totalSiswaKelas = $j->kelas?->siswas?->count() ?? 0;
            @endphp

            <div class="rounded-2xl border transition-all duration-200 shadow-sm overflow-hidden {{ $isLive ? 'border-brand-500 ring-2 ring-brand-500/20 bg-gradient-to-r from-brand-50/40 via-white to-white dark:from-brand-500/10 dark:via-boxdark dark:to-boxdark' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-800 dark:bg-boxdark' }}">
                <div class="p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4 sm:gap-5">
                    <!-- Sisi Kiri: Waktu, Mapel, Kelas & Live Badge -->
                    <div class="flex items-start gap-3 sm:gap-4 min-w-0">
                        <!-- Waktu / Clock Badge -->
                        <div class="flex flex-col items-center justify-center rounded-xl sm:rounded-2xl p-2 sm:p-3 min-w-[76px] sm:min-w-[100px] text-center shrink-0 {{ $isLive ? 'bg-brand-500 text-white shadow-md' : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700' }}">
                            <span class="text-[10px] sm:text-[11px] font-bold tracking-wider uppercase opacity-80">
                                {{ $j->jamPelajaran?->nama_jam ?: 'Jam KBM' }}
                            </span>
                            <span class="text-sm sm:text-base font-extrabold font-mono mt-0.5">
                                {{ substr($j->jam_mulai, 0, 5) }}
                            </span>
                            <span class="text-[11px] sm:text-xs opacity-75 font-mono">
                                s/d {{ substr($j->jam_selesai, 0, 5) }}
                            </span>
                            @if(!empty($j->jeda_istirahat_info['has_istirahat']))
                                <span class="mt-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-200 border border-amber-200 dark:border-amber-700 whitespace-nowrap" title="Jeda Istirahat: {{ $j->jeda_istirahat_info['istirahat_text'] }}">
                                    ☕ {{ $j->jeda_istirahat_info['istirahat_text'] }}
                                </span>
                            @endif
                        </div>

                        <!-- Info Mata Pelajaran & Kelas -->
                        <div class="space-y-1 sm:space-y-1.5 min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                                <h4 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate">
                                    {{ $j->mapel?->nama_mapel ?? 'Mata Pelajaran' }}
                                </h4>

                                <!-- Live Badge -->
                                @if($isLive)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] sm:text-xs font-bold text-rose-600 dark:bg-rose-500/15 dark:text-rose-400 animate-pulse border border-rose-200 dark:border-rose-500/20">
                                        <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                        Sedang Berlangsung
                                    </span>
                                @endif

                                <!-- Status Absen Badge -->
                                @if($isAttended)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-[10px] sm:text-xs font-semibold text-success-700 dark:bg-success-500/15 dark:text-success-400 border border-success-200 dark:border-success-500/20" style="background-color: #ecfdf3; color: #027a48; border-color: #a6f4c5;">
                                        <i class="fas fa-check-circle" style="color: #12b76a;"></i> Sudah Diabsen
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-0.5 text-[10px] sm:text-xs font-semibold text-warning-700 dark:bg-warning-500/15 dark:text-warning-400 border border-warning-200 dark:border-warning-500/20" style="background-color: #fffaeb; color: #b54708; border-color: #fedf89;">
                                        <i class="fas fa-exclamation-circle" style="color: #f79009;"></i> Belum Diabsen
                                    </span>
                                @endif
                            </div>

                            <!-- Meta Kelas, Jam Ke, & Ruangan -->
                            <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 text-xs">
                                <span class="inline-flex items-center gap-1 sm:gap-1.5 font-bold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-800 px-2.5 py-0.5 sm:px-3 sm:py-1 rounded-lg border border-gray-200 dark:border-gray-700 text-[11px] sm:text-xs">
                                    <i class="fas fa-users text-brand-500" style="color: #4f6bed;"></i> 
                                    @if(!empty($j->is_multi_kelas))
                                        {{ implode(', ', $j->parallel_kelases ?? [$j->kelas?->nama_kelas]) }}
                                    @else
                                        {{ $j->kelas?->nama_kelas ?? '-' }}
                                    @endif
                                    <span class="text-gray-500 font-normal">({{ $j->total_siswa_gabungan ?? $totalSiswaKelas }} Siswa)</span>
                                </span>

                                @if(!empty($j->is_multi_kelas))
                                    <span class="inline-flex items-center gap-1 rounded-lg bg-brand-50 px-2 py-0.5 text-[11px] font-bold text-brand-700 dark:bg-brand-500/15 dark:text-brand-300 border border-brand-200 dark:border-brand-500/20" title="Sesi Gabungan {{ $j->parallel_count ?? count($j->parallel_kelases ?? []) }} Kelas">
                                        <i class="fas fa-layer-group text-[10px] text-brand-500" style="color: #4f6bed;"></i> Gabungan ({{ $j->parallel_count ?? count($j->parallel_kelases ?? []) }} Kelas)
                                    </span>
                                @endif

                                @if($j->jam_ke)
                                    <span class="inline-flex items-center font-mono font-bold text-brand-700 dark:text-brand-300 bg-brand-50 dark:bg-brand-500/15 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg border border-brand-200 dark:border-brand-500/20 text-[11px] sm:text-xs">
                                        Ke-{{ $j->jam_ke }}
                                    </span>
                                @endif

                                @if(!empty($j->jeda_istirahat_info['has_istirahat']))
                                    <span class="inline-flex items-center gap-1 font-mono font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-500/15 px-2 py-0.5 sm:px-2.5 sm:py-1 rounded-lg border border-amber-200 dark:border-amber-500/30 text-[11px] sm:text-xs" title="Jeda Istirahat di Sela KBM: {{ $j->jeda_istirahat_info['istirahat_text'] }} ({{ $j->jeda_istirahat_info['segments_format'] }})">
                                        <i class="fas fa-coffee text-amber-500 text-[11px]"></i>
                                        {{ $j->jeda_istirahat_info['istirahat_label'] }}
                                    </span>
                                @endif

                                @if($j->ruangan)
                                    <span class="inline-flex items-center gap-1 text-gray-600 dark:text-gray-400 text-[11px] sm:text-xs">
                                        <i class="fas fa-door-open text-gray-400"></i> {{ $j->ruangan }}
                                    </span>
                                @endif
                            </div>

                            <!-- Rekap Presensi Siswa -->
                            <div class="flex flex-wrap items-center gap-1 sm:gap-1.5 text-xs pt-0.5">
                                @if($isAttended)
                                    <span class="text-gray-500 mr-0.5 text-[11px] sm:text-xs font-semibold">Kehadiran:</span>
                                    <span class="px-2 py-0.5 rounded-md bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-400 font-bold border border-success-200 dark:border-success-500/20 text-[11px]" style="background-color: #ecfdf3; color: #027a48; border-color: #a6f4c5;" title="Hadir">
                                        {{ $abs->count_h ?? 0 }} Hadir
                                    </span>
                                    @if(($abs->count_s ?? 0) > 0)
                                        <span class="px-2 py-0.5 rounded-md bg-blue-light-50 text-blue-light-700 dark:bg-blue-light-500/15 dark:text-blue-light-400 font-bold border border-blue-light-200 dark:border-blue-light-500/20 text-[11px]" style="background-color: #f0f9ff; color: #026aa2; border-color: #b9e6fe;" title="Sakit">
                                            {{ $abs->count_s }} Sakit
                                        </span>
                                    @endif
                                    @if(($abs->count_i ?? 0) > 0)
                                        <span class="px-2 py-0.5 rounded-md bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-400 font-bold border border-warning-200 dark:border-warning-500/20 text-[11px]" style="background-color: #fffaeb; color: #b54708; border-color: #fedf89;" title="Izin">
                                            {{ $abs->count_i }} Izin
                                        </span>
                                    @endif
                                    @if(($abs->count_a ?? 0) > 0)
                                        <span class="px-2 py-0.5 rounded-md bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-400 font-bold border border-error-200 dark:border-error-500/20 text-[11px]" style="background-color: #fef3f2; color: #b42318; border-color: #fecdca;" title="Alpa">
                                            {{ $abs->count_a }} Alpa
                                        </span>
                                    @endif
                                    @if(($abs->count_t ?? 0) > 0)
                                        <span class="px-2 py-0.5 rounded-md bg-orange-50 text-orange-700 dark:bg-orange-500/15 dark:text-orange-400 font-bold border border-orange-200 dark:border-orange-500/20 text-[11px]" style="background-color: #fff6ed; color: #c4320a; border-color: #fddcab;" title="Terlambat">
                                            {{ $abs->count_t }} Telat
                                        </span>
                                    @endif
                                @else
                                    <span class="text-[11px] sm:text-xs text-warning-600 dark:text-warning-400 font-medium" style="color: #dc6803;">
                                        <i class="fas fa-exclamation-circle mr-1"></i> Presensi siswa belum diinput
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Sisi Kanan: Cuplikan Jurnal & Tombol Aksi -->
                    <div class="flex flex-col sm:flex-row lg:flex-col xl:flex-row items-stretch sm:items-center lg:items-end xl:items-center gap-2.5 sm:gap-3 pt-3 lg:pt-0 border-t lg:border-t-0 border-gray-100 dark:border-gray-800 w-full lg:w-auto">
                        <!-- Cuplikan Jurnal KBM -->
                        <div class="w-full sm:w-64 lg:w-56 xl:w-72 p-2.5 sm:p-3 rounded-xl border border-gray-200 bg-gray-50/80 dark:border-gray-800 dark:bg-gray-900/40 text-xs">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-gray-800 dark:text-gray-200 flex items-center gap-1.5 text-[11px] sm:text-xs">
                                    <i class="fas fa-book-open text-brand-500" style="color: #4f6bed;"></i> Jurnal KBM:
                                </span>
                                @if($isJurnalFilled)
                                    <span class="text-[10px] font-bold text-success-600 dark:text-success-400 flex items-center gap-1" style="color: #039855;">
                                        <i class="fas fa-check"></i> Terisi
                                    </span>
                                @else
                                    <span class="text-[10px] font-bold text-warning-600 dark:text-warning-400" style="color: #dc6803;">
                                        Kosong
                                    </span>
                                @endif
                            </div>
                            <p class="text-gray-600 dark:text-gray-400 line-clamp-2 text-[11px] sm:text-xs leading-relaxed">
                                {{ $j->jurnal_today?->materi ?: 'Belum ada ringkasan materi KBM yang diisi.' }}
                            </p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:flex-col xl:flex-row items-stretch gap-2 w-full sm:w-auto">
                            <!-- Tombol 1: Input / Edit Presensi & Jurnal Terpadu -->
                            <a href="{{ route('absensi-pelajaran.presensi', ['jadwal' => $j->id, 'tanggal' => $tanggal]) }}"
                                class="inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold text-white transition shadow-sm text-center {{ $isAttended ? 'bg-brand-500 hover:bg-brand-600' : 'bg-brand-500 hover:bg-brand-600' }}"
                                style="background-color: #4f6bed; color: #ffffff;"
                                onmouseover="this.style.backgroundColor='#3d57d6'"
                                onmouseout="this.style.backgroundColor='#4f6bed'">
                                <i class="fas fa-{{ $isAttended ? 'edit' : 'clipboard-check' }}"></i>
                                <span>{{ $isAttended ? 'Update Absen & Jurnal' : 'Input Absen & Jurnal' }}</span>
                            </a>

                            <!-- Tombol 2: Quick Jurnal Pop-up -->
                            <button type="button"
                                @click="openJurnalModal('{{ $j->id }}', '{{ addslashes($j->mapel?->nama_mapel ?? '') }}', '{{ addslashes(!empty($j->is_multi_kelas) ? implode(', ', $j->parallel_kelases ?? [$j->kelas?->nama_kelas]) : ($j->kelas?->nama_kelas ?? '')) }}', '{{ addslashes($j->jurnal_today?->materi ?? '') }}', '{{ addslashes($j->jurnal_today?->catatan ?? '') }}')"
                                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-gray-300 bg-white px-3.5 py-2.5 text-xs font-bold text-gray-700 hover:bg-brand-50 hover:text-brand-600 hover:border-brand-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 transition shadow-2xs text-center"
                                title="Input Materi & Catatan Jurnal Saja">
                                <i class="fas fa-edit text-brand-500" style="color: #4f6bed;"></i>
                                <span>Jurnal Cepat</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <!-- EMPTY STATE CERDAS & JADWAL MINGGUAN LANGSUNG MUNCUL -->
            <div class="rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm dark:border-gray-800 dark:bg-boxdark">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10 mb-4 shadow-xs">
                    <i class="fas fa-mug-hot text-2xl"></i>
                </div>
                <h4 class="text-xl font-bold text-gray-900 dark:text-white">
                    Tidak Ada Jam Mengajar di Hari {{ $filterHari }}
                </h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1.5 max-w-lg mx-auto">
                    Anda tidak memiliki jadwal tatap muka kelas pada hari ini. Total beban mengajar Anda adalah <strong>{{ $totalSesiMingguan }} sesi</strong> dalam seminggu.
                </p>

                @if($nextDayWithJadwal && $nextDayWithJadwal !== $filterHari)
                    <div class="mt-5">
                        <a href="{{ route('dashboard.guru', ['hari' => $nextDayWithJadwal]) }}"
                            class="inline-flex items-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-600 px-5 py-2.5 text-xs font-bold text-white shadow-md transition">
                            <i class="fas fa-arrow-right"></i>
                            <span>Buka Jadwal Hari {{ $nextDayWithJadwal }} ({{ $sesiPerHari[$nextDayWithJadwal] ?? 0 }} Sesi)</span>
                        </a>
                    </div>
                @endif
            </div>

            <!-- JADWAL MINGGUAN GURU LANGSUNG DIPERLIHATKAN AGAR TIDAK KOSONG -->
            @if($totalSesiMingguan > 0)
                <div class="mt-6 rounded-2xl border border-brand-500/30 bg-gradient-to-br from-brand-50/20 via-white to-brand-50/30 dark:from-boxdark dark:via-boxdark dark:to-brand-950/20 p-5 shadow-sm">
                    <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-brand-500 text-white flex items-center justify-center text-sm shadow-xs">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white text-base">
                                    Jadwal Mengajar Anda Minggu Ini ({{ $totalSesiMingguan }} Sesi KBM)
                                </h4>
                                <p class="text-xs text-gray-500">Klik tombol hari atau kartu di bawah untuk membuka jadwal dan menginput presensi:</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as $d)
                            @php
                                $dJadwals = $jadwalMingguan->get($d, collect());
                            @endphp
                            <div class="rounded-xl border {{ $dJadwals->isNotEmpty() ? 'border-brand-300 dark:border-brand-700 bg-white dark:bg-gray-900 shadow-xs' : 'border-gray-200 dark:border-gray-800/80 bg-gray-50/50 dark:bg-gray-900/20' }} p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full {{ $dJadwals->isNotEmpty() ? 'bg-brand-500' : 'bg-gray-300' }}"></span>
                                        <span class="font-bold text-sm text-gray-900 dark:text-white">{{ $d }}</span>
                                    </div>
                                    <span class="text-xs font-bold px-2 py-0.5 rounded-md {{ $dJadwals->isNotEmpty() ? 'bg-brand-50 text-brand-600 dark:bg-brand-500/20 dark:text-brand-300' : 'bg-gray-100 text-gray-400' }}">
                                        {{ $dJadwals->count() }} Sesi
                                    </span>
                                </div>

                                @if($dJadwals->isNotEmpty())
                                    <div class="space-y-2">
                                        @foreach($dJadwals as $dj)
                                            <div class="p-2.5 rounded-lg border border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/50 text-xs flex items-center justify-between gap-2">
                                                <div>
                                                    <div class="font-bold text-gray-900 dark:text-white">
                                                        {{ $dj->mapel?->nama_mapel }}
                                                    </div>
                                                    <div class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-2 font-mono">
                                                        <span><i class="far fa-clock text-brand-500"></i> {{ substr($dj->jam_mulai, 0, 5) }} - {{ substr($dj->jam_selesai, 0, 5) }}</span>
                                                        <span>•</span>
                                                        <span><strong>{{ $dj->kelas?->nama_kelas }}</strong></span>
                                                    </div>
                                                </div>
                                                <a href="{{ route('dashboard.guru', ['hari' => $d]) }}"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-brand-500 hover:bg-brand-600 text-white font-bold text-[11px] transition shadow-xs whitespace-nowrap">
                                                    Buka
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-gray-400 italic text-center py-3">Tidak ada jadwal KBM</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforelse
    </div>

    <!-- ========================= TABS BAWAH: RIWAYAT JURNAL TERBARU ========================= -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-800 dark:bg-boxdark mb-8">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100 dark:border-gray-800">
            <h4 class="font-bold text-gray-900 dark:text-white text-base flex items-center gap-2">
                <i class="fas fa-history text-brand-500"></i> Jurnal Mengajar Terakhir Saya
            </h4>
            <a href="{{ route('absensi-pelajaran.rekap') }}" class="text-xs text-brand-600 dark:text-brand-400 hover:underline font-bold">
                Lihat Rekap Lengkap &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($recentJurnals as $rj)
                <div class="p-4 rounded-xl border border-gray-200/80 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/30 space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-gray-900 dark:text-white">
                            {{ $rj->jadwal?->mapel?->nama_mapel ?? 'Mapel' }}
                        </span>
                        <span class="font-mono text-gray-400 text-[11px]">
                            {{ $rj->tanggal->translatedFormat('d M Y') }}
                        </span>
                    </div>
                    <div class="text-xs text-brand-600 dark:text-brand-400 font-semibold">
                        Kelas: {{ $rj->jadwal?->kelas?->nama_kelas ?? 'Kelas' }}
                    </div>
                    <p class="text-xs text-gray-700 dark:text-gray-300 line-clamp-2">
                        <strong>Materi:</strong> {{ $rj->materi }}
                    </p>
                    @if($rj->catatan)
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 line-clamp-1 italic">
                            Catatan: {{ $rj->catatan }}
                        </p>
                    @endif
                </div>
            @empty
                <div class="col-span-full text-center py-6 text-gray-400 text-xs">
                    <i class="fas fa-book-open text-2xl mb-2 block opacity-40"></i>
                    Belum ada riwayat jurnal mengajar yang tersimpan.
                </div>
            @endforelse
        </div>
    </div>

    <!-- ========================= MODAL QUICK JURNAL KBM ========================= -->
    <div x-show="showQuickJurnalModal" style="display: none;"
        class="fixed inset-0 z-99999 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        
        <div class="relative w-full max-w-lg rounded-2xl bg-white p-4 sm:p-6 shadow-xl dark:bg-boxdark border border-gray-200 dark:border-gray-800 max-h-[90vh] overflow-y-auto"
            @click.away="showQuickJurnalModal = false">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100 dark:border-gray-800">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-book-open text-brand-500"></i> Input / Update Jurnal KBM
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        <span x-text="selectedJadwalNama"></span> • <span x-text="selectedJadwalKelas"></span>
                    </p>
                </div>
                <button type="button" @click="showQuickJurnalModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <form action="{{ route('dashboard.guru.quick-jurnal') }}" method="POST">
                @csrf
                <input type="hidden" name="jadwal_pelajaran_id" :value="selectedJadwalId">
                <input type="hidden" name="tanggal" :value="selectedTanggal">

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1.5">
                            Materi Pembelajaran / Topik KBM <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="materi" rows="3" required x-model="selectedJadwalMateri"
                            placeholder="Contoh: Bab 2 Trigonometri Dasar, Latihan Soal No. 1-10, Praktik Kelompok..."
                            class="w-full rounded-xl border border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-900 p-3 text-sm outline-none focus:border-brand-500 dark:text-white"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300 mb-1.5">
                            Catatan / Hambatan Pembelajaran (Opsional)
                        </label>
                        <textarea name="catatan" rows="2" x-model="selectedJadwalCatatan"
                            placeholder="Contoh: Sebagian siswa butuh remidi pada materi kuadran, KBM berjalan kondusif..."
                            class="w-full rounded-xl border border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-900 p-3 text-sm outline-none focus:border-brand-500 dark:text-white"></textarea>
                    </div>

                    <div class="p-3 rounded-xl bg-blue-50 border border-blue-200 dark:bg-blue-950/30 dark:border-blue-900 text-xs text-blue-900 dark:text-blue-300 flex items-start gap-2">
                        <i class="fas fa-info-circle mt-0.5 text-blue-500"></i>
                        <span>
                            Untuk menginput daftar hadir siswa (Hadir, Sakit, Izin, Alpa), silakan klik tombol <strong>"Update Absen & Jurnal"</strong> pada kartu jadwal.
                        </span>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button type="button" @click="showQuickJurnalModal = false"
                            class="px-4 py-2 rounded-xl border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 transition">
                            Batal
                        </button>
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 px-5 py-2 rounded-xl bg-brand-500 text-xs font-bold text-white hover:bg-brand-600 transition shadow-sm">
                            <i class="fas fa-save"></i> Simpan Jurnal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
