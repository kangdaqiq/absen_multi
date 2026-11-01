@extends('layouts.app')

@section('title', 'Scanner Absensi Face Recognition')

@section('content')
<style>
    /* Robust Layout Styles (Bebas dari ketergantungan compile Tailwind) */
    .kiosk-header-bar {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        width: 100%;
    }
    @media (min-width: 640px) {
        .kiosk-header-bar {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }

    .kiosk-controls-group {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }

    .face-kiosk-wrapper {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        width: 100%;
    }
    @media (min-width: 1024px) {
        .face-kiosk-wrapper {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
            gap: 1.5rem;
            align-items: start;
            width: 100%;
        }
    }

    .stats-counter-row {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
        width: 100%;
    }

    .spotlight-layout {
        display: flex;
        align-items: center;
        gap: 1rem;
        text-align: left;
    }
    .spotlight-avatar-box {
        position: relative;
        flex-shrink: 0;
        width: 5.25rem;
        height: 5.25rem;
    }
    @media (min-width: 640px) {
        .spotlight-avatar-box {
            width: 6rem;
            height: 6rem;
        }
    }
    .spotlight-avatar-box img {
        width: 100%;
        height: 100%;
        border-radius: 1rem;
        object-fit: cover;
    }
    .spotlight-info {
        flex: 1 1 0%;
        min-width: 0;
    }

    .stat-card-title {
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .table-avatar {
        flex-shrink: 0;
        width: 1.75rem;
        height: 1.75rem;
    }

    .custom-scroll-box {
        max-height: 420px;
        overflow-y: auto;
    }
    .custom-scroll-box::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    .custom-scroll-box::-webkit-scrollbar-track {
        background: transparent;
    }
    .custom-scroll-box::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9999px;
    }
    .custom-scroll-box::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .dark .custom-scroll-box::-webkit-scrollbar-thumb {
        background: #334155;
    }
    .dark .custom-scroll-box::-webkit-scrollbar-thumb:hover {
        background: #475569;
    }

    /* Scrollbox untuk daftar siswa di modal enrollment */
    .enroll-scroll-list {
        max-height: 200px;
        overflow-y: auto;
    }
    .enroll-scroll-list::-webkit-scrollbar {
        width: 6px;
    }
    .enroll-scroll-list::-webkit-scrollbar-track {
        background: transparent;
    }
    .enroll-scroll-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 9999px;
    }
    .enroll-scroll-list::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .dark .enroll-scroll-list::-webkit-scrollbar-thumb {
        background: #334155;
    }
    .dark .enroll-scroll-list::-webkit-scrollbar-thumb:hover {
        background: #475569;
    }

    /* Video mirror styling */
    .video-mirror {
        transform: scaleX(-1);
        -webkit-transform: scaleX(-1);
    }

    /* Webcam Viewport */
    .webcam-viewport-card {
        position: relative;
        width: 100%;
        background: #000;
        border-radius: 1.5rem;
        overflow: hidden;
        border: 1px solid #1f2937;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
    }

    .webcam-video-holder {
        position: relative;
        width: 100%;
        height: 380px;
        background: #050505;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    @media (max-width: 640px) {
        .webcam-video-holder {
            height: 290px;
        }
    }

    .webcam-video-holder video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .webcam-video-holder canvas {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        pointer-events: none;
    }

    /* Scanning HUD laser animation */
    @keyframes scanLaser {
        0% { top: 8%; opacity: 0.2; }
        50% { top: 88%; opacity: 0.9; }
        100% { top: 8%; opacity: 0.2; }
    }
    .scanner-laser-line {
        position: absolute;
        left: 10%;
        right: 10%;
        height: 3px;
        background: linear-gradient(90deg, transparent, #ec4899, #a855f7, #6366f1, transparent);
        box-shadow: 0 0 16px #a855f7, 0 0 30px #ec4899;
        animation: scanLaser 2.4s ease-in-out infinite;
        border-radius: 9999px;
        pointer-events: none;
    }

    /* Face Guide Oval (Pusat presisi di tengah viewport kamera) */
    .face-guide-oval {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 180px;
        height: 240px;
        border-radius: 46%;
        border: 2px dashed rgba(168, 85, 247, 0.6);
        box-shadow: 0 0 25px rgba(168, 85, 247, 0.25), inset 0 0 25px rgba(168, 85, 247, 0.15);
        transition: border 0.3s ease, box-shadow 0.3s ease;
        z-index: 10;
        pointer-events: none;
    }
    @media (max-width: 640px) {
        .face-guide-oval {
            width: 140px;
            height: 190px;
        }
    }
    .face-guide-oval.detected {
        border-color: #10b981;
        border-style: solid;
        box-shadow: 0 0 35px rgba(16, 185, 129, 0.6), inset 0 0 30px rgba(16, 185, 129, 0.3);
    }
    .face-guide-oval.warning {
        border-color: #f59e0b;
        border-style: dashed;
        box-shadow: 0 0 35px rgba(245, 158, 11, 0.55), inset 0 0 25px rgba(245, 158, 11, 0.25);
    }
    .face-guide-oval.verifying {
        border-color: #3b82f6;
        border-style: solid;
        box-shadow: 0 0 35px rgba(59, 130, 246, 0.6), inset 0 0 30px rgba(59, 130, 246, 0.3);
    }

    /* Verified Badge in Spotlight Card (Fix Screenshot 2: White-on-white invisible badge) */
    .verified-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        background-color: #4f6bed;
        color: #ffffff !important;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        box-shadow: 0 2px 6px rgba(79, 107, 237, 0.35);
    }

    /* Tips Presensi Card Box (Fix Screenshot 1: Modern elegant brand styling) */
    .tips-card-box {
        background-color: #f8faff;
        border: 1px solid #d5dcff;
        border-radius: 1.25rem;
        padding: 1rem 1.25rem;
    }
    .dark .tips-card-box {
        background-color: rgba(79, 107, 237, 0.08);
        border-color: rgba(79, 107, 237, 0.25);
    }
    .tips-card-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 700;
        font-size: 0.8125rem;
        color: #3d57d6;
        margin-bottom: 0.5rem;
    }
    .dark .tips-card-title {
        color: #8a9ef5;
    }
    .tips-card-title i {
        color: #f79009;
        font-size: 0.875rem;
    }
    .tips-items-list {
        display: flex;
        flex-direction: column;
        gap: 0.375rem;
    }
    .tips-item-row {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        font-size: 0.75rem;
        color: #475467;
        line-height: 1.45;
    }
    .dark .tips-item-row {
        color: #98a2b3;
    }
    .tips-item-row i {
        color: #12b76a;
        font-size: 0.75rem;
        margin-top: 0.15rem;
        flex-shrink: 0;
    }

    /* Activity Table Header Bar & Search Box (Fix Screenshot 3: Icon overlapping search text) */
    .activity-header-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.875rem 1rem;
        border-bottom: 1px solid #f2f4f7;
        flex-wrap: wrap;
    }
    .dark .activity-header-bar {
        border-color: #1f2937;
    }
    .activity-header-title {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        white-space: nowrap;
    }
    .activity-dot {
        width: 0.625rem;
        height: 0.625rem;
        border-radius: 9999px;
        background-color: #4f6bed;
        flex-shrink: 0;
    }
    .activity-search-wrapper {
        position: relative;
        min-width: 190px;
        max-width: 260px;
        flex: 1;
    }
    .activity-search-wrapper i {
        position: absolute;
        left: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #98a2b3;
        font-size: 0.75rem;
        pointer-events: none;
    }
    .activity-search-wrapper input {
        width: 100%;
        padding-left: 2.25rem !important; /* Ruang cukup agar ikon kaca pembesar tidak menumpuk teks */
        padding-right: 0.75rem !important;
        padding-top: 0.45rem !important;
        padding-bottom: 0.45rem !important;
        font-size: 0.75rem !important;
        border-radius: 0.75rem;
        border: 1px solid #e4e7ec;
        background-color: #f9fafb;
        color: #1d2939;
        outline: none;
        transition: all 0.2s ease;
    }
    .activity-search-wrapper input:focus {
        border-color: #4f6bed;
        background-color: #ffffff;
        box-shadow: 0 0 0 3px rgba(79, 107, 237, 0.15);
    }
    .dark .activity-search-wrapper input {
        border-color: #344054;
        background-color: #101828;
        color: #f2f4f7;
    }
    .dark .activity-search-wrapper input:focus {
        border-color: #4f6bed;
        background-color: #1a2231;
    }
</style>

<!-- Load Vladmandic Face-API from fast CDN -->
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.12/dist/face-api.min.js"></script>

