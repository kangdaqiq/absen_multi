@extends('layouts.app')

@section('title', 'Absensi Pembelajaran (KBM)')

@section('content')
    <x-page-header 
        title="Absensi Pembelajaran (KBM)" 
        subtitle="Presensi kehadiran siswa dan pengisian jurnal mengajar per jam pelajaran" 
        icon="fas fa-chalkboard-teacher"
        x-data="{}">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('jadwal-pelajaran.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-center font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-sm text-sm">
                <i class="fas fa-calendar-alt text-brand-500"></i> Kelola Jadwal
            </a>
            <a href="{{ route('absensi-pelajaran.rekap') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-center font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-sm text-sm">
                <i class="fas fa-chart-bar text-brand-500"></i> Rekap Absensi
            </a>
        </div>
    </x-page-header>

    @if(session('success'))
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-success-200 bg-success-50 p-4 dark:border-success-500/20 dark:bg-success-500/10 text-success-800 dark:text-success-400">
            <i class="fas fa-check-circle text-lg"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Filter & Date Card -->
    <div class="mb-6 rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    <i class="fas fa-filter text-xs"></i>
                </div>
                <div>
                    <h6 class="font-semibold text-gray-800 dark:text-white/90 text-sm">Filter Absensi Pembelajaran</h6>
                </div>
            </div>
            @if(request()->hasAny(['guru_id', 'kelas_id']) || (request('tanggal') && request('tanggal') !== date('Y-m-d')))
                <a href="{{ route('absensi-pelajaran.index') }}" 
                   class="inline-flex items-center gap-1.5 text-xs text-error-600 hover:text-error-700 dark:text-error-400 font-medium hover:underline">
                    <i class="fas fa-times"></i> Reset Filter
                </a>
            @endif
        </div>
        <div class="p-5">
            <form action="{{ route('absensi-pelajaran.index') }}" method="GET" class="flex flex-wrap items-end gap-4">
                <!-- Tanggal -->
                <div class="w-full sm:w-48">
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Pilih Tanggal</label>
                    <input type="date" name="tanggal" value="{{ $tanggal }}" onchange="this.form.submit()"
                        class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                </div>

                <!-- Hari Aktif Info -->
                <div class="flex items-center gap-2 pb-0.5">
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-brand-50 px-3 py-2 text-xs font-bold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                        <i class="fas fa-calendar-day"></i> Hari: {{ $namaHari }}
                    </span>
                </div>

                @if($isAdminOrWaka)
                    <!-- Filter Guru (Khusus Admin/Waka) -->
                    <div class="w-full sm:w-56">
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Guru Pengajar</label>
                        <div class="relative">
                            <select name="guru_id" onchange="this.form.submit()"
                                class="w-full appearance-none rounded-xl border border-gray-200 bg-transparent py-2 pl-3.5 pr-8 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white cursor-pointer {{ request('guru_id') ? 'border-brand-500 bg-brand-50/20 font-semibold text-brand-600' : '' }}">
                                <option value="">Semua Guru</option>
                                @foreach($gurus as $g)
                                    <option value="{{ $g->id }}" {{ request('guru_id') == $g->id ? 'selected' : '' }}>{{ $g->nama }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                                <i class="fas fa-chevron-down text-[10px]"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Kelas -->
                    <div class="w-full sm:w-44">
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Kelas</label>
                        <div class="relative">
                            <select name="kelas_id" onchange="this.form.submit()"
                                class="w-full appearance-none rounded-xl border border-gray-200 bg-transparent py-2 pl-3.5 pr-8 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white cursor-pointer {{ request('kelas_id') ? 'border-brand-500 bg-brand-50/20 font-semibold text-brand-600' : '' }}">
                                <option value="">Semua Kelas</option>
                                @foreach($kelases as $k)
                                    <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                                <i class="fas fa-chevron-down text-[10px]"></i>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="ml-auto pb-1 text-xs text-gray-500 dark:text-gray-400">
                    Jadwal hari <strong class="text-gray-800 dark:text-white">{{ $namaHari }}</strong>, {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d F Y') }}
                </div>
            </form>
        </div>
    </div>

    <!-- Grid / Cards Jadwal Mengajar Hari Ini -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($jadwals as $j)
            <div class="rounded-2xl border {{ $j->is_attended ? 'border-success-200 dark:border-success-500/20' : 'border-gray-200 dark:border-gray-800' }} bg-white p-5 shadow-theme-sm dark:bg-gray-dark flex flex-col justify-between transition hover:shadow-md">
                <div>
                    <!-- Top Bar: Jam & Badge Status -->
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex rounded-md bg-brand-50 px-2 py-1 text-xs font-bold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                                Jam {{ $j->jam_ke ? 'Ke-' . $j->jam_ke : '' }}
                            </span>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                <i class="far fa-clock mr-1"></i>{{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}
                            </span>
                        </div>

                        @if($j->is_attended)
                            <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-1 text-[11px] font-semibold text-success-700 dark:bg-success-500/15 dark:text-success-400 border border-success-200 dark:border-success-500/20">
                                <i class="fas fa-check-circle"></i> Sudah Diabsen
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-1 text-[11px] font-semibold text-warning-700 dark:bg-warning-500/15 dark:text-warning-400 border border-warning-200 dark:border-warning-500/20">
                                <i class="fas fa-exclamation-circle"></i> Belum Diabsen
                            </span>
                        @endif
                    </div>

                    <!-- Subject & Class Name -->
                    <h3 class="text-lg font-bold text-gray-800 dark:text-white/90 flex items-center gap-2">
                        <span>{{ $j->mapel?->nama_mapel ?? 'Mata Pelajaran' }}</span>
                    </h3>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                        <span class="inline-flex rounded-lg bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-700 dark:bg-blue-500/15 dark:text-blue-400">
                            Kelas {{ $j->kelas?->nama_kelas ?? '-' }}
                        </span>
                        @if(!empty($j->is_multi_kelas))
                            <span class="inline-flex items-center gap-1 rounded-lg bg-purple-50 px-2 py-0.5 text-[11px] font-bold text-purple-700 dark:bg-purple-500/15 dark:text-purple-300 border border-purple-200 dark:border-purple-500/20" title="Digabung dengan kelas: {{ implode(', ', $j->parallel_kelases ?? []) }}">
                                <i class="fas fa-layer-group text-[10px]"></i> Gabungan ({{ implode(', ', $j->parallel_kelases ?? []) }})
                            </span>
                        @endif
                        @if($j->ruangan)
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                <i class="fas fa-map-marker-alt text-gray-400 mr-1"></i>{{ $j->ruangan }}
                            </span>
                        @endif
                    </div>

                    <!-- Teacher info (jika login sebagai admin / waka) -->
                    @if($isAdminOrWaka)
                        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 text-xs text-gray-600 dark:text-gray-300 flex items-center gap-2">
                            <i class="fas fa-chalkboard-teacher text-gray-400"></i>
                            <span class="font-medium">{{ $j->guru?->nama ?? 'Guru Pengajar' }}</span>
                        </div>
                    @endif

                    <!-- Jurnal KBM Preview jika sudah diisi -->
                    @if($j->jurnal_today)
                        <div class="mt-3 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800 text-xs space-y-1">
                            <div class="text-gray-500 dark:text-gray-400 font-medium">Materi Pokok:</div>
                            <div class="font-semibold text-gray-800 dark:text-gray-200 line-clamp-2">
                                {{ $j->jurnal_today->materi }}
                            </div>
                        </div>
                    @endif

                    <!-- Stats mini badges jika sudah diabsen -->
                    @if($j->is_attended && $j->absensi_summary)
                        <div class="mt-3 flex flex-wrap gap-1.5 text-[11px] font-semibold">
                            <span class="px-2 py-0.5 rounded bg-success-50 text-success-700 border border-success-200 dark:bg-success-500/10 dark:text-success-400 dark:border-success-500/20">
                                H: {{ $j->absensi_summary->count_h }}
                            </span>
                            <span class="px-2 py-0.5 rounded bg-warning-50 text-warning-700 border border-warning-200 dark:bg-warning-500/10 dark:text-warning-400 dark:border-warning-500/20">
                                S: {{ $j->absensi_summary->count_s }}
                            </span>
                            <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-500/10 dark:text-blue-400 dark:border-blue-500/20">
                                I: {{ $j->absensi_summary->count_i }}
                            </span>
                            <span class="px-2 py-0.5 rounded bg-error-50 text-error-700 border border-error-200 dark:bg-error-500/10 dark:text-error-400 dark:border-error-500/20">
                                A: {{ $j->absensi_summary->count_a }}
                            </span>
                            @if($j->absensi_summary->count_t > 0)
                                <span class="px-2 py-0.5 rounded bg-orange-50 text-orange-700 border border-orange-200 dark:bg-orange-500/10 dark:text-orange-400 dark:border-orange-500/20">
                                    T: {{ $j->absensi_summary->count_t }}
                                </span>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Action Button -->
                <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $j->kelas?->siswas_count ?? $j->kelas?->siswas()->count() }} Siswa Terdaftar
                    </span>
                    <a href="{{ route('absensi-pelajaran.presensi', ['jadwal' => $j->id, 'tanggal' => $tanggal]) }}"
                        class="inline-flex items-center gap-1.5 rounded-lg {{ $j->is_attended ? 'bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200' : 'bg-brand-500 text-white hover:bg-brand-600' }} px-3.5 py-2 text-xs font-bold transition shadow-sm">
                        <i class="fas {{ $j->is_attended ? 'fa-edit' : 'fa-clipboard-check' }}"></i>
                        <span>{{ $j->is_attended ? 'Edit Presensi' : 'Input Presensi Siswa' }}</span>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-gray-200 bg-white p-12 text-center shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500 text-2xl mb-4">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h4 class="text-base font-bold text-gray-800 dark:text-white/90">Tidak Ada Jadwal Pembelajaran</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                    Tidak ditemukan jadwal mengajar untuk hari {{ $namaHari }} ({{ \Carbon\Carbon::parse($tanggal)->format('d/m/Y') }}).
                </p>
                @if(in_array(auth()->user()->role, ['admin', 'super_admin', 'waka_kurikulum']))
                    <div class="mt-5">
                        <a href="{{ route('jadwal-pelajaran.index') }}"
                            class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-xs font-semibold text-white hover:bg-brand-600 transition">
                            <i class="fas fa-plus"></i> Buat Jadwal Baru
                        </a>
                    </div>
                @endif
            </div>
        @endforelse
    </div>
@endsection
