@extends('layouts.app')

@section('title', 'Absensi Harian Guru')

@section('content')
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Absensi Harian Guru</h1>
        <div class="d-flex align-items-center">
            <form method="GET" action="{{ route('absensi-guru.index') }}" class="form-inline bg-white p-2 rounded shadow-sm">
                <label for="tanggal" class="mr-2 font-weight-bold">Tanggal:</label>
                <input type="date" name="tanggal" id="tanggal" class="form-control form-control-sm mr-2" value="{{ $dateStr }}" onchange="this.form.submit()">
                <span class="badge badge-info">{{ $dayName }}</span>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Tabel Absensi: {{ \Carbon\Carbon::parse($dateStr)->isoFormat('D MMMM Y') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th>Nama Guru</th>
                            <th>NIP</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Jam Masuk</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report as $index => $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item['guru']->nama }}</td>
                                <td>{{ $item['guru']->nip }}</td>
                                <td class="text-center">
                                    @if($item['status'] == 'Hadir')
                                        <span class="badge badge-success">Hadir</span>
                                    @elseif($item['status'] == 'Belum Absen')
                                        <span class="badge badge-secondary">Belum Absen</span>
                                    @else
                                        <span class="badge badge-warning">{{ $item['status'] }}</span>
                                    @endif
                                </td>
                                <td class="text-center font-weight-bold">
                                    {{ $item['jam_masuk'] }}
                                </td>
                                <td>{{ $item['keterangan'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Tidak ada data guru.</td>
                            </tr>
                        @endforelse
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
            "pageLength": 50
        });
    });
</script>
@endpush