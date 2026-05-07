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
            background-image: radial-gradient(circle, #e5e7eb 1.5px, transparent 1.5px);
            background-size: 40px 40px;
        }
        .dark .bg-grid {
            background-image: radial-gradient(circle, #1f2937 1.5px, transparent 1.5px);
        }
        @keyframes pulse-red {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
            100% { transform: scale(1); opacity: 1; }
        }
        .live-indicator {
            animation: pulse-red 1.5s ease-in-out infinite;
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
        <div class="flex-1 flex flex-row gap-8 overflow-hidden">
            
            {{-- Left Column: Stats (Fixed width to prevent squashing) --}}
            <div class="w-[380px] flex flex-col gap-5 overflow-y-auto no-scrollbar flex-shrink-0">
                
                {{-- Major Stats --}}
                <div class="bg-white dark:bg-boxdark rounded-3xl shadow-xl border-l-[12px] border-blue-600 p-8 flex flex-col justify-center min-h-[160px]">
                    <p class="text-xs font-black text-blue-600 dark:text-blue-400 uppercase tracking-[0.2em] mb-2">Total Siswa</p>
                    <div class="flex items-baseline gap-3">
                        <span id="stat-total" class="text-7xl font-black text-gray-900 dark:text-white leading-none">--</span>
                        <span class="text-xl font-bold text-gray-400 uppercase">Siswa</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-boxdark rounded-3xl shadow-xl border-l-[12px] border-red-600 p-8 flex flex-col justify-center min-h-[160px]">
                    <p class="text-xs font-black text-red-600 dark:text-red-400 uppercase tracking-[0.2em] mb-2">Siswa Absen</p>
                    <div class="flex items-baseline gap-3">
                        <span id="stat-absen" class="text-7xl font-black text-red-600 dark:text-red-400 leading-none">--</span>
                        <span class="text-xl font-bold text-red-300 uppercase">A/S/I/B</span>
                    </div>
                </div>

                <div class="bg-white dark:bg-boxdark rounded-3xl shadow-xl border-l-[12px] border-orange-500 p-8 flex flex-col justify-center min-h-[160px]">
                    <p class="text-xs font-black text-orange-500 uppercase tracking-[0.2em] mb-2">Belum Tap</p>
                    <div class="flex items-baseline gap-3">
                        <span id="stat-belum" class="text-7xl font-black text-orange-600 dark:text-orange-400 leading-none">--</span>
                        <span class="text-xl font-bold text-orange-300 uppercase">Orang</span>
                    </div>
                </div>

                {{-- Detail Stats Grid --}}
                <div class="grid grid-cols-2 gap-4 mt-auto">
                    <div class="bg-emerald-500 rounded-3xl p-6 text-white shadow-lg shadow-emerald-500/30 flex flex-col items-center justify-center text-center">
                        <p class="text-[10px] font-black opacity-80 uppercase tracking-widest mb-1">Hadir</p>
                        <p id="stat-hadir" class="text-4xl font-black">--</p>
                    </div>
                    <div class="bg-rose-600 rounded-3xl p-6 text-white shadow-lg shadow-rose-600/30 flex flex-col items-center justify-center text-center">
                        <p class="text-[10px] font-black opacity-80 uppercase tracking-widest mb-1">Alpha</p>
                        <p id="stat-alpha" class="text-4xl font-black">--</p>
                    </div>
                    <div class="bg-sky-500 rounded-3xl p-6 text-white shadow-lg shadow-sky-500/30 flex flex-col items-center justify-center text-center">
                        <p class="text-[10px] font-black opacity-80 uppercase tracking-widest mb-1">Izin</p>
                        <p id="stat-izin" class="text-4xl font-black">--</p>
                    </div>
                    <div class="bg-amber-500 rounded-3xl p-6 text-white shadow-lg shadow-amber-500/30 flex flex-col items-center justify-center text-center">
                        <p class="text-[10px] font-black opacity-80 uppercase tracking-widest mb-1">Sakit</p>
                        <p id="stat-sakit" class="text-4xl font-black">--</p>
                    </div>
                </div>
            </div>

            {{-- Right Column: Live Logs (Takes remaining space) --}}
            <div class="flex-1 bg-white dark:bg-boxdark rounded-[3rem] shadow-2xl border border-stroke dark:border-strokedark flex flex-col overflow-hidden">
                <div class="px-12 py-10 border-b border-stroke dark:border-strokedark flex items-center justify-between bg-gray-50 dark:bg-meta-4/10">
                    <div>
                        <h3 class="text-3xl font-black text-gray-900 dark:text-white tracking-tighter">AKTIVITAS TERBARU</h3>
                        <p class="text-gray-500 dark:text-gray-400 font-medium">Monitoring absensi real-time dari seluruh perangkat terhubung</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <span class="text-[10px] font-black text-brand-500 uppercase tracking-[0.3em]">Live Sync</span>
                            <div class="flex justify-end gap-1 mt-1">
                                <div class="h-1.5 w-12 bg-brand-500 rounded-full animate-pulse"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex-1 overflow-y-auto px-8 py-6 no-scrollbar bg-gray-50/30 dark:bg-transparent">
                    <table class="w-full border-separate border-spacing-y-4">
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
                            <tr class="bg-white dark:bg-meta-4/20 rounded-[2rem] shadow-sm transition-all hover:shadow-md hover:bg-gray-50 dark:hover:bg-meta-4/30">
                                <td class="px-10 py-6 rounded-l-[2rem] w-36">
                                    <p class="text-3xl font-black font-mono text-gray-400 dark:text-gray-500">${log.time}</p>
                                </td>
                                <td class="px-6 py-6 w-44">
                                    <span class="${actionColor} px-5 py-2 rounded-full text-xs font-black tracking-[0.2em] shadow-sm">${actionLabel}</span>
                                </td>
                                <td class="px-6 py-6">
                                    <p class="text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight">${log.message}</p>
                                    <p class="text-sm text-gray-400 font-mono tracking-tight">${log.uid || '-'}</p>
                                </td>
                                <td class="px-10 py-6 rounded-r-[2rem] text-right">
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
