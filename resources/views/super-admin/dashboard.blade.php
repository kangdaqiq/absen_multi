@extends('layouts.app')

@section('title', 'Super Admin Dashboard')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-crown text-warning"></i> Super Admin Dashboard
        </h1>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <!-- Total Schools -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Sekolah</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalSchools }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-school fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Schools -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Sekolah Aktif</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $activeSchools }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Admins -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Admin</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalAdmins }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Students -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Total Siswa</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $totalStudents }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Schools List -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Sekolah</h6>
                    <a href="{{ route('super-admin.schools.index') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-list"></i> Lihat Semua
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama Sekolah</th>
                                    <th>Kode</th>
                                    <th>Siswa</th>
                                    <th>Guru</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($schoolsWithStats as $school)
                                    <tr>
                                        <td>{{ $school->name }}</td>
                                        <td><span class="badge badge-secondary">{{ $school->code }}</span></td>
                                        <td>{{ $school->siswa_count }}</td>
                                        <td>{{ $school->guru_count }}</td>
                                        <td>
                                            @if($school->is_active)
                                                <span class="badge badge-success">Aktif</span>
                                            @else
                                                <span class="badge badge-danger">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('super-admin.schools.edit', $school) }}"
                                                class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">Belum ada sekolah</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <a href="{{ route('super-admin.schools.create') }}" class="btn btn-primary btn-block mb-3">
                        <i class="fas fa-plus"></i> Tambah Sekolah Baru
                    </a>
                    <a href="{{ route('super-admin.schools.index') }}" class="btn btn-info btn-block mb-3">
                        <i class="fas fa-school"></i> Kelola Sekolah
                    </a>
                    <hr>
                    <h6 class="font-weight-bold mb-3">Statistik Global</h6>
                    <div class="mb-2">
                        <small class="text-muted">Total Guru:</small>
                        <strong class="float-right">{{ $totalTeachers }}</strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Total Siswa:</small>
                        <strong class="float-right">{{ $totalStudents }}</strong>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Total Admin:</small>
                        <strong class="float-right">{{ $totalAdmins }}</strong>
                    </div>
                </div>
            </div>

            <!-- Recent Schools -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Sekolah Terbaru</h6>
                </div>
                <div class="card-body">
                    @forelse($recentSchools as $school)
                        <div class="mb-3 pb-3 border-bottom">
                            <h6 class="mb-1">{{ $school->name }}</h6>
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i>
                                {{ $school->created_at->diffForHumans() }}
                            </small>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada sekolah</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection