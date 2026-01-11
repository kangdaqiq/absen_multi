@extends('layouts.app')

@section('title', 'Manajemen Jadwal')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Manajemen Jadwal</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Atur Jadwal Masuk & Pulang (Mingguan)</h6>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form action="{{ route('jadwal.update-all') }}" method="POST">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 15%">Hari</th>
                                <th style="width: 25%">Jam Masuk</th>
                                <th style="width: 25%">Jam Pulang</th>
                                <th style="width: 20%">Toleransi (Menit)</th>
                                <th style="width: 15%" class="text-center">Aktif?</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
                                // Map existing schedules by index_hari for easy access
                                $existingJadwal = $jadwal->keyBy('index_hari');
                            @endphp

                            @foreach($days as $index => $dayName)
                                @php
                                    $current = $existingJadwal->get($index);
                                @endphp
                                <tr>
                                    <td class="font-weight-bold align-middle">{{ $dayName }}</td>
                                    <td>
                                        <input type="time" name="schedules[{{ $index }}][jam_masuk]" 
                                            class="form-control" 
                                            value="{{ $current ? substr($current->jam_masuk, 0, 5) : '07:00' }}">
                                    </td>
                                    <td>
                                        <input type="time" name="schedules[{{ $index }}][jam_pulang]" 
                                            class="form-control" 
                                            value="{{ $current ? substr($current->jam_pulang, 0, 5) : '15:00' }}">
                                    </td>
                                    <td>
                                        <input type="number" name="schedules[{{ $index }}][toleransi]" 
                                            class="form-control" 
                                            value="{{ $current ? $current->toleransi : 15 }}" min="0">
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" 
                                                id="activeSwitch{{ $index }}" 
                                                name="schedules[{{ $index }}][is_active]" 
                                                value="1" 
                                                {{ ($current && $current->is_active) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="activeSwitch{{ $index }}"></label>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="form-group mt-3 text-right">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-save mr-1"></i> Simpan Semua Jadwal
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection