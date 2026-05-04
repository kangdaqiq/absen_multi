@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header bg-gradient-primary p-4 border-0">
                    <div class="d-flex align-items-center">
                        <div class="bg-white p-3 rounded-circle me-3 shadow-sm">
                            <i class="fas fa-microchip text-primary fa-2x"></i>
                        </div>
                        <div>
                            <h4 class="text-white mb-0 fw-bold">OTA Firmware Updates</h4>
                            <p class="text-white-50 mb-0">Kelola pembaruan firmware perangkat RFIDv2 secara online.</p>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    @if(session('success'))
                        <div class="alert alert-success border-0 shadow-sm mb-4 d-flex align-items-center" style="border-radius: 10px;">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <div class="p-4 bg-light rounded-4 border h-100">
                                <h5 class="fw-bold mb-3"><i class="fas fa-upload text-primary me-2"></i> Upload Firmware Baru</h5>
                                <p class="text-muted small mb-4">Pilih file binary (.bin) hasil compile dari Arduino IDE untuk perangkat RFIDV2.</p>
                                
                                <form action="{{ route('super-admin.ota.upload') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Pilih File Firmware (.bin)</label>
                                        <input type="file" name="firmware" class="form-control form-control-lg border-2" accept=".bin" required>
                                        <div class="form-text mt-2">Pastikan nama file sesuai atau sistem akan otomatis mengubahnya menjadi <code>RFIDV2.bin</code></div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm" style="border-radius: 10px;">
                                        <i class="fas fa-cloud-upload-alt me-2"></i> Upload & Publikasikan
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-4">
                            <div class="p-4 bg-light rounded-4 border h-100">
                                <h5 class="fw-bold mb-3"><i class="fas fa-info-circle text-info me-2"></i> Status Firmware Saat Ini</h5>
                                
                                @php
                                    $binPath = public_path('ota/RFIDV2.bin');
                                    $exists = file_exists($binPath);
                                @endphp

                                @if($exists)
                                    <div class="d-flex align-items-center p-3 bg-white rounded-3 border mb-3 shadow-sm">
                                        <div class="bg-success-soft p-3 rounded-3 me-3 text-success">
                                            <i class="fas fa-file-code fa-2x"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1 fw-bold">RFIDV2.bin</h6>
                                            <p class="mb-0 text-muted small">Ukuran: {{ round(filesize($binPath) / 1024, 2) }} KB</p>
                                            <p class="mb-0 text-muted small">Terakhir Update: {{ date('d M Y, H:i', filemtime($binPath)) }}</p>
                                        </div>
                                    </div>
                                    <div class="alert alert-info border-0 shadow-sm small">
                                        <i class="fas fa-link me-1"></i> URL Update: <br>
                                        <code>{{ url('ota/RFIDV2.bin') }}</code>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <i class="fas fa-times-circle text-danger fa-3x mb-3"></i>
                                        <p class="text-muted">Belum ada firmware yang diupload.</p>
                                    </div>
                                @endif

                                <div class="mt-4">
                                    <h6 class="fw-bold small text-uppercase text-muted mb-2">Panduan Update:</h6>
                                    <ul class="small text-muted ps-3">
                                        <li>Buka menu Config pada perangkat RFIDv2 (Mode AP).</li>
                                        <li>Klik tombol <strong>🚀 Update Firmware Online</strong>.</li>
                                        <li>Perangkat akan mendownload file dari server ini dan melakukan update otomatis.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-gradient-primary {
        background: linear-gradient(87deg, #5e72e4 0, #825ee4 100%) !important;
    }
    .bg-success-soft {
        background-color: rgba(45, 206, 137, 0.1);
    }
    .rounded-4 {
        border-radius: 1rem !important;
    }
</style>
@endsection
