@extends('layouts.app')

@section('title', 'Live Monitoring Absensi')

@section('content')
    <div class="flex flex-col gap-6">
        {{-- 1. UNIFIED HEADER BAR (Rapi, Bersih, Seragam dengan Mode Box) --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-4 sm:px-6 sm:py-4 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                
                {{-- Sisi Kiri: Judul, Indikator Real-time, dan Subtitle --}}
                <div class="flex items-center gap-3.5">
                    <div class="relative flex h-3.5 w-3.5 shrink-0">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-red-500"></span>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h2 class="text-xl sm:text-2xl font-black text-gray-800 dark:text-white tracking-tight">
                                LIVE Monitoring Absensi
                            </h2>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span>Real-time</span>
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Pemantauan aktivitas kehadiran dan log absensi siswa secara langsung
                        </p>
                    </div>
                </div>

                {{-- Sisi Kanan: Action Toolbar & Jam Digital --}}
                <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                    {{-- Mode Switcher (Tab) --}}
                    <div class="inline-flex items-center rounded-xl bg-gray-100 dark:bg-gray-800 p-1 text-xs">
                        <a href="{{ route('live.boxes') }}"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 font-semibold text-gray-600 dark:text-gray-400 hover:text-brand-500 dark:hover:text-white transition">
                            <i class="fas fa-th-large"></i> Mode Box
                        </a>
                        <span class="inline-flex items-center gap-1.5 rounded-lg bg-white dark:bg-gray-700 px-2.5 py-1 font-bold text-brand-500 dark:text-white shadow-2xs">
                            <i class="fas fa-table"></i> Mode Tabel
                        </span>
                    </div>

                    {{-- Fullscreen Toggle --}}
                    <button type="button" onclick="toggleDashboardFullscreen()" id="btn-fullscreen-toggle"
                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-xs font-bold text-white transition-all shadow-sm">
                        <i class="fas fa-expand" id="icon-fs"></i> <span id="label-fs">Fullscreen</span>
                    </button>

                    {{-- Divider --}}
                    <div class="hidden xl:block h-8 w-px bg-gray-200 dark:bg-gray-800 mx-1"></div>

                    {{-- Clock Widget --}}
                    <div class="flex items-center gap-3 pl-1 shrink-0">
                        <div class="text-right">
                            <p id="live-date" class="text-[10px] text-gray-400 dark:text-gray-500 font-bold uppercase tracking-wider">
                                --
                            </p>
                            <p id="live-clock" class="text-xl sm:text-2xl font-black text-brand-500 font-mono tracking-tight leading-none mt-0.5">--:--:--</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- Top Row: Main Stats (4 Cards) --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            {{-- Total Siswa --}}
            <div
                class="bg-white dark:bg-boxdark rounded-xl shadow-sm border-l-4 border-blue-500 p-6 flex items-center justify-between transition-all hover:shadow-md">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Siswa</p>
                    <h3 id="stat-total" class="text-3xl font-bold text-gray-800 dark:text-white mt-1">--</h3>
                </div>
                <div class="h-12 w-12 rounded-full bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                    <i class="fas fa-users text-blue-500 text-xl"></i>
                </div>
            </div>

            {{-- Sudah Absen --}}
            <div
                class="bg-white dark:bg-boxdark rounded-xl shadow-sm border-l-4 border-green-500 p-6 flex items-center justify-between transition-all hover:shadow-md">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sudah Absen</p>
                    <h3 id="stat-absen" class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1">--</h3>
                </div>
                <div class="h-12 w-12 rounded-full bg-green-50 dark:bg-green-900/20 flex items-center justify-center">
                    <i class="fas fa-fingerprint text-green-500 text-xl"></i>
                </div>
            </div>

            {{-- Belum Absen --}}
            <div
                class="bg-white dark:bg-boxdark rounded-xl shadow-sm border-l-4 border-orange-500 p-6 flex items-center justify-between transition-all hover:shadow-md">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Belum Absen</p>
                    <h3 id="stat-belum" class="text-3xl font-bold text-orange-600 dark:text-orange-400 mt-1">--</h3>
                </div>
                <div class="h-12 w-12 rounded-full bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center">
                    <i class="fas fa-hourglass-half text-orange-500 text-xl"></i>
                </div>
            </div>

            {{-- Tidak Hadir (Alpha + Izin + Sakit) --}}
            <div
                class="bg-white dark:bg-boxdark rounded-xl shadow-sm border-l-4 border-red-500 p-6 flex items-center justify-between transition-all hover:shadow-md">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tidak Hadir</p>
                    <h3 id="stat-tidak-hadir" class="text-3xl font-bold text-red-600 dark:text-red-400 mt-1">--</h3>
                </div>
                <div class="h-12 w-12 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center">
                    <i class="fas fa-user-slash text-red-500 text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Middle Row: Status Details (5 Cards) --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            {{-- Hadir --}}
            <div
                class="bg-white dark:bg-boxdark rounded-lg p-4 border border-stroke dark:border-strokedark flex items-center gap-4">
                <div
                    class="h-10 w-10 rounded bg-green-50 dark:bg-green-900/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-check text-green-500"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Hadir</p>
                    <p id="stat-hadir" class="text-xl font-bold text-gray-800 dark:text-white">--</p>
                </div>
            </div>
            {{-- Terlambat --}}
            <div
                class="bg-white dark:bg-boxdark rounded-lg p-4 border border-stroke dark:border-strokedark flex items-center gap-4">
                <div
                    class="h-10 w-10 rounded bg-orange-50 dark:bg-orange-900/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-clock text-orange-500"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Terlambat</p>
                    <p id="stat-terlambat" class="text-xl font-bold text-gray-800 dark:text-white">--</p>
                </div>
            </div>
            {{-- Alpha --}}
            <div
                class="bg-white dark:bg-boxdark rounded-lg p-4 border border-stroke dark:border-strokedark flex items-center gap-4">
                <div class="h-10 w-10 rounded bg-red-50 dark:bg-red-900/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-times text-red-500"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Alpha</p>
                    <p id="stat-alpha" class="text-xl font-bold text-gray-800 dark:text-white">--</p>
                </div>
            </div>
            {{-- Izin --}}
            <div
                class="bg-white dark:bg-boxdark rounded-lg p-4 border border-stroke dark:border-strokedark flex items-center gap-4">
                <div
                    class="h-10 w-10 rounded bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-envelope text-blue-500"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Izin</p>
                    <p id="stat-izin" class="text-xl font-bold text-gray-800 dark:text-white">--</p>
                </div>
            </div>
            {{-- Sakit --}}
            <div
                class="bg-white dark:bg-boxdark rounded-lg p-4 border border-stroke dark:border-strokedark flex items-center gap-4">
                <div
                    class="h-10 w-10 rounded bg-yellow-50 dark:bg-yellow-900/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-briefcase-medical text-yellow-500"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-medium">Sakit</p>
                    <p id="stat-sakit" class="text-xl font-bold text-gray-800 dark:text-white">--</p>
                </div>
            </div>
        </div>

        {{-- Bottom Section: Real-time Log --}}
        <div
            class="bg-white dark:bg-boxdark rounded-xl shadow-sm border border-stroke dark:border-strokedark overflow-hidden">
            <div
                class="bg-gray-50 dark:bg-meta-4 px-6 py-4 border-b border-stroke dark:border-strokedark flex items-center justify-between">
                <h3 class="font-bold text-gray-800 dark:text-white flex items-center gap-2">
                    <i class="fas fa-list-ul text-brand-500"></i>
                    Aktivitas Terbaru
                </h3>
                <span class="text-xs text-gray-500 dark:text-gray-400 italic">Terakhir diperbarui: <span
                        id="last-update">--</span></span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left bg-gray-100 dark:bg-meta-4/50">
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Waktu</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Aksi</th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase">Keterangan
                            </th>
                            <th class="px-6 py-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase text-center">
                                Status</th>
                        </tr>
                    </thead>
                    <tbody id="log-body">
                        {{-- Data injected via JS --}}
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-400 italic">
                                Memuat data aktivitas...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('live-date').textContent = now.toLocaleDateString('id-ID', options);
            document.getElementById('live-clock').textContent = now.toLocaleTimeString('id-ID', { hour12: false });
        }

        async function fetchLiveData() {
            try {
                const response = await fetch('{{ route('live.data') }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                const data = await response.json();

                // Update Stats
                document.getElementById('stat-total').textContent = data.stats.total;
                document.getElementById('stat-absen').textContent = data.stats.absen;
                document.getElementById('stat-belum').textContent = data.stats.belum;
                document.getElementById('stat-tidak-hadir').textContent = data.stats.tidak_hadir;
                document.getElementById('stat-hadir').textContent = data.stats.hadir;
                document.getElementById('stat-terlambat').textContent = data.stats.terlambat;
                document.getElementById('stat-alpha').textContent = data.stats.alpha;
                document.getElementById('stat-izin').textContent = data.stats.izin;
                document.getElementById('stat-sakit').textContent = data.stats.sakit;

                document.getElementById('last-update').textContent = new Date().toLocaleTimeString('id-ID', { hour12: false });

                // Update Logs
                const logBody = document.getElementById('log-body');
                logBody.innerHTML = '';

                if (data.logs.length === 0) {
                    logBody.innerHTML = '<tr><td colspan="4" class="px-6 py-10 text-center text-gray-400 italic">Belum ada aktivitas hari ini.</td></tr>';
                } else {
                    data.logs.forEach(log => {
                        let actionBadge = '';
                        switch (log.action) {
                            case 'checkin_success': 
                                actionBadge = '<span class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 px-2 py-0.5 rounded text-[10px] font-bold uppercase">MASUK</span>'; 
                                break;
                            case 'checkout_success': 
                                actionBadge = '<span class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 px-2 py-0.5 rounded text-[10px] font-bold uppercase">PULANG</span>'; 
                                break;
                            case 'terlambat': 
                                actionBadge = '<span class="bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 px-2 py-0.5 rounded text-[10px] font-bold uppercase">TERLAMBAT</span>'; 
                                break;
                            case 'izin': 
                                actionBadge = '<span class="bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400 px-2 py-0.5 rounded text-[10px] font-bold uppercase">IZIN</span>'; 
                                break;
                            case 'sakit': 
                                actionBadge = '<span class="bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 px-2 py-0.5 rounded text-[10px] font-bold uppercase">SAKIT</span>'; 
                                break;
                            case 'alpha': 
                                actionBadge = '<span class="bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400 px-2 py-0.5 rounded text-[10px] font-bold uppercase">ALPHA</span>'; 
                                break;
                            case 'gate_access': 
                                actionBadge = '<span class="bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400 px-2 py-0.5 rounded text-[10px] font-bold uppercase">GERBANG</span>'; 
                                break;
                            case 'unknown_card': 
                                actionBadge = '<span class="bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 px-2 py-0.5 rounded text-[10px] font-bold uppercase">KARTU ASING</span>'; 
                                break;
                            default: 
                                actionBadge = `<span class="bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 px-2 py-0.5 rounded text-[10px] font-bold uppercase">${log.action}</span>`;
                        }

                        const statusIcon = log.success
                            ? '<i class="fas fa-check-circle text-green-500"></i>'
                            : '<i class="fas fa-exclamation-circle text-red-500"></i>';

                        let msgDisplay = log.message || '';
                        if (msgDisplay.includes('[Sync]')) {
                            msgDisplay = `<span class="inline-flex items-center gap-1 rounded bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 border border-blue-200 dark:border-blue-800 px-1.5 py-0.5 text-[10px] font-bold mr-1.5"><i class="fas fa-sync-alt"></i> Sync</span>` + msgDisplay.replace('[Sync]', '').trim();
                        }

                        const row = `
                                <tr class="border-t border-stroke dark:border-strokedark hover:bg-gray-50 dark:hover:bg-meta-4/20 transition-colors animate-fade-in">
                                    <td class="px-6 py-4 text-sm font-mono text-gray-600 dark:text-gray-400">${log.time}</td>
                                    <td class="px-6 py-4">${actionBadge}</td>
                                    <td class="px-6 py-4">
                                        <p class="text-sm font-medium text-gray-800 dark:text-white">${msgDisplay}</p>
                                        <p class="text-[10px] text-gray-400 font-mono">${log.uid || '-'}</p>
                                    </td>
                                    <td class="px-6 py-4 text-center text-xl">${statusIcon}</td>
                                </tr>
                            `;
                        logBody.insertAdjacentHTML('beforeend', row);
                    });
                }
            } catch (error) {
                console.error('Failed to fetch live data:', error);
            }
        }

        function updateDashboardFullscreenUi(isFs) {
            const iconFs = document.getElementById('icon-fs');
            const labelFs = document.getElementById('label-fs');
            if (iconFs && labelFs) {
                if (isFs) {
                    iconFs.className = 'fas fa-compress';
                    labelFs.innerText = 'Exit Fullscreen';
                } else {
                    iconFs.className = 'fas fa-expand';
                    labelFs.innerText = 'Fullscreen';
                }
            }
        }

        function toggleDashboardFullscreen() {
            const isCurrentlyFs = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement || (window.Alpine && Alpine.store('sidebar') && Alpine.store('sidebar').isFullScreen));
            
            if (!isCurrentlyFs) {
                const docEl = document.documentElement;
                const req = docEl.requestFullscreen || docEl.webkitRequestFullscreen || docEl.mozRequestFullScreen || docEl.msRequestFullscreen;
                if (req) {
                    req.call(docEl).catch(err => {
                        console.warn('Native requestFullscreen failed:', err);
                    });
                }
                if (window.Alpine && Alpine.store('sidebar')) {
                    Alpine.store('sidebar').setFullScreen(true);
                }
                updateDashboardFullscreenUi(true);
            } else {
                const exit = document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen || document.msExitFullscreen;
                if (exit && document.fullscreenElement) {
                    exit.call(document).catch(err => console.warn(err));
                }
                if (window.Alpine && Alpine.store('sidebar')) {
                    Alpine.store('sidebar').setFullScreen(false);
                }
                updateDashboardFullscreenUi(false);
            }
        }

        ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach(evt => {
            document.addEventListener(evt, () => {
                const isFs = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
                if (window.Alpine && Alpine.store('sidebar')) {
                    Alpine.store('sidebar').setFullScreen(isFs);
                }
                updateDashboardFullscreenUi(isFs);
            });
        });

        // Initialize
        setInterval(updateClock, 1000);
        setInterval(fetchLiveData, 3000); // Update every 3 seconds
        updateClock();
        fetchLiveData();
    </script>

    <style>
        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fade-in 0.3s ease-out forwards;
        }
    </style>
@endpush