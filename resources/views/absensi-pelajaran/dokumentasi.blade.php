@extends('layouts.app')

@section('title', 'Dokumentasi Pembelajaran (KBM)')

@section('content')
<div x-data="dokumentasiGaleri()" class="space-y-6">
    <!-- Header Title & Action Buttons -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-title-md2 font-bold text-gray-800 dark:text-white/90 flex items-center gap-2.5">
                <i class="fas fa-photo-video text-brand-500"></i>
                <span>Dokumentasi Pembelajaran</span>
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Galeri arsip foto dan video kegiatan belajar mengajar yang diunggah oleh guru pengajar, dikelompokkan per guru.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('absensi-pelajaran.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-xs">
                <i class="fas fa-clipboard-check text-brand-500"></i>
                <span>Absensi Mapel Hari Ini</span>
            </a>
            <a href="{{ route('absensi-pelajaran.rekap') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs sm:text-sm font-medium text-gray-700 hover:bg-gray-50 transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300 dark:hover:bg-gray-800 shadow-xs">
                <i class="fas fa-book-open text-brand-500"></i>
                <span>Rekap Jurnal & Absensi</span>
            </a>
        </div>
    </div>

    <!-- Statistik Ringkasan (Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-5 gap-3.5 sm:gap-4">
        <!-- 1. Total Guru -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400 block truncate">Guru Pengunggah</span>
                    <h4 class="mt-1 text-xl sm:text-2xl font-bold text-gray-800 dark:text-white/90">
                        {{ $stats['total_gurus'] }}
                    </h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15">
                    <i class="fas fa-chalkboard-teacher text-lg"></i>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-gray-400">Guru yang mendokumentasikan KBM</p>
        </div>

        <!-- 2. Sesi KBM Terdokumentasi -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400 block truncate">Sesi KBM</span>
                    <h4 class="mt-1 text-xl sm:text-2xl font-bold text-gray-800 dark:text-white/90">
                        {{ $stats['total_sessions'] }}
                    </h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-500 dark:bg-emerald-500/15">
                    <i class="fas fa-calendar-check text-lg"></i>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-gray-400">Total pertemuan KBM terarsip</p>
        </div>

        <!-- 3. Total Berkas Media -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400 block truncate">Total Berkas</span>
                    <h4 class="mt-1 text-xl sm:text-2xl font-bold text-gray-800 dark:text-white/90">
                        {{ $stats['total_files'] }}
                    </h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-purple-50 text-purple-500 dark:bg-purple-500/15">
                    <i class="fas fa-folder-open text-lg"></i>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-gray-400">Media tersimpan di cloud storage</p>
        </div>

        <!-- 4. Total Foto -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400 block truncate">Foto Kegiatan</span>
                    <h4 class="mt-1 text-xl sm:text-2xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $stats['total_images'] }}
                    </h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-500 dark:bg-blue-500/15">
                    <i class="fas fa-image text-lg"></i>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-gray-400">Dokumentasi foto kegiatan KBM</p>
        </div>

        <!-- 5. Total Video -->
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark col-span-2 sm:col-span-1">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-gray-400 block truncate">Video Pembelajaran</span>
                    <h4 class="mt-1 text-xl sm:text-2xl font-bold text-amber-600 dark:text-amber-400">
                        {{ $stats['total_videos'] }}
                    </h4>
                </div>
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-500 dark:bg-amber-500/15">
                    <i class="fas fa-video text-lg"></i>
                </div>
            </div>
            <p class="mt-2 text-[11px] text-gray-400">Streaming video kegiatan KBM</p>
        </div>
    </div>

    <!-- Filter Card & Pencarian -->
    <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    <i class="fas fa-filter text-xs"></i>
                </div>
                <h6 class="font-semibold text-gray-800 dark:text-white/90 text-sm">Filter & Pencarian Dokumentasi</h6>
            </div>
            @if(request()->hasAny(['start_date', 'end_date', 'guru_id', 'kelas_id', 'mapel_id', 'media_type', 'q']))
                <a href="{{ route('absensi-pelajaran.dokumentasi') }}"
                   class="inline-flex items-center gap-1.5 text-xs text-error-600 hover:text-error-700 dark:text-error-400 font-medium hover:underline">
                    <i class="fas fa-times"></i> Reset Filter
                </a>
            @endif
        </div>
        <div class="p-5">
            <form action="{{ route('absensi-pelajaran.dokumentasi') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5">
                <!-- Dari Tanggal -->
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Dari Tanggal</label>
                    <input type="date" name="start_date" value="{{ $startDate }}"
                        class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white {{ request('start_date') ? 'border-brand-500 font-semibold' : '' }}">
                </div>

                <!-- Sampai Tanggal -->
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="{{ $endDate }}"
                        class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white {{ request('end_date') ? 'border-brand-500 font-semibold' : '' }}">
                </div>

                <!-- Guru Pengajar -->
                @if($isAdminOrWaka)
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Guru Pengajar</label>
                        <select name="guru_id" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                            <option value="">-- Semua Guru --</option>
                            @foreach($gurus as $g)
                                <option value="{{ $g->id }}" {{ $selectedGuruId == $g->id ? 'selected' : '' }}>
                                    {{ $g->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Kelas -->
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Kelas</label>
                    <select name="kelas_id" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="">-- Semua Kelas --</option>
                        @foreach($kelases as $k)
                            <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>
                                {{ $k->nama_kelas }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Mapel -->
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Mata Pelajaran</label>
                    <select name="mapel_id" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="">-- Semua Mapel --</option>
                        @foreach($mapels as $m)
                            <option value="{{ $m->id }}" {{ $mapelId == $m->id ? 'selected' : '' }}>
                                {{ $m->nama_mapel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Tipe Media -->
                <div>
                    <label class="mb-1.5 block text-xs font-semibold text-gray-700 dark:text-gray-300">Tipe Media</label>
                    <select name="media_type" class="w-full rounded-xl border border-gray-200 bg-transparent px-3 py-2 text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <option value="all" {{ $mediaType == 'all' ? 'selected' : '' }}>Semua (Foto & Video)</option>
                        <option value="image" {{ $mediaType == 'image' ? 'selected' : '' }}>Hanya Foto (Gambar)</option>
                        <option value="video" {{ $mediaType == 'video' ? 'selected' : '' }}>Hanya Video</option>
                    </select>
                </div>

                <!-- Search Keyword & Submit Buttons (Full Width Row) -->
                <div class="col-span-1 sm:col-span-2 md:col-span-3 lg:col-span-6 flex flex-col sm:flex-row items-center gap-3 pt-1">
                    <div class="relative w-full sm:flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-400">
                            <i class="fas fa-search text-xs"></i>
                        </span>
                        <input type="text" name="q" value="{{ $search }}" placeholder="Cari materi pembelajaran, catatan, nama guru, atau mapel..."
                            class="w-full pl-9 pr-4 py-2 rounded-xl border border-gray-200 bg-transparent text-xs outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                    </div>

                    <button type="submit"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-600 px-5 py-2 text-xs font-bold text-white transition shadow-sm">
                        <i class="fas fa-search"></i>
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Konten Utama: Daftar Dokumentasi KBM Dikelompokkan Per Guru -->
    <div class="space-y-6">
        @forelse($groupedByGuru as $group)
            <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark overflow-hidden transition-all duration-200"
                 x-data="{ isExpanded: true }">
                <!-- Header Kartu Guru -->
                <div class="p-4 sm:p-5 bg-gradient-to-r from-gray-50/80 to-white dark:from-gray-800/60 dark:to-gray-900/60 border-b border-gray-200 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 cursor-pointer select-none"
                     @click="isExpanded = !isExpanded">
                    <div class="flex items-center gap-3.5">
                        <!-- Foto Guru -->
                        <div class="relative shrink-0">
                            @if(!empty($group->guru_foto))
                                <img src="{{ $group->guru_foto }}" alt="{{ $group->guru_nama }}"
                                     class="h-12 w-12 rounded-full object-cover ring-2 ring-brand-500/30">
                            @else
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-400 font-bold text-base ring-2 ring-brand-500/20">
                                    {{ strtoupper(substr($group->guru_nama, 0, 2)) }}
                                </div>
                            @endif
                            <span class="absolute -bottom-0.5 -right-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500 ring-2 ring-white dark:ring-gray-900" title="Guru Pengajar Aktif">
                                <i class="fas fa-check text-[8px] text-white"></i>
                            </span>
                        </div>

                        <!-- Info Nama & NIP -->
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold text-gray-800 dark:text-white/90 text-base sm:text-lg">
                                    {{ $group->guru_nama }}
                                </h3>
                                @if($group->guru_nip && $group->guru_nip !== '-')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                        NIP: {{ $group->guru_nip }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Terdata {{ $group->total_kbm }} sesi KBM dengan total {{ $group->total_files }} berkas dokumentasi
                            </p>
                        </div>
                    </div>

                    <!-- Statistik Badge Guru & Toggle Accordion -->
                    <div class="flex items-center gap-2 self-end sm:self-auto">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400 border border-brand-200 dark:border-brand-500/30">
                            <i class="fas fa-layer-group text-[10px]"></i> {{ $group->total_kbm }} Sesi
                        </span>
                        @if($group->total_images > 0)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30">
                                <i class="fas fa-image text-[10px]"></i> {{ $group->total_images }} Foto
                            </span>
                        @endif
                        @if($group->total_videos > 0)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30">
                                <i class="fas fa-video text-[10px]"></i> {{ $group->total_videos }} Video
                            </span>
                        @endif

                        <button type="button" class="ml-2 flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-white transition">
                            <i class="fas fa-chevron-down text-sm transition-transform duration-200"
                               :class="isExpanded ? 'rotate-180' : ''"></i>
                        </button>
                    </div>
                </div>

                <!-- Body Accordion: Sesi-sesi KBM Guru ini -->
                <div x-show="isExpanded" x-collapse class="divide-y divide-gray-100 dark:divide-gray-800/80">
                    @foreach($group->jurnals as $jurnal)
                        @php
                            $docs = $jurnal->filtered_docs ?? $jurnal->dokumentasi_list;
                            $jadwal = $jurnal->jadwal ?? $jurnal->jadwalPelajaran;
                        @endphp
                        <div class="p-4 sm:p-5 hover:bg-gray-50/50 dark:hover:bg-gray-800/30 transition">
                            <!-- Header Sesi KBM -->
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mb-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <!-- Tanggal Sesi -->
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                        <i class="fas fa-calendar-alt text-brand-500"></i>
                                        {{ $jurnal->tanggal->isoFormat('dddd, D MMMM Y') }}
                                    </span>

                                    <!-- Jam Ke / Waktu -->
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                        <i class="fas fa-clock text-gray-400 text-[10px]"></i>
                                        Jam ke-{{ $jurnal->jam_ke ?: ($jadwal?->jam_ke ?: '-') }}
                                        @if($jurnal->jam_mulai && $jurnal->jam_selesai)
                                            ({{ substr($jurnal->jam_mulai, 0, 5) }} - {{ substr($jurnal->jam_selesai, 0, 5) }})
                                        @endif
                                    </span>

                                    <!-- Kelas -->
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300 border border-blue-200 dark:border-blue-500/20">
                                        <i class="fas fa-users text-[10px]"></i>
                                        {{ $jadwal?->kelas?->nama_kelas ?: 'Semua Kelas' }}
                                    </span>

                                    <!-- Mata Pelajaran -->
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/20">
                                        <i class="fas fa-book text-[10px]"></i>
                                        {{ $jadwal?->mapel?->nama_mapel ?: 'Mata Pelajaran' }}
                                    </span>
                                </div>

                                <!-- Tombol Aksi Buka Presensi Sesi Ini -->
                                @if($jadwal)
                                    <a href="{{ route('absensi-pelajaran.presensi', [$jadwal->id, 'tanggal' => $jurnal->tanggal->format('Y-m-d')]) }}"
                                       target="_blank"
                                       class="inline-flex items-center gap-1.5 text-xs text-brand-600 hover:text-brand-700 dark:text-brand-400 font-semibold hover:underline self-start sm:self-auto">
                                        <span>Buka Presensi Sesi Ini</span>
                                        <i class="fas fa-external-link-alt text-[10px]"></i>
                                    </a>
                                @endif
                            </div>

                            <!-- Materi & Catatan Pembelajaran -->
                            <div class="mb-3.5 rounded-xl bg-gray-50/70 dark:bg-gray-800/40 p-3.5 border border-gray-100 dark:border-gray-800 text-xs sm:text-sm">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-quote-left text-brand-400 mt-0.5 text-xs"></i>
                                    <div class="space-y-1">
                                        <p class="font-bold text-gray-800 dark:text-white/90">
                                            {{ $jurnal->materi ?: 'Materi KBM' }}
                                        </p>
                                        @if(!empty($jurnal->catatan))
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                <span class="font-semibold text-gray-600 dark:text-gray-300">Catatan:</span> {{ $jurnal->catatan }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Grid Media Berkas (Foto & Video) -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                @foreach($docs as $doc)
                                    <div class="group relative rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 shadow-xs hover:shadow-md transition">
                                        @if($doc['is_video'])
                                            <!-- Video Thumbnail Card with Play Overlay -->
                                            <div class="aspect-video sm:aspect-square w-full bg-gray-900 flex items-center justify-center relative cursor-pointer overflow-hidden"
                                                 @click="openVideoModal('{{ $doc['view_url'] }}', '{{ addslashes($doc['name']) }}', '{{ $doc['thumbnail_url'] ?? '' }}')">
                                                @if(!empty($doc['thumbnail_url']))
                                                    <img src="{{ $doc['thumbnail_url'] }}"
                                                         alt="{{ $doc['name'] }}"
                                                         loading="lazy"
                                                         class="w-full h-full object-cover opacity-85 group-hover:opacity-100 group-hover:scale-105 transition duration-300"
                                                         onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.style.display='block';">
                                                    <video src="{{ $doc['view_url'] }}#t=0.5" preload="metadata" style="display: none;" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition duration-300"></video>
                                                @else
                                                    <video src="{{ $doc['view_url'] }}#t=0.5" preload="metadata" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition duration-300"></video>
                                                @endif
                                                <div class="absolute inset-0 bg-black/30 flex items-center justify-center group-hover:bg-black/10 transition">
                                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-500 text-white shadow-lg group-hover:scale-110 transition duration-300">
                                                        <i class="fas fa-play text-xs ml-0.5"></i>
                                                    </div>
                                                </div>
                                                <span class="absolute top-2 left-2 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-500 text-white flex items-center gap-1 shadow">
                                                    <i class="fas fa-video"></i> VIDEO
                                                </span>
                                            </div>
                                        @else
                                            <!-- Photo Thumbnail Card -->
                                            <div class="aspect-square w-full overflow-hidden bg-gray-100 dark:bg-gray-900 relative cursor-pointer group"
                                                 @click="openPhotoModal('{{ $doc['view_url'] }}', '{{ addslashes($doc['name']) }}')">
                                                <img src="{{ $doc['thumbnail_url'] ?? $doc['view_url'] }}" alt="{{ $doc['name'] }}" loading="lazy"
                                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-center justify-center opacity-0 group-hover:opacity-100">
                                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-gray-800 shadow">
                                                        <i class="fas fa-search-plus text-xs"></i>
                                                    </div>
                                                </div>
                                                <span class="absolute top-2 left-2 px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-600 text-white flex items-center gap-1 shadow">
                                                    <i class="fas fa-image"></i> FOTO
                                                </span>
                                            </div>
                                        @endif

                                        <!-- Footer Info Berkas & Tombol Unduh -->
                                        <div class="p-2 text-[10px] text-gray-600 dark:text-gray-400 flex items-center justify-between gap-1 border-t border-gray-100 dark:border-gray-700/60 bg-white dark:bg-gray-800">
                                            <span class="truncate" title="{{ $doc['name'] }}">{{ $doc['name'] }}</span>
                                            <a href="{{ $doc['download_url'] }}" target="_blank" download class="text-brand-500 hover:text-brand-600 p-1 shrink-0" title="Unduh Berkas Ini">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <!-- Tampilan Kosong (Empty State) -->
            <div class="rounded-2xl border border-gray-200 bg-white p-8 sm:p-12 text-center shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/15 mb-4">
                    <i class="fas fa-photo-video text-2xl"></i>
                </div>
                <h4 class="text-base sm:text-lg font-bold text-gray-800 dark:text-white/90">
                    Belum Ada Dokumentasi Pembelajaran
                </h4>
                <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                    Tidak ditemukan foto atau video kegiatan KBM pada rentang tanggal atau filter yang dipilih. Guru dapat mengunggah dokumentasi saat mengisi presensi & jurnal melalui aplikasi Android atau web.
                </p>
                <div class="mt-5">
                    <a href="{{ route('absensi-pelajaran.dokumentasi') }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition shadow-sm">
                        <i class="fas fa-sync-alt"></i>
                        <span>Reset Semua Filter</span>
                    </a>
                </div>
            </div>
        @endforelse
    </div>

    <!-- ========================= MODAL LIGHTBOX FOTO ========================= -->
    <div x-show="photoModalOpen" style="display: none;"
         class="fixed inset-0 z-99999 flex items-center justify-center bg-black/90 p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="photoModalOpen = false">
        <div class="relative max-w-4xl w-full max-h-[90vh] flex flex-col items-center"
             @click.away="photoModalOpen = false">
            <!-- Close button -->
            <button @click="photoModalOpen = false"
                    class="absolute -top-10 right-0 text-white/80 hover:text-white text-lg p-2 transition">
                <i class="fas fa-times"></i> Tutup (ESC)
            </button>
            <div class="w-full flex justify-between items-center text-white/90 mb-2 px-1 text-xs sm:text-sm">
                <span class="truncate font-medium" x-text="activeMediaName"></span>
                <a :href="activeMediaUrl" target="_blank" download class="inline-flex items-center gap-1.5 text-brand-400 hover:text-brand-300 font-bold ml-3">
                    <i class="fas fa-download"></i> Unduh Resolusi Asli
                </a>
            </div>
            <img :src="activeMediaUrl" :alt="activeMediaName"
                 class="max-h-[80vh] w-auto max-w-full rounded-xl object-contain shadow-2xl border border-white/10">
        </div>
    </div>

    <!-- ========================= MODAL VIDEO STREAMING PLAYER ========================= -->
    <div x-show="videoModalOpen" style="display: none;"
         class="fixed inset-0 z-99999 flex items-center justify-center bg-black/90 p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="closeVideoModal()">
        <div class="relative max-w-4xl w-full flex flex-col items-center"
             @click.away="closeVideoModal()">
            <button @click="closeVideoModal()"
                    class="absolute -top-10 right-0 text-white/80 hover:text-white text-lg p-2 transition">
                <i class="fas fa-times"></i> Tutup (ESC)
            </button>
            <div class="w-full flex justify-between items-center text-white/90 mb-2 px-1 text-xs sm:text-sm">
                <span class="truncate font-medium flex items-center gap-2">
                    <i class="fas fa-video text-amber-400"></i>
                    <span x-text="activeMediaName"></span>
                </span>
                <a :href="activeMediaUrl" target="_blank" download class="inline-flex items-center gap-1.5 text-brand-400 hover:text-brand-300 font-bold ml-3">
                    <i class="fas fa-download"></i> Unduh Video
                </a>
            </div>
            <div class="w-full bg-black rounded-2xl overflow-hidden shadow-2xl border border-white/10 aspect-video flex items-center justify-center">
                <video id="modalVideoPlayer" controls autoplay preload="auto" :poster="activeMediaPoster" class="w-full h-full object-contain">
                    <source :src="activeMediaUrl" type="video/mp4">
                    Browser Anda tidak mendukung tag video.
                </video>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function dokumentasiGaleri() {
    return {
        photoModalOpen: false,
        videoModalOpen: false,
        activeMediaUrl: '',
        activeMediaPoster: '',
        activeMediaName: '',

        openPhotoModal(url, name) {
            this.activeMediaUrl = url;
            this.activeMediaName = name;
            this.photoModalOpen = true;
        },

        openVideoModal(url, name, poster = '') {
            this.activeMediaUrl = url;
            this.activeMediaPoster = poster || '';
            this.activeMediaName = name;
            this.videoModalOpen = true;
            this.$nextTick(() => {
                const vid = document.getElementById('modalVideoPlayer');
                if (vid) {
                    if (poster) {
                        vid.poster = poster;
                    } else {
                        vid.removeAttribute('poster');
                    }
                    vid.src = url;
                    vid.load();
                    vid.play().catch(() => {});
                }
            });
        },

        closeVideoModal() {
            const vid = document.getElementById('modalVideoPlayer');
            if (vid) {
                vid.pause();
                vid.src = '';
                vid.removeAttribute('poster');
            }
            this.activeMediaPoster = '';
            this.videoModalOpen = false;
        }
    }
}
</script>
@endpush
@endsection
