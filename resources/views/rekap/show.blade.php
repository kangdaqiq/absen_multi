@extends('layouts.app')

@section('title', 'Detail Rekap Absensi')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Detail Rekap Absensi</h1>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Informasi Siswa</h6>
        <div>
            <a href="{{ route('rekap.exportDetail', ['id' => $siswa->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-sm btn-success mr-2">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
            <a href="{{ route('rekap.index', ['start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-sm btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>
    <div class="card-body">
        <table class="table table-borderless">
            <tr>
                <th width="150">Nama</th>
                <td>: {{ $siswa->nama }}</td>
            </tr>
            <tr>
                <th>Kelas</th>
                <td>: {{ $siswa->kelas->nama_kelas ?? '-' }}</td>
            </tr>
            <tr>
                <th>NIS</th>
                <td>: {{ $siswa->nis }}</td>
            </tr>
            <tr>
                <th>Periode</th>
                <td>: {{ \Carbon\Carbon::parse($startDate)->format('d-m-Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d-m-Y') }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Riwayat Kehadiran</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
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
                    @foreach($attendance as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->tanggal)->locale('id')->isoFormat('dddd, D MMMM Y') }}</td>
                        <td>{{ $row->jam_masuk ?? '-' }}</td>
                        <td>{{ $row->jam_pulang ?? '-' }}</td>
                        <td>
                            @if($row->status == 'H') <span class="badge badge-success">Hadir</span>
                            @elseif($row->status == 'I') <span class="badge badge-info">Izin</span>
                            @elseif($row->status == 'S') <span class="badge badge-warning">Sakit</span>
                            @elseif($row->status == 'T') <span class="badge badge-warning">Terlambat</span>
                            @elseif($row->status == 'B') <span class="badge badge-danger">Bolos</span>
                            @else <span class="badge badge-danger">Alpha</span>
                            @endif
                        </td>
                        <td>{{ $row->keterangan }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
