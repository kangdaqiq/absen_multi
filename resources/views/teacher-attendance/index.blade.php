@extends('layouts.app')

@section('title', 'Absensi Harian Guru')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Absensi Harian Guru</h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <!-- Filter -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Pilih Tanggal & Guru</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('absensi-guru.index') }}" class="form-inline">
                <div class="form-group mb-2 mr-sm-2">
                    <label class="mr-2">Tanggal:</label>
                    <input type="date" name="tanggal" class="form-control" value="{{ $dateStr }}">
                </div>

                <div class="form-group mb-2 mr-sm-2">
                     <label class="mr-2">Hari:</label>
                     <input type="text" class="form-control" value="{{ $dayName }}" readonly disabled>
                </div>

                <select name="guru_id" class="form-control mb-2 mr-sm-2">
                    <option value="">-- Semua Guru --</option>
                    @foreach($gurus as $g)
                        <option value="{{ $g->id }}" {{ request('guru_id') == $g->id ? 'selected' : '' }}>{{ $g->nama }}</option>
                    @endforeach
                </select>

                <button type="submit" class="btn btn-primary mb-2">Tampilkan</button>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Jadwal Pelajaran - {{ $dayName }}, {{ \Carbon\Carbon::parse($dateStr)->format('d-m-Y') }}</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="thead-light">
                        <tr>
                            <th width="5%">No</th>
                            <th>Jam</th>
                            <th>Guru</th>
                            <th>Mata Pelajaran</th>
                            <th>Kelas</th>
                            <th>Status Kehadiran</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($schedules as $item)
                            @php
                                // Check if attendance record exists
                                $attendance = $item->absensis->first(); 
                                $status = $attendance ? $attendance->status : 'Belum Absen';
                                $badgeClass = 'secondary';
                                if($status == 'Hadir') $badgeClass = 'success';
                                elseif($status == 'Tidak Hadir') $badgeClass = 'danger';
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ \Carbon\Carbon::parse($item->jam_mulai)->format('H:i') }} - {{ \Carbon\Carbon::parse($item->jam_selesai)->format('H:i') }}</td>
                                <td>{{ $item->guru->nama ?? '-' }}</td>
                                <td>{{ $item->mapel->nama_mapel ?? '-' }}</td>
                                <td>{{ $item->kelas->nama_kelas ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-{{ $badgeClass }}" style="font-size: 0.9rem;">{{ $status }}</span>
                                    @if($attendance && $attendance->waktu_hadir)
                                        <div class="small text-muted mt-1">
                                            <i class="fas fa-clock"></i> {{ \Carbon\Carbon::parse($attendance->waktu_hadir)->format('H:i') }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary btn-block" data-toggle="modal" data-target="#updateModal{{ $item->id }}">
                                        Update Status
                                    </button>

                                    <!-- Modal for this item -->
                                    <div class="modal fade" id="updateModal{{ $item->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <form action="{{ route('absensi-guru.store') }}" method="POST">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Update Absensi</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p><strong>Guru:</strong> {{ $item->guru->nama }}</p>
                                                        <p><strong>Mapel:</strong> {{ $item->mapel->nama_mapel }} ({{ $item->kelas->nama_kelas }})</p>
                                                        
                                                        <input type="hidden" name="guru_id" value="{{ $item->guru_id }}">
                                                        <input type="hidden" name="jadwal_pelajaran_id" value="{{ $item->id }}">
                                                        <input type="hidden" name="tanggal" value="{{ $dateStr }}">

                                                        <div class="form-group">
                                                            <label>Status Kehadiran:</label>
                                                            <select name="status" class="form-control">
                                                                <option value="Hadir" {{ $status == 'Hadir' ? 'selected' : '' }}>Hadir</option>
                                                                <option value="Tidak Hadir" {{ $status == 'Tidak Hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                                        <button type="submit" class="btn btn-primary">Simpan</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada jadwal pelajaran untuk hari {{ $dayName }} ini.</td>
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
            "pageLength": 50,
            "ordering": false 
        });
    });
</script>
@endpush
