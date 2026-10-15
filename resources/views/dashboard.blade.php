@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<x-announcement-modal :announcements="$popupAnnouncements ?? collect()" />

@php
  $isFaceEnabled = $isFaceEnabled ?? app(\App\Services\LicenseService::class)->isFaceFeatureEnabled(auth()->user()?->school);
@endphp

<div x-data="{ showAndroidModal: false, copied: false, faceEnabled: {{ $isFaceEnabled ? 'true' : 'false' }} }">
  <x-page-header 
    title="Dashboard" 
    subtitle="Ringkasan statistik kehadiran siswa & guru real-time" 
    icon="fas fa-chart-pie" 
  />


  <!-- Hero Shortcut Banner: Jagat Absen Android App -->
  <div class="mb-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
      <div class="flex items-start gap-4">
        <div class="relative flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-white border border-gray-200/80 p-2.5 shadow-theme-xs dark:border-gray-700 dark:bg-white">
          <img src="{{ asset('images/logo/logo-icon.svg') }}" alt="Logo Jagat Absen" class="h-9 w-9 object-contain" />
          @if($isFaceEnabled)
            <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
              <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-500 opacity-75"></span>
              <span class="relative inline-flex h-3.5 w-3.5 rounded-full bg-success-500 ring-2 ring-white dark:ring-gray-900"></span>
            </span>
          @endif
        </div>

        <div>
          <div class="flex flex-wrap items-center gap-2">
            <h3 class="text-base font-bold text-gray-800 dark:text-white/90 md:text-lg">
              Aplikasi Mobile Jagat Absen (Android)
            </h3>
            <span class="rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-semibold text-success-700 dark:bg-success-500/15 dark:text-success-400"
                  style="background-color: #ecfdf3; color: #027a48;">
              APK Resmi
            </span>
          </div>

          <p class="mt-1 text-xs sm:text-sm text-gray-600 dark:text-gray-400 max-w-2xl leading-relaxed">
            Aplikasi resmi Android untuk <strong>Guru, Wali Kelas, Wali Murid, dan Siswa</strong>: Absensi biometrik <strong>Face ID Recognition</strong>, radius <strong>Geofence</strong> GPS sekolah, rekapitulasi kelas wali kelas, dan notifikasi kehadiran real-time.
          </p>
          <div class="mt-2.5 flex flex-wrap items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
            <span class="inline-flex items-center gap-1.5"><i class="fas fa-check-circle text-success-500"></i> Face Recognition</span>
            <span class="inline-flex items-center gap-1.5"><i class="fas fa-check-circle text-success-500"></i> Geofence GPS Validated</span>
            <span class="inline-flex items-center gap-1.5"><i class="fas fa-check-circle text-success-500"></i> Notifikasi Pop-up</span>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2.5 sm:flex-nowrap lg:flex-shrink-0">
        @if($isFaceEnabled)
          <button @click="showAndroidModal = true" type="button"
            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition">
            <i class="fas fa-qrcode text-brand-500"></i>
            <span>Scan QR</span>
          </button>

          <a href="{{ route('download.android-app') }}"
            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition"
            style="background-color: #4f6bed; color: #ffffff;">
            <i class="fas fa-download"></i>
            <span>Download File APK</span>
          </a>
        @else
          <button type="button" disabled
            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm font-semibold text-gray-400 cursor-not-allowed dark:border-gray-800 dark:bg-gray-800 dark:text-gray-500"
            title="Scan QR dikunci: Fitur Face ID nonaktif">
            <i class="fas fa-lock text-xs"></i>
            <span>Scan QR</span>
          </button>

          <button type="button" disabled
            class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 rounded-xl bg-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-400 cursor-not-allowed dark:bg-gray-800 dark:text-gray-500 shadow-theme-xs"
            title="Download APK dikunci: Fitur Face ID nonaktif">
            <i class="fas fa-lock text-xs"></i>
            <span>Download File APK</span>
          </button>
        @endif
      </div>
    </div>
  </div>

  @if($isFaceEnabled)
  <!-- Modal Shortcut & QR Code Download Android -->
  <div x-show="showAndroidModal"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 overflow-y-auto"
       style="display: none;">
    <div @click.away="showAndroidModal = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 dark:border dark:border-gray-800">
      
      <!-- Close Button -->
      <button @click="showAndroidModal = false" type="button"
        class="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200 transition">
        <i class="fas fa-times text-sm"></i>
      </button>

      <!-- Modal Header -->
      <div class="flex items-center gap-3 border-b border-gray-100 dark:border-gray-800 pb-4">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white border border-gray-200/80 p-2 shadow-theme-xs dark:border-gray-700 dark:bg-white">
          <img src="{{ asset('images/logo/logo-icon.svg') }}" alt="Logo Jagat Absen" class="h-7 w-7 object-contain" />
        </div>
        <div>
          <h3 class="text-lg font-bold text-gray-800 dark:text-white/90">
            Download Jagat Absen (Android)
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            Pindai QR code dari HP atau unduh langsung file instalasi APK
          </p>
        </div>
      </div>

      <!-- Modal Body -->
      <div class="py-5 text-center">
        <!-- QR Code Container -->
        <div class="mx-auto inline-block rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800/50">
          <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=4&data={{ urlencode(route('public.download.android')) }}"
               alt="QR Code Download Android App Jagat Absen"
               class="mx-auto h-48 w-48 rounded-xl bg-white p-2 shadow-sm" />
          <p class="mt-2 text-[11px] font-semibold text-brand-500 dark:text-brand-400">
            <i class="fas fa-camera mr-1"></i> Scan dengan Kamera / Google Lens HP
          </p>
        </div>

        <p class="mt-4 text-xs text-gray-600 dark:text-gray-300">
          Arahkan smartphone Android Anda ke QR Code di atas untuk mengunduh aplikasi.
        </p>

        <!-- Direct Actions -->
        <div class="mt-5 flex flex-col sm:flex-row gap-2.5">
          <a href="{{ route('download.android-app') }}"
            class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white shadow-theme-xs hover:bg-brand-600 transition"
            style="background-color: #4f6bed; color: #ffffff;">
            <i class="fas fa-download"></i>
            <span>Unduh File APK</span>
          </a>

          <button @click="navigator.clipboard.writeText('{{ route('public.download.android') }}'); copied = true; setTimeout(() => copied = false, 2500);"
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 transition">
            <i :class="copied ? 'fas fa-check text-success-500' : 'fas fa-copy text-gray-500'"></i>
            <span x-text="copied ? 'Link Disalin!' : 'Salin Link Download'"></span>
          </button>
        </div>

        <!-- Panduan Instalasi -->
        <div class="mt-5 rounded-xl bg-gray-50 p-3.5 text-left text-xs text-gray-600 dark:bg-gray-800/60 dark:text-gray-300">
          <p class="font-bold text-gray-800 dark:text-white/90 mb-1 flex items-center gap-1.5">
            <i class="fas fa-info-circle text-brand-500"></i> Panduan Instalasi Singkat:
          </p>
          <ol class="list-decimal list-inside space-y-1 text-[11px] text-gray-500 dark:text-gray-400">
            <li>Setelah download selesai, buka file <strong>Jagat-Absen.apk</strong> di smartphone.</li>
            <li>Jika muncul peringatan keamanan, pilih <em>"Tetap Install"</em> / Izinkan instalasi dari sumber ini.</li>
            <li>Buka aplikasi, izinkan izin Kamera & Lokasi GPS, lalu login dengan akun Anda.</li>
          </ol>
        </div>
      </div>

      <div class="border-t border-gray-100 dark:border-gray-800 pt-3 flex justify-end">
        <button @click="showAndroidModal = false" type="button"
          class="rounded-lg px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition">
          Tutup
        </button>
      </div>
    </div>
  </div>
  @endif

