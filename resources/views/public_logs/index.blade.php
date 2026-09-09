<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Live Request & API Log Inspector | Jagat Tech</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/logo/logo-icon.ico') }}">

    <!-- Google Fonts & Tailwind -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 5 Free -->
    <link href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">

    <!-- Tailwind Play CDN for standalone public page aesthetics -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        brand: {
                            50: '#f2f7ff',
                            100: '#e1edff',
                            200: '#cbe0ff',
                            300: '#a5cbff',
                            400: '#75aaff',
                            500: '#4682b4',
                            600: '#346699',
                            700: '#294f7a',
                            800: '#244366',
                            900: '#223956',
                        },
                        darkbg: '#0B1120',
                        darkcard: '#111827',
                        darkborder: '#1F2937',
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(156, 163, 175, 0.4); border-radius: 9999px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(156, 163, 175, 0.7); }
    </style>
</head>

<body x-data="publicLogInspector()" x-init="initLogger()" class="h-full bg-slate-50 text-slate-800 dark:bg-darkbg dark:text-slate-100 antialiased font-sans transition-colors duration-200">

    <div class="min-h-screen flex flex-col">

        <!-- Top Header Navigation -->
        <header class="sticky top-0 z-40 bg-white/80 dark:bg-darkcard/80 backdrop-blur-md border-b border-slate-200 dark:border-darkborder px-4 lg:px-8 py-3.5 transition-colors">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                <!-- Left: Logo & Live Status -->
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-500 text-white flex items-center justify-center shadow-md shadow-brand-500/20">
                        <i class="fas fa-satellite-dish text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">
                                Live Request Inspector
                            </h1>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                Public Live Stream
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                            Endpoint: <span class="text-brand-600 dark:text-brand-400 font-semibold">/api/*</span> (Mobile App, RFID, R307, QRIS)
                        </p>
                    </div>
                </div>

                <!-- Right: Real-time Controls & Theme Toggle -->
                <div class="flex flex-wrap items-center gap-2.5">
                    
                    <!-- Auto-Refresh Toggle -->
                    <button type="button" @click="toggleAutoRefresh()"
                        :class="isAutoRefresh ? 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-400' : 'bg-slate-100 border-slate-200 text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300'"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-semibold shadow-sm transition hover:scale-[1.02] active:scale-95">
                        <i class="fas" :class="isAutoRefresh ? 'fa-sync-alt fa-spin' : 'fa-pause'"></i>
                        <span x-text="isAutoRefresh ? 'Auto Refresh (2s)' : 'Paused'"></span>
                    </button>

                    <!-- Manual Fetch Button -->
                    <button type="button" @click="fetchLogs()" :disabled="isLoading"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition">
                        <i class="fas fa-redo-alt" :class="isLoading ? 'fa-spin text-brand-500' : ''"></i>
                        <span>Refresh</span>
                    </button>

                    <!-- Sound Toggle -->
                    <button type="button" @click="soundEnabled = !soundEnabled"
                        :title="soundEnabled ? 'Suara Notifikasi Aktif' : 'Suara Notifikasi Bisu'"
                        class="h-8 w-8 rounded-xl border border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center text-xs shadow-sm hover:text-brand-500 transition">
                        <i class="fas fa-volume-up text-brand-500" x-show="soundEnabled"></i>
                        <i class="fas fa-volume-mute text-slate-400" x-show="!soundEnabled" style="display: none;"></i>
                    </button>

                    <!-- Test Ping Simulation Dropdown -->
                    <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                        <button type="button" @click="open = !open"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-semibold shadow-sm transition">
                            <i class="fas fa-paper-plane"></i>
                            <span>Simulasi Request</span>
                            <i class="fas fa-chevron-down text-[10px] ml-0.5"></i>
                        </button>
                        
                        <div x-show="open" x-cloak
                            class="absolute right-0 mt-2 w-48 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl py-1 text-xs z-50">
                            <button type="button" @click="sendTestPing('mobile'); open = false;"
                                class="w-full text-left px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                <i class="fas fa-mobile-alt text-brand-500"></i> Tes Mobile App Request
                            </button>
                            <button type="button" @click="sendTestPing('rfid'); open = false;"
                                class="w-full text-left px-3 py-2 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 flex items-center gap-2">
                                <i class="fas fa-id-card text-emerald-500"></i> Tes RFID Tap Scan
                            </button>
                            <button type="button" @click="sendTestPing('failed'); open = false;"
                                class="w-full text-left px-3 py-2 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 flex items-center gap-2">
                                <i class="fas fa-exclamation-triangle"></i> Tes Auth Failed Request
                            </button>
                        </div>
                    </div>

                    <!-- Clear Logs -->
                    <button type="button" @click="confirmClearLogs()"
                        title="Bersihkan Semua Log"
                        class="h-8 w-8 rounded-xl border border-rose-200 bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:border-rose-900/50 dark:text-rose-400 flex items-center justify-center text-xs shadow-sm hover:bg-rose-100 transition">
                        <i class="fas fa-trash-alt"></i>
                    </button>

                    <!-- Dark Mode Toggle -->
                    <button type="button" @click="toggleTheme()"
                        class="h-8 w-8 rounded-xl border border-slate-200 bg-white dark:bg-slate-800 dark:border-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center text-xs shadow-sm hover:text-amber-500 transition">
                        <i class="fas fa-sun text-amber-500" x-show="theme === 'dark'"></i>
                        <i class="fas fa-moon text-slate-600" x-show="theme === 'light'" style="display: none;"></i>
                    </button>
                </div>

            </div>
        </header>

        <!-- Main Content Area -->
        <main class="flex-1 max-w-7xl w-full mx-auto p-4 lg:p-8 space-y-6">

            <!-- Real-time Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Total Requests Today -->
                <div class="rounded-2xl border border-slate-200 dark:border-darkborder bg-white dark:bg-darkcard p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Total Request Hari Ini</span>
                        <div class="h-8 w-8 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                            <i class="fas fa-exchange-alt text-xs"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white font-mono" x-text="stats.total_today">{{ $totalLogsToday }}</h3>
                        <span class="text-xs text-slate-400">hits</span>
                    </div>
                </div>

                <!-- Success Requests -->
                <div class="rounded-2xl border border-emerald-200/80 dark:border-emerald-900/40 bg-emerald-50/40 dark:bg-emerald-950/20 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">Request Sukses</span>
                        <div class="h-8 w-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center shadow-sm">
                            <i class="fas fa-check text-xs"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <h3 class="text-2xl font-bold text-emerald-700 dark:text-emerald-400 font-mono" x-text="stats.success_today">{{ $successLogsToday }}</h3>
                        <span class="text-xs text-emerald-600/70 dark:text-emerald-400/70">200 OK</span>
                    </div>
                </div>

                <!-- Failed / Unauthorized Requests -->
                <div class="rounded-2xl border border-rose-200/80 dark:border-rose-900/40 bg-rose-50/40 dark:bg-rose-950/20 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-rose-700 dark:text-rose-400">Gagal / Auth Failed</span>
                        <div class="h-8 w-8 rounded-xl bg-rose-500 text-white flex items-center justify-center shadow-sm">
                            <i class="fas fa-times text-xs"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <h3 class="text-2xl font-bold text-rose-700 dark:text-rose-400 font-mono" x-text="stats.failed_today">{{ $failedLogsToday }}</h3>
                        <span class="text-xs text-rose-600/70 dark:text-rose-400/70">Errors</span>
                    </div>
                </div>

                <!-- Unique Client IPs -->
                <div class="rounded-2xl border border-slate-200 dark:border-darkborder bg-white dark:bg-darkcard p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Client / Device IP Unik</span>
                        <div class="h-8 w-8 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                            <i class="fas fa-network-wired text-xs"></i>
                        </div>
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <h3 class="text-2xl font-bold text-slate-900 dark:text-white font-mono" x-text="stats.unique_ips_today">{{ $uniqueIpsToday }}</h3>
                        <span class="text-xs text-slate-400">IPs</span>
                    </div>
                </div>

            </div>

            <!-- Filter & Search Toolbar -->
            <div class="rounded-2xl border border-slate-200 dark:border-darkborder bg-white dark:bg-darkcard p-4 shadow-sm space-y-3">
                <div class="flex flex-col md:flex-row items-center gap-3">
                    
                    <!-- Search Input -->
                    <div class="relative w-full md:flex-1">
                        <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" x-model="searchQuery" @input.debounce.300ms="fetchLogs()"
                            placeholder="Cari berdasarkan IP, UID Kartu, Action, Pesan, atau Device..."
                            class="w-full pl-9 pr-4 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>

                    <!-- Category Filter -->
                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <select x-model="selectedCategory" @change="fetchLogs()"
                            class="w-full md:w-44 px-3 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-500 font-medium">
                            <option value="all">Semua Kategori</option>
                            <option value="mobile">📱 Mobile App</option>
                            <option value="rfid">💳 RFID Reader</option>
                            <option value="fingerprint">👆 Fingerprint (R307)</option>
                            <option value="auth_failed">⚠️ Auth Failed</option>
                        </select>

                        <!-- Status Filter -->
                        <select x-model="selectedStatus" @change="fetchLogs()"
                            class="w-full md:w-36 px-3 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-500 font-medium">
                            <option value="all">Semua Status</option>
                            <option value="success">✅ Sukses Saja</option>
                            <option value="failed">❌ Gagal Saja</option>
                        </select>

                        <!-- Limit Filter -->
                        <select x-model="limit" @change="fetchLogs()"
                            class="hidden sm:block px-3 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-500 font-medium">
                            <option value="25">25 Baris</option>
                            <option value="50">50 Baris</option>
                            <option value="100">100 Baris</option>
                        </select>
                    </div>

                </div>

                <!-- Active Filter Tags -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400">
                    <div class="flex items-center gap-2">
                        <span>Menampilkan: <strong class="text-slate-800 dark:text-slate-200" x-text="logs.length + ' request terbaru'"></strong></span>
                        <span x-show="searchQuery" class="px-2 py-0.5 rounded-md bg-brand-50 text-brand-600 dark:bg-brand-950/50 dark:text-brand-400 font-mono">
                            Filter: "<span x-text="searchQuery"></span>"
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-mono" x-text="'Server Time: ' + (stats.server_time || '--')"></span>
                    </div>
                </div>
            </div>

            <!-- Log Stream Table / Feed -->
            <div class="rounded-2xl border border-slate-200 dark:border-darkborder bg-white dark:bg-darkcard shadow-sm overflow-hidden flex flex-col">
                
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 dark:bg-slate-800/70 border-b border-slate-200 dark:border-darkborder text-slate-500 dark:text-slate-400 uppercase tracking-wider font-semibold">
                            <tr>
                                <th class="px-4 py-3 text-center w-12">Status</th>
                                <th class="px-4 py-3 w-40">Waktu</th>
                                <th class="px-4 py-3 w-44">Action / Method</th>
                                <th class="px-4 py-3">Pesan & Detail Payload</th>
                                <th class="px-4 py-3 w-36">IP & Client</th>
                                <th class="px-4 py-3 w-36">API Key / Device</th>
                                <th class="px-4 py-3 text-center w-20">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-darkborder font-mono text-[11px]">
                            
                            <!-- Loading Skeleton -->
                            <template x-if="isLoading && logs.length === 0">
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                        <i class="fas fa-circle-notch fa-spin text-2xl text-brand-500 mb-2"></i>
                                        <p class="font-sans text-xs">Memuat data log request...</p>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty State -->
                            <template x-if="!isLoading && logs.length === 0">
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                        <i class="fas fa-inbox text-3xl mb-2 text-slate-300 dark:text-slate-600"></i>
                                        <p class="font-sans text-xs font-semibold text-slate-600 dark:text-slate-400">Belum ada request yang tercatat sesuai filter.</p>
                                        <p class="font-sans text-[11px] text-slate-400 mt-0.5">Kirim request dari Mobile App / Scanner RFID untuk melihat log secara live.</p>
                                    </td>
                                </tr>
                            </template>

                            <!-- Logs Rows -->
                            <template x-for="(log, idx) in logs" :key="log.id">
                                <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors"
                                    :class="log.success ? '' : 'bg-rose-50/30 dark:bg-rose-950/10'">
                                    
                                    <!-- Status Icon -->
                                    <td class="px-4 py-3 text-center">
                                        <span x-show="log.success" class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                                            <i class="fas fa-check text-[10px]"></i>
                                        </span>
                                        <span x-show="!log.success" class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-100 text-rose-600 dark:bg-rose-950/50 dark:text-rose-400">
                                            <i class="fas fa-times text-[10px]"></i>
                                        </span>
                                    </td>

                                    <!-- Timestamp -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-semibold text-slate-800 dark:text-slate-200" x-text="log.time_short"></div>
                                        <div class="text-[10px] text-slate-400 font-sans" x-text="log.time_ago"></div>
                                    </td>

                                    <!-- Action / Endpoint Badge -->
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg font-bold text-[10px]"
                                            :class="{
                                                'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-900': log.action.startsWith('mobile'),
                                                'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-900': log.action.includes('rfid') || log.action.includes('tap') || log.action.includes('scan'),
                                                'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-900': log.action.includes('r307') || log.action.includes('finger'),
                                                'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:border-rose-900': log.action.includes('failed') || !log.success
                                            }">
                                            <i class="fas" :class="getActionIcon(log.action)"></i>
                                            <span x-text="log.action"></span>
                                        </span>
                                    </td>

                                    <!-- Message & UID -->
                                    <td class="px-4 py-3 font-sans">
                                        <div class="font-medium text-slate-800 dark:text-slate-200 leading-snug" x-text="log.message || '-'"></div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <template x-if="log.uid">
                                                <span class="inline-flex items-center gap-1 font-mono text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/60 dark:border-slate-700">
                                                    <i class="fas fa-tag text-[9px] text-slate-400"></i> UID/User: <strong x-text="log.uid"></strong>
                                                </span>
                                            </template>
                                            <template x-if="log.school_name">
                                                <span class="text-[10px] text-slate-400 font-sans" x-text="'• ' + log.school_name"></span>
                                            </template>
                                        </div>
                                    </td>

                                    <!-- IP Address & User Agent -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-semibold text-slate-700 dark:text-slate-300" x-text="log.ip_address"></div>
                                        <div class="text-[10px] text-slate-400 truncate max-w-[140px] font-sans" :title="log.user_agent" x-text="log.user_agent || 'Unknown Client'"></div>
                                    </td>

                                    <!-- API Key -->
                                    <td class="px-4 py-3 whitespace-nowrap font-mono text-[10px]">
                                        <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700" x-text="log.api_key"></span>
                                    </td>

                                    <!-- Action Button -->
                                    <td class="px-4 py-3 text-center whitespace-nowrap font-sans">
                                        <button type="button" @click="openModal(log)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-slate-700 transition text-[11px] font-medium">
                                            <i class="fas fa-eye text-[10px]"></i>
                                            <span>Detail</span>
                                        </button>
                                    </td>

                                </tr>
                            </template>

                        </tbody>
                    </table>
                </div>

            </div>

        </main>

        <!-- Footer -->
        <footer class="border-t border-slate-200 dark:border-darkborder bg-white dark:bg-darkcard py-4 px-4 text-center text-xs text-slate-400 dark:text-slate-500 font-sans">
            Sistem Absensi Multi-Device & Mobile REST API • Public Diagnostic Monitor &copy; {{ date('Y') }}
        </footer>

        <!-- Log Detail Modal -->
        <div x-show="modalOpen" x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm transition-opacity"
            @keydown.escape.window="modalOpen = false">
            
            <div class="relative w-full max-w-xl rounded-3xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-darkborder p-6 shadow-2xl overflow-hidden font-sans space-y-4"
                @click.outside="modalOpen = false">
                
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl"
                            :class="selectedLog?.success ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400' : 'bg-rose-100 text-rose-600 dark:bg-rose-950 dark:text-rose-400'">
                            <i class="fas" :class="selectedLog?.success ? 'fa-check' : 'fa-times'"></i>
                        </span>
                        <div>
                            <h3 class="font-bold text-sm text-slate-900 dark:text-white">Rincian Request Log #<span x-text="selectedLog?.id"></span></h3>
                            <p class="text-[11px] text-slate-400" x-text="selectedLog?.time_full"></p>
                        </div>
                    </div>

                    <button type="button" @click="modalOpen = false"
                        class="h-8 w-8 rounded-full text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 flex items-center justify-center">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Modal Body (Key-Value Inspection) -->
                <div class="space-y-3 text-xs">
                    
                    <div class="grid grid-cols-2 gap-2">
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                            <span class="text-slate-400 text-[10px] uppercase font-semibold">Action / Event</span>
                            <div class="font-mono font-bold text-brand-600 dark:text-brand-400 mt-0.5" x-text="selectedLog?.action"></div>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                            <span class="text-slate-400 text-[10px] uppercase font-semibold">Status</span>
                            <div class="font-bold mt-0.5" :class="selectedLog?.success ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'" x-text="selectedLog?.success ? 'Sukses (200 OK)' : 'Gagal (Failed / Unauthorized)'"></div>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 space-y-1">
                        <span class="text-slate-400 text-[10px] uppercase font-semibold">Pesan / Response Message</span>
                        <p class="text-slate-800 dark:text-slate-100 font-medium" x-text="selectedLog?.message || '-'"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                            <span class="text-slate-400 text-[10px] uppercase font-semibold">IP Address</span>
                            <div class="font-mono text-slate-800 dark:text-slate-200 mt-0.5" x-text="selectedLog?.ip_address"></div>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60">
                            <span class="text-slate-400 text-[10px] uppercase font-semibold">API Key / Token</span>
                            <div class="font-mono text-slate-800 dark:text-slate-200 mt-0.5 truncate" x-text="selectedLog?.api_key"></div>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-700/60 space-y-1">
                        <span class="text-slate-400 text-[10px] uppercase font-semibold">User Agent / Client Header</span>
                        <div class="font-mono text-[11px] text-slate-700 dark:text-slate-300 break-all" x-text="selectedLog?.user_agent || '-'"></div>
                    </div>

                    <!-- JSON Raw Preview -->
                    <div class="p-3 rounded-xl bg-slate-900 text-slate-100 font-mono text-[11px] space-y-1">
                        <div class="flex items-center justify-between text-slate-400 text-[10px] border-b border-slate-800 pb-1">
                            <span>RAW JSON OBJECT</span>
                            <button type="button" @click="copyJson(selectedLog)" class="text-brand-400 hover:underline">Salin JSON</button>
                        </div>
                        <pre class="overflow-x-auto custom-scrollbar pt-1 text-[10px]" x-text="JSON.stringify(selectedLog, null, 2)"></pre>
                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="pt-2 flex justify-end">
                    <button type="button" @click="modalOpen = false"
                        class="px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 font-semibold text-xs hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                        Tutup
                    </button>
                </div>

            </div>

        </div>

    </div>

    <!-- Script Logic -->
    <script>
        function publicLogInspector() {
            return {
                logs: [],
                stats: {
                    total_today: {{ $totalLogsToday }},
                    success_today: {{ $successLogsToday }},
                    failed_today: {{ $failedLogsToday }},
                    unique_ips_today: {{ $uniqueIpsToday }},
                    server_time: ''
                },
                searchQuery: '',
                selectedCategory: 'all',
                selectedStatus: 'all',
                limit: 50,
                isLoading: false,
                isAutoRefresh: true,
                refreshInterval: null,
                soundEnabled: false,
                theme: 'light',
                lastLogId: 0,
                modalOpen: false,
                selectedLog: null,

                initLogger() {
                    // Init Theme
                    this.theme = localStorage.getItem('log_inspector_theme') || 
                                 (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    this.applyTheme();

                    // Initial Fetch
                    this.fetchLogs(true);

                    // Start auto refresh
                    this.startPolling();
                },

                applyTheme() {
                    if (this.theme === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                    localStorage.setItem('log_inspector_theme', this.theme);
                },

                toggleTheme() {
                    this.theme = this.theme === 'dark' ? 'light' : 'dark';
                    this.applyTheme();
                },

                startPolling() {
                    if (this.refreshInterval) clearInterval(this.refreshInterval);
                    this.refreshInterval = setInterval(() => {
                        if (this.isAutoRefresh) {
                            this.fetchLogs(false);
                        }
                    }, 2500);
                },

                toggleAutoRefresh() {
                    this.isAutoRefresh = !this.isAutoRefresh;
                },

                async fetchLogs(showSpinner = false) {
                    if (showSpinner) this.isLoading = true;

                    try {
                        const params = new URLSearchParams({
                            search: this.searchQuery,
                            category: this.selectedCategory,
                            status: this.selectedStatus,
                            limit: this.limit
                        });

                        const res = await fetch(`/log-request/data?${params.toString()}`);
                        const json = await res.json();

                        if (json.success) {
                            // Check for new logs to play chime
                            if (this.logs.length > 0 && json.data.length > 0 && json.data[0].id > this.lastLogId) {
                                if (this.soundEnabled) this.playNotificationSound();
                            }

                            this.logs = json.data;
                            if (this.logs.length > 0) {
                                this.lastLogId = this.logs[0].id;
                            }
                            if (json.stats) {
                                this.stats = json.stats;
                            }
                        }
                    } catch (err) {
                        console.error('Fetch logs failed:', err);
                    } finally {
                        if (showSpinner) this.isLoading = false;
                    }
                },

                async sendTestPing(type) {
                    try {
                        const res = await fetch('/log-request/test', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ type: type })
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.fetchLogs(false);
                            if (this.soundEnabled) this.playNotificationSound();
                        }
                    } catch (err) {
                        alert('Gagal mengirim simulasi test ping');
                    }
                },

                async confirmClearLogs() {
                    if (!confirm('Apakah Anda yakin ingin menghapus semua riwayat request log?')) return;

                    try {
                        const res = await fetch('/log-request/clear', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.logs = [];
                            this.stats.total_today = 0;
                            this.stats.success_today = 0;
                            this.stats.failed_today = 0;
                            this.stats.unique_ips_today = 0;
                        }
                    } catch (err) {
                        alert('Gagal membersihkan log');
                    }
                },

                getActionIcon(action) {
                    if (!action) return 'fa-exchange-alt';
                    if (action.startsWith('mobile')) return 'fa-mobile-alt';
                    if (action.includes('rfid') || action.includes('tap')) return 'fa-id-card';
                    if (action.includes('r307') || action.includes('finger')) return 'fa-fingerprint';
                    if (action.includes('failed')) return 'fa-exclamation-triangle';
                    return 'fa-code';
                },

                openModal(log) {
                    this.selectedLog = log;
                    this.modalOpen = true;
                },

                copyJson(obj) {
                    if (!obj) return;
                    navigator.clipboard.writeText(JSON.stringify(obj, null, 2)).then(() => {
                        alert('JSON berhasil disalin ke clipboard!');
                    });
                },

                playNotificationSound() {
                    try {
                        const AudioCtx = window.AudioContext || window.webkitAudioContext;
                        if (!AudioCtx) return;
                        const ctx = new AudioCtx();
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(880, ctx.currentTime);
                        gain.gain.setValueAtTime(0.08, ctx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.15);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start();
                        osc.stop(ctx.currentTime + 0.15);
                    } catch (e) {
                        console.warn(e);
                    }
                }
            }
        }
    </script>
</body>
</html>