<div class="relative w-full min-h-[550px]">
@if(!$isFaceEnabled)
    <x-feature-locked-overlay
        title="Fitur Scanner Face ID Terkunci"
        featureName="Scanner Wajah (AI Face Recognition)"
        description="Fitur Scanner Wajah (AI Face Recognition) belum diaktifkan untuk sekolah ini. Hubungi Admin untuk mengaktifkan fitur ini."
        :school="$school"
        icon="fas fa-camera"
    />
@endif

<div x-data="faceScannerApp()" x-init="init()" class="space-y-6 w-full"
    @if(!$isFaceEnabled) style="filter: blur(8px); pointer-events: none; user-select: none; opacity: 0.55;" @endif>

    <!-- ==================== TOP HEADER BAR ==================== -->
    <div class="kiosk-header-bar">
        <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl text-white shadow-md" style="background-color: #4f6bed; box-shadow: 0 4px 12px rgba(79, 107, 237, 0.3);">
                <i class="fas fa-camera text-xl"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-white/90">
                        Scanner Face Recognition
                    </h2>
                    <!-- Status Badge -->
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold border transition"
                        :class="{
                            'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-800/40': modelLoading || !cameraReady,
                            'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-800/40': cameraReady && !modelLoading
                        }">
                        <span class="h-1.5 w-1.5 rounded-full"
                            :class="cameraReady && !modelLoading ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500 animate-ping'"></span>
                        <span x-text="statusText">Menyiapkan Kamera...</span>
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Arahkan wajah ke kamera
                </p>
            </div>
        </div>

        <!-- Controls: Digital Clock, Audio, Fullscreen -->
        <div class="kiosk-controls-group">
            <!-- Digital Clock & Date -->
            <div class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark dark:text-gray-200">
                <i class="fas fa-clock" style="color: #4f6bed;"></i>
                <span x-text="currentTime" class="font-mono text-sm text-gray-800 dark:text-white font-bold">--:--:--</span>
            </div>

            <!-- Audio Toggle Button -->
            <button type="button" @click="soundEnabled = !soundEnabled"
                :title="soundEnabled ? 'Suara Aktif' : 'Suara Bisu'"
                class="flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 hover:border-brand-500 shadow-theme-xs transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-400">
                <i class="fas fa-volume-up text-xs" style="color: #4f6bed;" x-show="soundEnabled"></i>
                <i class="fas fa-volume-mute text-gray-400 text-xs" x-show="!soundEnabled" style="display: none;"></i>
            </button>

            <!-- Fullscreen Toggle -->
            <button type="button" @click="toggleFullscreen()"
                title="Layar Penuh Kiosk"
                class="flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-600 hover:border-brand-500 shadow-theme-xs transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-400">
                <i class="fas fa-expand text-gray-600 dark:text-gray-300 text-xs" x-show="!isFullscreen"></i>
                <i class="fas fa-compress text-xs" style="color: #4f6bed;" x-show="isFullscreen" style="display: none;"></i>
            </button>

            <!-- Link Switch to Scanner USB -->
            <a href="{{ route('absensi.scanner-usb') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 shadow-theme-xs transition dark:border-indigo-800/40 dark:bg-indigo-900/20 dark:text-indigo-300">
                <i class="fas fa-id-card-clip"></i>
                <span>Scanner USB</span>
            </a>

            <!-- Link Kembali ke Data Tabel Absensi -->
            <a href="{{ route('absensi.index') }}"
                class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 shadow-theme-xs transition dark:border-gray-800 dark:bg-gray-dark dark:text-gray-300">
                <i class="fas fa-list text-gray-400"></i>
                <span>Tabel</span>
            </a>
        </div>
    </div>

    <!-- ==================== MAIN KIOSK WORKSPACE (2-COLUMN GRID) ==================== -->
    <div class="face-kiosk-wrapper">

        <!-- ==================== KOLOM KIRI: WEBCAM VIEWPORT ==================== -->
        <div class="space-y-4">

            <!-- WEBCAM HUD CONTAINER -->
            <div class="webcam-viewport-card">
                
                <!-- Camera Header Overlay -->
                <div class="absolute top-3 left-3 right-3 z-20 flex items-center justify-between pointer-events-auto">
                    <!-- Live Camera Feed Badge -->
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-black/60 backdrop-blur-md px-3 py-1 text-[11px] font-semibold text-white border border-white/10 shadow-sm">
                        <span class="h-2 w-2 rounded-full bg-red-500 animate-pulse"></span>
                        LIVE WEBCAM
                    </span>

                    <!-- Camera Selector Dropdown -->
                    <div class="relative" x-show="availableCameras.length > 1">
                        <select x-model="selectedCameraId" @change="switchCamera()"
                            class="rounded-xl bg-black/60 backdrop-blur-md text-white text-[11px] font-medium border border-white/20 px-2 py-1 outline-none cursor-pointer">
                            <template x-for="cam in availableCameras" :key="cam.deviceId">
                                <option :value="cam.deviceId" x-text="cam.label || 'Kamera ' + cam.deviceId.slice(0, 5)"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <!-- Video Element (Mirrored Preview) -->
                <div class="webcam-video-holder">
                    <video x-ref="webcam" autoplay playsinline muted class="video-mirror"></video>
                    <canvas x-ref="canvasOverlay" class="video-mirror"></canvas>

                    <!-- Scanning HUD Laser -->
                    <div class="scanner-laser-line" x-show="cameraReady && !isProcessing"></div>

                    <!-- Target Oval Guide in Center -->
                    <div class="absolute face-guide-oval flex items-center justify-center pointer-events-none"
                        :class="{
                            'detected': faceDetected && isPoseValid && !isProcessing,
                            'warning': faceDetected && !isPoseValid && !isProcessing,
                            'verifying': isProcessing
                        }">
                        <!-- Corner Crosshairs / Accents -->
                        <div class="absolute -top-2 left-1/2 -translate-x-1/2 w-6 h-0.5"
                            :class="faceDetected && !isPoseValid ? 'bg-amber-400' : (faceDetected ? 'bg-emerald-400' : 'bg-purple-400')"></div>
                        <div class="absolute -bottom-2 left-1/2 -translate-x-1/2 w-6 h-0.5"
                            :class="faceDetected && !isPoseValid ? 'bg-amber-400' : (faceDetected ? 'bg-emerald-400' : 'bg-purple-400')"></div>
                        <div class="absolute -left-2 top-1/2 -translate-y-1/2 h-6 w-0.5"
                            :class="faceDetected && !isPoseValid ? 'bg-amber-400' : (faceDetected ? 'bg-emerald-400' : 'bg-purple-400')"></div>
                        <div class="absolute -right-2 top-1/2 -translate-y-1/2 h-6 w-0.5"
                            :class="faceDetected && !isPoseValid ? 'bg-amber-400' : (faceDetected ? 'bg-emerald-400' : 'bg-purple-400')"></div>
                    </div>

                    <!-- Camera Loading Overlay -->
                    <div x-show="!cameraReady || modelLoading" class="absolute inset-0 bg-gray-950 flex flex-col items-center justify-center p-6 text-center text-white z-10">
                        <div class="w-12 h-12 rounded-full border-4 border-purple-500/20 border-t-purple-500 animate-spin mb-3"></div>
                        <p class="font-bold text-sm" x-text="statusText">Menyiapkan Kamera Scanner...</p>
                        <button type="button" @click="startCamera()" x-show="!cameraReady && !modelLoading"
                            class="mt-3 px-4 py-1.5 rounded-xl bg-purple-600 text-xs font-semibold hover:bg-purple-700 transition">
                            Izinkan Akses Kamera
                        </button>
                    </div>

                    <!-- Live HUD Detection Feedback Label -->
                    <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-20 pointer-events-none max-w-[90%] text-center">
                        <div class="px-4 py-1.5 rounded-full text-xs font-bold tracking-wide backdrop-blur-md border shadow-lg transition-all flex items-center justify-center gap-1.5"
                            :class="{
                                'bg-black/60 text-white border-white/20': !faceDetected && !isProcessing && !lastScanError,
                                'bg-amber-500/95 text-white border-amber-300 shadow-amber-500/30': faceDetected && !isPoseValid && !isProcessing && !lastScanError,
                                'bg-emerald-600/90 text-white border-emerald-400 shadow-emerald-500/30': faceDetected && isPoseValid && !isProcessing && !lastScanError,
                                'bg-rose-600/95 text-white border-rose-400': lastScanError,
                                'bg-blue-600/90 text-white border-blue-400 animate-pulse': isProcessing
                            }">
                            <i class="fas text-[11px]"
                                :class="{
                                    'fa-expand': !faceDetected && !isProcessing && !lastScanError,
                                    'fa-exclamation-triangle text-amber-100': faceDetected && !isPoseValid && !isProcessing && !lastScanError,
                                    'fa-check-circle text-emerald-100': faceDetected && isPoseValid && !isProcessing && !lastScanError,
                                    'fa-times-circle': lastScanError,
                                    'fa-spinner fa-spin': isProcessing
                                }"></i>
                            <span x-text="detectionMessage">Posisikan wajah di dalam bingkai oval</span>
                        </div>
                    </div>
                </div>

                <!-- Footer Bar below Video -->
                <div class="p-3 bg-gray-900 border-t border-gray-800 flex items-center justify-between text-xs text-gray-300">
                    <div class="flex items-center gap-2 overflow-hidden text-ellipsis whitespace-nowrap">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-400 shrink-0"></span>
                        <span class="text-[11px] font-mono text-gray-400 truncate max-w-[320px] sm:max-w-[450px]" x-text="debugInfo">Memulai Kiosk...</span>
                    </div>
                    <button type="button" @click="showEnrollModal ? (showEnrollModal = false) : openEnrollModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-white font-semibold transition active:scale-95 shadow-sm"
                        :style="showEnrollModal ? 'background-color: #64748b;' : 'background-color: #4f6bed;'">
                        <i class="fas text-[10px]" :class="showEnrollModal ? 'fa-times' : 'fa-user-plus'"></i>
                        <span x-text="showEnrollModal ? 'Tutup Form Daftar' : 'Daftarkan Wajah Baru'">Daftarkan Wajah Baru</span>
                    </button>
                </div>
            </div>

            <!-- Panduan Penggunaan Singkat (Tips Presensi Wajah) -->
            <div class="tips-card-box">
                <div class="tips-card-title">
                    <i class="fas fa-lightbulb"></i>
                    <span>Tips Absensi:</span>
                </div>
                <div class="tips-items-list">
                    <div class="tips-item-row">
                        <i class="fas fa-check-circle"></i>
                        <span>Berdiri tegak menghadap webcam dengan pencahayaan yang cukup.</span>
                    </div>
                    <div class="tips-item-row">
                        <i class="fas fa-check-circle"></i>
                        <span>Lepaskan kacamata hitam atau masker yang menutupi area hidung & mulut.</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- ==================== KOLOM KANAN: SPOTLIGHT CARD, 3 COUNTER, & TABEL LOG / FORM ENROLL ==================== -->
        <div>

            <!-- TAMPILAN NORMAL: SPOTLIGHT + COUNTER + TABEL -->
            <div x-show="!showEnrollModal" class="space-y-4">

            <!-- 1. SPOTLIGHT CARD (HASIL PRESENSI TERAKHIR) -->
            <div class="relative overflow-hidden rounded-3xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark transition-all duration-300">
                <!-- Ambient Glow -->
                <div class="absolute -top-16 -right-16 w-40 h-40 rounded-full blur-3xl pointer-events-none" style="background-color: rgba(79, 107, 237, 0.1);"></div>

                <!-- Top Header Row (Fix Screenshot 2: verified-badge with crisp background) -->
                <div class="flex items-center justify-between gap-2 pb-3 mb-3 border-b border-gray-100 dark:border-gray-800">
                    <span class="inline-flex items-center gap-1.5 font-mono text-xs font-bold text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-2.5 py-1 rounded-lg">
                        <i class="fas fa-clock text-[10px] text-gray-400"></i>
                        <span x-text="spotlight.waktu || '--:--:--'">--:--:--</span>
                    </span>
                </div>

                <!-- Profile Spotlight Layout -->
                <div class="spotlight-layout">
                    <!-- Photo Container -->
                    <div class="spotlight-avatar-box">
                        <div class="w-full h-full rounded-2xl overflow-hidden border-2 border-[#d5dcff] dark:border-[#2d42b8]/40 bg-gray-100 dark:bg-gray-800 shadow-md">
                            <template x-if="spotlight.foto">
                                <img :src="spotlight.foto" alt="Foto Siswa" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!spotlight.foto">
                                <div class="w-full h-full flex flex-col items-center justify-center text-gray-300 dark:text-gray-600">
                                    <i class="fas fa-user text-3xl"></i>
                                </div>
                            </template>
                        </div>
                        <span x-show="spotlight.similarity"
                            class="absolute -bottom-1 -right-1 px-2 py-0.5 rounded-full bg-emerald-500 text-white font-mono text-[10px] font-bold shadow">
                            <span x-text="spotlight.similarity">92%</span>
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="spotlight-info space-y-1">
                        <h3 class="text-base sm:text-lg font-bold text-gray-800 dark:text-white truncate" x-text="spotlight.nama || 'Menunggu Presensi Wajah...'">
                            Menunggu Scanner...
                        </h3>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                            NIS: <span x-text="spotlight.nis || '-'" class="font-mono text-gray-700 dark:text-gray-300"></span> | 
                            Kelas: <span x-text="spotlight.kelas || '-'" class="font-bold" style="color: #4f6bed;"></span>
                        </p>

                        <!-- Status Badge -->
                        <div class="pt-1 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1 text-xs font-bold shadow-sm"
                                :class="{
                                    'bg-emerald-500 text-white': spotlight.statusType === 'success',
                                    'bg-amber-500 text-white': spotlight.statusType === 'warning',
                                    'bg-blue-600 text-white': spotlight.statusType === 'pulang',
                                    'bg-gray-200 text-gray-700 dark:bg-gray-800 dark:text-gray-300': !spotlight.statusType
                                }">
                                <i class="fas fa-id-badge text-xs"></i>
                                <span x-text="spotlight.status || 'Siap Scan'">Siap Scan</span>
                            </span>

                            <span x-show="spotlight.keterangan" class="text-xs text-gray-500 dark:text-gray-400 italic truncate" x-text="'(' + spotlight.keterangan + ')'"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. 3 STATISTIC COUNTER CARDS (PROPORTIONAL 3-COLUMN GRID) -->
            <div class="stats-counter-row">
                <!-- Card 1: Tepat Waktu -->
                <div class="rounded-2xl border border-gray-200 bg-white p-3.5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark">
                    <div class="flex items-center justify-between">
                        <span class="stat-card-title text-gray-500 dark:text-gray-400">Tepat Waktu</span>
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                            <i class="fas fa-check text-xs"></i>
                        </div>
                    </div>
                    <div class="mt-1.5 flex items-baseline gap-1">
                        <span class="text-2xl font-black text-gray-800 dark:text-white" x-text="stats.tepatWaktu">0</span>
                        <span class="text-[11px] text-gray-400 font-medium">siswa</span>
                    </div>
                </div>

                <!-- Card 2: Terlambat -->
                <div class="rounded-2xl border border-gray-200 bg-white p-3.5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark">
                    <div class="flex items-center justify-between">
                        <span class="stat-card-title text-gray-500 dark:text-gray-400">Terlambat</span>
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                            <i class="fas fa-clock text-xs"></i>
                        </div>
                    </div>
                    <div class="mt-1.5 flex items-baseline gap-1">
                        <span class="text-2xl font-black text-gray-800 dark:text-white" x-text="stats.terlambat">0</span>
                        <span class="text-[11px] text-gray-400 font-medium">siswa</span>
                    </div>
                </div>

                <!-- Card 3: Sudah Absen -->
                <div class="rounded-2xl border border-gray-200 bg-white p-3.5 shadow-theme-xs dark:border-gray-800 dark:bg-gray-dark">
                    <div class="flex items-center justify-between">
                        <span class="stat-card-title text-gray-500 dark:text-gray-400">Sudah Absen</span>
                        <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background-color: #eaedff; color: #4f6bed;">
                            <i class="fas fa-users text-xs"></i>
                        </div>
                    </div>
                    <div class="mt-1.5 flex items-baseline gap-1">
                        <span class="text-2xl font-black text-gray-800 dark:text-white" x-text="stats.sudahAbsen">0</span>
                        <span class="text-[11px] text-gray-400 font-medium">siswa</span>
                    </div>
                </div>
            </div>

            <!-- 3. REALTIME ATTENDANCE LOG TABLE -->
            <div class="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
                <!-- Table Header with Search (Fix Screenshot 3: No overlap, search input has proper 36px left padding) -->
                <div class="activity-header-bar">
                    <div class="activity-header-title">
                        <span class="activity-dot"></span>
                        <h4 class="font-bold text-sm text-gray-800 dark:text-white">
                            Aktivitas Absensi hari ini
                        </h4>
                        <span class="text-xs text-gray-400 font-medium" x-text="'(' + attendances.length + ' data)'"></span>
                    </div>

                    <!-- Search Box -->
                    <div class="activity-search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" x-model="searchQuery" placeholder="Cari nama / NIS...">
                    </div>
                </div>

                <!-- Custom Scroll Table -->
                <div class="custom-scroll-box">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-800/90 backdrop-blur-sm text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold text-[10px]">
                            <tr>
                                <th class="py-2.5 px-3">Jam</th>
                                <th class="py-2.5 px-3">Nama & NIS</th>
                                <th class="py-2.5 px-3">Kelas</th>
                                <th class="py-2.5 px-3">Status</th>
                                <th class="py-2.5 px-3 text-right">Metode</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-700 dark:text-gray-300">
                            <template x-for="item in filteredAttendances" :key="item.id || item.nama + item.waktu">
                                <tr class="hover:bg-purple-50/50 dark:hover:bg-purple-950/20 transition">
                                    <td class="py-2 px-3 font-mono font-bold text-gray-800 dark:text-gray-200 whitespace-nowrap" x-text="item.waktu"></td>
                                    <td class="py-2 px-3">
                                        <div class="flex items-center gap-2">
                                            <div class="table-avatar rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-800">
                                                <template x-if="item.foto">
                                                    <img :src="item.foto" class="w-full h-full object-cover">
                                                </template>
                                                <template x-if="!item.foto">
                                                    <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-bold text-gray-800 dark:text-white truncate" x-text="item.nama"></p>
                                                <p class="text-[10px] text-gray-400 font-mono" x-text="item.nis"></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-2 px-3 font-medium text-gray-600 dark:text-gray-400 whitespace-nowrap" x-text="item.kelas"></td>
                                    <td class="py-2 px-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="{
                                                'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400': item.statusType === 'success',
                                                'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400': item.statusType === 'warning',
                                                'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400': item.statusType === 'pulang',
                                                'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300': !item.statusType || item.statusType === 'info'
                                            }"
                                            x-text="item.status">
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 text-right whitespace-nowrap">
                                        <span class="inline-flex items-center gap-1 text-[10px] font-semibold" style="color: #4f6bed;" x-show="item.is_face">
                                            <i class="fas fa-camera text-[9px]"></i> Face
                                        </span>
                                        <span class="inline-flex items-center gap-1 text-[10px] text-gray-400" x-show="!item.is_face">
                                            <i class="fas fa-id-card text-[9px]"></i> Kartu
                                        </span>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="filteredAttendances.length === 0">
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-400">
                                        <i class="fas fa-inbox text-2xl mb-1 block"></i>
                                        Belum ada daftar absensi
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            </div><!-- End x-show="!showEnrollModal" -->

            <!-- TAMPILAN ENROLLMENT: SEJAJAR DI KOLOM KANAN (TIDAK MENUTUPI KAMERA KIRI & TIDAK TUMPUK) -->
            <div x-show="showEnrollModal" style="display: none;"
                class="rounded-3xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark"
                @keydown.escape.window="showEnrollModal = false">
                
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl text-white" style="background-color: #4f6bed;">
                            <i class="fas fa-user-plus text-xs"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-sm sm:text-base text-gray-800 dark:text-white">Daftarkan Face ID</h4>
                            <p class="text-[11px] text-gray-400">Lihat kamera di sebelah kiri, pilih siswa, lalu klik Daftarkan Wajah</p>
                        </div>
                    </div>
                    <button type="button" @click="showEnrollModal = false"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                        <i class="fas fa-arrow-left text-[10px]"></i>
                        <span>Kembali</span>
                    </button>
                </div>

                <div class="space-y-4 text-xs">
                    <!-- Siswa Selector with search -->
                    <div>
                        <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Cari & Pilih Siswa:</label>
                        <input type="text" x-model="enrollSearch" placeholder="Ketik nama atau NIS siswa..."
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-xs text-gray-800 outline-none focus:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 mb-2.5">
                        
                        <div class="border border-gray-200 dark:border-gray-800 rounded-xl divide-y divide-gray-100 dark:divide-gray-800 overflow-y-auto" style="max-height: 280px;">
                            <template x-for="s in candidateStudents" :key="s.id">
                                <div @click="selectedEnrollStudent = s; clearEnrollAlert()"
                                    :class="selectedEnrollStudent && selectedEnrollStudent.id === s.id ? 'font-bold' : 'hover:bg-gray-50 dark:hover:bg-gray-800'"
                                    :style="selectedEnrollStudent && selectedEnrollStudent.id === s.id ? 'background-color: #eaedff;' : ''"
                                    class="p-2.5 cursor-pointer flex items-center justify-between transition">
                                    <div>
                                        <p class="text-gray-800 dark:text-white" x-text="s.nama"></p>
                                        <p class="text-[10px] text-gray-400" x-text="s.nis + ' (' + s.kelas + ')'"></p>
                                    </div>
                                    <span x-show="s.has_face" class="px-2 py-0.5 rounded-full bg-emerald-50 text-[10px] text-emerald-600 font-semibold">Sudah Ada</span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div x-show="selectedEnrollStudent" class="p-3.5 rounded-xl flex items-center justify-between" style="background-color: #f4f6ff; border: 1px solid #d5dcff;">
                        <div>
                            <p class="font-bold text-xs" style="color: #3d57d6;" x-text="'Target: ' + (selectedEnrollStudent ? selectedEnrollStudent.nama : '')"></p>
                            <p class="text-[11px] text-gray-500" x-text="'NIS: ' + (selectedEnrollStudent ? selectedEnrollStudent.nis : '') + ' | Kelas: ' + (selectedEnrollStudent ? selectedEnrollStudent.kelas : '')"></p>
                        </div>
                        <i class="fas fa-check-circle text-base" style="color: #4f6bed;"></i>
                    </div>

                    <!-- Mode Pendaftaran jika siswa sudah punya Face ID -->
                    <div x-show="selectedEnrollStudent && selectedEnrollStudent.has_face" class="p-3 rounded-xl border border-amber-200 bg-amber-50/70 dark:border-amber-800/40 dark:bg-amber-950/20 space-y-2">
                        <div class="flex items-center gap-1.5 text-[11px] font-bold text-amber-800 dark:text-amber-400">
                            <i class="fas fa-info-circle"></i>
                            <span>Siswa ini sudah memiliki data Face ID terdaftar:</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px]">
                            <label class="flex items-start gap-2 p-2 rounded-lg border cursor-pointer transition"
                                :class="enrollMode === 'append' ? 'bg-white border-brand-500 shadow-sm dark:bg-gray-800' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40'">
                                <input type="radio" value="append" x-model="enrollMode" class="mt-0.5 text-brand-600">
                                <div>
                                    <p class="font-bold text-gray-800 dark:text-gray-200">Tambah Variasi</p>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">Gabungkan sudut/pencahayaan baru untuk akurasi lebih baik</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2 p-2 rounded-lg border cursor-pointer transition"
                                :class="enrollMode === 'replace' ? 'bg-white border-rose-500 shadow-sm dark:bg-gray-800' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40'">
                                <input type="radio" value="replace" x-model="enrollMode" class="mt-0.5 text-rose-600">
                                <div>
                                    <p class="font-bold text-gray-800 dark:text-gray-200">Ganti Total</p>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400">Hapus template lama & daftarkan ulang dari awal</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Inline Alert Banner di dalam Form (Bebas dari pop-up alert browser standar) -->
                    <div x-show="enrollAlert" x-transition class="rounded-xl p-3 text-xs border"
                        :class="{
                            'bg-rose-50 border-rose-300 text-rose-900 dark:bg-rose-950/40 dark:border-rose-800 dark:text-rose-200': enrollAlert && enrollAlert.type === 'error',
                            'bg-amber-50 border-amber-300 text-amber-900 dark:bg-amber-950/40 dark:border-amber-800 dark:text-amber-200': enrollAlert && (enrollAlert.type === 'warning' || enrollAlert.type === 'confirm_replace'),
                            'bg-emerald-50 border-emerald-300 text-emerald-900 dark:bg-emerald-950/40 dark:border-emerald-800 dark:text-emerald-200': enrollAlert && enrollAlert.type === 'success'
                        }">
                        <div class="flex items-start gap-2.5">
                            <i class="fas text-sm mt-0.5 shrink-0"
                                :class="{
                                    'fa-times-circle text-rose-600 dark:text-rose-400': enrollAlert && enrollAlert.type === 'error',
                                    'fa-exclamation-triangle text-amber-600 dark:text-amber-400': enrollAlert && (enrollAlert.type === 'warning' || enrollAlert.type === 'confirm_replace'),
                                    'fa-check-circle text-emerald-600 dark:text-emerald-400': enrollAlert && enrollAlert.type === 'success'
                                }"></i>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-[11px]" x-text="enrollAlert ? enrollAlert.message : ''"></p>
                                <p x-show="enrollAlert && enrollAlert.details" class="text-[10px] mt-0.5 opacity-90 leading-relaxed" x-text="enrollAlert ? enrollAlert.details : ''"></p>

                                <!-- Tombol Aksi Langsung jika mode perlu diganti ke Ganti Total -->
                                <div x-show="enrollAlert && enrollAlert.type === 'confirm_replace'" class="mt-2.5 flex items-center gap-2">
                                    <button type="button" @click="enrollMode = 'replace'; executeEnrollment()"
                                        class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-bold text-[10px] shadow-sm transition">
                                        <i class="fas fa-sync-alt mr-1"></i> Ganti Total Face ID Sekarang
                                    </button>
                                    <button type="button" @click="clearEnrollAlert()"
                                        class="px-2.5 py-1.5 rounded-lg bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold text-[10px] transition">
                                        Batal
                                    </button>
                                </div>
                            </div>
                            <button type="button" @click="clearEnrollAlert()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xs ml-1">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-2">
                        <button type="button" @click="showEnrollModal = false"
                            class="px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-50 transition">
                            Batal
                        </button>
                        <button type="button" @click="executeEnrollment()" :disabled="!selectedEnrollStudent || isEnrolling"
                            class="px-5 py-2.5 rounded-xl text-white font-bold transition disabled:opacity-50 disabled:cursor-not-allowed shadow-sm"
                            style="background-color: #4f6bed;">
                            <span x-show="!isEnrolling"><i class="fas fa-camera mr-1"></i> Daftarkan Wajah</span>
                            <span x-show="isEnrolling">Memproses...</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
