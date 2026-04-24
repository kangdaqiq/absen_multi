@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    </div>

    <div class="row">

        <!-- Siswa Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Siswa</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $countSiswa }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Guru Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Guru</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $countGuru }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hadir Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Hadir Hari Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $countHadir }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Telat Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Terlambat Hari Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $countTelat }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Row -->
    <div class="row">
        <div class="col-xl-12 col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Grafik Kehadiran 7 Hari Terakhir</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area" style="height: 320px;">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Activity -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Aktivitas Absensi Terakhir Hari Ini</h6>
                </div>
                <div class="card-body">
                    @if($recentLogs->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Jam Masuk</th>
                                        <th>Ket</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentLogs as $log)
                                        <tr>
                                            <td>{{ $log->student->nama ?? '-' }}</td>
                                            <td>{{ $log->student->kelas->nama_kelas ?? '-' }}</td>
                                            <td>{{ $log->jam_masuk }}</td>
                                            <td>
                                                @if($log->jam_pulang)
                                                    <span class="badge badge-success">Pulang</span>
                                                @elseif($log->keterangan)
                                                    <span class="badge badge-warning">Telat</span>
                                                @else
                                                    <span class="badge badge-info">Masuk</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-center">Belum ada aktivitas hari ini.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Quick Links / Info -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi & Pengumuman</h6>
                </div>
                <div class="card-body">
                    <p>Selamat datang di Sistem Absensi RFID v2.0 .</p>
                    
                    @if(isset($announcements) && $announcements->count() > 0)
                        <div class="mt-4">
                            <h6 class="font-weight-bold">Pengumuman Terbaru:</h6>
                            <div class="accordion" id="accordionAnnouncements">
                                @foreach($announcements as $index => $announcement)
                                    <div class="card mb-2">
                                        <div class="card-header p-2" id="heading{{ $index }}">
                                            <h2 class="mb-0">
                                                <button class="btn btn-link btn-block text-left text-dark font-weight-bold" type="button" data-toggle="collapse" data-target="#collapse{{ $index }}" aria-expanded="{{ $index == 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $index }}">
                                                    <i class="fas fa-bullhorn text-warning mr-2"></i> {{ $announcement->title }}
                                                    <small class="float-right text-muted">{{ $announcement->created_at->diffForHumans() }}</small>
                                                </button>
                                            </h2>
                                        </div>
                                        <div id="collapse{{ $index }}" class="collapse {{ $index == 0 ? 'show' : '' }}" aria-labelledby="heading{{ $index }}" data-parent="#accordionAnnouncements">
                                            <div class="card-body bg-light">
                                                {!! nl2br(e($announcement->content)) !!}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    <hr>
                    <div class="mt-3">
                        <a href="{{ route('siswa.index') }}" class="btn btn-primary btn-icon-split btn-sm mb-2">
                            <span class="icon text-white-50"><i class="fas fa-users"></i></span>
                            <span class="text">Kelola Siswa</span>
                        </a>
                        <a href="{{ route('guru.index') }}" class="btn btn-success btn-icon-split btn-sm mb-2">
                            <span class="icon text-white-50"><i class="fas fa-chalkboard-teacher"></i></span>
                            <span class="text">Kelola Guru</span>
                        </a>
                        <a href="{{ route('absensi.index') }}" class="btn btn-info btn-icon-split btn-sm mb-2">
                            <span class="icon text-white-50"><i class="fas fa-clipboard-list"></i></span>
                            <span class="text">Laporan Absensi</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        var ctx = document.getElementById('attendanceChart').getContext('2d');
        var attendanceChart = new Chart(ctx, {
            type: 'bar', // or 'line'
            data: {
                labels: @json($dates),
                datasets: [
                    {
                        label: 'Hadir (H)',
                        data: @json($chartData['H']),
                        backgroundColor: 'rgba(28, 200, 138, 0.5)',
                        borderColor: 'rgba(28, 200, 138, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Izin (I)',
                        data: @json($chartData['I']),
                        backgroundColor: 'rgba(54, 185, 204, 0.5)',
                        borderColor: 'rgba(54, 185, 204, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Sakit (S)',
                        data: @json($chartData['S']),
                        backgroundColor: 'rgba(133, 135, 150, 0.5)',
                        borderColor: 'rgba(133, 135, 150, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Alpha (A)',
                        data: @json($chartData['A']),
                        backgroundColor: 'rgba(231, 74, 59, 0.5)',
                        borderColor: 'rgba(231, 74, 59, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        left: 10,
                        right: 25,
                        top: 25,
                        bottom: 0
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            maxTicksLimit: 7
                        }
                    },
                    y: {
                        ticks: {
                            beginAtZero: true,
                            maxTicksLimit: 5,
                            padding: 10,
                        },
                        grid: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    },
                },
                legend: {
                    display: true,
                    position: 'bottom'
                },
                tooltips: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyFontColor: "#858796",
                    titleMarginBottom: 10,
                    titleFontColor: '#6e707e',
                    titleFontSize: 14,
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    xPadding: 15,
                    yPadding: 15,
                    displayColors: false,
                    caretPadding: 10,
                },
            }
        });
    </script>
@endpush