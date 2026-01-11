@extends('layouts.app')

@section('title', 'Atur Jadwal Pelajaran')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Atur Jadwal Pelajaran</h1>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @foreach ($errors->all() as $error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endforeach

    {{-- Class Selection Card --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Pilih Kelas</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('jadwal-pelajaran.index') }}" method="GET" id="formFilterResult">
                <div class="form-group row">
                    <label class="col-sm-2 col-form-label font-weight-bold">Kelas:</label>
                    <div class="col-sm-10">
                        <select name="kelas_id" class="form-control select2"
                            onchange="document.getElementById('formFilterResult').submit()">
                            <option value="">-- Pilih Kelas --</option>
                            @foreach($kelas as $k)
                                <option value="{{ $k->id }}" {{ request('kelas_id') == $k->id ? 'selected' : '' }}>
                                    {{ $k->nama_kelas }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted">Pilih kelas terlebih dahulu untuk mengatur jadwal
                            pelajaran.</small>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(request('kelas_id'))
        <div class="row">
            @php
                $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
                $selectedKelas = $kelas->where('id', request('kelas_id'))->first();
            @endphp

            @foreach($days as $day)
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card shadow h-100">
                        <div
                            class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-primary text-white">
                            <h6 class="m-0 font-weight-bold">{{ $day }}</h6>
                            <button class="btn btn-sm btn-light text-primary btnAddMapel" data-day="{{ $day }}"
                                data-target="#modalTambahJadwal" data-toggle="modal">
                                <i class="fas fa-plus"></i> Tambah
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @php
                                    $daySchedules = $jadwals->where('hari', $day);
                                @endphp

                                @if($daySchedules->count() > 0)
                                    @foreach($daySchedules as $j)
                                        <li class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge badge-info p-1 mr-1">
                                                        {{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}
                                                    </span>
                                                    <div class="font-weight-bold mt-1 text-gray-800">{{ $j->mapel->nama_mapel ?? '-' }}
                                                    </div>
                                                    <div class="small text-muted"><i class="fas fa-user-tie mr-1"></i>
                                                        {{ $j->guru->nama ?? '-' }}</div>
                                                </div>
                                                <div class="dropdown no-arrow">
                                                    <form action="{{ route('jadwal-pelajaran.destroy', $j->id) }}" method="POST"
                                                        onsubmit="return confirm('Yakin hapus jadwal ini?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-circle btn-danger" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                @else
                                    <li class="list-group-item text-center text-muted small py-4">
                                        <em>Belum ada jadwal</em>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Modal Tambah --}}
        <div class="modal fade" id="modalTambahJadwal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('jadwal-pelajaran.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="kelas_id" value="{{ request('kelas_id') }}">

                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Tambah Jadwal - {{ $selectedKelas->nama_kelas ?? '' }}</h5>
                            <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="font-weight-bold">Hari</label>
                                <input type="text" name="hari" id="inputHari" class="form-control" readonly>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Mata Pelajaran</label>
                                <select name="mapel_id" class="form-control" required>
                                    <option value="">-- Pilih Mapel --</option>
                                    @foreach($mapels as $m)
                                        <option value="{{ $m->id }}">{{ $m->nama_mapel }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Guru Pengajar</label>
                                <select name="guru_id" class="form-control select2-modal" required style="width: 100%">
                                    <option value="">-- Pilih Guru --</option>
                                    @foreach($gurus as $g)
                                        <option value="{{ $g->id }}">{{ $g->nama }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-6">
                                    <label class="font-weight-bold">Jam Mulai</label>
                                    <input type="time" name="jam_mulai" class="form-control" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label class="font-weight-bold">Jam Selesai</label>
                                    <input type="time" name="jam_selesai" class="form-control" required>
                                </div>
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
    @endif

@endsection

@section('scripts')
    <script>
        $(document).ready(function () {
            $('.select2').select2({ theme: 'bootstrap4' });
            $('.select2-modal').select2({ theme: 'bootstrap4', dropdownParent: $('#modalTambahJadwal') });

            // Handle Add Button Click to set Day
            $('.btnAddMapel').click(function () {
                var day = $(this).data('day');
                $('#inputHari').val(day);
            });
        });
    </script>
@endsection