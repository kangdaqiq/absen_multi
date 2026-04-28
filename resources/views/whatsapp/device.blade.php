@extends('layouts.app')

@section('title', 'WhatsApp Device')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            WhatsApp Device
        </h2>
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400 mt-1">Hubungkan nomor WhatsApp untuk sekolah ini</p>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
    <div class="xl:col-span-3">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800 flex justify-between items-center">
                <h6 class="font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2">
                    <i class="fab fa-whatsapp text-success-500"></i> Status Koneksi
                </h6>
                <button id="btnRefresh" onclick="checkStatus()" class="inline-flex items-center justify-center gap-2 rounded-lg border border-brand-500 text-brand-500 px-3 py-1.5 text-sm font-medium hover:bg-brand-50 dark:hover:bg-brand-500/15 transition">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            
            <div class="p-8 md:p-12 text-center">
                {{-- Loading State --}}
                <div id="stateLoading">
                    <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-brand-500 border-r-transparent align-[-0.125em] mb-4" role="status">
                        <span class="!absolute !-m-px !h-px !w-px !overflow-hidden !whitespace-nowrap !border-0 !p-0 ![clip:rect(0,0,0,0)]">Loading...</span>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">Mengambil status koneksi...</p>
                </div>

                {{-- Connected State --}}
                <div id="stateConnected" class="hidden">
                    <div class="mb-6 flex justify-center">
                        <div class="flex h-24 w-24 items-center justify-center rounded-full bg-success-50 text-success-500 dark:bg-success-500/15">
                            <i class="fab fa-whatsapp text-6xl"></i>
                        </div>
                    </div>
                    <h4 class="mb-2 text-2xl font-bold text-success-600 dark:text-success-500">WhatsApp Terhubung!</h4>
                    <p class="mb-8 text-gray-500 dark:text-gray-400 max-w-md mx-auto">Nomor WhatsApp sekolah ini sudah aktif dan siap digunakan untuk mengirim notifikasi absensi.</p>
                    <button id="btnLogout" onclick="doLogout()" class="inline-flex items-center justify-center gap-2 rounded-lg bg-error-500 px-6 py-2.5 text-center font-medium text-white hover:bg-error-600 transition">
                        <i class="fas fa-sign-out-alt"></i> Putuskan Koneksi (Logout)
                    </button>
                </div>

                {{-- QR State --}}
                <div id="stateQr" class="hidden">
                    <h4 class="mb-2 text-xl font-bold text-gray-800 dark:text-white/90">Scan QR Code dengan WhatsApp</h4>
                    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Buka WhatsApp di HP → Perangkat Tertaut → Tautkan Perangkat → Scan QR di bawah ini.</p>
                    
                    <div class="mb-6 flex justify-center">
                        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700">
                            <img id="qrImage" src="" alt="QR Code WhatsApp" class="h-64 w-64 object-contain">
                        </div>
                    </div>
                    
                    <div class="mb-6 inline-flex items-center gap-2 rounded-lg bg-info-50 px-5 py-3 text-sm text-info-700 dark:bg-info-500/15 dark:text-info-500">
                        <i class="fas fa-clock text-info-500"></i>
                        <span>QR Code berlaku selama <strong id="qrCountdown" class="font-bold text-info-600 dark:text-info-400 text-base">30</strong> detik.</span>
                    </div>
                    
                    <div>
                        <button onclick="checkStatus()" class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-100 px-5 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-200 transition dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <i class="fas fa-sync-alt"></i> Sudah scan? Cek status
                        </button>
                    </div>
                </div>

                {{-- QR Pending State --}}
                <div id="stateQrPending" class="hidden">
                    <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-warning-500 border-r-transparent align-[-0.125em] mb-4" role="status"></div>
                    <h4 class="mb-2 text-xl font-bold text-warning-600 dark:text-warning-500">QR Code sedang disiapkan...</h4>
                    <p class="mb-6 text-gray-500 dark:text-gray-400">Mohon tunggu beberapa detik lalu klik Refresh.</p>
                    <button onclick="checkStatus()" class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-6 py-2.5 text-center font-medium text-white hover:bg-brand-600 transition">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>

                {{-- Error State --}}
                <div id="stateError" class="hidden">
                    <div class="mb-6 flex justify-center">
                        <div class="flex h-24 w-24 items-center justify-center rounded-full bg-error-50 text-error-500 dark:bg-error-500/15">
                            <i class="fas fa-exclamation-triangle text-5xl"></i>
                        </div>
                    </div>
                    <h4 class="mb-2 text-xl font-bold text-error-600 dark:text-error-500">Gagal terhubung ke WA API</h4>
                    <p id="errorMsg" class="mb-4 text-gray-500 dark:text-gray-400"></p>
                    
                    <div id="errorDebugWrapper" class="hidden mb-6 max-w-2xl mx-auto">
                        <div class="rounded-lg bg-gray-100 p-4 text-left text-xs font-mono text-gray-600 dark:bg-gray-800 dark:text-gray-400 max-h-32 overflow-y-auto">
                            <pre id="errorDebug" class="whitespace-pre-wrap break-words"></pre>
                        </div>
                    </div>
                    
                    <button onclick="checkStatus()" class="inline-flex items-center justify-center gap-2 rounded-lg border border-brand-500 text-brand-500 px-6 py-2.5 text-center font-medium hover:bg-brand-50 dark:hover:bg-brand-500/15 transition">
                        <i class="fas fa-redo"></i> Coba Lagi
                    </button>
                </div>

            </div>
        </div>
    </div>

    <div class="xl:col-span-1">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-800">
                <h6 class="font-semibold text-gray-800 dark:text-white/90 flex items-center gap-2">
                    <i class="fas fa-info-circle text-brand-500"></i> Informasi
                </h6>
            </div>
            <div class="p-5">
                <ul class="flex flex-col gap-3 text-sm text-gray-500 dark:text-gray-400">
                    <li class="flex gap-2">
                        <i class="fas fa-check text-success-500 mt-1"></i>
                        <span>Setiap sekolah memiliki koneksi WhatsApp tersendiri (multi-device).</span>
                    </li>
                    <li class="flex gap-2">
                        <i class="fas fa-check text-success-500 mt-1"></i>
                        <span>Notifikasi absensi akan dikirim dari nomor WhatsApp yang sudah di-scan di halaman ini.</span>
                    </li>
                    <li class="flex gap-2">
                        <i class="fas fa-check text-success-500 mt-1"></i>
                        <span>Jika QR tidak muncul, pastikan server <strong>restapi-wa</strong> sudah berjalan di <code class="rounded bg-gray-100 px-1 py-0.5 text-xs text-brand-500 dark:bg-gray-800">{{ env('WA_API_BASE_URL', 'http://localhost:3000') }}</code>.</span>
                    </li>
                    <li class="flex gap-2">
                        <i class="fas fa-check text-success-500 mt-1"></i>
                        <span>Jika mengganti HP, silakan klik <strong>"Putuskan Koneksi"</strong> lalu scan ulang dari HP baru.</span>
                    </li>
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
            document.getElementById(id).classList.add('hidden');
        });
        document.getElementById(name).classList.remove('hidden');
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
        document.getElementById('btnRefresh').classList.add('opacity-50', 'cursor-not-allowed');

        fetch('{{ route("whatsapp.device.status") }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('btnRefresh').disabled = false;
            document.getElementById('btnRefresh').classList.remove('opacity-50', 'cursor-not-allowed');

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
                const debugWrapper = document.getElementById('errorDebugWrapper');
                
                if (data.debug) {
                    debugEl.textContent = data.debug;
                    debugWrapper.classList.remove('hidden');
                } else {
                    debugWrapper.classList.add('hidden');
                }
                showState('stateError');
            }
        })
        .catch(err => {
            document.getElementById('btnRefresh').disabled = false;
            document.getElementById('btnRefresh').classList.remove('opacity-50', 'cursor-not-allowed');
            document.getElementById('errorMsg').textContent = 'Tidak dapat menghubungi server Laravel. Periksa koneksi Anda.';
            document.getElementById('errorDebugWrapper').classList.add('hidden');
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

        const btn = document.getElementById('btnLogout');
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        btn.innerHTML = '<div class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-solid border-white border-r-transparent align-[-0.125em] mr-2"></div> Memproses...';

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
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
                btn.innerHTML = '<i class="fas fa-sign-out-alt mr-1"></i> Putuskan Koneksi (Logout)';
            }
        })
        .catch(() => {
            alert('Terjadi kesalahan saat logout.');
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
            btn.innerHTML = '<i class="fas fa-sign-out-alt mr-1"></i> Putuskan Koneksi (Logout)';
        });
    }

    // Init on page load
    document.addEventListener('DOMContentLoaded', () => checkStatus());
</script>
@endpush