</div>

<script>
function faceScannerApp() {
    return {
        // App State
        currentTime: '--:--:--',
        statusText: 'Menyiapkan Kamera...',
        detectionMessage: 'Menyiapkan kamera...',
        modelLoading: true,
        cameraReady: false,
        faceDetected: false,
        isProcessing: false,
        isEnrolling: false,
        scanMode: 'auto', // 'auto', 'masuk', 'pulang'
        soundEnabled: true,
        isFullscreen: false,

        // Server Face Count (dari database server, 0 payload download ke browser)
        faceCount: {{ (int) $registeredFaceCount }},

        // Head Pose Alignment & Warning State
        hasLandmarkNet: false,
        isPoseValid: false,
        poseWarning: null,
        currentPose: { roll: 0, yaw: 0, pitchRatio: 1.0 },

        // Cameras
        availableCameras: [],
        selectedCameraId: '',

        // Data Pendaftaran (Lazy-load hanya saat modal 'Daftarkan Wajah' dibuka)
        allStudents: [],
        isLoadingStudents: false,
        searchQuery: '',
        enrollSearch: '',
        showEnrollModal: false,
        selectedEnrollStudent: null,
        enrollMode: 'append', // 'append' (tambah variasi) atau 'replace' (ganti total)
        enrollAlert: null,    // { type: 'error'|'warning'|'success'|'confirm_replace', message: '', details: '' }
        cooldownMs: 25000,    // Cooldown 25 detik untuk orang yang sama agar tidak spam scan/duplikat baris

        setEnrollAlert(type, message, details = '') {
            this.enrollAlert = { type, message, details };
        },

        clearEnrollAlert() {
            this.enrollAlert = null;
        },

        // Realtime stats & attendees
        stats: {
            tepatWaktu: {{ (int) $totalTepatWaktu }},
            terlambat: {{ (int) $totalTerlambat }},
            sudahAbsen: {{ (int) $totalSudahAbsen }},
        },
        attendances: @json($initialAttendances),

        // Spotlight Result Card
        spotlight: {
            nama: 'Belum Ada Scan',
            nis: '-',
            kelas: '-',
            foto: null,
            status: 'Siap Scan',
            statusType: '',
            keterangan: '',
            waktu: '--:--:--',
            similarity: null,
        },

        // Debounce map: personId -> timestamp
        recentScans: {},
        holdMessageUntil: 0,
        lastScanError: false,
        debugInfo: 'Kiosk Siap & Matching Server Aktif',
        audioCtx: null,

        async init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);

            const isFaceEnabled = {{ $isFaceEnabled ? 'true' : 'false' }};
            if (!isFaceEnabled) {
                this.statusText = 'Fitur Dinonaktifkan';
                this.detectionMessage = 'Fitur Face ID terkunci oleh Admin.';
                this.modelLoading = false;
                return;
            }

            // Inisialisasi kamera & detektor pose lokal tanpa download vektor wajah
            await this.initFaceApi();
        },

        updateClock() {
            const now = new Date();
            this.currentTime = now.toLocaleTimeString('id-ID', { hour12: false });
        },

        get candidateStudents() {
            if (!this.enrollSearch.trim()) return this.allStudents.slice(0, 15);
            const q = this.enrollSearch.toLowerCase();
            return this.allStudents.filter(s => 
                (s.nama && s.nama.toLowerCase().includes(q)) || 
                (s.nis && s.nis.toLowerCase().includes(q))
            ).slice(0, 15);
        },

        get filteredAttendances() {
            if (!this.searchQuery.trim()) return this.attendances;
            const q = this.searchQuery.toLowerCase();
            return this.attendances.filter(a => 
                (a.nama && a.nama.toLowerCase().includes(q)) || 
                (a.nis && a.nis.toLowerCase().includes(q)) ||
                (a.kelas && a.kelas.toLowerCase().includes(q))
            );
        },

        async initFaceApi() {
            this.statusText = 'Menyiapkan Kamera...';
            try {
                // Memuat tinyFaceDetector & faceLandmark68TinyNet (~350 KB) untuk estimasi Head Pose (Yaw/Pitch/Roll)
                const modelUrl = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api@1.7.12/model/';
                await faceapi.nets.tinyFaceDetector.loadFromUri(modelUrl);

                try {
                    await faceapi.nets.faceLandmark68TinyNet.loadFromUri(modelUrl);
                    this.hasLandmarkNet = true;
                    console.log('✓ Model landmark pose 68-tiny aktif untuk deteksi kemiringan wajah');
                } catch (landErr) {
                    console.warn('Landmark 68-tiny load error, fallback ke basic detector:', landErr);
                    this.hasLandmarkNet = false;
                }

                this.modelLoading = false;
                this.statusText = 'Kamera Aktif & Siap Scan';

                await this.initCamera();
            } catch (err) {
                console.error('Model load failed:', err);
                this.statusText = 'Gagal memuat kamera: ' + err.message;
            }
        },

        async initCamera() {
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                this.availableCameras = devices.filter(d => d.kind === 'videoinput');
                if (this.availableCameras.length > 0 && !this.selectedCameraId) {
                    this.selectedCameraId = this.availableCameras[0].deviceId;
                }

                await this.startCamera();
            } catch (err) {
                console.error('Camera enumerate failed:', err);
                this.statusText = 'Izin kamera ditolak / tidak ditemukan';
            }
        },

        async startCamera() {
            const video = this.$refs.webcam;
            if (!video) return;

            // Stop existing stream if running
            if (video.srcObject) {
                video.srcObject.getTracks().forEach(t => t.stop());
            }

            try {
                const constraints = {
                    video: {
                        deviceId: this.selectedCameraId ? { exact: this.selectedCameraId } : undefined,
                        width: { ideal: 640 },
                        height: { ideal: 480 },
                        facingMode: 'user'
                    }
                };

                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                video.srcObject = stream;

                video.onloadedmetadata = () => {
                    this.cameraReady = true;
                    this.statusText = 'Kamera Aktif & Siap Scan';
                    this.detectionMessage = 'Posisikan wajah di dalam bingkai oval';
                    this.startDetectionLoop();
                };
            } catch (err) {
                console.error('Camera access failed:', err);
                this.statusText = 'Gagal mengakses kamera: ' + err.message;
            }
        },

        async switchCamera() {
            await this.startCamera();
        },

        // CORE CLIENT-SIDE LOOP: LIGHTWEIGHT FACE DETECTION & HEAD POSE -> PYTHON 512-D PRIMARY RECOGNITION
        startDetectionLoop() {
            const video = this.$refs.webcam;
            const canvas = this.$refs.canvasOverlay;

            let isRunning = false;
            let stableCount = 0;

            const loop = async () => {
                if (!this.cameraReady || this.isProcessing || !video || video.paused || video.ended) {
                    requestAnimationFrame(loop);
                    return;
                }

                if (!isRunning) {
                    isRunning = true;
                    try {
                        const detOptions = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 });
                        let detection = null;

                        // Gunakan landmark jika model tersedia, fallback ke deteksi dasar jika tidak
                        if (this.hasLandmarkNet) {
                            try {
                                detection = await faceapi.detectSingleFace(video, detOptions).withFaceLandmarks(true);
                            } catch (e) {
                                detection = await faceapi.detectSingleFace(video, detOptions);
                            }
                        } else {
                            detection = await faceapi.detectSingleFace(video, detOptions);
                        }

                        if (detection) {
                            this.faceDetected = true;
                            const landmarks = detection.landmarks || null;
                            const rawBox = detection.box || (detection.detection ? detection.detection.box : null);

                            // 1. Analisis Kemiringan Pose Kepala (Head Pose: Roll, Yaw, Pitch)
                            const pose = this.analyzeHeadPose(landmarks, rawBox, video.videoWidth, video.videoHeight);
                            this.isPoseValid = pose.isValid;
                            this.poseWarning = pose.warning;
                            this.currentPose = { roll: pose.roll, yaw: pose.yaw, pitchRatio: pose.pitchRatio };

                            // 2. Render Visual HUD & Alignment Guides pada Canvas Overlay
                            if (canvas) {
                                const dims = { width: video.videoWidth, height: video.videoHeight };
                                faceapi.matchDimensions(canvas, dims);
                                const resized = faceapi.resizeResults(detection, dims);
                                const ctx = canvas.getContext('2d');
                                ctx.clearRect(0, 0, canvas.width, canvas.height);
                                const box = resized.box || (resized.detection ? resized.detection.box : null);
                                if (box) {
                                    this.drawFacePoseHUD(ctx, box, resized.landmarks, pose);
                                }
                            }

                            // 3. Logika Verifikasi berdasarkan Pose Wajah
                            if (this.showEnrollModal) {
                                if (!pose.isValid) {
                                    this.detectionMessage = pose.warning;
                                } else {
                                    this.detectionMessage = 'Mode Daftar: Wajah lurus & siap. Klik Daftarkan Wajah';
                                }
                            } else if (pose.isValid) {
                                stableCount++;
                                if (stableCount >= 2 && !this.isProcessing && Date.now() > this.holdMessageUntil) {
                                    this.detectionMessage = 'Posisi wajah pas! Memverifikasi...';
                                    await this.matchAndAttend(rawBox);
                                    stableCount = 0;
                                } else if (Date.now() > this.holdMessageUntil) {
                                    this.detectionMessage = 'Wajah lurus & pas. Tahan sebentar...';
                                }
                            } else {
                                // Pose wajah masih miring/menoleh/menunduk -> Tahan verifikasi & beri instruksi ramah
                                stableCount = 0;
                                if (Date.now() > this.holdMessageUntil) {
                                    this.detectionMessage = pose.warning || 'Posisikan wajah lurus menghadap kamera';
                                }
                            }
                        } else {
                            this.faceDetected = false;
                            this.isPoseValid = false;
                            this.poseWarning = null;
                            stableCount = 0;
                            if (Date.now() > this.holdMessageUntil) {
                                this.detectionMessage = 'Posisikan wajah di dalam bingkai oval';
                            }
                            if (canvas) {
                                const ctx = canvas.getContext('2d');
                                ctx.clearRect(0, 0, canvas.width, canvas.height);
                            }
                        }
                    } catch (err) {
                        // ignore frame error
                    } finally {
                        isRunning = false;
                    }
                }

                requestAnimationFrame(loop);
            };

            requestAnimationFrame(loop);
        },

        // ALGORITMA ESTIMASI HEAD POSE (YAW, PITCH, ROLL & DISTANCE)
        analyzeHeadPose(landmarks, box, vw, vh) {
            if (!landmarks) {
                return { isValid: true, warning: null, poseType: 'normal', roll: 0, yaw: 0, pitchRatio: 1.0 };
            }

            try {
                const leftEye = landmarks.getLeftEye();
                const rightEye = landmarks.getRightEye();
                const nose = landmarks.getNose();
                const mouth = landmarks.getMouth();

                if (!leftEye || !rightEye || !nose || !mouth || leftEye.length === 0 || rightEye.length === 0) {
                    return { isValid: true, warning: null, poseType: 'normal', roll: 0, yaw: 0, pitchRatio: 1.0 };
                }

                const lx = leftEye.reduce((s, p) => s + p.x, 0) / leftEye.length;
                const ly = leftEye.reduce((s, p) => s + p.y, 0) / leftEye.length;
                const rx = rightEye.reduce((s, p) => s + p.x, 0) / rightEye.length;
                const ry = rightEye.reduce((s, p) => s + p.y, 0) / rightEye.length;

                // Ujung hidung (titik apex)
                const noseTip = nose[3] || nose[Math.floor(nose.length / 2)] || nose[0];
                const mouthY = mouth.reduce((s, p) => s + p.y, 0) / mouth.length;

                const dx = rx - lx;
                const dy = ry - ly;
                const eyeDist = Math.hypot(dx, dy);

                // 1. Roll: Derajat kemiringan kepala menyamping (tilt)
                const roll = Math.atan2(dy, dx) * (180 / Math.PI);

                // 2. Yaw: Posisi ujung hidung relatif terhadap garis tengah kedua mata (menoleh)
                const eyeMidX = (lx + rx) / 2;
                const noseRelX = (noseTip.x - eyeMidX) / (eyeDist || 1);
                const yaw = noseRelX * 62.0;

                // 3. Pitch: Rasio jarak vertikal mata-ke-hidung vs hidung-ke-mulut
                const eyeMidY = (ly + ry) / 2;
                const eyeToNose = Math.max(1, noseTip.y - eyeMidY);
                const noseToMouth = Math.max(1, mouthY - noseTip.y);
                const pitchRatio = eyeToNose / noseToMouth;

                let warning = null;
                let poseType = 'normal';

                // Toleransi Roll (Miring samping): batas 13°
                if (Math.abs(roll) > 13) {
                    warning = 'Kepala miring! Harap tegakkan posisi kepala Anda';
                    poseType = 'roll';
                }
                // Toleransi Yaw (Menoleh kiri/kanan): batas 16°
                else if (Math.abs(yaw) > 16) {
                    warning = 'Wajah menoleh! Harap hadap lurus ke kamera';
                    poseType = 'yaw';
                }
                // Toleransi Pitch (Mendongak / Menunduk)
                else if (pitchRatio < 0.55) {
                    warning = 'Kepala mendongak! Turunkan sedikit dagu Anda';
                    poseType = 'pitch_up';
                } else if (pitchRatio > 2.1) {
                    warning = 'Kepala terlalu menunduk! Angkat sedikit pandangan Anda';
                    poseType = 'pitch_down';
                }

                // Cek jarak wajah (terlalu jauh / terlalu dekat)
                if (!warning && box) {
                    if (box.width < 90 || eyeDist < 26) {
                        warning = 'Dekatkan wajah Anda sedikit ke kamera';
                        poseType = 'distance_far';
                    } else if (box.width > (vw * 0.78)) {
                        warning = 'Mundurkan sedikit wajah Anda dari kamera';
                        poseType = 'distance_near';
                    }
                }

                return {
                    isValid: !warning,
                    warning: warning,
                    poseType: poseType,
                    roll: Math.round(roll),
                    yaw: Math.round(yaw),
                    pitchRatio: Math.round(pitchRatio * 100) / 100,
                    eyeCoords: { lx, ly, rx, ry }
                };
            } catch (err) {
                return { isValid: true, warning: null, poseType: 'normal', roll: 0, yaw: 0, pitchRatio: 1.0 };
            }
        },

        // VISUALISASI CANVAS HUD (CORNER BRACKETS, GARIS HORIZON MATA & BADGE KEMIRINGAN)
        drawFacePoseHUD(ctx, box, landmarks, pose) {
            const isOk = pose.isValid;
            const strokeColor = isOk ? 'rgba(16, 185, 129, 0.9)' : 'rgba(245, 158, 11, 0.9)';

            // 1. Gambar Corner Brackets Modern
            this.drawCornerBrackets(ctx, box.x, box.y, box.width, box.height, strokeColor);

            // 2. Garis Horizon Mata & Titik Landmark Mata
            if (landmarks) {
                const leftEye = landmarks.getLeftEye();
                const rightEye = landmarks.getRightEye();
                if (leftEye && rightEye && leftEye.length && rightEye.length) {
                    const lx = leftEye.reduce((s, p) => s + p.x, 0) / leftEye.length;
                    const ly = leftEye.reduce((s, p) => s + p.y, 0) / leftEye.length;
                    const rx = rightEye.reduce((s, p) => s + p.x, 0) / rightEye.length;
                    const ry = rightEye.reduce((s, p) => s + p.y, 0) / rightEye.length;

                    // Garis horizontal penjajaran kedua mata
                    ctx.beginPath();
                    ctx.moveTo(lx, ly);
                    ctx.lineTo(rx, ry);
                    ctx.strokeStyle = isOk ? 'rgba(52, 211, 153, 0.65)' : 'rgba(251, 191, 36, 0.75)';
                    ctx.lineWidth = 2;
                    ctx.setLineDash([4, 4]);
                    ctx.stroke();
                    ctx.setLineDash([]);

                    // Titik pupil kedua mata
                    ctx.fillStyle = isOk ? '#10b981' : '#f59e0b';
                    ctx.beginPath();
                    ctx.arc(lx, ly, 3.5, 0, 2 * Math.PI);
                    ctx.arc(rx, ry, 3.5, 0, 2 * Math.PI);
                    ctx.fill();
                }
            }
        },

        drawCornerBrackets(ctx, x, y, w, h, color) {
            const len = Math.min(22, w * 0.2, h * 0.2);
            ctx.strokeStyle = color;
            ctx.lineWidth = 3;
            ctx.beginPath();
            // Top-left
            ctx.moveTo(x, y + len); ctx.lineTo(x, y); ctx.lineTo(x + len, y);
            // Top-right
            ctx.moveTo(x + w - len, y); ctx.lineTo(x + w, y); ctx.lineTo(x + w, y + len);
            // Bottom-right
            ctx.moveTo(x + w, y + h - len); ctx.lineTo(x + w, y + h); ctx.lineTo(x + w - len, y + h);
            // Bottom-left
            ctx.moveTo(x + len, y + h); ctx.lineTo(x, y + h); ctx.lineTo(x, y + h - len);
            ctx.stroke();
        },

        // PYTHON 512-D INSIGHTFACE + CLAHE + NUMPY MATRIX (100% SERVER-SIDE)
        async matchAndAttend(faceBox = null) {
            try {
                if (this.faceCount === 0) {
                    this.lastScanError = true;
                    this.detectionMessage = 'Belum ada Face ID terdaftar di sistem';
                    this.holdMessageUntil = Date.now() + 3000;
                    return;
                }

                this.isProcessing = true;
                const snapshot = this.captureFrameSnapshot(faceBox);

                // Kirim ke Server: Python 512-d NumPy Matrix
                this.detectionMessage = 'Memverifikasi wajah...';
                this.debugInfo = 'Identifikasi...';

                try {
                    const response = await fetch('{{ route('absensi.scan-face', [], false) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            mode: this.scanMode,
                            photo_snapshot: snapshot
                        })
                    });

                    const res = await response.json().catch(() => ({}));
                    if (response.ok && res.ok) {
                        this.lastScanError = false;
                        const simScore = res.data && res.data.similarity ? (parseFloat(res.data.similarity) / 100) : 0.88;
                        this.handleScanResponse(res, res.data || {}, simScore);
                        this.holdMessageUntil = Date.now() + 3800;
                    } else {
                        this.lastScanError = true;
                        const msg = res.message || ('Wajah tidak dikenali (HTTP ' + response.status + ')');
                        this.detectionMessage = msg;
                        this.holdMessageUntil = Date.now() + 2800;
                        this.playTone(response.status === 503 ? 'error' : 'warning');
                    }
                } catch (err) {
                    console.error('server scan error:', err);
                    this.lastScanError = true;
                    this.detectionMessage = 'Gagal koneksi ke server: ' + (err.message || 'Koneksi terputus');
                    this.holdMessageUntil = Date.now() + 3500;
                    this.playTone('error');
                } finally {
                    setTimeout(() => {
                        this.isProcessing = false;
                        this.lastScanError = false;
                    }, 1400);
                }
            } catch (fatalErr) {
                console.error('Fatal matching error:', fatalErr);
                this.isProcessing = false;
                this.lastScanError = true;
                this.detectionMessage = 'Galat: ' + fatalErr.message;
                this.holdMessageUntil = Date.now() + 3500;
            }
        },

        // Smart High-Clarity Face Snapshot (margin 80% agar konteks kepala & bahu utuh untuk presisi 5-keypoint alignment 512-d InsightFace)
        captureFrameSnapshot(faceBox = null, isEnroll = false) {
            try {
                const video = this.$refs.webcam;
                const vw = video.videoWidth || 640;
                const vh = video.videoHeight || 480;
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                if (faceBox && faceBox.width > 30 && faceBox.height > 30) {
                    const padRatio = isEnroll ? 0.85 : 0.75;
                    const padX = faceBox.width * padRatio;
                    const padY = faceBox.height * padRatio;
                    const sx = Math.max(0, Math.floor(faceBox.x - padX));
                    const sy = Math.max(0, Math.floor(faceBox.y - padY));
                    const sw = Math.min(vw - sx, Math.ceil(faceBox.width + padX * 2));
                    const sh = Math.min(vh - sy, Math.ceil(faceBox.height + padY * 2));

                    const maxOut = isEnroll ? 600 : 500;
                    const scale = Math.min(1, maxOut / Math.max(sw, sh));
                    canvas.width = Math.max(200, Math.round(sw * scale));
                    canvas.height = Math.max(200, Math.round(sh * scale));
                    ctx.drawImage(video, sx, sy, sw, sh, 0, 0, canvas.width, canvas.height);
                    return canvas.toDataURL('image/jpeg', isEnroll ? 0.93 : 0.88);
                }

                canvas.width = Math.min(640, vw);
                canvas.height = Math.min(480, vh);
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                return canvas.toDataURL('image/jpeg', isEnroll ? 0.93 : 0.88);
            } catch (e) {
                return null;
            }
        },

        updateSpotlightOnly(res, data = {}, matchedItem = {}) {
            this.spotlight = {
                nama: data.nama || matchedItem.nama || 'Siswa',
                nis: data.nis || matchedItem.nis || '-',
                kelas: data.kelas || matchedItem.kelas || '-',
                foto: data.foto || matchedItem.foto || null,
                status: data.status || (res.type === 'absen_pulang' ? 'Pulang' : 'Hadir'),
                statusType: res.type === 'absen_pulang' ? 'pulang' : (data.status === 'Terlambat' ? 'warning' : 'success'),
                keterangan: data.keterangan || res.message || '',
                waktu: data.jam || this.currentTime,
                similarity: data.similarity || (this.spotlight.similarity || null),
            };
        },

        handleScanResponse(res, matchedItem, similarity) {
            const data = res.data || {};
            const sound = res.sound || (res.ok ? 'ok' : 'warning');
            this.playTone(sound);

            // Update Spotlight
            this.updateSpotlightOnly(res, data, matchedItem);
            if (similarity) {
                this.spotlight.similarity = Math.round(similarity * 100) + '%';
            }

            this.detectionMessage = res.message || 'Presensi berhasil!';

            // If success, update data tabel aktivitas dengan aman (cegah duplikasi baris)
            if (res.ok) {
                const personKey = (res.person && res.person.key) || ('siswa:' + (data.nis || matchedItem.nis || ''));
                const existingIdx = this.attendances.findIndex(a => {
                    if (a.personKey && personKey && a.personKey === personKey) return true;
                    if (data.nis && a.nis && a.nis === data.nis && a.nis !== '-') return true;
                    if (data.nama && a.nama === data.nama) return true;
                    return false;
                });

                const timeDisplay = (res.type === 'absen_pulang' ? (data.jam_pulang || data.jam) : (data.jam_masuk || data.jam)) || this.currentTime;

                if (existingIdx !== -1) {
                    // Baris sudah ada di tabel: perbarui datanya & NAIKKAN ke baris teratas (tidak perlu buat baris baru)
                    const existing = this.attendances.splice(existingIdx, 1)[0];

                    if (res.type === 'absen_pulang') {
                        existing.pulang = data.jam_pulang || data.jam || this.currentTime;
                        existing.status = data.status || 'Pulang';
                        existing.statusType = 'pulang';
                    } else if (data.status) {
                        existing.status = data.status;
                        existing.statusType = (data.status === 'Terlambat') ? 'warning' : (data.status === 'Pulang' ? 'pulang' : 'success');
                    }

                    existing.waktu = timeDisplay;
                    if (data.foto) existing.foto = data.foto;
                    if (data.kelas) existing.kelas = data.kelas;
                    if (personKey) existing.personKey = personKey;

                    // Naikkan ke urutan teratas tabel
                    this.attendances.unshift(existing);
                } else {
                    // Belum ada di tabel: tambahkan baris baru
                    this.attendances.unshift({
                        id: Date.now(),
                        personKey: personKey,
                        waktu: timeDisplay,
                        nama: data.nama || matchedItem.nama,
                        nis: data.nis || matchedItem.nis,
                        foto: data.foto || matchedItem.foto,
                        kelas: data.kelas || matchedItem.kelas,
                        masuk: data.jam_masuk || (res.type === 'absen_masuk' ? data.jam : '--:--'),
                        pulang: data.jam_pulang || (res.type === 'absen_pulang' ? data.jam : '--:--'),
                        status: data.status || (res.type === 'absen_pulang' ? 'Pulang' : 'Hadir'),
                        statusType: res.type === 'absen_pulang' ? 'pulang' : (data.status === 'Terlambat' ? 'warning' : 'success'),
                        is_face: true,
                    });

                    // Update total counter hanya jika absensi masuk baru tercatat
                    if (res.type === 'absen_masuk') {
                        if (data.status === 'Terlambat') {
                            this.stats.terlambat++;
                        } else {
                            this.stats.tepatWaktu++;
                        }
                        this.stats.sudahAbsen++;
                    }
                }
            }
        },

        // Quick In-Kiosk Face Enrollment (Lazy-load daftar siswa hanya saat modal dibuka)
        async openEnrollModal() {
            this.showEnrollModal = true;
            this.enrollSearch = '';
            this.selectedEnrollStudent = null;
            this.enrollMode = 'append';
            this.clearEnrollAlert();

            if (this.allStudents.length === 0) {
                this.isLoadingStudents = true;
                try {
                    const res = await fetch('{{ route('absensi.face-descriptors', [], false) }}', {
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    });
                    const data = await res.json();
                    if (data.ok) {
                        this.allStudents = data.students || [];
                    }
                } catch (err) {
                    console.error('Failed to load students for enrollment:', err);
                } finally {
                    this.isLoadingStudents = false;
                }
            }
        },

        async executeEnrollment() {
            if (!this.selectedEnrollStudent) return;
            const video = this.$refs.webcam;
            if (!video) return;

            this.clearEnrollAlert();
            this.isEnrolling = true;
            try {
                // Standar Ketat Enrollment di Kamera: deteksi seluruh wajah dengan threshold tinggi (0.65)
                const detections = await faceapi.detectAllFaces(
                    video, 
                    new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.65 })
                );

                if (!detections || detections.length === 0) {
                    this.playTone('warning');
                    this.setEnrollAlert('warning', 'Wajah tidak terdeteksi dengan jelas!', 'Pastikan pencahayaan terang, tidak memakai masker, dan menghadap lurus ke kamera.');
                    this.isEnrolling = false;
                    return;
                }

                if (detections.length > 1) {
                    this.playTone('warning');
                    this.setEnrollAlert('warning', 'Terdeteksi lebih dari 1 wajah di kamera!', 'Pastikan hanya siswa yang didaftarkan yang berada di depan kamera.');
                    this.isEnrolling = false;
                    return;
                }

                if (!this.isPoseValid && this.poseWarning) {
                    this.playTone('warning');
                    this.setEnrollAlert('warning', this.poseWarning, 'Pastikan posisi kepala tegak dan menghadap lurus ke depan.');
                    this.isEnrolling = false;
                    return;
                }

                const detection = detections[0];
                const rawBox = detection.box || (detection.detection ? detection.detection.box : null);
                const vw = video.videoWidth || 640;
                const vh = video.videoHeight || 480;

                if (rawBox) {
                    // 1. Cek ukuran minimal wajah (tidak boleh terlalu jauh)
                    if (rawBox.width < 110 || rawBox.height < 110) {
                        this.playTone('warning');
                        this.setEnrollAlert('warning', 'Jarak wajah terlalu jauh dari kamera!', 'Harap maju sedikit lebih dekat ke arah kamera.');
                        this.isEnrolling = false;
                        return;
                    }

                    // 2. Cek posisi wajah agar berada di tengah & tidak terpotong tepi kamera
                    const marginX = vw * 0.08;
                    const marginY = vh * 0.08;
                    if (
                        rawBox.x < marginX ||
                        rawBox.y < marginY ||
                        (rawBox.x + rawBox.width) > (vw - marginX) ||
                        (rawBox.y + rawBox.height) > (vh - marginY)
                    ) {
                        this.playTone('warning');
                        this.setEnrollAlert('warning', 'Posisi wajah terlalu di pinggir layar!', 'Posisikan wajah tepat di tengah bingkai oval panduan.');
                        this.isEnrolling = false;
                        return;
                    }
                }

                const snapshot = this.captureFrameSnapshot(rawBox, true);

                const response = await fetch('{{ route('absensi.register-face-kiosk', [], false) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        person_id: this.selectedEnrollStudent.id,
                        type: 'siswa',
                        photo: snapshot,
                        enroll_mode: this.enrollMode
                    })
                });

                const res = await response.json().catch(() => ({}));

                if (response.ok && res.ok) {
                    this.playTone('ok');
                    this.faceCount++;
                    const stu = this.allStudents.find(s => s.id === this.selectedEnrollStudent.id);
                    if (stu) stu.has_face = true;
                    this.setEnrollAlert('success', res.message || 'Face ID siswa berhasil didaftarkan!');
                    setTimeout(() => {
                        this.showEnrollModal = false;
                        this.clearEnrollAlert();
                    }, 1800);
                } else if (response.status === 409 && res.code === 'requires_replace') {
                    this.playTone('warning');
                    this.setEnrollAlert('confirm_replace', res.message, 'Klik tombol "Ganti Total Face ID Sekarang" di bawah untuk menimpa template lama.');
                } else if (response.status === 409 && res.code === 'duplicate_face') {
                    this.playTone('error');
                    this.setEnrollAlert('error', 'Wajah Sudah Terdaftar Atas Nama Siswa Lain!', res.message);
                } else {
                    this.playTone('error');
                    this.setEnrollAlert('error', 'Gagal Mendaftarkan Wajah', res.message || 'Layanan AI Face ID tidak merespons.');
                }
            } catch (err) {
                this.playTone('error');
                this.setEnrollAlert('error', 'Galat saat mendaftarkan wajah: ' + err.message);
            } finally {
                this.isEnrolling = false;
            }
        },

        // Web Audio Synthesizers (Zero Audio File Downloads)
        playTone(type) {
            if (!this.soundEnabled) return;
            try {
                if (!this.audioCtx) {
                    this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                const ctx = this.audioCtx;
                if (ctx.state === 'suspended') {
                    ctx.resume();
                }

                if (type === 'ok') {
                    // Success chord (C5 -> E5 -> G5 chime)
                    const notes = [523.25, 659.25, 783.99];
                    notes.forEach((freq, idx) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, ctx.currentTime + idx * 0.08);
                        gain.gain.setValueAtTime(0.2, ctx.currentTime + idx * 0.08);
                        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + idx * 0.08 + 0.35);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(ctx.currentTime + idx * 0.08);
                        osc.stop(ctx.currentTime + idx * 0.08 + 0.35);
                    });
                } else if (type === 'warning') {
                    // Warning tone (Dual pulse)
                    [440, 440].forEach((freq, idx) => {
                        const osc = ctx.createOscillator();
                        const gain = ctx.createGain();
                        osc.type = 'triangle';
                        osc.frequency.setValueAtTime(freq, ctx.currentTime + idx * 0.12);
                        gain.gain.setValueAtTime(0.25, ctx.currentTime + idx * 0.12);
                        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + idx * 0.12 + 0.1);
                        osc.connect(gain);
                        gain.connect(ctx.destination);
                        osc.start(ctx.currentTime + idx * 0.12);
                        osc.stop(ctx.currentTime + idx * 0.12 + 0.1);
                    });
                } else {
                    // Error buzzer (Low square wave)
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(150, ctx.currentTime);
                    gain.gain.setValueAtTime(0.25, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime);
                    osc.stop(ctx.currentTime + 0.3);
                }
            } catch (e) {
                // ignore audio error
            }
        },

        toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(() => {});
                this.isFullscreen = true;
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen().catch(() => {});
                }
                this.isFullscreen = false;
            }
        }
    };
}
</script>
@endsection
