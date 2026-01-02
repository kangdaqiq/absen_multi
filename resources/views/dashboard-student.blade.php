@extends('layouts.app')

@section('title', 'Dashboard Siswa')

@section('content')

@if(!$linked)
    <div class="alert alert-warning">
        <h4 class="alert-heading">Akun Belum Terhubung!</h4>
        <p>Akun Anda belum terhubung dengan Data Siswa. Silakan hubungi Administrator untuk menghubungkan akun ini dengan data NIS Anda.</p>
    </div>
@else
    <h1 class="h3 mb-4 text-gray-800">Halo, {{ $siswa->nama }}</h1>

    <div class="row">
        <!-- Hadir -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Hadir</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['H'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Telat -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Terlambat</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['T'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Izin/Sakit -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Izin / Sakit</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['I'] + $stats['S'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-notes-medical fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alpha -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Alpha</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['A'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Riwayat Absensi Terakhir</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jam Masuk</th>
                            <th>Jam Pulang</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentLogs as $log)
                        <tr>
                            <td>{{ $log->tanggal }}</td>
                            <td>{{ $log->jam_masuk ?? '-' }}</td>
                            <td>{{ $log->jam_pulang ?? '-' }}</td>
                            <td>
                                @if($log->status == 'H') <span class="badge badge-success">Hadir</span>
                                @elseif($log->status == 'I') <span class="badge badge-info">Izin</span>
                                @elseif($log->status == 'S') <span class="badge badge-warning">Sakit</span>
                                @elseif($log->status == 'A') <span class="badge badge-danger">Alpha</span>
                                @else <span class="badge badge-secondary">{{ $log->status }}</span>
                                @endif
                            </td>
                            <td>{{ $log->keterangan }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <a href="{{ route('rekap.index') }}" class="btn btn-primary btn-sm">Lihat Rekap Lengkap &rarr;</a>
            </div>
        </div>
    </div>
@endif

@endsection
