@extends('layouts.app')

@section('title', 'WhatsApp Device')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">WhatsApp Device</h1>
    <small class="text-muted">Hubungkan nomor WhatsApp untuk sekolah ini</small>
</div>

<div class="row">
    <div class="col-lg-7 col-md-9 col-12 mx-auto">
        <div class="card shadow">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fab fa-whatsapp mr-2"></i> Status Koneksi
                </h6>
                <button id="btnRefresh" class="btn btn-sm btn-outline-primary" onclick="checkStatus()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div class="card-body text-center py-5">

                {{-- Loading State --}}
                <div id="stateLoading">
                    <div class="spinner-border text-primary mb-3" style="width:3rem;height:3rem;" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="text-muted">Mengambil status koneksi...</p>
                </div>

                {{-- Connected State --}}
                <div id="stateConnected" class="d-none">
                    <div class="mb-4">
                        <span style="font-size:5rem; color:#25D366;">
                            <i class="fab fa-whatsapp"></i>
                        </span>
                    </div>
                    <h4 class="font-weight-bold text-success mb-1">WhatsApp Terhubung!</h4>
                    <p class="text-muted mb-4">Nomor WhatsApp sekolah ini sudah aktif dan siap digunakan untuk mengirim notifikasi absensi.</p>
                    <button class="btn btn-danger" id="btnLogout" onclick="doLogout()">
                        <i class="fas fa-sign-out-alt mr-1"></i> Putuskan Koneksi (Logout)
                    </button>
                </div>

                {{-- QR State --}}
                <div id="stateQr" class="d-none">
                    <p class="font-weight-bold text-dark mb-1">Scan QR Code dengan WhatsApp</p>
                    <p class="text-muted small mb-3">Buka WhatsApp di HP → Perangkat Tertaut → Tautkan Perangkat → Scan QR di bawah ini.</p>
                    <div class="d-flex justify-content-center mb-3">
                        <img id="qrImage" src="" alt="QR Code WhatsApp" class="border rounded shadow-sm"
                             style="width:260px; height:260px; object-fit:contain;">
                    </div>
                    <div class="d-inline-block px-4 py-2 mb-3 rounded" style="background:#e8f4fd; border:1px solid #bee5eb;">
                        <i class="fas fa-clock mr-1 text-info"></i>
                        QR Code berlaku selama <strong id="qrCountdown">30</strong> detik.
                        Halaman akan otomatis refresh setelah habis.
                    </div>
                    <br>
                    <button class="btn btn-outline-secondary btn-sm" onclick="checkStatus()">
                        <i class="fas fa-sync-alt mr-1"></i> Sudah scan? Cek status
                    </button>
                </div>

                {{-- QR Pending State --}}
                <div id="stateQrPending" class="d-none">
                    <div class="spinner-border text-warning mb-3" style="width:3rem;height:3rem;" role="status"></div>
                    <h5 class="font-weight-bold text-warning mb-1">QR Code sedang disiapkan...</h5>
                    <p class="text-muted small mb-3">Mohon tunggu beberapa detik lalu klik Refresh.</p>
                    <button class="btn btn-outline-primary" onclick="checkStatus()">
                        <i class="fas fa-sync-alt mr-1"></i> Refresh
                    </button>
                </div>

                {{-- Error State --}}
                <div id="stateError" class="d-none">
                    <div class="mb-3" style="font-size:4rem; color:#e74a3b;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h5 class="font-weight-bold text-danger mb-1">Gagal terhubung ke WA API</h5>
                    <p id="errorMsg" class="text-muted small mb-2"></p>
                    <pre id="errorDebug" class="text-left small text-muted bg-light border rounded p-2 mb-3 d-none" style="max-height:120px;overflow:auto;"></pre>
                    <button class="btn btn-outline-primary" onclick="checkStatus()">
                        <i class="fas fa-redo mr-1"></i> Coba Lagi
                    </button>
                </div>

            </div>
        </div>

        <div class="card shadow mt-4">
            <div class="card-body">
                <h6 class="font-weight-bold text-gray-700 mb-2"><i class="fas fa-info-circle mr-1"></i> Informasi</h6>
                <ul class="small text-muted mb-0 pl-3">
                    <li>Setiap sekolah memiliki koneksi WhatsApp tersendiri (multi-device).</li>
                    <li>Notifikasi absensi akan dikirim dari nomor WhatsApp yang sudah di-scan di halaman ini.</li>
                    <li>Jika QR tidak muncul, pastikan server <strong>restapi-wa</strong> sudah berjalan di <code>{{ env('WA_API_BASE_URL', 'http://localhost:3000') }}</code>.</li>
                    <li>Jika mengganti HP, silakan klik <strong>"Putuskan Koneksi"</strong> lalu scan ulang dari HP baru.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let qrCountdownTimer = null;

    function showState(name) {
        ['stateLoading', 'stateConnected', 'stateQr', 'stateQrPending', 'stateError'].forEach(id => {
            document.getElementById(id).classList.add('d-none');
        });
        document.getElementById(name).classList.remove('d-none');
    }

    function startQrCountdown(seconds) {
        clearInterval(qrCountdownTimer);
        let remaining = seconds;
        document.getElementById('qrCountdown').textContent = remaining;
        qrCountdownTimer = setInterval(() => {
            remaining--;
            document.getElementById('qrCountdown').textContent = remaining;
            if (remaining <= 0) {
                clearInterval(qrCountdownTimer);
                checkStatus();
            }
        }, 1000);
    }

    function checkStatus() {
        clearInterval(qrCountdownTimer);
        stopPolling();
        showState('stateLoading');
        document.getElementById('btnRefresh').disabled = true;

        fetch('{{ route("whatsapp.device.status") }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('btnRefresh').disabled = false;

            if (data.status === 'connected') {
                showState('stateConnected');

            } else if (data.status === 'qr_ready') {
                document.getElementById('qrImage').src = data.qr_link + '?t=' + Date.now();
                showState('stateQr');
                startQrCountdown(data.qr_duration ?? 30);
                startPolling(); // continuously detect scan every 5s

            } else if (data.status === 'qr_pending') {
                showState('stateQrPending');
                setTimeout(() => checkStatus(), 3000);

            } else {
                document.getElementById('errorMsg').textContent = data.message ?? 'Unknown error.';
                const debugEl = document.getElementById('errorDebug');
                if (data.debug) {
                    debugEl.textContent = data.debug;
                    debugEl.classList.remove('d-none');
                } else {
                    debugEl.classList.add('d-none');
                }
                showState('stateError');
            }
        })
        .catch(err => {
            document.getElementById('btnRefresh').disabled = false;
            document.getElementById('errorMsg').textContent = 'Tidak dapat menghubungi server Laravel. Periksa koneksi Anda.';
            document.getElementById('errorDebug').classList.add('d-none');
            showState('stateError');
        });
    }

    // Poll status quietly every 5s — detects scan without showing loading flash
    // Uses /check (status only) — does NOT call /app/login, so qr_duration stays intact
    let pollTimer = null;

    function startPolling() {
        stopPolling();
        pollTimer = setInterval(() => {
            fetch('{{ route("whatsapp.device.check") }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.connected) {
                    stopPolling();
                    clearInterval(qrCountdownTimer);
                    showState('stateConnected');
                }
            })
            .catch(() => {});
        }, 5000);
    }

    function stopPolling() {
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
    }

    function doLogout() {
        if (!confirm('Yakin ingin memutuskan koneksi WhatsApp? Notifikasi tidak akan terkirim sampai Anda scan ulang.')) return;

        document.getElementById('btnLogout').disabled = true;
        document.getElementById('btnLogout').innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memproses...';

        fetch('{{ route("whatsapp.device.logout") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                setTimeout(() => checkStatus(), 1500);
            } else {
                alert('Logout gagal: ' + (data.message ?? 'Unknown error'));
                document.getElementById('btnLogout').disabled = false;
                document.getElementById('btnLogout').innerHTML = '<i class="fas fa-sign-out-alt mr-1"></i> Putuskan Koneksi (Logout)';
            }
        })
        .catch(() => {
            alert('Terjadi kesalahan saat logout.');
            document.getElementById('btnLogout').disabled = false;
        });
    }

    // Init on page load
    document.addEventListener('DOMContentLoaded', () => checkStatus());
</script>
@endpush
