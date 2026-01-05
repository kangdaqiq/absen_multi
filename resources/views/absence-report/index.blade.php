@extends('layouts.app')

@section('title', 'Laporan Ketidakhadiran Berlebihan')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Laporan Ketidakhadiran Berlebihan</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Laporan</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('absence-report.index') }}" class="form-inline">
                <div class="form-group mr-3 mb-2">
                    <label class="mr-2">Kelas:</label>
                    <select name="kelas_id" class="form-control">
                        <option value="">Semua Kelas</option>
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id }}" {{ $kelasId == $k->id ? 'selected' : '' }}>
                                {{ $k->nama_kelas }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary mb-2 mr-2">
                    <i class="fas fa-search"></i> Filter
                </button>

                @if(count($students) > 0)
                    <a href="{{ route('absence-report.export', request()->query()) }}" class="btn btn-success mb-2">
                        <i class="fas fa-file-excel"></i> Export CSV
                    </a>
                @endif
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-danger">
                Siswa dengan Ketidakhadiran Berlebihan ({{ count($students) }} siswa)
            </h6>
        </div>
        <div class="card-body">
            @if(count($students) > 0)
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTable">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>NIS</th>
                                <th>Nama</th>
                                <th>Kelas</th>
                                <th width="10%">Total</th>
                                <th width="10%">Alpha</th>
                                <th width="10%">Bolos</th>
                                <th>No WA Ortu</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $index => $student)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $student['nis'] }}</td>
                                    <td>{{ $student['nama'] }}</td>
                                    <td>{{ $student['kelas'] }}</td>
                                    <td>
                                        <span class="badge badge-danger badge-lg">
                                            {{ $student['total'] }}x
                                        </span>
                                    </td>
                                    <td>
                                        @if($student['alpha'] > 0)
                                            <span class="badge badge-warning">{{ $student['alpha'] }}x</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($student['bolos'] > 0)
                                            <span class="badge badge-info">{{ $student['bolos'] }}x</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($student['wa_ortu'])
                                            <small>{{ $student['wa_ortu'] }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info btnDetail" data-id="{{ $student['id'] }}"
                                            data-nama="{{ $student['nama'] }}" data-kelas="{{ $student['kelas'] }}"
                                            data-details='@json($student['details'])' data-toggle="modal"
                                            data-target="#modalDetail">
                                            <i class="fas fa-eye"></i> Detail
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <h5>Tidak ada siswa dengan ketidakhadiran berlebihan</h5>
                    <p class="mb-0">Semua siswa memiliki tingkat kehadiran yang baik dalam periode yang dipilih.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal Detail -->
    <div class="modal fade" id="modalDetail" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Detail Ketidakhadiran</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <h6 id="detail-nama" class="font-weight-bold"></h6>
                    <p id="detail-kelas" class="text-muted"></p>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                </tr>
                            </thead>
                            <tbody id="detail-tbody">
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            $('#dataTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
                },
                "order": [[4, "desc"]] // Sort by total absences
            });

            $('.btnDetail').on('click', function () {
                var nama = $(this).data('nama');
                var kelas = $(this).data('kelas');
                var details = $(this).data('details');

                $('#detail-nama').text(nama);
                $('#detail-kelas').text('Kelas: ' + kelas);

                var tbody = $('#detail-tbody');
                tbody.empty();

                if (details && details.length > 0) {
                    details.forEach(function (item) {
                        var statusBadge = '';
                        var statusText = '';

                        if (item.status === 'A') {
                            statusBadge = '<span class="badge badge-warning">Alpha</span>';
                            statusText = 'Alpha';
                        } else if (item.status === 'B') {
                            statusBadge = '<span class="badge badge-info">Bolos</span>';
                            statusText = 'Bolos';
                        }

                        var tanggal = new Date(item.tanggal).toLocaleDateString('id-ID', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });

                        tbody.append(
                            '<tr>' +
                            '<td>' + tanggal + '</td>' +
                            '<td>' + statusBadge + '</td>' +
                            '<td>' + (item.keterangan || '-') + '</td>' +
                            '</tr>'
                        );
                    });
                } else {
                    tbody.append('<tr><td colspan="3" class="text-center">Tidak ada data</td></tr>');
                }
            });
        });
    </script>
@endsection