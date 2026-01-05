@extends('layouts.app')

@section('title', 'Pengaturan Sekolah')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Pengaturan Sekolah</h1>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Konfigurasi Umum</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Nama Sekolah</label>
                            <input type="text" name="nama_sekolah" class="form-control"
                                value="{{ $settings['nama_sekolah'] ?? 'SMK Assuniyah Tumijajar' }}">
                        </div>

                        <div class="form-group">
                            <label>Alamat Sekolah (Kop Surat)</label>
                            <textarea name="alamat_sekolah" class="form-control"
                                rows="3">{{ $settings['alamat_sekolah'] ?? '' }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>Kota / Lokasi Tanda Tangan (Laporan)</label>
                            <input type="text" name="alamat_ttd" class="form-control"
                                value="{{ $settings['alamat_ttd'] ?? 'Jakarta' }}" placeholder="Contoh: Jakarta">
                        </div>

                        <div class="form-group">
                            <label>Toleransi Buka Absensi Pulang(menit)</label>
                            <input type="number" name="checkout_tolerance_minutes" class="form-control"
                                value="{{ $settings['checkout_tolerance_minutes'] ?? '15' }}" min="0" max="60"
                                placeholder="15">
                            <small class="text-muted">Guru bisa buka absensi pulang x menit sebelum jam pulang</small>
                        </div>

                        <hr>
                        <h6 class="font-weight-bold text-primary">Konfigurasi Jadwal Otomatis (Scheduler)</h6>

                        <div class="form-group">
                            <label>Proses Absensi Harian (Auto Bolos/Alpha)</label>
                            <input type="time" name="schedule_process_daily" class="form-control"
                                value="{{ $settings['schedule_process_daily'] ?? '13:30' }}">
                            <small class="text-muted">Waktu sistem memproses siswa yang tidak hadir (Alpha) atau lupa
                                checkout (Bolos)</small>
                        </div>

                        <div class="form-group">
                            <label>Laporan Harian (Pagi)</label>
                            <input type="time" name="schedule_daily_report" class="form-control"
                                value="{{ $settings['schedule_daily_report'] ?? '08:15' }}">
                            <small class="text-muted">Waktu pengiriman rekap kehadiran ke grup guru & wali kelas</small>
                        </div>

                        <div class="form-group">
                            <label>Info Jadwal Guru</label>
                            <input type="time" name="schedule_send_teacher_schedule" class="form-control"
                                value="{{ $settings['schedule_send_teacher_schedule'] ?? '07:30' }}">
                            <small class="text-muted">Waktu pengiriman jadwal mengajar ke guru ybs</small>
                        </div>

                        <div class="form-group">
                            <label>Backup Database</label>
                            <input type="time" name="schedule_backup_db" class="form-control"
                                value="{{ $settings['schedule_backup_db'] ?? '23:59' }}">
                        </div>

                        <hr>
                        <h6 class="font-weight-bold text-primary">Notifikasi WhatsApp</h6>

                        <div class="form-group">
                            <label>Target Jadwal Guru (Nomor WA)</label>
                            <input type="text" name="report_target_jid" class="form-control"
                                value="{{ $settings['report_target_jid'] ?? '' }}"
                                placeholder="Contoh: 628123456789@s.whatsapp.net">
                            <small class="text-muted">Khusus untuk jadwal guru jam 06:00. Format: 628xxx@s.whatsapp.net
                                (Grup/Pribadi)</small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-save"></i> Simpan Pengaturan
                        </button>

                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Informasi</h6>
                </div>
                <div class="card-body">
                    <p>Pengaturan ini digunakan untuk:</p>
                    <ul>
                        <li>Fitur Laporan Harian (Scheduler)</li>
                        <li>Kop Surat / Laporan (Mendatang)</li>
                    </ul>
                    <div class="alert alert-info">
                        <strong>Tips:</strong> Untuk mendapatkan ID Grup WhatsApp (`@g.us`), gunakan fitur "Get Group ID"
                        pada tools WA Gateway Anda, atau gunakan nomor pribadi (`@s.whatsapp.net`).
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection