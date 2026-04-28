@extends('layouts.app')

@section('title', 'Pengaturan Sekolah')

@section('content')
<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-title-md2 font-semibold text-gray-800 dark:text-white/90">
        Pengaturan Sekolah
    </h2>
</div>

<form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div x-data="{ activeTab: 'general' }" class="rounded-2xl border border-gray-200 bg-white shadow-theme-sm dark:border-gray-800 dark:bg-gray-dark">
        <!-- Tabs Header -->
        <div class="border-b border-gray-200 px-5 dark:border-gray-800">
            <nav class="flex gap-4">
                <button @click.prevent="activeTab = 'general'" 
                    :class="activeTab === 'general' ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-white'"
                    class="border-b-2 py-4 px-2 text-sm font-medium transition-colors">
                    Umum
                </button>
                <button @click.prevent="activeTab = 'automation'" 
                    :class="activeTab === 'automation' ? 'border-brand-500 text-brand-500' : 'border-transparent text-gray-500 hover:text-gray-800 dark:hover:text-white'"
                    class="border-b-2 py-4 px-2 text-sm font-medium transition-colors">
                    Otomatisasi & Notifikasi
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Tab Umum -->
            <div x-show="activeTab === 'general'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-6">
                
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 border-b border-gray-200 dark:border-gray-800 pb-2">Konfigurasi Umum</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-6">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Sekolah</label>
                            <input type="text" name="nama_sekolah" value="{{ $settings['nama_sekolah'] ?? 'SMK Assuniyah Tumijajar' }}" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Alamat Sekolah (Kop Surat)</label>
                            <textarea name="alamat_sekolah" rows="3" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">{{ $settings['alamat_sekolah'] ?? '' }}</textarea>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Kota / Lokasi Tanda Tangan (Laporan)</label>
                            <input type="text" name="alamat_ttd" value="{{ $settings['alamat_ttd'] ?? 'Jakarta' }}" placeholder="Contoh: Jakarta" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        </div>
                    </div>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Logo Sekolah</label>
                            <div class="mb-3">
                                @php
                                    $logo = !empty($settings['logo_filename']) ? $settings['logo_filename'] : 'logo.png';
                                    $isStorage = \Illuminate\Support\Str::startsWith($logo, 'schools/');
                                    $logoUrl = asset('img/logo.png'); // Default

                                    if ($isStorage && file_exists(storage_path('app/public/' . $logo))) {
                                        $logoUrl = asset('storage/' . $logo);
                                    } elseif (!$isStorage && file_exists(public_path('img/' . $logo))) {
                                        $logoUrl = asset('img/' . $logo);
                                    }
                                @endphp
                                <div class="rounded-lg border border-gray-200 p-2 dark:border-gray-800 inline-block bg-white dark:bg-gray-800">
                                    <img src="{{ $logoUrl }}" alt="Logo" id="logo-preview" class="h-24 object-contain">
                                </div>
                            </div>
                            <input type="file" name="logo" id="logo-input" accept="image/*" class="w-full cursor-pointer rounded-lg border border-gray-200 bg-transparent text-sm outline-none dark:border-gray-800 dark:bg-gray-900 dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-gray-800 dark:file:text-white dark:hover:file:bg-gray-700">
                            <p class="mt-1 text-xs text-gray-500">Format: JPG, PNG, GIF, SVG. Maksimal 2MB</p>
                        </div>
                        
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                            <h4 class="mb-4 text-sm font-semibold text-gray-800 dark:text-white/90">Pengaturan Checkout</h4>
                            
                            <div class="mb-4">
                                <label class="flex items-center cursor-pointer select-none">
                                    <div class="relative">
                                        <input type="checkbox" id="enable_checkout_attendance" name="enable_checkout_attendance" value="true" class="sr-only" {{ ($settings['enable_checkout_attendance'] ?? 'true') === 'true' ? 'checked' : '' }}>
                                        <div class="block h-6 w-10 rounded-full bg-gray-300 dark:bg-gray-600 toggle-bg transition"></div>
                                        <div class="dot absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition toggle-dot"></div>
                                    </div>
                                    <div class="ml-3 font-medium text-gray-800 dark:text-white/90 text-sm">Aktifkan Absen Pulang (Siswa)</div>
                                </label>
                                <p class="mt-1 ml-13 text-xs text-gray-500">Jika dinonaktifkan, siswa hanya perlu absen masuk (1x scan). Jika diaktifkan, siswa perlu absen masuk dan pulang (2x scan).</p>
                            </div>
                            
                            <div>
                                <label class="flex items-center cursor-pointer select-none">
                                    <div class="relative">
                                        <input type="checkbox" id="enable_checkout_teacher" name="enable_checkout_teacher" value="true" class="sr-only" {{ ($settings['enable_checkout_teacher'] ?? 'false') === 'true' ? 'checked' : '' }}>
                                        <div class="block h-6 w-10 rounded-full bg-gray-300 dark:bg-gray-600 toggle-bg transition"></div>
                                        <div class="dot absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition toggle-dot"></div>
                                    </div>
                                    <div class="ml-3 font-medium text-gray-800 dark:text-white/90 text-sm">Aktifkan Absen Pulang (Karyawan/Guru)</div>
                                </label>
                                <p class="mt-1 ml-13 text-xs text-gray-500">Jika diaktifkan, Karyawan/Guru diwajibkan untuk menempelkan kartu kembali setelah Kartu Gerbang di-scan untuk merekam Jam Pulang.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Otomatisasi -->
            <div x-show="activeTab === 'automation'" style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="space-y-6">
                
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 border-b border-gray-200 dark:border-gray-800 pb-2">Konfigurasi Jadwal Otomatis (Scheduler)</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Proses Absensi Harian (Auto Bolos/Alpha)</label>
                        <input type="time" name="schedule_process_daily" value="{{ $settings['schedule_process_daily'] ?? '13:30' }}" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500">Waktu sistem memproses siswa yang tidak hadir (Alpha) atau lupa checkout (Bolos)</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Laporan Harian (Pagi)</label>
                        <input type="time" name="schedule_daily_report" value="{{ $settings['schedule_daily_report'] ?? '08:15' }}" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500">Waktu pengiriman rekap kehadiran ke grup guru & wali kelas</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Info Jadwal Guru</label>
                        <input type="time" name="schedule_send_teacher_schedule" value="{{ $settings['schedule_send_teacher_schedule'] ?? '07:30' }}" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500">Waktu pengiriman jadwal mengajar ke guru ybs</p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Backup Database</label>
                        <input type="time" name="schedule_backup_db" value="{{ $settings['schedule_backup_db'] ?? '23:59' }}" class="w-full rounded-lg border border-gray-200 bg-transparent px-4 py-2 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                        <p class="mt-1 text-xs text-gray-500">Waktu sistem melakukan backup harian otomatis</p>
                    </div>
                </div>

                @if(auth()->user()->school && auth()->user()->school->wa_enabled)
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90 border-b border-gray-200 dark:border-gray-800 pb-2 mt-8">Notifikasi WhatsApp</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Target Jadwal Guru (Nomor WA)</label>
                        <div class="relative">
                            <input type="text" name="report_target_jid" id="report_target_jid" value="{{ $settings['report_target_jid'] ?? '' }}" placeholder="Contoh: 628123456789@s.whatsapp.net" class="w-full rounded-lg border border-gray-200 bg-transparent py-2 pl-4 pr-32 outline-none focus:border-brand-500 dark:border-gray-800 dark:bg-gray-900 dark:text-white">
                            <button type="button" @click="$dispatch('open-modal', 'waGroupModal')" onclick="loadWaGroups('report_target_jid')" class="absolute right-1 top-1 bottom-1 rounded-md bg-brand-500 px-3 py-1 text-sm font-medium text-white hover:bg-brand-600 transition flex items-center gap-1">
                                <i class="fab fa-whatsapp"></i> Pilih Grup
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Khusus untuk jadwal guru jam 06:00. Format: 628xxx@s.whatsapp.net (Grup/Pribadi)</p>
                    </div>
                </div>
                @endif
            </div>
            
            <div class="mt-8 border-t border-gray-200 pt-6 dark:border-gray-800">
                <button type="submit" class="flex w-full md:w-auto items-center justify-center gap-2 rounded-lg bg-brand-500 px-6 py-3 font-medium text-white hover:bg-brand-600 transition">
                    <i class="fas fa-save"></i> Simpan Pengaturan
                </button>
            </div>
        </div>
    </div>
