@extends('layouts.app')

@section('title', 'OTA Firmware Updates')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
            <i class="fas fa-microchip text-brand-500 mr-2"></i> OTA Firmware Updates
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Kelola dan publikasikan pembaruan firmware perangkat secara terpisah berdasarkan tipe device & generasi alat.</p>
    </div>
</div>

@if(session('success'))
<div class="mb-6 rounded-lg bg-success-50 p-4 border border-success-200 text-success-600 dark:bg-success-500/15 dark:border-success-500/20 dark:text-success-400">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-3 fa-lg"></i>
        <p class="font-medium">{{ session('success') }}</p>
    </div>
</div>
@endif

@if(session('error'))
<div class="mb-6 rounded-lg bg-error-50 p-4 border border-error-200 text-error-600 dark:bg-error-500/15 dark:border-error-500/20 dark:text-error-400">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle mr-3 fa-lg"></i>
        <p class="font-medium">{{ session('error') }}</p>
    </div>
</div>
@endif

@if($errors->any())
<div class="mb-6 rounded-lg bg-error-50 p-4 border border-error-200 text-error-600 dark:bg-error-500/15 dark:border-error-500/20 dark:text-error-400">
    <div class="flex flex-col gap-1">
        @foreach($errors->all() as $err)
            <p class="font-medium"><i class="fas fa-exclamation-triangle mr-2"></i> {{ $err }}</p>
        @endforeach
    </div>
</div>
@endif

