@extends('layouts.app')

@section('title', 'Rekap Absensi')

@section('content')
<h1 class="h3 mb-4 text-gray-800">Rekap Absensi</h1>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Filter Periode & Kelas</h6>
    </div>
    <div class="card-body">
        <form action="{{ route('rekap.index') }}" method="GET" class="form-row align-items-end">
            <div class="form-group col-md-2">
                <label>Dari Tanggal:</label>
                <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
            </div>
            <div class="form-group col-md-2">
                <label>Sampai Tanggal:</label>
                <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
            </div>
            <div class="form-group col-md-3">
                <label>Kelas:</label>
                <select name="kelas_id" class="form-control">
                    <option value="">Semua Kelas</option>
                    @foreach($allKelas as $k)
                        <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-5">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Tampilkan</button>
                <button type="submit" formaction="{{ route('rekap.export') }}" class="btn btn-success"><i class="fas fa-file-excel"></i> Excel</button>
                <a href="{{ route('rekap.pdf', request()->all()) }}" class="btn btn-danger" target="_blank">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Hasil Rekap</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th rowspan="2" class="align-middle">Nama Siswa</th>
                        <th rowspan="2" class="align-middle">Kelas</th>
                        <th colspan="5" class="text-center">Jumlah Kehadiran</th>
                        <th rowspan="2" class="align-middle">Aksi</th>
                    </tr>
                    <tr>
                        <th class="text-center bg-success text-white">H</th>
                        <th class="text-center bg-info text-white">I</th>
                        <th class="text-center bg-secondary text-white">S</th>
                        <th class="text-center bg-danger text-white">B</th>
                        <th class="text-center bg-dark text-white">A</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allSiswa as $s)
                    @php 
                        $sum = $summary[$s->id] ?? ['H'=>0,'I'=>0,'S'=>0,'B'=>0,'A'=>0];
                    @endphp
                    <tr>
                        <td>{{ $s->nama }}</td>
                        <td>{{ $s->kelas->nama_kelas ?? '-' }}</td>
                        <td class="text-center font-weight-bold">{{ $sum['H'] }}</td>
                        <td class="text-center font-weight-bold">{{ $sum['I'] }}</td>
                        <td class="text-center font-weight-bold">{{ $sum['S'] }}</td>
                        <td class="text-center font-weight-bold">{{ $sum['B'] }}</td>
                        <td class="text-center font-weight-bold">{{ $sum['A'] }}</td>
                        <td class="text-center">
                            <a href="{{ route('rekap.show', ['id' => $s->id, 'start_date' => $startDate, 'end_date' => $endDate]) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i> Detail
                            </a>
                        </td>
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
            // pageLength: 50
        });
    });
</script>
@endpush
