@extends('layouts.app')

@section('title', 'Pengaturan Sekolah')

@section('content')
    <h1 class="h3 mb-4 text-gray-800">Pengaturan Sekolah</h1>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Konfigurasi Umum</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <ul class="nav nav-tabs mb-4" id="settingTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="general-tab" data-toggle="tab" href="#general" role="tab"
                                    aria-controls="general" aria-selected="true">Umum</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="automation-tab" data-toggle="tab" href="#automation" role="tab"
                                    aria-controls="automation" aria-selected="false">Otomatisasi & Notifikasi</a>
                            </li>
                        </ul>

                        <div class="tab-content" id="settingTabsContent">
                            <!-- Tab Umum -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel"
                                aria-labelledby="general-tab">
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
                                    <small class="text-muted">Guru bisa buka absensi pulang x menit sebelum jam
                                        pulang</small>
                                </div>



                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input"
                                            id="enable_checkout_attendance" name="enable_checkout_attendance"
                                            value="true" {{ ($settings['enable_checkout_attendance'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="enable_checkout_attendance">
                                            Aktifkan Absen Pulang
                                        </label>
                                    </div>
                                    <small class="text-muted">Jika dinonaktifkan, siswa hanya perlu absen masuk (1x scan). Jika diaktifkan, siswa perlu absen masuk dan pulang (2x scan).</small>
                                </div>

                                <div class="form-group">
                                    <label>Logo Sekolah</label>
                                    <div class="mb-2">
                                        @php
                                            $logo = $settings['logo_filename'] ?? 'logo.png';
                                            $isStorage = \Illuminate\Support\Str::startsWith($logo, 'schools/');
                                            $logoUrl = $isStorage ? asset('storage/' . $logo) : asset('img/' . $logo);
                                        @endphp
                                        <img src="{{ $logoUrl }}"
                                            alt="Logo" id="logo-preview"
                                            style="max-width: 150px; max-height: 150px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;">
                                    </div>
                                    <input type="file" name="logo" class="form-control-file" id="logo-input"
                                        accept="image/*">
                                    <small class="text-muted">Format: JPG, PNG, GIF, SVG. Maksimal 2MB</small>
                                </div>
                            </div>

                            <!-- Tab Otomatisasi & Notifikasi -->
                            <div class="tab-pane fade" id="automation" role="tabpanel" aria-labelledby="automation-tab">
                                <h6 class="font-weight-bold text-primary mt-3">Konfigurasi Jadwal Otomatis (Scheduler)</h6>

                                <div class="form-group">
                                    <label>Proses Absensi Harian (Auto Bolos/Alpha)</label>
                                    <input type="time" name="schedule_process_daily" class="form-control"
                                        value="{{ $settings['schedule_process_daily'] ?? '13:30' }}">
                                    <small class="text-muted">Waktu sistem memproses siswa yang tidak hadir (Alpha) atau
                                        lupa checkout (Bolos)</small>
                                </div>

                                <div class="form-group">
                                    <label>Laporan Harian (Pagi)</label>
                                    <input type="time" name="schedule_daily_report" class="form-control"
                                        value="{{ $settings['schedule_daily_report'] ?? '08:15' }}">
                                    <small class="text-muted">Waktu pengiriman rekap kehadiran ke grup guru & wali
                                        kelas</small>
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
                                    <div class="input-group">
                                        <input type="text" name="report_target_jid" id="report_target_jid"
                                            class="form-control" value="{{ $settings['report_target_jid'] ?? '' }}"
                                            placeholder="Contoh: 628123456789@s.whatsapp.net">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button"
                                                onclick="loadWaGroups('report_target_jid')">
                                                <i class="fab fa-whatsapp"></i> Pilih Grup
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">Khusus untuk jadwal guru jam 06:00. Format:
                                        628xxx@s.whatsapp.net (Grup/Pribadi)</small>
                                </div>



                                <hr>
                                <h6 class="font-weight-bold text-primary">Deteksi Ketidakhadiran Berlebihan</h6>

                                <div class="form-group">
                                    <label>Threshold Ketidakhadiran (hari)</label>
                                    <input type="number" name="absence_threshold_days" class="form-control"
                                        value="{{ $settings['absence_threshold_days'] ?? '3' }}" min="1" max="30">
                                    <small class="text-muted">Jumlah hari Alpha/Bolos yang dianggap berlebihan</small>
                                </div>

                                <div class="form-group">
                                    <label>Periode Pengecekan (hari)</label>
                                    <input type="number" name="absence_check_period_days" class="form-control"
                                        value="{{ $settings['absence_check_period_days'] ?? '7' }}" min="1" max="180">
                                    <small class="text-muted">Periode pengecekan dalam hari (default: 7 hari, max: 180
                                        hari)</small>
                                </div>

                                <div class="form-group">
                                    <label>Waktu Laporan Harian</label>
                                    <input type="time" name="schedule_check_abnormal" class="form-control"
                                        value="{{ $settings['schedule_check_abnormal'] ?? '16:00' }}">
                                    <small class="text-muted">Waktu pengiriman laporan siswa bermasalah (default:
                                        16:00)</small>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input"
                                            id="absence_notification_enabled" name="absence_notification_enabled"
                                            value="true" {{ ($settings['absence_notification_enabled'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="absence_notification_enabled">
                                            Aktifkan Notifikasi Harian Siswa Bermasalah
                                        </label>
                                    </div>
                                    <small class="text-muted">Laporan dikirim setiap hari jam 16:00 jika ada siswa
                                        bermasalah yang tidak hadir.</small>
                                </div>

                                <div class="alert alert-warning alert-static mt-2">
                                    <strong>Laporan Harian:</strong> Sistem akan mengirim laporan siswa yang melebihi batas
                                    ketidakhadiran
                                    dan <b>sedang tidak hadir hari ini</b>. Laporan dikirim ke grup kelas dan grup guru.
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block mt-4">
                            <i class="fas fa-save"></i> Simpan Pengaturan
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

<!-- WA Group Modal -->
<div class="modal fade" id="waGroupModal" tabindex="-1" role="dialog" aria-labelledby="waGroupModalLabel"
    aria-hidden="true" style="z-index: 100000;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="waGroupModalLabel">Pilih Grup WhatsApp</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="waGroupLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Mengambil daftar grup...</p>
                </div>
                <div id="waGroupError" class="alert alert-danger d-none"></div>
                <div class="list-group" id="waGroupList">
                    <!-- Groups will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        // Logo preview
        document.getElementById('logo-input').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('logo-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // WA Group Selector Logic
        let targetInputId = '';

        function loadWaGroups(inputId) {
            targetInputId = inputId;
            $('#waGroupModal').modal('show');

            // Reset state
            $('#waGroupLoading').removeClass('d-none');
            $('#waGroupList').html('');
            $('#waGroupError').addClass('d-none');

            // Fetch groups
            fetch('{{ route("api.whatsapp.groups") }}')
                .then(response => response.json())
                .then(data => {
                    $('#waGroupLoading').addClass('d-none');
                    console.log("WA Groups Data:", data); // Debug

                    if (data.success) {
                        if (data.groups.length === 0) {
                            $('#waGroupList').html('<div class="text-center text-muted p-3">Tidak ada grup ditemukan.</div>');
                            return;
                        }

                        let html = '';
                        data.groups.forEach(group => {
                            html += `
                                                <button type="button" class="list-group-item list-group-item-action" 
                                                    onclick="selectWaGroup('${group.jid}')">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <h6 class="mb-1 font-weight-bold">${group.name}</h6>
                                                        <small class="text-muted">ID: ${group.jid.split('@')[0]}</small> 
                                                    </div>
                                                    <small class="text-muted d-block text-truncate">${group.jid}</small>
                                                </button>
                                            `;
                        });
                        $('#waGroupList').html(html);
                    } else {
                        $('#waGroupError').text(data.message || 'Gagal mengambil data grup.').removeClass('d-none');
                    }
                })
                .catch(error => {
                    $('#waGroupLoading').addClass('d-none');
                    $('#waGroupError').text('Terjadi kesalahan koneksi.').removeClass('d-none');
                    console.error('Error:', error);
                });
        }

        function selectWaGroup(jid) {
            if (targetInputId) {
                document.getElementById(targetInputId).value = jid;
            }
            $('#waGroupModal').modal('hide');
        }
    </script>
@endpush