</form>

<!-- WA Group Modal -->
<x-ui.modal id="waGroupModal" :is-open="false" class="max-w-2xl">
    <div class="p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white/90">Pilih Grup WhatsApp</h3>
            <button @click="open = false" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white"><i class="fas fa-times"></i></button>
        </div>
        
        <div id="waGroupLoading" class="text-center py-8">
            <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-brand-500 border-r-transparent align-[-0.125em]" role="status">
                <span class="!absolute !-m-px !h-px !w-px !overflow-hidden !whitespace-nowrap !border-0 !p-0 ![clip:rect(0,0,0,0)]">Loading...</span>
            </div>
            <p class="mt-3 text-gray-500 dark:text-gray-400">Mengambil daftar grup dari WhatsApp...</p>
        </div>
        
        <div id="waGroupError" class="hidden mb-4 rounded-lg bg-error-50 p-4 text-sm text-error-700 dark:bg-error-500/15 dark:text-error-500"></div>
        
        <div id="waGroupList" class="flex flex-col gap-2 max-h-96 overflow-y-auto pr-2">
            <!-- Groups will be loaded here -->
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="button" @click="open = false" class="rounded-lg bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">Tutup</button>
        </div>
    </div>
</x-ui.modal>

<style>
    /* Custom Toggle Switch Styles */
    input:checked ~ .toggle-bg {
        background-color: #3C50E0; /* brand-500 */
    }
    input:checked ~ .toggle-dot {
        transform: translateX(100%);
    }
