@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
  <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
    Dashboard
  </h2>
</div>

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