<!-- Metrics Cards -->
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4 2xl:gap-7.5">
    <!-- Siswa Card -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
      <div class="flex items-center justify-center w-12 h-12 bg-brand-50 rounded-xl dark:bg-brand-500/15 text-brand-500">
        <i class="fas fa-users fa-lg"></i>
      </div>
      <div class="flex items-end justify-between mt-5">
        <div>
          <span class="text-sm text-gray-500 dark:text-gray-400">Total Siswa</span>
          <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $countSiswa }}</h4>
        </div>
      </div>
    </div>
    
    <!-- Guru Card -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
      <div class="flex items-center justify-center w-12 h-12 bg-success-50 rounded-xl dark:bg-success-500/15 text-success-500">
        <i class="fas fa-chalkboard-teacher fa-lg"></i>
      </div>
      <div class="flex items-end justify-between mt-5">
        <div>
          <span class="text-sm text-gray-500 dark:text-gray-400">Total Guru</span>
          <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $countGuru }}</h4>
        </div>
      </div>
    </div>

    <!-- Hadir Card -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
      <div class="flex items-center justify-center w-12 h-12 bg-info-50 rounded-xl dark:bg-info-500/15 text-info-500">
        <i class="fas fa-user-check fa-lg"></i>
      </div>
      <div class="flex items-end justify-between mt-5">
        <div>
          <span class="text-sm text-gray-500 dark:text-gray-400">Siswa Hadir</span>
          <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $countHadir }}</h4>
        </div>
      </div>
    </div>

    <!-- Tidak Hadir Card -->
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark md:p-6">
      <div class="flex items-center justify-center w-12 h-12 bg-error-50 rounded-xl dark:bg-error-500/15 text-error-500">
        <i class="fas fa-user-times fa-lg"></i>
      </div>
      <div class="flex items-end justify-between mt-5">
        <div>
          <span class="text-sm text-gray-500 dark:text-gray-400">Siswa Tidak Hadir</span>
          <h4 class="mt-2 font-bold text-gray-800 text-title-sm dark:text-white/90">{{ $countTidakHadir }}</h4>
        </div>
      </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="mt-4 grid grid-cols-12 gap-4 md:mt-6 md:gap-6 2xl:mt-7.5 2xl:gap-7.5">

    <!-- Chart -->
    <div class="col-span-12 xl:col-span-12">
        <div class="rounded-2xl border border-gray-200 bg-white px-5 pt-7.5 pb-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark sm:px-7.5">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h4 class="text-xl font-bold text-gray-800 dark:text-white/90">Grafik Kehadiran 7 Hari Terakhir</h4>
                </div>
            </div>
            <div class="relative w-full h-[320px]">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-span-12">
        <div class="rounded-2xl border border-gray-200 bg-white px-5 pt-6 pb-2.5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark sm:px-7.5 xl:pb-1">
            <h4 class="mb-6 text-xl font-bold text-gray-800 dark:text-white/90">Aktivitas Absensi Terakhir Hari Ini</h4>
            @if($recentLogs->count() > 0)
                <div class="max-w-full overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-50 text-left dark:bg-gray-800/50">
                                <th class="min-w-[150px] px-4 py-4 font-medium text-gray-800 dark:text-white/90 xl:pl-5">Nama</th>
                                <th class="min-w-[120px] px-4 py-4 font-medium text-gray-800 dark:text-white/90">Kelas</th>
                                <th class="min-w-[120px] px-4 py-4 font-medium text-gray-800 dark:text-white/90">Jam Masuk</th>
                                <th class="px-4 py-4 font-medium text-gray-800 dark:text-white/90">Ket</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentLogs as $log)
                                <tr>
                                    <td class="border-b border-gray-100 px-4 py-5 dark:border-gray-800 xl:pl-5">
                                        <p class="text-gray-800 dark:text-white/90 font-medium">{{ $log->student->nama ?? '-' }}</p>
                                    </td>
                                    <td class="border-b border-gray-100 px-4 py-5 dark:border-gray-800">
                                        <p class="text-gray-500 dark:text-gray-400">{{ $log->student->kelas->nama_kelas ?? '-' }}</p>
                                    </td>
                                    <td class="border-b border-gray-100 px-4 py-5 dark:border-gray-800">
                                        <p class="text-gray-500 dark:text-gray-400">{{ $log->jam_masuk }}</p>
                                    </td>
                                    <td class="border-b border-gray-100 px-4 py-5 dark:border-gray-800">
                                        @if($log->jam_pulang)
                                            <p class="inline-flex rounded-full bg-success-50 px-3 py-1 text-xs font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">Pulang</p>
                                        @elseif($log->keterangan)
                                            <p class="inline-flex rounded-full bg-warning-50 px-3 py-1 text-xs font-medium text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">Telat</p>
                                        @else
                                            <p class="inline-flex rounded-full bg-info-50 px-3 py-1 text-xs font-medium text-info-600 dark:bg-info-500/15 dark:text-info-500">Masuk</p>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="pb-6 text-center text-gray-500 dark:text-gray-400">Belum ada aktivitas hari ini.</p>
            @endif
        </div>
    </div>

    @if(isset($kegiatanHariIni) && $kegiatanHariIni->count() > 0)
    <!-- Active Activities Today -->
    <div class="col-span-12">
        <div class="rounded-2xl border border-brand-200 bg-brand-50/50 p-6 dark:border-brand-500/30 dark:bg-brand-500/10">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-bold text-gray-800 dark:text-white/90 flex items-center gap-2">
                    🎯 Kegiatan Hari Ini
                </h4>
                <a href="{{ route('kegiatan.absen') }}" class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">
                    Buka Absen Kegiatan <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($kegiatanHariIni as $keg)
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-dark flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1">
                                <h5 class="font-bold text-gray-800 dark:text-white/90">{{ $keg->nama_kegiatan }}</h5>
                                <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded-full bg-brand-100 text-brand-700 dark:bg-brand-500/20 dark:text-brand-300">
                                    {{ $keg->frekuensi }}
                                </span>
                            </div>
                            @if($keg->deskripsi)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ \Illuminate\Support\Str::limit($keg->deskripsi, 60) }}</p>
                            @endif
                            <div class="flex items-center gap-3 text-xs text-gray-600 dark:text-gray-400 font-mono mb-3">
                                <span><i class="fas fa-clock text-brand-500 mr-1"></i> {{ $keg->jam_mulai ? \Carbon\Carbon::parse($keg->jam_mulai)->format('H:i') : '-' }} - {{ $keg->jam_selesai ? \Carbon\Carbon::parse($keg->jam_selesai)->format('H:i') : '-' }}</span>
                            </div>
                        </div>
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Hadir Hari Ini:</span>
                            <span class="font-bold text-success-600 dark:text-success-400 bg-success-50 dark:bg-success-500/10 px-2.5 py-1 rounded-full">
                                {{ $keg->total_hadir ?? 0 }} siswa
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/chart.js/Chart.min.js') }}"></script>
    <script>
        var ctx = document.getElementById('attendanceChart').getContext('2d');
        
        // Define colors based on theme
        const isDark = document.documentElement.classList.contains('dark');
        const textColor = isDark ? '#9CA3AF' : '#6B7280';
        const gridColor = isDark ? '#1F2937' : '#F3F4F6';

        var attendanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($dates),
                datasets: [
                    {
                        label: 'Hadir (H)',
                        data: @json($chartData['H']),
                        backgroundColor: 'rgba(28, 200, 138, 0.8)',
                        borderRadius: 4
                    },
                    {
                        label: 'Izin (I)',
                        data: @json($chartData['I']),
                        backgroundColor: 'rgba(54, 185, 204, 0.8)',
                        borderRadius: 4
                    },
                    {
                        label: 'Sakit (S)',
                        data: @json($chartData['S']),
                        backgroundColor: 'rgba(133, 135, 150, 0.8)',
                        borderRadius: 4
                    },
                    {
                        label: 'Alpha (A)',
                        data: @json($chartData['A']),
                        backgroundColor: 'rgba(231, 74, 59, 0.8)',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            color: textColor,
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: isDark ? '#1F2937' : '#FFFFFF',
                        titleColor: isDark ? '#FFFFFF' : '#1F2937',
                        bodyColor: textColor,
                        borderColor: gridColor,
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        usePointStyle: true
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: textColor,
                            maxTicksLimit: 7
                        }
                    },
                    y: {
                        grid: {
                            color: gridColor,
                            drawBorder: false,
                        },
                        ticks: {
                            color: textColor,
                            beginAtZero: true,
                            maxTicksLimit: 5,
                            padding: 10,
                        }
                    },
                }
            }
        });
        
        // Listen for theme changes to update chart colors
        document.addEventListener('alpine:init', () => {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.attributeName === 'class') {
                        const isDark = document.documentElement.classList.contains('dark');
                        const textColor = isDark ? '#9CA3AF' : '#6B7280';
                        const gridColor = isDark ? '#1F2937' : '#F3F4F6';
                        
                        attendanceChart.options.plugins.legend.labels.color = textColor;
                        attendanceChart.options.plugins.tooltip.backgroundColor = isDark ? '#1F2937' : '#FFFFFF';
                        attendanceChart.options.plugins.tooltip.titleColor = isDark ? '#FFFFFF' : '#1F2937';
                        attendanceChart.options.plugins.tooltip.bodyColor = textColor;
                        attendanceChart.options.plugins.tooltip.borderColor = gridColor;
                        attendanceChart.options.scales.x.ticks.color = textColor;
                        attendanceChart.options.scales.y.ticks.color = textColor;
                        attendanceChart.options.scales.y.grid.color = gridColor;
                        
                        attendanceChart.update();
                    }
                });
            });
            
            observer.observe(document.documentElement, { attributes: true });
        });
    </script>
@endpush