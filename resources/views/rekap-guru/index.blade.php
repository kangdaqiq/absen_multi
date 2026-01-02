@extends('layouts.app')

@section('title', 'Rekap Absensi Guru')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Rekap Absensi Guru</h1>
    <div>
        <a href="{{ route('rekap-guru.export', request()->query()) }}" class="btn btn-success shadow-sm">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
        <a href="{{ route('rekap-guru.pdf', request()->query()) }}" class="btn btn-danger shadow-sm ml-2">
            <i class="fas fa-file-pdf"></i> Export PDF
        </a>
    </div>
</div>

<!-- Filter Card -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filter Rekap</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('rekap-guru.index') }}" method="GET">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Tanggal Akhir</label>
                        <input type="date" name="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Guru (Opsional)</label>
                        <select name="guru_id" class="form-control">
                            <option value="">-- Semua Guru --</option>
                            @foreach($gurus as $g)
                                <option value="{{ $g->id }}" {{ $guruId == $g->id ? 'selected' : '' }}>
                                    {{ $g->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Statistics Card -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Absensi</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Hadir</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['hadir'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Tidak Hadir</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['tidak_hadir'] }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Data Table Card -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Data Absensi Guru</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tanggal</th>
                        <th>Guru</th>
                        <th>Mata Pelajaran</th>
                        <th>Kelas</th>
                        <th>Jam Mengajar</th>
                        <th>Status</th>
                        <th>Waktu Hadir</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($absensi as $a)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ \Carbon\Carbon::parse($a->tanggal)->format('d/m/Y') }}</td>
                        <td>{{ $a->guru->nama ?? '-' }}</td>
                        <td>{{ $a->jadwal->mapel->nama_mapel ?? '-' }}</td>
                        <td>{{ $a->jadwal->kelas->nama_kelas ?? '-' }}</td>
                        <td>
                            @if($a->jadwal)
                                {{ substr($a->jadwal->jam_mulai, 0, 5) }} - {{ substr($a->jadwal->jam_selesai, 0, 5) }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($a->status == 'Hadir')
                                <span class="badge badge-success">Hadir</span>
                            @else
                                <span class="badge badge-danger">Tidak Hadir</span>
                            @endif
                        </td>
                        <td>{{ $a->waktu_hadir ? \Carbon\Carbon::parse($a->waktu_hadir)->format('H:i') : '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            "order": [[1, "desc"]]
        });
    });
</script>
@endpush
