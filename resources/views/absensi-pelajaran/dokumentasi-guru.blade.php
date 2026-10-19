@extends('layouts.app')

@section('title', 'Galeri Dokumentasi KBM - ' . $guru->nama)

@section('content')
<div x-data="dokumentasiGaleri()" class="space-y-6">
    <!-- Header Title & Teacher Profile Card -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Info Guru -->
            <div class="flex items-center gap-4">
                @if(!empty($guru->foto_url))
                    <img src="{{ $guru->foto_url }}" alt="{{ $guru->nama }}"
                         class="h-16 w-16 sm:h-20 sm:w-20 rounded-2xl object-cover border-2 border-brand-500/30 dark:border-brand-500/40 shadow-sm shrink-0">
                @else
                    <div class="flex h-16 w-16 sm:h-20 sm:w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-xl sm:text-2xl shadow-sm shrink-0">
                        {{ strtoupper(substr($guru->nama, 0, 2)) }}
                    </div>
                @endif
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                            <i class="fas fa-chalkboard-teacher"></i> Galeri Dokumentasi KBM
                        </span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white/90 mt-1">
                        {{ $guru->nama }}
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-500 dark:text-gray-400 font-mono mt-0.5">
                        NIP: {{ $guru->nip ?: '-' }}
                    </p>
                </div>
            </div>

            <!-- Ringkasan Statistik Guru Ini -->
            <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
                <div class="rounded-xl border border-gray-100 bg-gray-50/70 dark:border-gray-800 dark:bg-gray-800/40 px-3.5 py-2 text-center min-w-[70px]">
                    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider block">Sesi</span>
                    <span class="text-sm sm:text-base font-bold text-emerald-600 dark:text-emerald-400">{{ $teacherStats['total_sessions'] }}</span>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50/70 dark:border-gray-800 dark:bg-gray-800/40 px-3.5 py-2 text-center min-w-[70px]">
                    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider block">Foto</span>
                    <span class="text-sm sm:text-base font-bold text-blue-600 dark:text-blue-400">{{ $teacherStats['total_images'] }}</span>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50/70 dark:border-gray-800 dark:bg-gray-800/40 px-3.5 py-2 text-center min-w-[70px]">
                    <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider block">Video</span>
                    <span class="text-sm sm:text-base font-bold text-amber-500 dark:text-amber-400">{{ $teacherStats['total_videos'] }}</span>
                </div>
                <div class="rounded-xl border border-gray-100 bg-purple-50/70 dark:border-purple-900/30 dark:bg-purple-950/20 px-3.5 py-2 text-center min-w-[70px]">
                    <span class="text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider block">Total</span>
                    <span class="text-sm sm:text-base font-bold text-purple-700 dark:text-purple-300">{{ $teacherStats['total_files'] }}</span>
                </div>
                <a href="{{ route('absensi-pelajaran.dokumentasi') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 transition shadow-xs">
                    <i class="fas fa-arrow-left"></i>
                    <span>Kembali ke Daftar</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Filter & Pencarian Bar Galeri -->
    <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:p-5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark">
        <form method="GET" action="{{ route('absensi-pelajaran.dokumentasi.guru', $guru->id) }}" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5">
                <!-- 1. Dari Tanggal -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5 flex items-center gap-1.5">
                        <i class="fas fa-calendar-day text-brand-500 text-[10px]"></i>
                        <span>Dari Tanggal</span>
                    </label>
                    <div class="relative">
                        <input type="text" id="filter_start_date" name="start_date" value="{{ $startDate }}"
                            placeholder="Pilih tanggal..."
                            class="w-full rounded-xl border border-gray-200 bg-gray-50/50 pl-3 pr-9 py-2 text-xs text-gray-800 focus:border-brand-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-white transition cursor-pointer"
                            autocomplete="off">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                            <i class="fas fa-calendar-alt text-xs"></i>
                        </span>
                    </div>
                </div>

                <!-- 2. Sampai Tanggal -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5 flex items-center gap-1.5">
                        <i class="fas fa-calendar-check text-brand-500 text-[10px]"></i>
                        <span>Sampai Tanggal</span>
                    </label>
                    <div class="relative">
                        <input type="text" id="filter_end_date" name="end_date" value="{{ $endDate }}"
                            placeholder="Pilih tanggal..."
                            class="w-full rounded-xl border border-gray-200 bg-gray-50/50 pl-3 pr-9 py-2 text-xs text-gray-800 focus:border-brand-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-white transition cursor-pointer"
                            autocomplete="off">
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                            <i class="fas fa-calendar-alt text-xs"></i>
                        </span>
                    </div>
                </div>

                <!-- 3. Filter Kelas -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5 flex items-center gap-1.5">
                        <i class="fas fa-school text-brand-500 text-[10px]"></i>
                        <span>Kelas</span>
                    </label>
                    <select name="kelas_id"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-3 py-2 text-xs text-gray-800 focus:border-brand-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-white transition cursor-pointer">
                        <option value="">-- Semua Kelas --</option>
                        @foreach($kelases as $k)
                            <option value="{{ $k->id }}" {{ (string)$kelasId === (string)$k->id ? 'selected' : '' }}>
                                {{ $k->nama_kelas }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 4. Filter Mapel -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5 flex items-center gap-1.5">
                        <i class="fas fa-book-open text-brand-500 text-[10px]"></i>
                        <span>Mata Pelajaran</span>
                    </label>
                    <select name="mapel_id"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-3 py-2 text-xs text-gray-800 focus:border-brand-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-white transition cursor-pointer">
                        <option value="">-- Semua Mapel --</option>
                        @foreach($mapels as $m)
                            <option value="{{ $m->id }}" {{ (string)$mapelId === (string)$m->id ? 'selected' : '' }}>
                                {{ $m->nama_mapel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 5. Tipe Media -->
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5 flex items-center gap-1.5">
                        <i class="fas fa-photo-video text-brand-500 text-[10px]"></i>
                        <span>Tipe Media</span>
                    </label>
                    <select name="media_type"
                        class="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-3 py-2 text-xs text-gray-800 focus:border-brand-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-white transition cursor-pointer">
                        <option value="all" {{ $mediaType === 'all' ? 'selected' : '' }}>Semua (Foto & Video)</option>
                        <option value="image" {{ $mediaType === 'image' ? 'selected' : '' }}>Hanya Foto ({{ $teacherStats['total_images'] }})</option>
                        <option value="video" {{ $mediaType === 'video' ? 'selected' : '' }}>Hanya Video ({{ $teacherStats['total_videos'] }})</option>
                    </select>
                </div>
            </div>

            <!-- Baris Bawah: Pencarian Materi & Tombol Action -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                <!-- Search Keyword -->
                <div class="relative w-full md:w-96">
                    <input type="text" name="q" value="{{ $search }}"
                        placeholder="Cari materi pembelajaran atau catatan..."
                        class="w-full rounded-xl border border-gray-200 bg-gray-50/50 py-2 pl-9 pr-8 text-xs text-gray-800 focus:border-brand-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-white transition">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                        <i class="fas fa-search text-xs"></i>
                    </span>
                    @if(!empty($search))
                        <a href="{{ route('absensi-pelajaran.dokumentasi.guru', $guru->id) }}" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <i class="fas fa-times text-xs"></i>
                        </a>
                    @endif
                </div>

                <!-- Tombol Action -->
                <div class="flex items-center gap-2 self-end md:self-auto shrink-0">
                    @if(!empty($startDate) || !empty($endDate) || !empty($kelasId) || !empty($mapelId) || $mediaType !== 'all' || !empty($search))
                        <a href="{{ route('absensi-pelajaran.dokumentasi.guru', $guru->id) }}"
                           class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3.5 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition shadow-xs">
                            <i class="fas fa-undo text-[10px]"></i>
                            <span>Reset Filter</span>
                        </a>
                    @endif
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition shadow-sm cursor-pointer">
                        <i class="fas fa-filter text-[10px]"></i>
                        <span>Terapkan Filter</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Galeri Grid Media (Foto & Video) -->
    @if($allDocs->isNotEmpty())
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3.5 sm:gap-4">
            @foreach($allDocs as $doc)
                <div class="group relative rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-dark shadow-theme-xs hover:shadow-md transition flex flex-col justify-between">
                    <div>
                        @if($doc['is_video'])
                            <!-- Video Thumbnail Card with Centered Play Button & Corner Badge -->
                            <div class="aspect-square w-full bg-gray-900 flex items-center justify-center relative cursor-pointer overflow-hidden group/thumb"
                                 @click="openVideoModal('{{ $doc['view_url'] }}', '{{ addslashes($doc['name']) }}', '{{ $doc['thumbnail_url'] ?? '' }}')">
                                @if(!empty($doc['thumbnail_url']))
                                    <img src="{{ $doc['thumbnail_url'] }}"
                                         alt="{{ $doc['name'] }}"
                                         loading="lazy"
                                         class="w-full h-full object-cover opacity-85 group-hover/thumb:opacity-100 group-hover/thumb:scale-105 transition duration-300"
                                         onerror="this.style.display='none'; if(this.nextElementSibling){ this.nextElementSibling.style.display='block'; }">
                                    <video src="{{ $doc['view_url'] }}#t=0.5" preload="metadata" style="display: none;" class="w-full h-full object-cover opacity-80 group-hover/thumb:opacity-100 transition duration-300"></video>
                                @else
                                    <video src="{{ $doc['view_url'] }}#t=0.5" preload="metadata" class="w-full h-full object-cover opacity-80 group-hover/thumb:opacity-100 transition duration-300"></video>
                                @endif

                                <!-- Play Icon Overlay (Hanya Icon Play di Tengah) -->
                                <div class="absolute inset-0 bg-black/25 flex items-center justify-center group-hover/thumb:bg-black/15 transition">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-500/95 text-white shadow-xl group-hover/thumb:scale-110 transition duration-300">
                                        <i class="fas fa-play text-sm ml-0.5"></i>
                                    </div>
                                </div>

                                <!-- Badge VIDEO Terkunci Permanen di Pojok Kiri Atas -->
                                <span style="position: absolute; top: 8px; left: 8px; z-index: 20;"
                                      class="px-2 py-0.5 rounded-md text-[9px] font-bold bg-amber-500 text-white flex items-center gap-1 shadow-md">
                                    <i class="fas fa-video text-[9px]"></i> VIDEO
                                </span>
                            </div>
                        @else
                            <!-- Photo Card with Corner Badge & Centered Hover Zoom -->
                            <div class="aspect-square w-full overflow-hidden bg-gray-100 dark:bg-gray-900 relative cursor-pointer group/thumb"
                                 @click="openPhotoModal('{{ $doc['view_url'] }}', '{{ addslashes($doc['name']) }}')">
                                <img src="{{ $doc['thumbnail_url'] ?? $doc['view_url'] }}" alt="{{ $doc['name'] }}" loading="lazy"
                                     class="w-full h-full object-cover group-hover/thumb:scale-105 transition duration-300">
                                <div class="absolute inset-0 bg-black/0 group-hover/thumb:bg-black/20 transition flex items-center justify-center opacity-0 group-hover/thumb:opacity-100">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-white/95 text-gray-800 shadow-lg">
                                        <i class="fas fa-search-plus text-xs"></i>
                                    </div>
                                </div>

                                <!-- Badge FOTO Terkunci Permanen di Pojok Kiri Atas -->
                                <span style="position: absolute; top: 8px; left: 8px; z-index: 20;"
                                      class="px-2 py-0.5 rounded-md text-[9px] font-bold bg-blue-600 text-white flex items-center gap-1 shadow-md">
                                    <i class="fas fa-image text-[9px]"></i> FOTO
                                </span>
                            </div>
                        @endif

                        <!-- Informasi Sesi KBM -->
                        <div class="p-3 space-y-1.5 text-xs">
                            <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400">
                                <span class="font-bold flex items-center gap-1">
                                    <i class="fas fa-calendar-alt text-brand-500 text-[10px]"></i>
                                    {{ \Carbon\Carbon::parse($doc['tanggal'])->translatedFormat('d M Y') }}
                                </span>
                                <span class="font-mono text-[10px]">Jam {{ $doc['jam_ke'] }}</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-1">
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">
                                    {{ $doc['nama_kelas'] }}
                                </span>
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 truncate max-w-[110px]" title="{{ $doc['nama_mapel'] }}">
                                    {{ $doc['nama_mapel'] }}
                                </span>
                            </div>
                            <p class="font-semibold text-gray-800 dark:text-white/90 line-clamp-2 leading-snug pt-0.5" title="{{ $doc['materi'] }}">
                                {{ $doc['materi'] ?: 'KBM' }}
                            </p>
                        </div>
                    </div>

                    <!-- Footer Action: Buka Presensi & Download -->
                    <div class="px-3 py-2 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 flex items-center justify-between text-[11px]">
                        @if(!empty($doc['jadwal_id']))
                            <a href="{{ route('absensi-pelajaran.presensi', [$doc['jadwal_id'], 'tanggal' => \Carbon\Carbon::parse($doc['tanggal'])->format('Y-m-d')]) }}"
                               target="_blank" rel="noopener noreferrer"
                               class="text-brand-600 hover:text-brand-700 dark:text-brand-400 font-semibold flex items-center gap-1 hover:underline">
                                <span>Presensi</span>
                                <i class="fas fa-arrow-up-right-from-square text-[9px]"></i>
                            </a>
                        @else
                            <span></span>
                        @endif

                        <a href="{{ $doc['download_url'] }}" target="_blank" download
                           class="text-gray-500 hover:text-brand-500 p-1" title="Unduh Berkas Ini">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State Galeri -->
        <div class="rounded-2xl border border-gray-200 bg-white p-12 text-center shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/15 mb-4">
                <i class="fas fa-images text-2xl"></i>
            </div>
            <h4 class="text-base sm:text-lg font-bold text-gray-800 dark:text-white/90">
                Tidak Ada Berkas Dokumentasi Ditemukan
            </h4>
            <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                Tidak ada foto atau video yang sesuai dengan kriteria filter yang Anda pilih untuk guru ini.
            </p>
            <div class="mt-5">
                <a href="{{ route('absensi-pelajaran.dokumentasi.guru', $guru->id) }}"
                   class="inline-flex items-center gap-2 rounded-xl bg-brand-500 hover:bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition shadow-sm">
                    <i class="fas fa-sync-alt"></i>
                    <span>Reset Semua Filter</span>
                </a>
            </div>
        </div>
    @endif

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
        <div class="relative max-w-4xl w-full flex flex-col items-center"
             @click.away="photoModalOpen = false">
            <!-- Modal Header Bar -->
            <div class="w-full flex items-center justify-between text-white mb-2.5 px-2">
                <div class="flex items-center gap-2 truncate mr-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-600 text-white shrink-0">FOTO</span>
                    <span class="truncate font-medium text-xs sm:text-sm text-gray-200" x-text="activeMediaName"></span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a :href="activeMediaUrl" target="_blank" download class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-xs font-semibold transition shadow">
                        <i class="fas fa-download text-[11px]"></i>
                        <span>Unduh Foto</span>
                    </a>
                    <button type="button" @click="photoModalOpen = false" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 text-white text-xs font-semibold transition cursor-pointer">
                        <i class="fas fa-times text-[11px]"></i>
                        <span>Tutup (ESC)</span>
                    </button>
                </div>
            </div>
            <!-- Media Preview Container -->
            <div class="max-h-[80vh] overflow-hidden rounded-2xl shadow-2xl border border-white/10 bg-black flex items-center justify-center p-1">
                <img :src="activeMediaUrl" :alt="activeMediaName" class="max-h-[78vh] w-auto max-w-full object-contain rounded-xl">
            </div>
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
            <!-- Modal Header Bar -->
            <div class="w-full flex items-center justify-between text-white mb-2.5 px-2">
                <div class="flex items-center gap-2 truncate mr-3">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-500 text-white shrink-0">VIDEO</span>
                    <span class="truncate font-medium text-xs sm:text-sm text-gray-200" x-text="activeMediaName"></span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a :href="activeMediaUrl" target="_blank" download class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-xs font-semibold transition shadow">
                        <i class="fas fa-download text-[11px]"></i>
                        <span>Unduh Video</span>
                    </a>
                    <button type="button" @click="closeVideoModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/15 hover:bg-white/25 text-white text-xs font-semibold transition cursor-pointer">
                        <i class="fas fa-times text-[11px]"></i>
                        <span>Tutup (ESC)</span>
                    </button>
                </div>
            </div>
            <!-- Video Player Container (mendukung potret ponsel & landscape) -->
            <div class="w-full bg-black/95 rounded-2xl overflow-hidden shadow-2xl border border-white/10 flex items-center justify-center p-1 sm:p-2">
                <video id="modalVideoPlayer" controls autoplay playsinline preload="auto" :poster="activeMediaPoster"
                       class="max-h-[75vh] w-auto max-w-full rounded-xl object-contain mx-auto">
                    Browser Anda tidak mendukung tag video.
                </video>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function initDokumentasiFlatpickr() {
    const config = {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d M Y",
        altInputClass: "w-full rounded-xl border border-gray-200 bg-gray-50/50 pl-3 pr-9 py-2 text-xs text-gray-800 focus:border-brand-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-white transition cursor-pointer",
        allowInput: false,
    };
    if (window.flatpickr) {
        window.flatpickr("#filter_start_date", config);
        window.flatpickr("#filter_end_date", config);
    } else {
        setTimeout(initDokumentasiFlatpickr, 100);
    }
}

document.addEventListener('DOMContentLoaded', initDokumentasiFlatpickr);

function dokumentasiGaleri() {
    return {
        photoModalOpen: false,
        videoModalOpen: false,
        activeMediaUrl: '',
        activeMediaPoster: '',
        activeMediaName: '',

        init() {
            this.$nextTick(() => {
                initDokumentasiFlatpickr();
            });
        },

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