<div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
    <!-- Left Column: Upload Form (5 Cols) -->
    <div class="xl:col-span-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark sm:p-7.5 sticky top-6">
            <h4 class="mb-4 text-xl font-bold text-gray-800 dark:text-white/90 flex items-center">
                <i class="fas fa-upload text-brand-500 mr-2.5"></i> Upload Firmware Per Tipe Device
            </h4>
            <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Pilih tipe alat dan upload file binary compile (<code class="rounded bg-gray-100 px-1.5 py-0.5 font-medium text-brand-500 dark:bg-gray-800">.bin</code>) dari Arduino IDE.</p>

            <form action="{{ route('super-admin.ota.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf
                
                <div>
                    <label class="mb-2.5 block text-sm font-medium text-gray-800 dark:text-white/90">
                        Tipe Device <span class="text-error-500">*</span>
                    </label>
                    <select name="device_type_preset" id="device_type_preset" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-gray-800 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white/90" onchange="toggleCustomDeviceInput(this.value)" required>
                        <option value="" disabled selected>-- Pilih Tipe Device --</option>
                        @foreach($presetTypes as $key => $label)
                            <option value="{{ $key }}">{{ $key }} - {{ $label }}</option>
                        @endforeach
                        <option value="custom">➕ Tipe Device Kustom Baru...</option>
                    </select>
                </div>

                <div id="custom_device_wrapper" class="hidden">
                    <label class="mb-2.5 block text-sm font-medium text-gray-800 dark:text-white/90">
                        Nama Tipe Device Kustom <span class="text-error-500">*</span>
                    </label>
                    <input type="text" name="device_type_custom" id="device_type_custom" placeholder="Contoh: RFIDV3_Bat, ESP32_Cam, dll" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-gray-800 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white/90">
                    <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">File akan disimpan sebagai <code class="rounded bg-gray-100 px-1 py-0.5 text-brand-500 dark:bg-gray-800">&lt;tipe_device&gt;.bin</code></p>
                </div>

                <div>
                    <label class="mb-2.5 block text-sm font-medium text-gray-800 dark:text-white/90">
                        Pilih File Firmware (.bin) <span class="text-error-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="file" name="firmware" class="w-full cursor-pointer rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-gray-800 outline-none transition focus:border-brand-500 active:border-brand-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white/90 file:mr-4 file:rounded file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-brand-500 hover:file:bg-brand-100 dark:file:bg-brand-500/15 dark:file:text-brand-400" accept=".bin" required>
                    </div>
                </div>

                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 p-3.5 font-medium text-white hover:bg-brand-600 transition focus:ring-4 focus:ring-brand-500/20 shadow-theme-xs">
                    <i class="fas fa-cloud-upload-alt"></i> Upload & Publikasikan
                </button>
            </form>
        </div>
    </div>

    <!-- Right Column: Status & List (7 Cols) -->
    <div class="xl:col-span-7 space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark sm:p-7.5">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-xl font-bold text-gray-800 dark:text-white/90 flex items-center">
                    <i class="fas fa-layer-group text-info-500 mr-2.5"></i> Firmware Aktif Per Tipe Device
                </h4>
                <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-600 dark:bg-brand-500/15 dark:text-brand-400">
                    Total: {{ count($firmwares) }} Tipe
                </span>
            </div>

            @if(count($firmwares) > 0)
                <div class="space-y-4">
                    @foreach($firmwares as $fw)
                        <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 transition hover:border-gray-300 dark:border-gray-800 dark:bg-gray-800/40">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="flex items-start gap-3.5">
                                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-400">
                                        <i class="fas fa-microchip fa-xl"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h5 class="text-base font-bold text-gray-800 dark:text-white/90">
                                                {{ $fw['device_type'] }}
                                            </h5>
                                            <span class="rounded bg-gray-200 px-2 py-0.5 text-xs font-mono text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                {{ $fw['filename'] }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ $fw['label'] }}
                                        </p>
                                        <div class="mt-2 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                            <span><i class="fas fa-weight-hanging mr-1"></i> {{ $fw['size_formatted'] }}</span>
                                            <span><i class="far fa-clock mr-1"></i> {{ $fw['date_formatted'] }}</span>
                                        </div>
                                    </div>
                                </div>

                                <form action="{{ route('super-admin.ota.destroy', $fw['filename']) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus firmware {{ $fw['filename'] }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="flex items-center gap-1 text-xs font-medium text-error-500 hover:text-error-600 dark:text-error-400 transition py-1 px-2.5 rounded-lg hover:bg-error-50 dark:hover:bg-error-500/10">
                                        <i class="fas fa-trash-alt"></i> Hapus
                                    </button>
                                </form>
                            </div>

                            <!-- OTA URL Box -->
                            <div class="mt-3.5 rounded-lg bg-white p-2.5 border border-gray-200 text-xs dark:bg-gray-900/60 dark:border-gray-700/60">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="truncate font-mono text-gray-700 dark:text-gray-300 select-all">
                                        <span class="text-gray-400 mr-1">URL:</span> {{ $fw['url'] }}
                                    </div>
                                    <button type="button" onclick="copyOtaUrl('{{ $fw['url'] }}', this)" class="flex-shrink-0 rounded bg-gray-100 px-2 py-1 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition">
                                        <i class="fas fa-copy"></i> Copy URL
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-10 text-center rounded-xl border border-dashed border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/30">
                    <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800">
                        <i class="fas fa-microchip fa-2xl"></i>
                    </div>
                    <h5 class="text-base font-semibold text-gray-800 dark:text-white/90">Belum Ada Firmware Terupload</h5>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-sm">Silakan pilih tipe device dan upload file binary (.bin) melalui form di sebelah kiri.</p>
                </div>
            @endif

            <!-- Panduan -->
            <div class="mt-6 border-t border-gray-100 pt-5 dark:border-gray-800">
                <h6 class="mb-2.5 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    <i class="fas fa-lightbulb text-warning-500 mr-1"></i> Panduan Update OTA Per Tipe Device:
                </h6>
                <ul class="list-disc list-inside space-y-1.5 text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                    <li>Setiap generasi/tipe alat memiliki file firmware terpisah (misal <code class="text-brand-500">RFIDV2.bin</code>, <code class="text-brand-500">RFIDV2_NoBat.bin</code>, <code class="text-brand-500">FingerprintV3.bin</code>).</li>
                    <li>Pastikan URL OTA pada konfigurasi WiFi AP alat diisi sesuai dengan URL Tipe Device alat tersebut.</li>
                    <li>Pada menu Config AP WiFi perangkat, pilih menu <strong class="text-gray-800 dark:text-white">OTA Online Update</strong> lalu klik <strong class="text-gray-800 dark:text-white">🚀 Mulai Update</strong>.</li>
                    <li>Perangkat akan mengunduh firmware terbaru yang sesuai dan melakukan reboot otomatis setelah selesai.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function toggleCustomDeviceInput(val) {
    const wrapper = document.getElementById('custom_device_wrapper');
    const input = document.getElementById('device_type_custom');
    if (val === 'custom') {
        wrapper.classList.remove('hidden');
        input.setAttribute('required', 'required');
        input.focus();
    } else {
        wrapper.classList.add('hidden');
        input.removeAttribute('required');
        input.value = '';
    }
}

function copyOtaUrl(url, btn) {
    navigator.clipboard.writeText(url).then(() => {
        const origText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check text-success-500"></i> Copied!';
        setTimeout(() => {
            btn.innerHTML = origText;
        }, 2000);
    });
}
</script>
@endsection
