<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Dokumentasi KBM - {{ $guru->nama }} | {{ $school->name ?? config('app.name', 'Sistem Absensi') }}</title>

    <!-- Meta SEO & Social Sharing (OpenGraph) -->
    <meta name="description" content="Galeri dan dokumentasi kegiatan belajar mengajar (KBM) {{ $guru->nama }} di {{ $school->name ?? 'Sekolah' }}. Menampilkan foto, video, tanggal, kelas, dan jurnal pembelajaran.">
    <meta property="og:title" content="Galeri Dokumentasi KBM - {{ $guru->nama }} | {{ $school->name ?? 'Sekolah' }}">
    <meta property="og:description" content="Lihat foto, video, kelas, tanggal, dan jurnal pembelajaran {{ $guru->nama }}.">
    @if(!empty($guru->foto_url))
        <meta property="og:image" content="{{ $guru->foto_url }}">
    @endif
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo/logo-icon.ico') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <!-- Tailwind / Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const theme = savedTheme || systemTheme;
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <style>
        body {
            font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
        }
        [x-cloak] { display: none !important; }
        .glass-header {
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(156, 163, 175, 0.4);
            border-radius: 9999px;
        }
    </style>
</head>
<body x-data="publicDokumentasiApp()" class="min-h-full bg-slate-50 text-slate-800 antialiased dark:bg-gray-950 dark:text-gray-100 flex flex-col justify-between selection:bg-brand-500 selection:text-white">

    <!-- Toast Notification Copy Link -->
    <div x-show="toast.show" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-4"
         class="fixed top-5 right-5 z-99999 flex items-center gap-3 px-4 py-3 rounded-2xl bg-gray-900/95 text-white shadow-2xl border border-white/10 backdrop-blur-md">
        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-400">
            <i class="fas fa-check-circle text-base"></i>
        </div>
        <div>
            <p class="text-xs font-bold" x-text="toast.title"></p>
            <p class="text-[11px] text-gray-300" x-text="toast.message"></p>
        </div>
    </div>

    <!-- ========================= TOP NAVIGATION BAR ========================= -->
    <header class="sticky top-0 z-40 w-full glass-header bg-white/85 dark:bg-gray-900/85 border-b border-gray-200/80 dark:border-gray-800 transition-colors duration-200 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <!-- Brand / School Info -->
            <div class="flex items-center gap-3 min-w-0">
                @if(!empty($school->logo_url))
                    <img src="{{ $school->logo_url }}" alt="{{ $school->name }}" class="h-10 w-10 sm:h-11 sm:w-11 rounded-xl object-contain border border-gray-200/80 dark:border-gray-800 bg-white p-1 shadow-xs shrink-0">
                @else
                    <div class="flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-600 text-white font-bold text-base shadow-xs shrink-0">
                        <i class="fas fa-school"></i>
                    </div>
                @endif
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-brand-500/10 text-brand-600 dark:text-brand-400 border border-brand-500/20">
                            <i class="fas fa-globe text-[9px]"></i> Portal Publik KBM
                        </span>
                        @if(!empty($school->type))
                            <span class="hidden sm:inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                {{ strtoupper($school->type) }}
                            </span>
                        @endif
                    </div>
                    <h1 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white truncate leading-tight mt-0.5">
                        {{ $school->name }}
                    </h1>
                </div>
            </div>

            <!-- Header Action Buttons -->
            <div class="flex items-center gap-2 sm:gap-2.5 shrink-0">
                <!-- Share Button (WhatsApp) -->
                @php
                    $waShareText = "Dokumentasi Pembelajaran (KBM) {$guru->nama} di {$school->name}: " . url()->current();
                    $waShareUrl  = "https://api.whatsapp.com/send?text=" . urlencode($waShareText);
                @endphp
                <a href="{{ $waShareUrl }}" target="_blank" rel="noopener noreferrer"
                   title="Bagikan ke WhatsApp"
                   class="inline-flex items-center justify-center h-9 sm:h-9.5 px-3 rounded-xl bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-500/15 dark:text-emerald-400 dark:hover:bg-emerald-500/25 border border-emerald-200/80 dark:border-emerald-500/30 text-xs font-semibold transition shadow-xs gap-1.5">
                    <i class="fab fa-whatsapp text-sm"></i>
                    <span class="hidden sm:inline">WhatsApp</span>
                </a>

                <!-- Copy Link Button -->
                <button type="button" @click="copyShareLink()"
                        title="Salin Link Halaman Ini"
                        class="inline-flex items-center justify-center h-9 sm:h-9.5 px-3 rounded-xl bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700/80 border border-gray-200 dark:border-gray-700 text-xs font-semibold transition shadow-xs gap-1.5 cursor-pointer">
                    <i class="fas fa-link text-xs"></i>
                    <span class="hidden sm:inline">Salin Link</span>
                </button>

                <!-- Dark / Light Theme Toggle -->
                <button type="button" @click="toggleTheme()"
                        aria-label="Toggle Tema"
                        class="flex h-9 w-9 sm:h-9.5 sm:w-9.5 items-center justify-center rounded-xl bg-white text-gray-600 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/80 border border-gray-200 dark:border-gray-700 transition shadow-xs cursor-pointer">
                    <i class="fas fa-moon text-xs dark:hidden"></i>
                    <i class="fas fa-sun text-xs hidden dark:inline"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- ========================= MAIN CONTENT WRAPPER ========================= -->
    <main class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6 sm:space-y-8 flex-1">

        @if(!$isDokKbmEnabled)
            <!-- State Fitur Belum Aktif -->
            <div class="rounded-3xl border border-amber-200 bg-amber-50/70 p-8 sm:p-12 text-center dark:border-amber-900/40 dark:bg-amber-950/20 shadow-sm max-w-2xl mx-auto my-12">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-600 dark:text-amber-400 mb-4">
                    <i class="fas fa-lock text-2xl"></i>
                </div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
                    Galeri Dokumentasi KBM Belum Diaktifkan
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    Modul dokumentasi kegiatan belajar mengajar untuk sekolah ini saat ini sedang tidak aktif. Silakan hubungi pihak sekolah untuk informasi lebih lanjut.
                </p>
            </div>
        @else

            <!-- ========================= HERO TEACHER PROFILE CARD ========================= -->
            <div class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white p-5 sm:p-7 shadow-sm dark:border-gray-800 dark:bg-gray-900 transition-colors">
                <!-- Subtle Gradient Background Glow -->
                <div class="absolute -top-24 -right-24 h-64 w-64 rounded-full bg-brand-500/10 blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-24 -left-24 h-64 w-64 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>

                <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <!-- Avatar & Teacher Bio -->
                    <div class="flex items-center gap-4 sm:gap-5 min-w-0">
                        <div class="relative shrink-0">
                            @if(!empty($guru->foto_url))
                                <img src="{{ $guru->foto_url }}" alt="{{ $guru->nama }}"
                                     class="h-20 w-20 sm:h-24 sm:w-24 rounded-2xl sm:rounded-3xl object-cover border-2 border-brand-500/30 dark:border-brand-500/40 shadow-md">
                            @else
                                <div class="flex h-20 w-20 sm:h-24 sm:w-24 items-center justify-center rounded-2xl sm:rounded-3xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white font-bold text-2xl sm:text-3xl shadow-md">
                                    {{ strtoupper(substr($guru->nama, 0, 2)) }}
                                </div>
                            @endif
                            <div class="absolute -bottom-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500 text-white text-[10px] shadow-sm ring-2 ring-white dark:ring-gray-900" title="Guru Terdaftar">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                                    <i class="fas fa-chalkboard-teacher text-[11px]"></i> Profil Pendidik
                                </span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">•</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium truncate max-w-[200px]">
                                    {{ $school->name }}
                                </span>
                            </div>

                            <h2 class="text-xl sm:text-3xl font-bold text-gray-900 dark:text-white tracking-tight truncate">
                                {{ $guru->nama }}
                            </h2>

                            <div class="flex items-center gap-3 text-xs sm:text-sm text-gray-500 dark:text-gray-400 font-mono mt-1 flex-wrap">
                                <span class="inline-flex items-center gap-1.5">
                                    <i class="fas fa-id-badge text-gray-400"></i>
                                    NIP: {{ $guru->nip ?: '-' }}
                                </span>
                                @if(!empty($guru->no_wa))
                                    <span class="text-gray-300 dark:text-gray-700">•</span>
                                    <span class="inline-flex items-center gap-1 text-emerald-600 dark:text-emerald-400">
                                        <i class="fab fa-whatsapp"></i> Terverifikasi
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Chips Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3 shrink-0">
                        <!-- Sesi KBM -->
                        <div class="rounded-2xl border border-gray-100 bg-gray-50/80 dark:border-gray-800 dark:bg-gray-800/40 p-3 text-center min-w-[85px] sm:min-w-[95px]">
                            <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-400 uppercase tracking-wider block">Sesi KBM</span>
                            <span class="text-lg sm:text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ $teacherStats['total_sessions'] }}</span>
                            <span class="text-[10px] text-gray-400 block -mt-0.5">Pertemuan</span>
                        </div>

                        <!-- Foto -->
                        <div class="rounded-2xl border border-gray-100 bg-gray-50/80 dark:border-gray-800 dark:bg-gray-800/40 p-3 text-center min-w-[85px] sm:min-w-[95px]">
                            <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-400 uppercase tracking-wider block">Foto</span>
                            <span class="text-lg sm:text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $teacherStats['total_images'] }}</span>
                            <span class="text-[10px] text-gray-400 block -mt-0.5">Dokumentasi</span>
                        </div>

                        <!-- Video -->
                        <div class="rounded-2xl border border-gray-100 bg-gray-50/80 dark:border-gray-800 dark:bg-gray-800/40 p-3 text-center min-w-[85px] sm:min-w-[95px]">
                            <span class="text-[10px] font-semibold text-gray-400 dark:text-gray-400 uppercase tracking-wider block">Video</span>
                            <span class="text-lg sm:text-2xl font-bold text-amber-500 dark:text-amber-400">{{ $teacherStats['total_videos'] }}</span>
                            <span class="text-[10px] text-gray-400 block -mt-0.5">Rekaman KBM</span>
                        </div>

                        <!-- Total Berkas -->
                        <div class="rounded-2xl border border-purple-100 bg-purple-50/60 dark:border-purple-900/30 dark:bg-purple-950/20 p-3 text-center min-w-[85px] sm:min-w-[95px]">
                            <span class="text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider block">Total Media</span>
                            <span class="text-lg sm:text-2xl font-bold text-purple-700 dark:text-purple-300">{{ $teacherStats['total_files'] }}</span>
                            <span class="text-[10px] text-purple-500/80 block -mt-0.5">Foto & Video</span>
                        </div>
                    </div>
                </div>

                <!-- Public Info Banner Bar -->
                <div class="mt-5 pt-4 border-t border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-gray-500 dark:text-gray-400">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-shield-alt text-brand-500"></i>
                        <span>Halaman ini diverifikasi resmi dari sistem kehadiran & jurnal KBM <strong>{{ $school->name }}</strong>.</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="copyShareLink()" class="inline-flex items-center gap-1.5 text-brand-600 hover:text-brand-700 dark:text-brand-400 font-semibold cursor-pointer">
                            <i class="fas fa-copy"></i>
                            <span>Salin Tautan Guru</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================= FILTER & PENCARIAN ========================= -->
            <div class="rounded-3xl border border-gray-200 bg-white p-5 sm:p-6 shadow-sm dark:border-gray-800 dark:bg-gray-900 transition-colors">
                <form method="GET" action="{{ route('publik.dokumentasi-guru', $guru->id) }}" class="space-y-4">
                    <input type="hidden" name="view" value="{{ $viewMode }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5">
                        <!-- 1. Dari Tanggal -->
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5 flex items-center gap-1.5">
                                <i class="fas fa-calendar-day text-brand-500 text-[10px]"></i>
                                <span>Dari Tanggal</span>
                            </label>
                            <div class="relative">
                                <input type="text" id="public_filter_start_date" name="start_date" value="{{ $startDate }}"
                                    placeholder="Pilih tanggal..."
                                    class="w-full rounded-2xl border border-gray-200 bg-gray-50/50 pl-3.5 pr-9 py-2.5 text-xs text-gray-800 focus:border-brand-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-white transition cursor-pointer"
                                    autocomplete="off">
                                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
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
                                <input type="text" id="public_filter_end_date" name="end_date" value="{{ $endDate }}"
                                    placeholder="Pilih tanggal..."
                                    class="w-full rounded-2xl border border-gray-200 bg-gray-50/50 pl-3.5 pr-9 py-2.5 text-xs text-gray-800 focus:border-brand-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-white transition cursor-pointer"
                                    autocomplete="off">
                                <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
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
                                class="w-full rounded-2xl border border-gray-200 bg-gray-50/50 px-3.5 py-2.5 text-xs text-gray-800 focus:border-brand-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-white transition cursor-pointer">
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
                                class="w-full rounded-2xl border border-gray-200 bg-gray-50/50 px-3.5 py-2.5 text-xs text-gray-800 focus:border-brand-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-white transition cursor-pointer">
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
                                class="w-full rounded-2xl border border-gray-200 bg-gray-50/50 px-3.5 py-2.5 text-xs text-gray-800 focus:border-brand-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-white transition cursor-pointer">
                                <option value="all" {{ $mediaType === 'all' ? 'selected' : '' }}>Semua (Foto & Video)</option>
                                <option value="image" {{ $mediaType === 'image' ? 'selected' : '' }}>Hanya Foto ({{ $teacherStats['total_images'] }})</option>
                                <option value="video" {{ $mediaType === 'video' ? 'selected' : '' }}>Hanya Video ({{ $teacherStats['total_videos'] }})</option>
                            </select>
                        </div>
                    </div>

                    <!-- Baris Bawah: Pencarian Materi & Tombol Action -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <!-- Search Keyword (Materi/Catatan/Mapel/Kelas) -->
                        <div class="relative w-full md:w-96">
                            <input type="text" name="q" value="{{ $search }}"
                                placeholder="Cari materi pembelajaran atau jurnal KBM..."
                                class="w-full rounded-2xl border border-gray-200 bg-gray-50/50 py-2.5 pl-10 pr-8 text-xs text-gray-800 focus:border-brand-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-white transition">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                                <i class="fas fa-search text-xs"></i>
                            </span>
                            @if(!empty($search))
                                <a href="{{ route('publik.dokumentasi-guru', $guru->id) }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                                    <i class="fas fa-times text-xs"></i>
                                </a>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2 self-end md:self-auto shrink-0">
                            @if(!empty($startDate) || !empty($endDate) || !empty($kelasId) || !empty($mapelId) || $mediaType !== 'all' || !empty($search))
                                <a href="{{ route('publik.dokumentasi-guru', $guru->id) }}"
                                   class="inline-flex items-center gap-1.5 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 transition shadow-xs">
                                    <i class="fas fa-undo text-[10px]"></i>
                                    <span>Reset Filter</span>
                                </a>
                            @endif
                            <button type="submit"
                                class="inline-flex items-center gap-2 rounded-2xl bg-brand-500 hover:bg-brand-600 px-5 py-2.5 text-xs font-semibold text-white transition shadow-sm cursor-pointer">
                                <i class="fas fa-filter text-[10px]"></i>
                                <span>Terapkan Filter</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ========================= VIEW MODE SWITCHER & HEADER LIST ========================= -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>Dokumentasi Pembelajaran</span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                            {{ $allDocs->count() }} Media
                        </span>
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Menampilkan dokumentasi foto & video dengan rincian tanggal, kelas, dan jurnal pembelajaran.
                    </p>
                </div>

                <!-- Tabs: Mode Tampilan -->
                <div class="inline-flex p-1 rounded-2xl bg-gray-200/60 dark:bg-gray-800/80 border border-gray-200/50 dark:border-gray-700/50 self-start sm:self-auto shrink-0">
                    <button type="button" @click="activeTab = 'gallery'"
                            :class="activeTab === 'gallery' ? 'bg-white dark:bg-gray-900 text-brand-600 dark:text-brand-400 shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 font-medium'"
                            class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer">
                        <i class="fas fa-th-large text-xs"></i>
                        <span>Galeri Media ({{ $allDocs->count() }})</span>
                    </button>
                    <button type="button" @click="activeTab = 'sessions'"
                            :class="activeTab === 'sessions' ? 'bg-white dark:bg-gray-900 text-brand-600 dark:text-brand-400 shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 font-medium'"
                            class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs transition cursor-pointer">
                        <i class="fas fa-book-reader text-xs"></i>
                        <span>Sesi Jurnal KBM ({{ $filteredJurnals->count() }})</span>
                    </button>
                </div>
            </div>

            <!-- ========================= TAB 1: GALERI MEDIA (FOTO & VIDEO) ========================= -->
            <div x-show="activeTab === 'gallery'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                @if($allDocs->isNotEmpty())
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-5">
                        @foreach($allDocs as $doc)
                            <div class="group relative rounded-3xl overflow-hidden border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col justify-between">
                                <div>
                                    <!-- Thumbnail Media -->
                                    @if($doc['is_video'])
                                        <!-- Video Thumbnail Card with Play Button -->
                                        <div class="aspect-square w-full bg-gray-950 flex items-center justify-center relative cursor-pointer overflow-hidden group/thumb"
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

                                            <!-- Centered Play Icon Overlay -->
                                            <div class="absolute inset-0 bg-black/25 flex items-center justify-center group-hover/thumb:bg-black/15 transition">
                                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-500/95 text-white shadow-xl group-hover/thumb:scale-110 transition duration-300">
                                                    <i class="fas fa-play text-sm ml-0.5"></i>
                                                </div>
                                            </div>

                                            <!-- Badge VIDEO -->
                                            <span style="position: absolute; top: 10px; left: 10px; z-index: 20;"
                                                  class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-amber-500 text-white flex items-center gap-1 shadow-md">
                                                <i class="fas fa-video text-[10px]"></i> VIDEO
                                            </span>
                                        </div>
                                    @else
                                        <!-- Photo Thumbnail Card with Hover Zoom -->
                                        <div class="aspect-square w-full overflow-hidden bg-gray-100 dark:bg-gray-900 relative cursor-pointer group/thumb"
                                             @click="openPhotoModal('{{ $doc['view_url'] }}', '{{ addslashes($doc['name']) }}')">
                                            <img src="{{ $doc['thumbnail_url'] ?? $doc['view_url'] }}" alt="{{ $doc['name'] }}" loading="lazy"
                                                 class="w-full h-full object-cover group-hover/thumb:scale-105 transition duration-300">
                                            <div class="absolute inset-0 bg-black/0 group-hover/thumb:bg-black/20 transition flex items-center justify-center opacity-0 group-hover/thumb:opacity-100">
                                                <div class="flex h-11 w-11 items-center justify-center rounded-full bg-white/95 text-gray-900 shadow-xl group-hover/thumb:scale-110 transition">
                                                    <i class="fas fa-search-plus text-sm"></i>
                                                </div>
                                            </div>

                                            <!-- Badge FOTO -->
                                            <span style="position: absolute; top: 10px; left: 10px; z-index: 20;"
                                                  class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-blue-600 text-white flex items-center gap-1 shadow-md">
                                                <i class="fas fa-image text-[10px]"></i> FOTO
                                            </span>
                                        </div>
                                    @endif

                                    <!-- Informasi KBM: Tanggal, Kelas, Mapel & Jurnal -->
                                    <div class="p-3.5 sm:p-4 space-y-2.5">
                                        <!-- Tanggal & Jam Ke -->
                                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                            <span class="font-bold flex items-center gap-1.5 text-gray-800 dark:text-gray-200">
                                                <i class="fas fa-calendar-alt text-brand-500 text-xs"></i>
                                                {{ \Carbon\Carbon::parse($doc['tanggal'])->translatedFormat('d M Y') }}
                                            </span>
                                            <span class="font-mono text-[11px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                                                Jam {{ $doc['jam_ke'] }}
                                            </span>
                                        </div>

                                        <!-- Badge Kelas & Mapel -->
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300 border border-blue-200/50 dark:border-blue-500/20">
                                                <i class="fas fa-chalkboard text-[10px] mr-1"></i>{{ $doc['nama_kelas'] }}
                                            </span>
                                            <span class="px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 border border-emerald-200/50 dark:border-emerald-500/20 truncate max-w-[140px]" title="{{ $doc['nama_mapel'] }}">
                                                <i class="fas fa-book text-[10px] mr-1"></i>{{ $doc['nama_mapel'] }}
                                            </span>
                                        </div>

                                        <!-- Jurnal: Materi & Catatan Pembelajaran -->
                                        <div class="pt-1 border-t border-gray-100 dark:border-gray-800/80 space-y-1">
                                            <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 flex items-center gap-1">
                                                <i class="fas fa-book-reader text-[9px]"></i>
                                                <span>Materi KBM</span>
                                            </div>
                                            <h4 class="text-xs sm:text-sm font-bold text-gray-900 dark:text-white line-clamp-2 leading-snug" title="{{ $doc['materi'] }}">
                                                {{ $doc['materi'] ?: 'Kegiatan Belajar Mengajar' }}
                                            </h4>

                                            @if(!empty($doc['catatan']))
                                                <p class="text-[11px] text-gray-600 dark:text-gray-300 line-clamp-2 bg-gray-50 dark:bg-gray-800/50 p-2 rounded-xl border border-gray-100 dark:border-gray-800 italic mt-1.5">
                                                    "{{ $doc['catatan'] }}"
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer Action: Detail Jurnal & Unduh Media -->
                                <div class="px-3.5 py-2.5 border-t border-gray-100 dark:border-gray-800 bg-gray-50/60 dark:bg-gray-800/40 flex items-center justify-between text-xs">
                                    <button type="button"
                                            @click="openJournalModal(@js($doc))"
                                            class="text-brand-600 hover:text-brand-700 dark:text-brand-400 font-semibold flex items-center gap-1.5 hover:underline cursor-pointer">
                                        <i class="fas fa-info-circle text-[11px]"></i>
                                        <span>Rincian Jurnal</span>
                                    </button>

                                    <a href="{{ $doc['download_url'] }}" target="_blank" download
                                       class="inline-flex items-center gap-1 text-gray-500 hover:text-brand-600 dark:hover:text-brand-400 p-1 font-medium transition"
                                       title="Unduh Berkas Ini">
                                        <i class="fas fa-download text-xs"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- Empty State Galeri Media -->
                    <div class="rounded-3xl border border-gray-200 bg-white p-12 text-center shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/15 mb-4">
                            <i class="fas fa-images text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white">
                            Tidak Ada Media Dokumentasi Ditemukan
                        </h4>
                        <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                            Tidak ada foto atau video pembelajaran yang sesuai dengan kriteria filter yang Anda pilih untuk guru ini.
                        </p>
                        <div class="mt-5">
                            <a href="{{ route('publik.dokumentasi-guru', $guru->id) }}"
                               class="inline-flex items-center gap-2 rounded-2xl bg-brand-500 hover:bg-brand-600 px-5 py-2.5 text-xs font-semibold text-white transition shadow-sm">
                                <i class="fas fa-sync-alt"></i>
                                <span>Reset Semua Filter</span>
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- ========================= TAB 2: SESI JURNAL KBM (TIMELINE LENGKAP) ========================= -->
            <div x-show="activeTab === 'sessions'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                @if($filteredJurnals->isNotEmpty())
                    <div class="space-y-5">
                        @foreach($filteredJurnals as $jurnal)
                            @php
                                $sessionDocs = $jurnal->filtered_docs ?? $jurnal->dokumentasi_list;
                            @endphp
                            <div class="rounded-3xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 shadow-sm overflow-hidden p-5 sm:p-6 transition hover:shadow-md">
                                <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
                                    <!-- Sesi Info -->
                                    <div class="space-y-1.5 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300">
                                                <i class="fas fa-calendar-day text-[11px]"></i>
                                                {{ \Carbon\Carbon::parse($jurnal->tanggal)->translatedFormat('l, d F Y') }}
                                            </span>
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 font-mono">
                                                Jam ke-{{ $jurnal->jam_ke ?: ($jurnal->jadwal?->jam_ke ?: '-') }}
                                                @if(!empty($jurnal->jam_mulai))
                                                    ({{ substr($jurnal->jam_mulai, 0, 5) }} - {{ substr($jurnal->jam_selesai, 0, 5) }})
                                                @endif
                                            </span>
                                            <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300 border border-blue-200/50">
                                                <i class="fas fa-school mr-1 text-[10px]"></i>{{ $jurnal->jadwal?->kelas?->nama_kelas ?: 'Kelas' }}
                                            </span>
                                            <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300 border border-emerald-200/50">
                                                <i class="fas fa-book mr-1 text-[10px]"></i>{{ $jurnal->jadwal?->mapel?->nama_mapel ?: 'Mata Pelajaran' }}
                                            </span>
                                        </div>

                                        <!-- Materi Jurnal -->
                                        <h4 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white pt-1">
                                            {{ $jurnal->materi ?: 'Kegiatan Pembelajaran (KBM)' }}
                                        </h4>

                                        @if(!empty($jurnal->catatan))
                                            <div class="mt-2 text-xs sm:text-sm text-gray-600 dark:text-gray-300 bg-gray-50/80 dark:bg-gray-800/50 p-3 rounded-2xl border border-gray-100 dark:border-gray-800/80">
                                                <strong class="text-gray-800 dark:text-gray-200 block text-xs mb-0.5 flex items-center gap-1.5">
                                                    <i class="fas fa-comment-alt text-brand-500"></i> Catatan Guru:
                                                </strong>
                                                <span>{{ $jurnal->catatan }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Counter Dok Sesi Ini -->
                                    <div class="shrink-0 flex items-center gap-2">
                                        <span class="px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 text-xs font-bold border border-purple-200/60 dark:border-purple-800/30">
                                            <i class="fas fa-photo-video mr-1"></i> {{ count($sessionDocs) }} Berkas Media
                                        </span>
                                    </div>
                                </div>

                                <!-- Galeri Lampiran Media Dalam Sesi Ini -->
                                <div class="mt-4">
                                    <h5 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3 flex items-center gap-1.5">
                                        <i class="fas fa-images text-brand-500"></i>
                                        <span>Dokumentasi Foto & Video Pertemuan Ini:</span>
                                    </h5>

                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                        @foreach($sessionDocs as $sDoc)
                                            <div class="group relative rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-800 aspect-square bg-black shadow-xs cursor-pointer"
                                                 @if(!empty($sDoc['is_video']))
                                                     @click="openVideoModal('{{ $sDoc['view_url'] }}', '{{ addslashes($sDoc['name']) }}', '{{ $sDoc['thumbnail_url'] ?? '' }}')"
                                                 @else
                                                     @click="openPhotoModal('{{ $sDoc['view_url'] }}', '{{ addslashes($sDoc['name']) }}')"
                                                 @endif>
                                                @if(!empty($sDoc['is_video']))
                                                    @if(!empty($sDoc['thumbnail_url']))
                                                        <img src="{{ $sDoc['thumbnail_url'] }}" alt="{{ $sDoc['name'] }}" loading="lazy"
                                                             class="w-full h-full object-cover opacity-80 group-hover:opacity-100 group-hover:scale-105 transition duration-300">
                                                    @else
                                                        <video src="{{ $sDoc['view_url'] }}#t=0.5" preload="metadata" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition duration-300"></video>
                                                    @endif
                                                    <div class="absolute inset-0 bg-black/25 flex items-center justify-center group-hover:bg-black/15 transition">
                                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-500 text-white shadow-lg group-hover:scale-110 transition">
                                                            <i class="fas fa-play text-xs ml-0.5"></i>
                                                        </div>
                                                    </div>
                                                    <span style="position: absolute; top: 6px; left: 6px; z-index: 10;" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-500 text-white shadow">
                                                        VIDEO
                                                    </span>
                                                @else
                                                    <img src="{{ $sDoc['thumbnail_url'] ?? $sDoc['view_url'] }}" alt="{{ $sDoc['name'] }}" loading="lazy"
                                                         class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-center justify-center opacity-0 group-hover:opacity-100">
                                                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-gray-900 shadow">
                                                            <i class="fas fa-search-plus text-xs"></i>
                                                        </div>
                                                    </div>
                                                    <span style="position: absolute; top: 6px; left: 6px; z-index: 10;" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-blue-600 text-white shadow">
                                                        FOTO
                                                    </span>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- Empty State Sesi Jurnal -->
                    <div class="rounded-3xl border border-gray-200 bg-white p-12 text-center shadow-sm dark:border-gray-800 dark:bg-gray-900">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/15 mb-4">
                            <i class="fas fa-book-reader text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 dark:text-white">
                            Tidak Ada Sesi Jurnal KBM Ditemukan
                        </h4>
                        <p class="mt-1 text-xs sm:text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                            Tidak ditemukan sesi pembelajaran yang cocok dengan filter tanggal, kelas, atau kata kunci pencarian Anda.
                        </p>
                    </div>
                @endif
            </div>

        @endif

    </main>

    <!-- ========================= FOOTER ========================= -->
    <footer class="mt-12 border-t border-gray-200/80 bg-white dark:border-gray-800 dark:bg-gray-900 transition-colors">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-500 dark:text-gray-400">
            <div class="flex items-center gap-2">
                <i class="fas fa-graduation-cap text-brand-500 text-sm"></i>
                <span>&copy; {{ date('Y') }} {{ $school->name }}. Portal Dokumentasi & Jurnal KBM.</span>
            </div>
            <div class="flex items-center gap-4">
                <span>Guru: <strong>{{ $guru->nama }}</strong></span>
                <span class="text-gray-300 dark:text-gray-700">•</span>
                <a href="#top" @click.prevent="window.scrollTo({top: 0, behavior: 'smooth'})" class="hover:text-brand-500 transition font-medium">
                    <i class="fas fa-arrow-up mr-1"></i> Ke Atas
                </a>
            </div>
        </div>
    </footer>

    <!-- ========================= MODAL LIGHTBOX FOTO ========================= -->
    <div x-show="photoModalOpen" x-cloak style="display: none;"
         class="fixed inset-0 z-99999 flex items-center justify-center bg-black/90 p-3 sm:p-5 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="photoModalOpen = false">
        <div class="relative max-w-5xl w-full flex flex-col items-center"
             @click.away="photoModalOpen = false">
            <!-- Modal Header Bar -->
            <div class="w-full flex items-center justify-between text-white mb-3 px-2">
                <div class="flex items-center gap-2 truncate mr-3">
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-blue-600 text-white shrink-0">FOTO</span>
                    <span class="truncate font-semibold text-xs sm:text-sm text-gray-200" x-text="activeMediaName"></span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a :href="activeMediaUrl" target="_blank" download class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-xs font-semibold transition shadow cursor-pointer">
                        <i class="fas fa-download text-[11px]"></i>
                        <span class="hidden sm:inline">Unduh Foto</span>
                    </a>
                    <button type="button" @click="photoModalOpen = false" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/15 hover:bg-white/25 text-white text-xs font-semibold transition cursor-pointer">
                        <i class="fas fa-times text-[11px]"></i>
                        <span>Tutup (ESC)</span>
                    </button>
                </div>
            </div>
            <!-- Media Preview Container -->
            <div class="max-h-[82vh] overflow-hidden rounded-3xl shadow-2xl border border-white/10 bg-black flex items-center justify-center p-1 sm:p-2">
                <img :src="activeMediaUrl" :alt="activeMediaName" class="max-h-[79vh] w-auto max-w-full object-contain rounded-2xl">
            </div>
        </div>
    </div>

    <!-- ========================= MODAL VIDEO STREAMING PLAYER ========================= -->
    <div x-show="videoModalOpen" x-cloak style="display: none;"
         class="fixed inset-0 z-99999 flex items-center justify-center bg-black/90 p-3 sm:p-5 backdrop-blur-sm"
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
            <div class="w-full flex items-center justify-between text-white mb-3 px-2">
                <div class="flex items-center gap-2 truncate mr-3">
                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-500 text-white shrink-0">VIDEO</span>
                    <span class="truncate font-semibold text-xs sm:text-sm text-gray-200" x-text="activeMediaName"></span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a :href="activeMediaUrl" target="_blank" download class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-xs font-semibold transition shadow cursor-pointer">
                        <i class="fas fa-download text-[11px]"></i>
                        <span class="hidden sm:inline">Unduh Video</span>
                    </a>
                    <button type="button" @click="closeVideoModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/15 hover:bg-white/25 text-white text-xs font-semibold transition cursor-pointer">
                        <i class="fas fa-times text-[11px]"></i>
                        <span>Tutup (ESC)</span>
                    </button>
                </div>
            </div>
            <!-- Video Player Container (Mendukung potret ponsel & landscape) -->
            <div class="w-full bg-black/95 rounded-3xl overflow-hidden shadow-2xl border border-white/10 flex items-center justify-center p-1 sm:p-2">
                <video id="publicModalVideoPlayer" controls autoplay playsinline preload="auto" :poster="activeMediaPoster"
                       class="max-h-[76vh] w-auto max-w-full rounded-2xl object-contain mx-auto">
                    Browser Anda tidak mendukung tag pemutar video.
                </video>
            </div>
        </div>
    </div>

    <!-- ========================= MODAL DETAIL JURNAL KBM ========================= -->
    <div x-show="journalModalOpen" x-cloak style="display: none;"
         class="fixed inset-0 z-99999 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="journalModalOpen = false">
        <div class="relative max-w-xl w-full bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-2xl border border-gray-200 dark:border-gray-800"
             @click.away="journalModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                        <i class="fas fa-book-reader text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-sm sm:text-base font-bold text-gray-900 dark:text-white">Rincian Jurnal Pembelajaran</h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400">Dokumentasi resmi kegiatan belajar mengajar</p>
                    </div>
                </div>
                <button type="button" @click="journalModalOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1.5 rounded-lg">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>

            <div class="mt-4 space-y-3.5 text-xs">
                <!-- Sesi Metadata -->
                <div class="grid grid-cols-2 gap-2.5 bg-gray-50 dark:bg-gray-800/50 p-3 rounded-2xl border border-gray-100 dark:border-gray-800">
                    <div>
                        <span class="text-[10px] text-gray-400 uppercase font-semibold block">Tanggal KBM</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200" x-text="activeJournal.tanggal_formatted"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 uppercase font-semibold block">Jam Pelajaran</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200 font-mono" x-text="'Jam ke-' + (activeJournal.jam_ke || '-')"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 uppercase font-semibold block">Kelas</span>
                        <span class="font-bold text-blue-600 dark:text-blue-400" x-text="activeJournal.nama_kelas"></span>
                    </div>
                    <div>
                        <span class="text-[10px] text-gray-400 uppercase font-semibold block">Mata Pelajaran</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="activeJournal.nama_mapel"></span>
                    </div>
                </div>

                <!-- Materi KBM -->
                <div>
                    <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider block mb-1">
                        Materi Pembelajaran:
                    </span>
                    <div class="p-3.5 rounded-2xl bg-brand-50/50 dark:bg-brand-500/10 border border-brand-200/50 dark:border-brand-500/20 text-xs sm:text-sm font-semibold text-gray-900 dark:text-white"
                         x-text="activeJournal.materi || 'Tidak ada materi tertulis.'">
                    </div>
                </div>

                <!-- Catatan Guru -->
                <template x-if="activeJournal.catatan">
                    <div>
                        <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider block mb-1">
                            Catatan / Evaluasi Pembelajaran:
                        </span>
                        <div class="p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-800 text-xs text-gray-700 dark:text-gray-300 italic"
                             x-text="activeJournal.catatan">
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                <button type="button" @click="journalModalOpen = false" class="px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold transition cursor-pointer">
                    Tutup Rincian
                </button>
            </div>
        </div>
    </div>

    <!-- Flatpickr Script -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/id.js"></script>

    <script>
    function initPublicFlatpickr() {
        const config = {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d M Y",
            altInputClass: "w-full rounded-2xl border border-gray-200 bg-gray-50/50 pl-3.5 pr-9 py-2.5 text-xs text-gray-800 focus:border-brand-500 focus:bg-white focus:outline-none dark:border-gray-800 dark:bg-gray-800 dark:text-white transition cursor-pointer",
            allowInput: false,
            locale: "id"
        };
        if (window.flatpickr) {
            window.flatpickr("#public_filter_start_date", config);
            window.flatpickr("#public_filter_end_date", config);
        } else {
            setTimeout(initPublicFlatpickr, 100);
        }
    }

    document.addEventListener('DOMContentLoaded', initPublicFlatpickr);

    function publicDokumentasiApp() {
        return {
            activeTab: '{{ $viewMode === "sessions" ? "sessions" : "gallery" }}',
            photoModalOpen: false,
            videoModalOpen: false,
            journalModalOpen: false,
            activeMediaUrl: '',
            activeMediaPoster: '',
            activeMediaName: '',
            activeJournal: {},

            toast: {
                show: false,
                title: '',
                message: ''
            },

            init() {
                this.$nextTick(() => {
                    initPublicFlatpickr();
                });
            },

            showToast(title, message) {
                this.toast.title = title;
                this.toast.message = message;
                this.toast.show = true;
                setTimeout(() => {
                    this.toast.show = false;
                }, 3500);
            },

            copyShareLink() {
                const url = window.location.href;
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(url).then(() => {
                        this.showToast('Tautan Berhasil Disalin!', 'Link halaman publik guru telah disalin ke clipboard.');
                    }).catch(() => {
                        this.fallbackCopyText(url);
                    });
                } else {
                    this.fallbackCopyText(url);
                }
            },

            fallbackCopyText(text) {
                const textArea = document.createElement("textarea");
                textArea.value = text;
                textArea.style.position = "fixed";
                textArea.style.left = "-999999px";
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                    this.showToast('Tautan Berhasil Disalin!', 'Link halaman publik guru telah disalin ke clipboard.');
                } catch (err) {
                    prompt("Salin tautan berikut:", text);
                }
                document.body.removeChild(textArea);
            },

            toggleTheme() {
                const isDark = document.documentElement.classList.contains('dark');
                if (isDark) {
                    document.documentElement.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                } else {
                    document.documentElement.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                }
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
                    const vid = document.getElementById('publicModalVideoPlayer');
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
                const vid = document.getElementById('publicModalVideoPlayer');
                if (vid) {
                    vid.pause();
                    vid.src = '';
                    vid.removeAttribute('poster');
                }
                this.activeMediaPoster = '';
                this.videoModalOpen = false;
            },

            openJournalModal(doc) {
                let formattedDate = doc.tanggal;
                try {
                    const d = new Date(doc.tanggal);
                    formattedDate = d.toLocaleDateString('id-ID', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                } catch (e) {}

                this.activeJournal = {
                    ...doc,
                    tanggal_formatted: formattedDate
                };
                this.journalModalOpen = true;
            }
        }
    }
    </script>
</body>
</html>
