<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LIVE MONITORING | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <style>
        [x-cloak] { display: none !important; }
        .bg-grid {
            background-image: radial-gradient(circle, #e5e7eb 1px, transparent 1px);
            background-size: 30px 30px;
        }
        .dark .bg-grid {
            background-image: radial-gradient(circle, #374151 1px, transparent 1px);
        }
        @keyframes pulse-red {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        .live-indicator {
            animation: pulse-red 2s infinite;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 h-full overflow-hidden bg-grid font-sans text-gray-900 dark:text-white">
    <div class="h-full flex flex-col p-6 gap-6">
        
        {{-- Top Header --}}
        <div class="flex items-center justify-between bg-white dark:bg-boxdark rounded-2xl shadow-lg border border-stroke dark:border-strokedark px-8 py-4">
            <div class="flex items-center gap-6">
                <div class="flex items-center gap-3">
                    <div class="h-4 w-4 bg-red-500 rounded-full live-indicator"></div>
                    <h1 class="text-3xl font-black tracking-tight">LIVE MONITORING</h1>
                </div>
                <div class="h-10 w-px bg-gray-200 dark:bg-gray-700"></div>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    <p id="live-date">--</p>
                    <p class="uppercase tracking-widest text-brand-500">Sistem Absensi v2.0</p>
                </div>
            </div>
            
            <div class="text-center">
                <p id="live-clock" class="text-5xl font-black font-mono text-gray-800 dark:text-white"></p>
            </div>

            <div class="flex items-center gap-4">
                <button onclick="toggleFullscreen()" class="p-3 rounded-xl bg-gray-100 dark:bg-meta-4 hover:bg-gray-200 dark:hover:bg-opacity-50 transition-colors">
                    <i class="fas fa-expand"></i>
                </button>
                <div class="h-12 w-12 rounded-2xl bg-brand-500 flex items-center justify-center shadow-lg shadow-brand-500/30">
                    <i class="fas fa-desktop text-white text-xl"></i>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="flex-1 flex flex-row gap-6 overflow-hidden">
            
            {{-- Left Column: Stats (Fixed Width) --}}
            <div class="w-1/3 flex flex-col gap-6 overflow-y-auto no-scrollbar">
                {{-- Major Stats --}}
                <div class="flex flex-col gap-4">
                    <div class="bg-white dark:bg-boxdark rounded-2xl shadow-lg border-l-[10px] border-blue-500 p-6">
                        <p class="text-xs font-bold text-gray-400 mb-1 uppercase tracking-widest">Total Siswa</p>
                        <div class="flex items-baseline gap-2">
                            <span id="stat-total" class="text-5xl font-black text-gray-800 dark:text-white">--</span>
                            <span class="text-lg font-medium text-gray-400">Siswa</span>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-boxdark rounded-2xl shadow-lg border-l-[10px] border-red-500 p-6">
                        <p class="text-xs font-bold text-red-400 mb-1 uppercase tracking-widest">Siswa Absen</p>
                        <div class="flex items-baseline gap-2">
                            <span id="stat-absen" class="text-5xl font-black text-red-600 dark:text-red-400">--</span>
                            <span class="text-lg font-medium text-red-300">A/S/I/B</span>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-boxdark rounded-2xl shadow-lg border-l-[10px] border-orange-500 p-6">
                        <p class="text-xs font-bold text-orange-400 mb-1 uppercase tracking-widest">Belum Tap</p>
                        <div class="flex items-baseline gap-2">
                            <span id="stat-belum" class="text-5xl font-black text-orange-600 dark:text-orange-400">--</span>
                            <span class="text-lg font-medium text-orange-300">Orang</span>
                        </div>
                    </div>
                </div>

                {{-- Detail Stats --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-green-500 rounded-2xl p-5 text-white shadow-lg">
                        <p class="text-[10px] font-bold opacity-80 uppercase mb-1">Hadir</p>
                        <p id="stat-hadir" class="text-3xl font-black">--</p>
                    </div>
                    <div class="bg-red-600 rounded-2xl p-5 text-white shadow-lg">
                        <p class="text-[10px] font-bold opacity-80 uppercase mb-1">Alpha</p>
                        <p id="stat-alpha" class="text-3xl font-black">--</p>
                    </div>
                    <div class="bg-blue-500 rounded-2xl p-5 text-white shadow-lg">
                        <p class="text-[10px] font-bold opacity-80 uppercase mb-1">Izin</p>
                        <p id="stat-izin" class="text-3xl font-black">--</p>
                    </div>
                    <div class="bg-yellow-500 rounded-2xl p-5 text-white shadow-lg">
                        <p class="text-[10px] font-bold opacity-80 uppercase mb-1">Sakit</p>
                        <p id="stat-sakit" class="text-3xl font-black">--</p>
                    </div>
                </div>
            </div>

            {{-- Right Column: Live Logs (Flexible Width) --}}
            <div class="flex-1 bg-white dark:bg-boxdark rounded-3xl shadow-xl border border-stroke dark:border-strokedark flex flex-col overflow-hidden">
                <div class="px-8 py-6 border-b border-stroke dark:border-strokedark flex items-center justify-between bg-gray-50 dark:bg-meta-4/10">
                    <div>
                        <h3 class="text-xl font-black text-gray-800 dark:text-white uppercase tracking-tight">Aktivitas Terbaru</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Log absensi realtime dari perangkat</p>
                    </div>
                    <div class="flex flex-col items-end">
                        <span class="text-[10px] font-bold text-brand-500 uppercase tracking-widest mb-1">Live Sync</span>
                        <div class="flex gap-1">
                            <div class="h-1 w-6 bg-brand-500 rounded-full animate-pulse"></div>
                        </div>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto p-4 no-scrollbar">
                    <table class="w-full border-separate border-spacing-y-2">
                        <tbody id="log-body">
                            {{-- Data injected via JS --}}
                            <tr>
                                <td colspan="4" class="text-center py-20 text-gray-400 italic text-xl">
                                    Menghubungkan ke server...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="flex justify-between items-center text-gray-400 text-xs font-bold tracking-[0.2em] uppercase px-4">
            <p>&copy; {{ date('Y') }} JAGAT TECH - ABSENSI MULTI TENANT</p>
            <p id="last-sync-footer">LAST SYNC: --</p>
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('live-date').textContent = now.toLocaleDateString('id-ID', options);
            document.getElementById('live-clock').textContent = now.toLocaleTimeString('id-ID', { hour12: false });
        }

        async function fetchLiveData() {
            try {
                const response = await fetch('{{ route('live.data') }}');
                const data = await response.json();

                // Update Stats with Animation
                animateValue("stat-total", data.stats.total);
                animateValue("stat-absen", data.stats.absen);
                animateValue("stat-belum", data.stats.belum);
                animateValue("stat-hadir", data.stats.hadir);
                animateValue("stat-alpha", data.stats.alpha);
                animateValue("stat-izin", data.stats.izin);
                animateValue("stat-sakit", data.stats.sakit);
                
                document.getElementById('last-sync-footer').textContent = 'LAST SYNC: ' + new Date().toLocaleTimeString('id-ID', { hour12: false });

                // Update Logs
                const logBody = document.getElementById('log-body');
                const oldContent = logBody.innerHTML;
                
                let newRows = '';
                if (data.logs.length === 0) {
                    newRows = '<tr><td colspan="4" class="px-6 py-10 text-center text-gray-400 italic">Belum ada aktivitas.</td></tr>';
                } else {
                    data.logs.forEach(log => {
                        let actionColor = 'bg-gray-100 text-gray-700';
                        let actionLabel = log.action;

                        if (log.action === 'checkin_success') { actionColor = 'bg-green-100 text-green-700'; actionLabel = 'MASUK'; }
                        else if (log.action === 'checkout_success') { actionColor = 'bg-blue-100 text-blue-700'; actionLabel = 'PULANG'; }
                        else if (log.action === 'gate_access') { actionColor = 'bg-purple-100 text-purple-700'; actionLabel = 'GERBANG'; }
                        else if (log.action === 'unknown_card') { actionColor = 'bg-red-100 text-red-700'; actionLabel = 'UNKNOWN'; }

                        const statusIcon = log.success 
                            ? '<div class="h-12 w-12 rounded-full bg-green-50 dark:bg-green-900/20 flex items-center justify-center text-green-500 text-2xl"><i class="fas fa-check"></i></div>' 
                            : '<div class="h-12 w-12 rounded-full bg-red-50 dark:bg-red-900/20 flex items-center justify-center text-red-500 text-2xl"><i class="fas fa-times"></i></div>';

                        newRows += `
                            <tr class="bg-gray-50/50 dark:bg-meta-4/5 rounded-2xl transition-all hover:bg-gray-100 dark:hover:bg-meta-4/10">
                                <td class="px-6 py-3 rounded-l-2xl w-24">
                                    <p class="text-lg font-black font-mono text-gray-400">${log.time}</p>
                                </td>
                                <td class="px-4 py-3 w-32">
                                    <span class="${actionColor} px-3 py-1 rounded-full text-[9px] font-black tracking-widest">${actionLabel}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-base font-bold text-gray-800 dark:text-white uppercase tracking-tight">${log.message}</p>
                                    <p class="text-[9px] text-gray-400 font-mono tracking-tighter">${log.uid || '-'}</p>
                                </td>
                                <td class="px-6 py-3 rounded-r-2xl text-right">
                                    ${statusIcon}
                                </td>
                            </tr>
                        `;
                    });
                }
                
                if (oldContent !== newRows) {
                    logBody.innerHTML = newRows;
                }
            } catch (error) {
                console.error('Failed to fetch live data:', error);
            }
        }

        function animateValue(id, end) {
            const obj = document.getElementById(id);
            const start = parseInt(obj.textContent) || 0;
            if (start === end) return;
            
            const range = Math.abs(end - start);
            let current = start;
            const increment = end > start ? 1 : -1;
            const stepTime = Math.max(1, Math.floor(1000 / (range || 1)));
            
            const timer = setInterval(() => {
                if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                    obj.textContent = end;
                    clearInterval(timer);
                    return;
                }
                current += increment;
                obj.textContent = current;
            }, stepTime);
        }

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

        // Initialize
        setInterval(updateClock, 1000);
        setInterval(fetchLiveData, 3000);
        updateClock();
        fetchLiveData();
    </script>
</body>
</html>