</style>
@endsection

@push('scripts')
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
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
            
            // Reset state
            $('#waGroupLoading').removeClass('hidden');
            $('#waGroupList').html('');
            $('#waGroupError').addClass('hidden');

            // Fetch groups
            fetch('{{ route("api.whatsapp.groups") }}')
                .then(response => response.json())
                .then(data => {
                    $('#waGroupLoading').addClass('hidden');
                    console.log("WA Groups Data:", data); // Debug

                    if (data.success) {
                        if (data.groups.length === 0) {
                            $('#waGroupList').html('<div class="text-center text-gray-500 py-4">Tidak ada grup ditemukan.</div>');
                            return;
                        }

                        let html = '';
                        data.groups.forEach(group => {
                            html += `
                                <button type="button" class="flex flex-col items-start w-full rounded-lg border border-gray-200 p-4 hover:border-brand-500 hover:bg-brand-50 dark:border-gray-700 dark:hover:border-brand-500 dark:hover:bg-brand-500/10 transition text-left" 
                                    onclick="selectWaGroup('${group.jid}')">
                                    <div class="flex w-full justify-between items-center mb-1">
                                        <h6 class="font-semibold text-gray-800 dark:text-white/90">${group.name}</h6>
                                        <span class="text-xs text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">ID: ${group.jid.split('@')[0]}</span> 
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 break-all">${group.jid}</span>
                                </button>
                            `;
                        });
                        $('#waGroupList').html(html);
                    } else {
                        $('#waGroupError').text(data.message || 'Gagal mengambil data grup.').removeClass('hidden');
                    }
                })
                .catch(error => {
                    $('#waGroupLoading').addClass('hidden');
                    $('#waGroupError').text('Terjadi kesalahan koneksi ke server.').removeClass('hidden');
                    console.error('Error:', error);
                });
        }

        function selectWaGroup(jid) {
            if (targetInputId) {
                document.getElementById(targetInputId).value = jid;
            }
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'waGroupModal' }));
        }
    </script>
@endpush